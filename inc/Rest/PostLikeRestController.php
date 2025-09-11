<?php // phpcs:disable WordPress.Files.FileName
/**
 * Post like REST controller (enhanced)
 *
 * @package Lerm\Inc\Rest
 */

declare( strict_types=1 );

namespace Lerm\Inc\Rest;

use Lerm\Inc\Traits\Singleton;
use function Lerm\Inc\Functions\Helpers\client_ip;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PostLikeRestController
 *
 * Improved REST endpoint for liking/unliking posts & comments.
 *
 * - returns structured payload with integer count + formatted HTML
 * - caches counts
 * - emits action for third-parties
 *
 * @package Lerm\Inc\Rest
 */
final class PostLikeRestController extends BaseRestController {
	use Singleton;

	protected const ROUTE  = 'post_like';
	protected const PUBLIC = true;

	private const LIKE_COUNT_META_KEY         = '_post_like_count';
	private const COMMENT_LIKE_COUNT_META_KEY = '_comment_like_count';
	private const USER_LIKE_META_KEY          = '_user_liked';
	private const USER_COMMENT_LIKE_META_KEY  = '_user_comment_liked';

	/**
	 * Constructor: keep admin hooks and l10n.
	 */
	public function __construct() {
		parent::__construct();

		add_filter( 'manage_post_posts_columns', array( __CLASS__, 'set_post_columns' ) );
		add_action( 'manage_post_posts_custom_column', array( __CLASS__, 'post_custom_column' ), 10, 2 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'post_like_meta_box' ) );

		add_action( 'show_user_profile', array( __CLASS__, 'show_user_likes' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'show_user_likes' ) );
	}

	/**
	 * Permission check — keep legacy nonce 'like_nonce' for backward compatibility.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return true|WP_Error
	 */
	public function permission_check( $request ): bool|WP_Error {
		$nonce = $request->get_header( 'x-wp-nonce' ) ?: $request->get_param( self::NONCE_FIELD );

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'rest_forbidden', __( 'Invalid nonce.', 'lerm' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Handle create (like/unlike) requests.
	 *
	 * Keep signature compatible with WP_REST_Controller.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		if ( ! $request instanceof WP_REST_Request ) {
			return $this->error( __( 'Invalid request object.', 'lerm' ), 400, 'invalid_request' );
		}

		$postdata = (array) $request->get_body_params();
		if ( empty( $postdata ) ) {
			$postdata = (array) $request->get_params();
		}
		$postdata = wp_unslash( $postdata );

		// sanitize inputs
		$type       = isset( $postdata['type'] ) && 'comment' === $postdata['type'] ? 'comment' : 'post';
		$is_comment = 'comment' === $type;
		$id         = isset( $postdata['id'] ) && is_numeric( $postdata['id'] ) ? intval( $postdata['id'] ) : 0;

		if ( 0 === $id ) {
			return $this->error( __( 'Invalid ID.', 'lerm' ), 400, 'invalid_id' );
		}

		$new_count = self::process_like_action( $id, $is_comment );

		$user_id = is_user_logged_in() ? get_current_user_id() : client_ip();
		$liked   = self::already_liked( $id, $is_comment );

		// clear cache & set fresh count for consistency
		$cache_key = $this->get_cache_key( $id, $is_comment );
		wp_cache_set( $cache_key, (int) $new_count );

		$response = array(
			'status'     => $liked ? 'liked' : 'unliked',
			'liked'      => (bool) $liked,
			'count'      => (int) $new_count,
			'count_html' => self::get_like_count( $new_count ), // formatted string
		);

		/**
		 * Action fired after like state changed.
		 *
		 * @param int          $id         post/comment ID
		 * @param string|int   $user_id    user id or client ip
		 * @param bool         $is_comment whether comment
		 * @param bool         $liked      new liked state
		 * @param int          $new_count  new like count
		 */
		do_action( 'lerm_post_like_changed', $id, $user_id, $is_comment, $liked, $new_count );

		return rest_ensure_response( $response );
	}

	/* ----------------------------- helpers ------------------------------ */

	/**
	 * Process like/unlike and update meta. Returns new count (int).
	 *
	 * @param int  $id
	 * @param bool $is_comment
	 * @return int
	 */
	private static function process_like_action( $id, $is_comment ): int {
		$already   = self::already_liked( $id, $is_comment );
		$new_count = $already ? self::unlike_post( $id, $is_comment ) : self::like_post( $id, $is_comment );

		$like_count_key    = $is_comment ? self::COMMENT_LIKE_COUNT_META_KEY : self::LIKE_COUNT_META_KEY;
		$like_modified_key = $is_comment ? '_comment_like_modified' : '_post_like_modified';

		self::update_like_meta( $id, $like_count_key, $like_modified_key, $new_count, $is_comment );

		return (int) $new_count;
	}

	/**
	 * Update meta values (count + modified).
	 *
	 * @param int    $id
	 * @param string $count_key
	 * @param string $modified_key
	 * @param int    $like_count
	 * @param bool   $is_comment
	 * @return void
	 */
	private static function update_like_meta( $id, $count_key, $modified_key, $like_count, $is_comment ): void {
		if ( $is_comment ) {
			update_comment_meta( $id, $count_key, (int) $like_count );
			update_comment_meta( $id, $modified_key, gmdate( 'Y-m-d H:i:s' ) );
		} else {
			update_post_meta( $id, $count_key, (int) $like_count );
			update_post_meta( $id, $modified_key, gmdate( 'Y-m-d H:i:s' ) );
		}
		// update cache
		$controller = self::instance();
		$cache_key  = $controller->get_cache_key( $id, $is_comment );
		wp_cache_set( $cache_key, (int) $like_count );
	}

	/**
	 * Like a post/comment.
	 *
	 * @param int  $id
	 * @param bool $is_comment
	 * @return int new count
	 */
	private static function like_post( $id, $is_comment ): int {
		return self::update_like( $id, $is_comment, true );
	}

	/**
	 * Unlike a post/comment.
	 *
	 * @param int  $id
	 * @param bool $is_comment
	 * @return int new count
	 */
	private static function unlike_post( $id, $is_comment ): int {
		return self::update_like( $id, $is_comment, false );
	}

	/**
	 * Update like counter & user-likes list, return new count.
	 *
	 * @param int  $id
	 * @param bool $is_comment
	 * @param bool $like
	 * @return int
	 */
	private static function update_like( $id, $is_comment, $like ): int {
		if ( $is_comment ) {
			$meta_key = self::COMMENT_LIKE_COUNT_META_KEY;
			$count    = (int) get_comment_meta( $id, $meta_key, true );
		} else {
			$meta_key = self::LIKE_COUNT_META_KEY;
			$count    = (int) get_post_meta( $id, $meta_key, true );
		}

		$user_id = is_user_logged_in() ? get_current_user_id() : client_ip();
		self::update_user_likes( $user_id, $id, $is_comment, $like );

		$new_count = $like ? ( $count + 1 ) : max( 0, $count - 1 );

		// persist
		if ( $is_comment ) {
			update_comment_meta( $id, $meta_key, (int) $new_count );
		} else {
			update_post_meta( $id, $meta_key, (int) $new_count );
		}

		// update cache
		$controller = self::instance();
		wp_cache_set( $controller->get_cache_key( $id, $is_comment ), (int) $new_count );

		return (int) $new_count;
	}

	/**
	 * Check whether user/IP has liked item.
	 *
	 * @param int      $id
	 * @param bool|null $is_comment
	 * @return bool
	 */
	public static function already_liked( $id, $is_comment = null ): bool {
		$is_comment = (bool) $is_comment;
		$meta_key   = $is_comment ? self::USER_COMMENT_LIKE_META_KEY : self::USER_LIKE_META_KEY;

		$likes = $is_comment ? get_comment_meta( $id, $meta_key, true ) : get_post_meta( $id, $meta_key, true );
		$likes = is_array( $likes ) ? $likes : array();

		$user_id = is_user_logged_in() ? get_current_user_id() : client_ip();

		return in_array( $user_id, $likes, true );
	}

	/**
	 * Update user-likes list.
	 *
	 * @param string|int $user_id
	 * @param int        $id
	 * @param bool       $is_comment
	 * @param bool       $like
	 * @return void
	 */
	private static function update_user_likes( $user_id, $id, $is_comment, $like ): void {
		$meta_key = $is_comment ? self::USER_COMMENT_LIKE_META_KEY : self::USER_LIKE_META_KEY;

		$likes = $is_comment ? get_comment_meta( $id, $meta_key, true ) : get_post_meta( $id, $meta_key, true );
		$likes = is_array( $likes ) ? $likes : array();

		if ( $like ) {
			if ( ! in_array( $user_id, $likes, true ) ) {
				$likes[] = $user_id;
			}
		} else {
			$likes = array_values( array_diff( $likes, array( $user_id ) ) );
		}

		if ( $is_comment ) {
			update_comment_meta( $id, $meta_key, $likes );
		} else {
			update_post_meta( $id, $meta_key, $likes );
		}
	}

	/**
	 * Generate a cache key for a given item.
	 *
	 * @param int  $id
	 * @param bool $is_comment
	 * @return string
	 */
	private function get_cache_key( $id, $is_comment ): string {
		return 'lerm_like_count_' . ( $is_comment ? 'c' : 'p' ) . '_' . (int) $id;
	}

	/**
	 * Output like button markup (default uses <button> for accessibility).
	 *
	 * @param int       $id
	 * @param bool|null $is_comment
	 * @param array     $args
	 * @return string|null
	 */
	public static function get_likes_button( $id, $is_comment = null, $args = array() ) {
		$defaults = array(
			'style' => 'button',
			'class' => '',
			'echo'  => true,
		);
		$args     = wp_parse_args( $args, $defaults );

		$id         = $is_comment ? get_comment_ID() : (int) $id;
		$type       = $is_comment ? 'comment' : 'post';
		$liked      = self::already_liked( $id, $is_comment );
		$like_count = (int) ( $is_comment ? get_comment_meta( $id, self::COMMENT_LIKE_COUNT_META_KEY, true ) : get_post_meta( $id, self::LIKE_COUNT_META_KEY, true ) );
		$count_html = self::get_like_count( $like_count );

		$classes = array_filter( array( 'like-button', $args['class'], $liked ? 'is-liked' : 'is-unliked' ) );
		$label   = $liked ? __( 'Unlike', 'lerm' ) : __( 'Like', 'lerm' );

		// Use nonce for frontend safety (cheap to generate)
		$nonce = wp_create_nonce( 'like_nonce' );

		$button = sprintf(
			'<button type="button" class="%1$s" aria-pressed="%2$s" aria-label="%3$s" data-id="%4$d" data-type="%5$s" data-nonce="%6$s">%7$s <span class="count-wrap">%8$s</span></button>',
			esc_attr( implode( ' ', $classes ) ),
			$liked ? 'true' : 'false',
			esc_attr( $label ),
			esc_attr( $id ),
			esc_attr( $type ),
			esc_attr( $nonce ),
			self::get_liked_icon(),
			$count_html
		);

		if ( false === $args['echo'] ) {
			return $button;
		}

		echo $button; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above.
		return null;
	}

	public static function get_liked_icon() {
		return '<i class="li li-heart" aria-hidden="true"></i>';
	}

	public static function get_unliked_icon() {
		return '<i class="li li-heart-o" aria-hidden="true"></i>';
	}

	public static function format_count( $number ) {
		$precision = 2;
		if ( $number >= 1000000000 ) {
			$formatted = number_format( $number / 1000000000, $precision ) . 'B';
		} elseif ( $number >= 1000000 ) {
			$formatted = number_format( $number / 1000000, $precision ) . 'M';
		} elseif ( $number >= 1000 ) {
			$formatted = number_format( $number / 1000, $precision ) . 'K';
		} else {
			$formatted = (string) $number;
		}

		if ( strpos( $formatted, '.' ) !== false ) {
			$formatted = rtrim( rtrim( $formatted, '0' ), '.' );
		}

		return $formatted;
	}

	public static function get_like_count( $like_count ) {
		$number = ( is_numeric( $like_count ) && $like_count > 0 ) ? self::format_count( $like_count ) : '0';
		return '<span class="count">' . esc_html( $number ) . '</span>';
	}

	/* ---------------- admin/profile helpers (unchanged logic but cleaned) ---------------- */

	public static function show_user_likes( $user ) {
		$types = get_post_types( array( 'public' => true ) );
		$args  = array(
			'numberposts' => -1,
			'post_type'   => $types,
			'meta_query'  => array(
				array(
					'key'     => self::USER_LIKE_META_KEY,
					'value'   => $user->ID,
					'compare' => 'LIKE',
				),
			),
		);

		$like_query = new WP_Query( $args );

		echo '<h3>' . esc_html__( 'Liked Posts', 'lerm' ) . '</h3>';

		if ( $like_query->have_posts() ) {
			$links = array();
			while ( $like_query->have_posts() ) {
				$like_query->the_post();
				$links[] = sprintf(
					'<a href="%1$s" title="%2$s">%3$s</a>',
					esc_url( get_permalink() ),
					esc_attr( get_the_title() ),
					esc_html( get_the_title() )
				);
			}
			echo wp_kses_post( implode( ' &middot; ', $links ) );
		} else {
			echo esc_html__( 'You do not like anything yet.', 'lerm' );
		}
		wp_reset_postdata();
	}

	public static function set_post_columns( $columns ) {
		$columns['likes'] = __( 'Likes', 'lerm' );
		return $columns;
	}

	public static function post_custom_column( $column, $id ) {
		if ( 'likes' === $column ) {
			$like_count = (int) get_post_meta( $id, self::LIKE_COUNT_META_KEY, true );
			echo esc_html( $like_count );
		}
	}

	public static function post_like_meta_box() {
		add_meta_box(
			'post_like_meta_box',
			__( 'Post Like Count', 'lerm' ),
			array( __CLASS__, 'render_meta_box' ),
			'post',
			'side',
			'high'
		);
	}

	public static function render_meta_box( $post ) {
		$like_count = (int) get_post_meta( $post->ID, self::LIKE_COUNT_META_KEY, true );
		echo '<p>' . esc_html__( 'Total Likes:', 'lerm' ) . ' ' . esc_html( $like_count ) . '</p>';
	}

	public static function rest_l10n_data( $l10n ) {
		$l10n = parent::rest_l10n_data( $l10n );
		$data = array(
			'like_action' => self::ROUTE,
		);
		return wp_parse_args( $data, $l10n );
	}
}
