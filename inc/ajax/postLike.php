<?php // phpcs:disable WordPress.Files.FileName
/**
 * Post like button ajax handler functions.
 *
 * @author Lerm https://www.hanost.com
 * @since  Lerm 2.0
 */
namespace Lerm\Inc\Ajax;

use Lerm\Inc\Traits\Singleton;
use function Lerm\Inc\Functions\Helpers\client_ip;

final class PostLike extends BaseAjax {

	use singleton;

	protected const AJAX_ACTION               = 'post_like';
	private const LIKE_COUNT_META_KEY         = '_post_like_count';
	private const COMMENT_LIKE_COUNT_META_KEY = '_comment_like_count';
	private const USER_LIKE_META_KEY          = '_user_liked';
	private const USER_COMMENT_LIKE_META_KEY  = '_user_comment_liked';

	/**
	 * The arguments for the class.
	 *
	 * @var array $args
	 */
	private static $args;

	/**
	 * The ID of the post being processed.
	 *
	 * @var int $id
	 */
	private static $id;

	/**
	 * Constructor.
	 *
	 * @param array $params Optional. Arguments for the class.
	 */
	public function __construct( $params = array() ) {
		parent::__construct( apply_filters( 'lerm_postlike_args', wp_parse_args( $params, self::$args ) ) );

		add_filter( 'manage_post_posts_columns', array( __CLASS__, 'set_post_columns' ) );
		add_action( 'manage_post_posts_custom_column', array( __CLASS__, 'post_custom_column' ), 10, 2 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'post_like_meta_box' ) );

		// User Profile List
		add_action( 'show_user_profile', array( __CLASS__, 'show_user_likes' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'show_user_likes' ) );

		add_filter( 'lerm_l10n_data', array( __CLASS__, 'l10n_data' ) );
	}

	/**
	 * AJAX handler for processing like/unlike actions.
	 *
	 *
	 */
	public static function ajax_handle() {
		// Verify the nonce
		check_ajax_referer( 'like_nonce', 'security', true );

		// Sanitize POST data
		$postdata = wp_unslash( $_POST );

		// Validate and sanitize input
		$is_comment = isset( $postdata['type'] ) && 'comment' === $postdata['type'] ? 1 : 0;
		$id         = isset( $postdata['id'] ) && is_numeric( $postdata['id'] ) ? intval( $postdata['id'] ) : '';

		if ( ! $id ) {
			self::error( array( 'message' => 'Invalid post ID' ) );
			return;
		}

		// Process like action
		$like_count  = self::process_like_action( $id, $is_comment );
		$like_status = self::already_liked( $id, $is_comment );

		// Prepare response
		$response = array(
			'status' => $like_status ? 'liked' : 'unliked',
			'count'  => $like_count,
		);

		if ( isset( $postdata['disabled'] ) && true === $postdata['disabled'] ) {
			$redirect_url = ( $is_comment ) ? get_permalink( get_the_ID() ) : get_permalink( $id );
			wp_safe_redirect( $redirect_url );
			exit();
		}

		// Send JSON success response
		self::success( $response );
	}

	/**
	 * Process like action for a post or comment.
	 *
	 * @param int $id The ID of the post or comment.
	 * @param bool $is_comment Whether the ID refers to a comment.
	 * @return int The updated like count after processing the like action.
	 */
	private static function process_like_action( $id, $is_comment ) {
		// Perform actions based on whether the post/comment is already liked or not
		$like_count = self::already_liked( $id, $is_comment ) ? self::unlike_post( $id, $is_comment ) : self::like_post( $id, $is_comment );

		// Constants for meta keys
		$like_count_key    = $is_comment ? self::COMMENT_LIKE_COUNT_META_KEY : self::LIKE_COUNT_META_KEY;
		$like_modified_key = $is_comment ? '_comment_like_modified' : '_post_like_modified';

		self::update_like_meta( $id, $like_count_key, $like_modified_key, $like_count, $is_comment );

		return $like_count;
	}

	/**
	 * Update like count and modified time in meta.
	 *
	 * @param int $id The ID of the post or comment.
	 * @param string $count_key The meta key for like count.
	 * @param string $modified_key The meta key for modified time.
	 * @param int $like_count The updated like count.
	 */
	private static function update_like_meta( $id, $count_key, $modified_key, $like_count, $is_comment ) {
		update_metadata( $is_comment ? 'comment' : 'post', $id, $count_key, $like_count );
		update_metadata( $is_comment ? 'comment' : 'post', $id, $modified_key, gmdate( 'Y-m-d H:i:s' ) );
	}

	/**
	 * Like a post or comment.
	 *
	 * @param int $id The ID of the post or comment.
	 * @param bool $is_comment Whether the ID refers to a comment.
	 * @return int The updated like count after liking the post or comment.
	 */
	private static function like_post( $id, $is_comment ) {
		return self::update_like( $id, $is_comment, true );
	}

	/**
	 * Unlike a post or comment.
	 *
	 * @param int $id The ID of the post or comment.
	 * @param bool $is_comment Whether the ID refers to a comment.
	 * @return int The updated like count after unliking the post or comment.
	 */
	private static function unlike_post( $id, $is_comment ) {
		return self::update_like( $id, $is_comment, false );
	}
	private static function update_like( $id, $is_comment, $like ) {
		$meta_key = $is_comment ? self::COMMENT_LIKE_COUNT_META_KEY : self::LIKE_COUNT_META_KEY;
		$count    = (int) get_metadata( $is_comment ? 'comment' : 'post', $id, $meta_key, true );

		$user_id = is_user_logged_in() ? get_current_user_id() : client_ip();
		self::update_user_likes( $user_id, $id, $is_comment, $like );

		$new_count = $like ? ++$count : max( 0, --$count );
		update_metadata( $is_comment ? 'comment' : 'post', $id, $meta_key, $new_count );
		return $new_count;
	}
	/**
	 * Checks if the current user has already liked the post or comment.
	 *
	 * @param int $id The ID of the post or comment.
	 * @param bool $is_comment Whether the ID refers to a comment.
	 * @return bool True if the user has already liked the post or comment, false otherwise.
	 */
	public static function already_liked( $id, $is_comment = null ) {
		$user_id    = is_user_logged_in() ? get_current_user_id() : client_ip();
		$meta_key   = $is_comment ? self::USER_COMMENT_LIKE_META_KEY : self::USER_LIKE_META_KEY;
		$post_users = get_metadata( $is_comment ? 'comment' : 'post', $id, $meta_key, true );
		// Check if user is in the list of liked users
		if ( is_array( $post_users ) && in_array( $user_id, $post_users, true ) ) {
			return true;
		} else {
			return false;
		}
	}

	private static function update_user_likes( $user_id, $id, $is_comment, $like ) {
		$meta_key   = $is_comment ? self::USER_COMMENT_LIKE_META_KEY : self::USER_LIKE_META_KEY;
		$user_likes = get_metadata( $is_comment ? 'comment' : 'post', $id, $meta_key, true ) ? get_metadata( $is_comment ? 'comment' : 'post', $id, $meta_key, true ) : array();

		if ( $like ) {
			if ( ! in_array( $user_id, $user_likes, true ) ) {
				$user_likes[] = $user_id;
			}
		} else {
			$user_likes = array_diff( $user_likes, array( $user_id ) );
		}

		update_metadata( $is_comment ? 'comment' : 'post', $id, $meta_key, $user_likes );
	}
	/**
	 * Output the like button
	 *
	 * @since    0.5
	 */
	public static function get_likes_button( $id, $is_comment = null, $args = array() ) {
		$tag       = ( 'button' === $args['style'] ) ? 'button' : 'a';
		$classes   = array( 'like-button' );
		$classes[] = $args['class'];
		$id        = $is_comment ? get_comment_ID() : $id;
		$type      = $is_comment ? 'comment' : 'post';
		$classes[] = self::already_liked( $id, $is_comment ) ? 'btn-outline-danger' : 'btn-outline-secondary';
		$classes[] = 'like-' . $type . '-' . $id;
		// Get current like count
		$meta_key   = $is_comment ? self::COMMENT_LIKE_COUNT_META_KEY : self::LIKE_COUNT_META_KEY;
		$like_count = (int) get_metadata( $type, $id, $meta_key, true );

		$count  = self::get_like_count( $like_count );
		$output = sprintf(
			'<%1$s  class="%2$s" data-id="%3$d" data-logged="%4$s" data-type="%5$s">
			<span class="li li-heart"></span>
			%6$s
			</%1$s>',
			$tag,
			implode( ' ', $classes ),
			$id,
			esc_attr( is_user_logged_in() ),
			esc_attr( $type ),
			$count
		);
		if ( false === $args['echo'] ) {
			return $output;
		}
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput -- Reason: has been escaped.
	}

	/**
	 * Utility returns the button icon for "like" action
	 *
	 * @since    0.5
	 */
	public static function get_liked_icon() {
		/* If already using Font Awesome with your theme, replace svg with: <i class="li li-heart"></i> */
		return '<i class="li li-heart"></i>';
	}

	/**
	 * Utility returns the button icon for "unlike" action
	 *
	 * @since    0.5
	 */
	public static function get_unliked_icon() {
		/* If already using Font Awesome with your theme, replace svg with: <i class="li li-heart-o"></i> */
		return '<i class="li li-heart-o"></i>';
	}

	/**
	 * Format a number into a human-readable format.
	 *
	 * @param int|float $number The number to format.
	 *
	 * @return string Formatted number.
	 */
	public static function format_count( $number ) {
		$precision = 2;
		if ( $number >= 1000000000 ) {
			$formatted = number_format( $number / 1000000000, $precision ) . 'B';
		} elseif ( $number >= 1000000 ) {
			$formatted = number_format( $number / 1000000, $precision ) . 'M';
		} elseif ( $number >= 1000 ) {
			$formatted = number_format( $number / 1000, $precision ) . 'K';
		} else {
			$formatted = $number; // Number is less than 1000
		}

		// Remove unnecessary decimal places
		if ( strpos( $formatted, '.' ) !== false ) {
			$formatted = rtrim( $formatted, '0' );
			$formatted = rtrim( $formatted, '.' );
		}

		return $formatted;
	}

	/**
	 * Utility retrieves count plus count options,
	 * returns appropriate format based on options
	 *
	 * @since    0.5
	 */
	public static function get_like_count( $like_count ) {
		$number = ( is_numeric( $like_count ) && $like_count > 0 ) ? self::format_count( $like_count ) : 0;
		return '<span class="count">' . $number . '</span>';
	}
	/**
	 * Display the list of liked posts on the user profile page.
	 *
	 * @param \WP_User $user The current user object.
	 */
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

		$like_query = new \WP_Query( $args );

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
			echo implode( ' &middot; ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			echo esc_html__( 'You do not like anything yet.', 'lerm' );
		}
		wp_reset_postdata();
	}
	/**
	 * Add custom column to the post list table.
	 *
	 * @param array $columns The existing columns.
	 * @return array The modified columns.
	 */
	public static function set_post_columns( $columns ) {
		$columns['likes'] = __( 'Likes', 'lerm' );
		return $columns;
	}
	/**
	 * Output the like count in the custom column.
	 *
	 * @param string $column The name of the column.
	 * @param int    $id The ID of the current post.
	 */
	public static function post_custom_column( $column, $id ) {
		if ( 'likes' === $column ) {
			$like_count = get_post_meta( $id, self::LIKE_COUNT_META_KEY, true );
			echo esc_html( $like_count );
		}
	}
	/**
	 * Add a meta box to the post editing screen.
	 */
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
	/**
	 * Render the content of the meta box.
	 *
	 * @param \WP_Post $post The current post object.
	 */
	public static function render_meta_box( $post ) {
		$like_count = get_post_meta( $post->ID, self::LIKE_COUNT_META_KEY, true );
		echo '<p>' . esc_html__( 'Total Likes:', 'lerm' ) . ' ' . esc_html( $like_count ) . '</p>';
	}
	/**
	 * Generate AJAX localization data.
	 *
	 * This function generates an array of localized data for use in AJAX requests.
	 *
	 * @param array $l10n Existing localization data.
	 * @return array Localized data for AJAX requests.
	 */
	public static function l10n_data( $l10n ) {
		$data = array(
			'like_nonce'  => wp_create_nonce( 'like_nonce' ),
			'like_action' => self::AJAX_ACTION,
		);
		$data = wp_parse_args( $data, $l10n );
		return $data;
	}
}
