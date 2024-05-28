<?php // phpcs:disable WordPress.Files.FileName
/**
 * Post like button ajax handler functions.
 *
 * @author Lerm https://www.hanost.com
 * @since  Lerm 2.0
 */
namespace Lerm\Inc;

use Lerm\Inc\Traits\Ajax;
use Lerm\Inc\Traits\Singleton;

class PostLike {
	use Ajax;
	use singleton;

	private const AJAX_ACTION                 = 'post_like';
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
	 * @var int $post_id
	 */
	private static $post_id;

	/**
	 * Constructor.
	 *
	 * @param array $params Optional. Arguments for the class.
	 */
	public function __construct( $params = array() ) {
		$this->register();
		add_filter( 'manage_post_posts_columns', array( __CLASS__, 'set_post_columns' ) );
		add_action( 'manage_post_posts_custom_column', array( __CLASS__, 'post_custom_column' ), 10, 2 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'post_like_meta_box' ) );

		// User Profile List
		add_action( 'show_user_profile', array( __CLASS__, 'show_user_likes' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'show_user_likes' ) );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'scripts' ) );
		// add_filter( 'wp_script_attributes', array( __CLASS__, 'script_type_module' ), 10, 1 );
		self::$args = wp_parse_args( $params, self::$args );
	}

	public static function register() {
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, array( __CLASS__, 'ajax_handler' ) );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( __CLASS__, 'ajax_handler' ) );
	}

	/**
	 * AJAX handler for processing like/unlike actions.
	 *
	 *
	 */
	public static function ajax_handler() {
		// Verify the nonce
		check_ajax_referer( 'lerm_nonce', 'security', true );

		// Sanitize POST data
		$postdata = wp_unslash( $_POST );

		// Validate and sanitize input
		$is_comment = isset( $postdata['type'] ) && 'comment' === $postdata['type'] ? 1 : 0;
		$post_id    = isset( $postdata['post_id'] ) && is_numeric( $postdata['post_id'] ) ? intval( $postdata['post_id'] ) : '';

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => 'Invalid post ID' ) );
			return;
		}

		// Process like action
		$like_count  = self::process_like_action( $post_id, $is_comment );
		$like_status = self::already_liked( $post_id, $is_comment );

		// Prepare response
		$response = array(
			'status' => $like_status ? 'liked' : 'unliked',
			'count'  => $like_count,
		);

		if ( isset( $postdata['disabled'] ) && true === $postdata['disabled'] ) {
			$redirect_url = ( $is_comment ) ? get_permalink( get_the_ID() ) : get_permalink( $post_id );
			wp_safe_redirect( $redirect_url );
			exit();
		}

		// Send JSON success response
		wp_send_json_success( $response );
	}

	/**
	 * Process like action for a post or comment.
	 *
	 * @param int $post_id The ID of the post or comment.
	 * @param bool $is_comment Whether the ID refers to a comment.
	 * @return int The updated like count after processing the like action.
	 */
	private static function process_like_action( $post_id, $is_comment ) {
		// Perform actions based on whether the post/comment is already liked or not
		$like_count = self::already_liked( $post_id, $is_comment ) ? self::unlike_post( $post_id, $is_comment ) : self::like_post( $post_id, $is_comment );

		// Constants for meta keys
		$like_count_key    = $is_comment ? self::COMMENT_LIKE_COUNT_META_KEY : self::LIKE_COUNT_META_KEY;
		$like_modified_key = $is_comment ? '_comment_like_modified' : '_post_like_modified';

		self::update_like_meta( $post_id, $like_count_key, $like_modified_key, $like_count, $is_comment );

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
	 * @param int $post_id The ID of the post or comment.
	 * @param bool $is_comment Whether the ID refers to a comment.
	 * @return int The updated like count after liking the post or comment.
	 */
	private static function like_post( $post_id, $is_comment ) {
		// Get current like count
		$meta_key = $is_comment ? self::COMMENT_LIKE_COUNT_META_KEY : self::LIKE_COUNT_META_KEY;
		$count    = (int) get_metadata( $is_comment ? 'comment' : 'post', $post_id, $meta_key, true );

		//get user id
		$user_id = is_user_logged_in() ? get_current_user_id() : self::lerm_client_ip();
		self::update_user_likes( $user_id, $post_id, $is_comment, true );

		// update like count
		update_metadata( $is_comment ? 'comment' : 'post', $post_id, $meta_key, ++$count );
		return $count;
	}

	/**
	 * Unlike a post or comment.
	 *
	 * @param int $post_id The ID of the post or comment.
	 * @param bool $is_comment Whether the ID refers to a comment.
	 * @return int The updated like count after unliking the post or comment.
	 */
	private static function unlike_post( $post_id, $is_comment ) {
		// Get current like count
		$meta_key = $is_comment ? self::COMMENT_LIKE_COUNT_META_KEY : self::LIKE_COUNT_META_KEY;
		$count    = (int) get_metadata( $is_comment ? 'comment' : 'post', $post_id, $meta_key, true );

		//get user id
		$user_id = is_user_logged_in() ? get_current_user_id() : self::lerm_client_ip();
		self::update_user_likes( $user_id, $post_id, $is_comment, false );

		// update like count.
		$new_count = max( 0, --$count );
		update_metadata( $is_comment ? 'comment' : 'post', $post_id, $meta_key, $new_count );
		return $new_count;
	}

	/**
	 * Checks if the current user has already liked the post or comment.
	 *
	 * @param int $post_id The ID of the post or comment.
	 * @param bool $is_comment Whether the ID refers to a comment.
	 * @return bool True if the user has already liked the post or comment, false otherwise.
	 */
	public static function already_liked( $post_id, $is_comment = null ) {
		$user_id    = is_user_logged_in() ? get_current_user_id() : self::lerm_client_ip();
		$meta_key   = $is_comment ? self::USER_COMMENT_LIKE_META_KEY : self::USER_LIKE_META_KEY;
		$post_users = get_metadata( $is_comment ? 'comment' : 'post', $post_id, $meta_key, true );

		// Check if user is in the list of liked users
		if ( is_array( $post_users ) && in_array( $user_id, $post_users, true ) ) {
			return true;
		} else {
			return false;
		}
	}

	private static function update_user_likes( $user_id, $post_id, $is_comment, $like = true ) {
		$meta_key   = $is_comment ? self::USER_COMMENT_LIKE_META_KEY : self::USER_LIKE_META_KEY;
		$user_likes = get_metadata( $is_comment ? 'comment' : 'post', $post_id, $meta_key, true ) ? get_metadata( $is_comment ? 'comment' : 'post', $post_id, $meta_key, true ) : array();

		if ( $like ) {
			if ( ! in_array( $user_id, $user_likes, true ) ) {
				$user_likes[] = $user_id;
			}
		} else {
			$user_likes = array_diff( $user_likes, array( $user_id ) );
		}

		update_metadata( $is_comment ? 'comment' : 'post', $post_id, $meta_key, $user_likes );
	}

	/**
	 * Output the like button
	 *
	 * @since    0.5
	 */
	public static function get_likes_button( $post_id, $is_comment = null ) {
		$classes = array( 'btn', 'like-button' );
		$id      = $is_comment ? get_comment_ID() : $post_id;
		$type    = $is_comment ? 'comment' : 'post';

		$classes[] = self::already_liked( $post_id, $is_comment ) ? 'btn-danger' : 'btn-outline-danger';
		$classes[] = 'like-' . $type . '-' . $id;

		// Get current like count
		$meta_key   = $is_comment ? self::COMMENT_LIKE_COUNT_META_KEY : self::LIKE_COUNT_META_KEY;
		$like_count = (int) get_metadata( $is_comment ? 'comment' : 'post', $id, $meta_key, true );

		$count  = self::get_like_count( $like_count );
		$output = sprintf(
			'<button data-id="%d" data-post-id="%d" data-logged="%s" data-type="%s" class="%s">
			<span><i class="fa fa-heart"></i></span> 
			%s
			</button>',
			$id,
			$post_id,
			esc_attr( is_user_logged_in() ),
			esc_attr( $type ),
			esc_attr( implode( ' ', $classes ) ),
			$count
		);

		return $output;
	}

	/**
	 * Get client IP address.
	 *
	 * @return string Client IP address.
	 */
	public static function lerm_client_ip() {
		$ip = '0.0.0.0';

		if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) && ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = ( isset( $_SERVER['REMOTE_ADDR'] ) ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
		}

		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
	}

	/**
	 * Utility returns the button icon for "like" action
	 *
	 * @since    0.5
	 */
	public static function get_liked_icon() {
		/* If already using Font Awesome with your theme, replace svg with: <i class="fa fa-heart"></i> */
		return '<i class="fa fa-heart"></i>';
	}

	/**
	 * Utility returns the button icon for "unlike" action
	 *
	 * @since    0.5
	 */
	public static function get_unliked_icon() {
		/* If already using Font Awesome with your theme, replace svg with: <i class="fa fa-heart-o"></i> */
		return '<i class="fa fa-heart-o"></i>';
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
		$like_text = __( 'Like', 'lerm' );
		if ( is_numeric( $like_count ) && $like_count > 0 ) {
			$number = self::format_count( $like_count );
		} else {
			$number = $like_text;
		}
		$count = '<span class="count">' . $number . '</span>';
		return $count;
	}

	/**
	 * Display the list of liked posts on the user profile page.
	 *
	 * @param \WP_User $user The current user object.
	 */
	public static function show_user_likes( $user ) {
		$user_likes = get_user_meta( $user->ID, self::USER_LIKE_META_KEY, true );
		echo '<h3>' . esc_html__( 'Liked Posts', 'lerm' ) . '</h3>';
		if ( ! empty( $user_likes ) ) {
			echo '<ul>';
			foreach ( $user_likes as $post_id ) {
				echo '<li><a href="' . esc_url( get_permalink( $post_id ) ) . '">' . esc_html( get_the_title( $post_id ) ) . '</a></li>';
			}
			echo '</ul>';
		} else {
			echo '<p>' . esc_html__( 'No liked posts found.', 'lerm' ) . '</p>';
		}
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
	 * @param int    $post_id The ID of the current post.
	 */
	public static function post_custom_column( $column, $post_id ) {
		if ( 'likes' === $column ) {
			$like_count = get_post_meta( $post_id, self::LIKE_COUNT_META_KEY, true );
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
	public static function scripts( $l10n ) {
		wp_register_script( 'likebtn', LERM_URI . 'assets/js/likebtn.js', array(), LERM_VERSION, true );
		wp_localize_script(
			'likebtn',
			'lermAjax',
			array(
				'ajaxURL'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'lerm_nonce' ),
				'logged'     => is_user_logged_in(),
				'noUnlike'   => false,
				'loggedOnly' => false,
				'loginURL'   => '',
				'like'       => 'like',
				'unlike'     => 'unlike',
				'loader'     => '',
			)
		);
		wp_enqueue_script( 'likebtn' );
	}

	/**
	 * Add module type check to module script.
	 *
	 * @param  array $attr Attributes of each script.
	 *
	 * @return array $attr Attributes of each script.
	 */
	public static function script_type_module( $attributes ) {
		// if ( empty( $attr['id'] ) || empty( $attr['src'] ) ) {
		// 	return $attr;
		// }
		// var_dump( '111' );
		// if ( 'likebtn-js' === $attr['id'] ) {
		// 	$attr['type'] = 'module';
		// }
		// Only do this for a specific script.
		if ( isset( $attributes['id'] ) && 'likebtn-js' === $attributes['id'] ) {
			$attributes['type'] = 'module';
		}

		return $attributes;
		// return $attr;
	}
}
