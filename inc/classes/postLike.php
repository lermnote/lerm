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

	private const AJAX_ACTION = 'post_like';
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
		$this->register( 'post_like' );
		add_filter( 'manage_post_posts_columns', array( __CLASS__, 'set_post_columns' ) );
		add_action( 'manage_post_posts_custom_column', array( __CLASS__, 'post_custom_column' ), 10, 2 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'post_like_meta_box' ) );

			// User Profile List
		add_action( 'show_user_profile', array( __CLASS__, 'show_user_likes' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'show_user_likes' ) );

		self::$args = wp_parse_args( $params, self::$args );
	}

	public static function register() {
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, array( __CLASS__, 'ajax_handler' ) );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( __CLASS__, 'ajax_handler' ) );
	}

	/**
	 * AJAX handler for processing like/unlike actions.
	 *
	 * Checks security nonce, processes like/unlike action, and returns response.
	 */
	public static function ajax_handler() {
		check_ajax_referer( 'ajax_nonce', 'security', true );
		$postdata = wp_unslash( $_POST );

		$is_comment = ( isset( $postdata['is_comment'] ) && 1 === $postdata['is_comment'] ) ? 1 : 0;
		$post_id    = ( isset( $postdata['post_id'] ) && is_numeric( $postdata['post_id'] ) ) ? $postdata['post_id'] : '';

		if ( '' !== $post_id ) {
			$like_count = self::process_like_action( $post_id, $is_comment );

			$response = array(
				'status'  => ( $like_count > 0 ) ? 'liked' : 'unliked',
				'icon'    => ( $like_count > 0 ) ? self::get_liked_icon() : self::get_unliked_icon(),
				'count'   => $like_count,
				'testing' => $is_comment,
			);

			if ( isset( $postdata['disabled'] ) && $postdata['disabled'] === true ) {
				$redirect_url = ( $is_comment ) ? get_permalink( get_the_ID() ) : get_permalink( $post_id );
				wp_safe_redirect( $redirect_url );
				exit();
			} else {
				wp_send_json( $response );
			}
		}
	}

	/**
	 * Process like action for a post or comment.
	 *
	 * @param int $post_id The ID of the post or comment.
	 * @param bool $is_comment Whether the ID refers to a comment.
	 * @return int The updated like count after processing the like action.
	 */
	private static function process_like_action( $post_id, $is_comment ) {
		$like_count = 0;

		// Perform actions based on whether the post/comment is already liked or not
		if ( ! self::already_liked( $post_id, $is_comment ) ) {
			$like_count = self::like_post( $post_id, $is_comment );
		} else {
			$like_count = self::unlike_post( $post_id, $is_comment );
		}

		// Update like count in post meta or comment meta
		if ( $is_comment ) {
			update_comment_meta( $post_id, '_comment_like_count', $like_count );
			update_comment_meta( $post_id, '_comment_like_modified', gmdate( 'Y-m-d H:i:s' ) );
		} else {
			update_post_meta( $post_id, '_post_like_count', $like_count );
			update_post_meta( $post_id, '_post_like_modified', gmdate( 'Y-m-d H:i:s' ) );
		}

		return $like_count;
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
		$count = ( $is_comment ) ? get_comment_meta( $post_id, '_comment_like_count', true ) : get_post_meta( $post_id, '_post_like_count', true );
		$count = ( isset( $count ) && is_numeric( $count ) ) ? $count : 0;

		if ( is_user_logged_in() ) {
			// Get current user ID
			$user_id = get_current_user_id();

			// Update user like count
			$user_like_option = ( $is_comment ) ? '_comment_like_count' : '_user_like_count';
			$user_like_count  = get_user_option( $user_like_option, $user_id );
			$user_like_count  = ( isset( $user_like_count ) && is_numeric( $user_like_count ) ) ? $user_like_count : 0;
			update_user_option( $user_id, $user_like_option, ++$user_like_count );

			// Update post or comment user likes
			$post_users = self::post_user_likes( $user_id, $post_id, $is_comment );
			if ( $post_users ) {
				$meta_key = ( $is_comment ) ? '_user_comment_liked' : '_user_liked';
				update_metadata( 'post', $post_id, $meta_key, $post_users );
			}
		} else {
			// User is anonymous, use IP address
			$user_ip    = self::lerm_client_ip();
			$post_users = self::post_ip_likes( $user_ip, $post_id, $is_comment );
			// Update post or comment IP likes
			if ( $post_users ) {
				$meta_key = ( $is_comment ) ? '_user_comment_IP' : '_user_IP';
				update_metadata( 'post', $post_id, $meta_key, $post_users );
			}
		}

		// Increment like count
		$like_count = ++$count;

		return $like_count;
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
		$count = ( $is_comment ) ? get_comment_meta( $post_id, '_comment_like_count', true ) : get_post_meta( $post_id, '_post_like_count', true );
		$count = ( isset( $count ) && is_numeric( $count ) ) ? $count : 0;

		// Check if user is logged in
		if ( is_user_logged_in() ) {
			// Get current user ID
			$user_id = get_current_user_id();

			// Update user like count
			$user_like_option = ( $is_comment ) ? '_comment_like_count' : '_user_like_count';
			$user_like_count  = get_user_option( $user_like_option, $user_id );
			$user_like_count  = ( isset( $user_like_count ) && is_numeric( $user_like_count ) ) ? $user_like_count : 0;
			if ( $user_like_count > 0 ) {
				update_user_option( $user_id, $user_like_option, --$user_like_count );
			}

			// Update post or comment user likes
			$post_users = self::post_user_likes( $user_id, $post_id, $is_comment );
			if ( $post_users ) {
				$uid_key = array_search( $user_id, $post_users, true );
				if ( false !== $uid_key ) {
					unset( $post_users[ $uid_key ] );
					$meta_key = ( $is_comment ) ? '_user_comment_liked' : '_user_liked';
					update_metadata( 'post', $post_id, $meta_key, $post_users );
				}
			}
		} else {
			// User is anonymous, use IP address
			$user_ip    = self::lerm_client_ip();
			$post_users = self::post_ip_likes( $user_ip, $post_id, $is_comment );
			// Update post or comment IP likes
			if ( $post_users ) {
				$uip_key = array_search( $user_ip, $post_users, true );
				if ( false !== $uip_key ) {
					unset( $post_users[ $uip_key ] );
					$meta_key = ( $is_comment ) ? '_user_comment_IP' : '_user_IP';
					update_metadata( 'post', $post_id, $meta_key, $post_users );
				}
			}
		}

		// Decrement like count and prevent negative number
		$like_count = ( $count > 0 ) ? --$count : 0;

		return $like_count;
	}

	/**
	 * Checks if the current user has already liked the post or comment.
	 *
	 * @param int $post_id The ID of the post or comment.
	 * @param bool $is_comment Whether the ID refers to a comment.
	 * @return bool True if the user has already liked the post or comment, false otherwise.
	 */
	public static function already_liked( $post_id, $is_comment ) {

		if ( is_user_logged_in() ) {
			// User is logged in
			$user_id         = get_current_user_id();
			$post_meta_users = ( $is_comment ) ? get_comment_meta( $post_id, '_user_comment_liked' ) : get_post_meta( $post_id, '_user_liked' );
		} else {
			// User is anonymous, use IP address
			$user_id         = self::lerm_client_ip();
			$post_meta_users = ( $is_comment ) ? get_comment_meta( $post_id, '_user_comment_IP' ) : get_post_meta( $post_id, '_user_IP' );
		}

		// Check if meta exists and set post_users value
		if ( ! empty( $post_meta_users ) ) {
			$post_users = $post_meta_users[0];
		}

		// Check if user is in the list of liked users
		if ( is_array( $post_users ) && in_array( $user_id, $post_users, true ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Output the like button
	 *
	 * @since    0.5
	 */
	public static function get_simple_likes_button( $post_id, $is_comment = null ) {
		$is_comment = ( null === $is_comment ) ? 0 : 1;
		$output     = '';
		$nonce      = wp_create_nonce( 'simple-likes-nonce' ); // Security
		if ( 1 === $is_comment ) {
			$post_id_class = esc_attr( ' sl-comment-button-' . $post_id );
			$comment_class = esc_attr( ' sl-comment' );
			$like_count    = get_comment_meta( $post_id, '_comment_like_count', true );
			$like_count    = ( isset( $like_count ) && is_numeric( $like_count ) ) ? $like_count : 0;
		} else {
			$post_id_class = esc_attr( ' sl-button-' . $post_id );
			$comment_class = esc_attr( '' );
			$like_count    = get_post_meta( $post_id, '_post_like_count', true );
			$like_count    = ( isset( $like_count ) && is_numeric( $like_count ) ) ? $like_count : 0;
		}
		$count      = self::get_like_count( $like_count );
		$icon_empty = self::get_unliked_icon();
		$icon_full  = self::get_liked_icon();
		// Loader
		$loader = '<span id="sl-loader"></span>';
		// Liked/Unliked Variables
		if ( self::already_liked( $post_id, $is_comment ) ) {
			$class = esc_attr( ' liked' );
			$title = __( 'Unlike', 'YourThemeTextDomain' );
			$icon  = $icon_full;
		} else {
			$class = '';
			$title = __( 'Like', 'YourThemeTextDomain' );
			$icon  = $icon_empty;
		}
		$output = '<span class="sl-wrapper"><a href="' . admin_url( 'admin-ajax.php?action=process_simple_like' . '&post_id=' . $post_id . '&nonce=' . $nonce . '&is_comment=' . $is_comment . '&disabled=true' ) . '" class="sl-button' . $post_id_class . $class . $comment_class . '" data-nonce="' . $nonce . '" data-post-id="' . $post_id . '" data-iscomment="' . $is_comment . '" title="' . $title . '">' . $icon . $count . '</a>' . $loader . '</span>';
		return $output;
	}

	/**
 * Retrieve and update the list of users who liked a post or comment.
 *
 * @param int $user_id   The ID of the user who liked the post/comment.
 * @param int $post_id   The ID of the post/comment.
 * @param bool $is_comment Whether the item being liked is a comment.
 *
 * @return array Updated list of users who liked the post/comment.
 */
	public static function post_user_likes( $user_id, $post_id, $is_comment ) {

		// Get existing liked users
		$post_users = ( $is_comment ) ? get_comment_meta( $post_id, '_user_comment_liked', true ) : get_post_meta( $post_id, '_user_liked', true );
		$post_users = ( ! empty( $post_users ) && is_array( $post_users ) ) ? $post_users : array();

		// Add user to the list if not already present
		if ( ! isset( $post_users[ 'user-' . $user_id ] ) ) {
			$post_users[ 'user-' . $user_id ] = $user_id;
		}

		return $post_users;
	}

	/**
	 * Utility function to handle post IP likes (IP array).
	 * It retrieves post meta IP likes, then adds new IP to the retrieved array.
	 *
	 * @param string $user_ip The user's IP address.
	 * @param int $post_id The ID of the post.
	 * @param bool $is_comment Whether the post is a comment or not.
	 * @return array The updated array of post IP likes.
	 */
	public static function post_ip_likes( $user_ip, $post_id, $is_comment ) {
		// Retrieve existing post IP likes from meta
		$post_ip_likes = ( 1 === $is_comment ) ? get_comment_meta( $post_id, '_user_comment_IP', true ) : get_post_meta( $post_id, '_user_IP', true );

		// Initialize as an empty array if not present
		if ( ! $post_ip_likes || ! is_array( $post_ip_likes ) ) {
			$post_ip_likes = array();
		}

		// If the user's IP is not already in the array, add it
		if ( ! in_array( $user_ip, $post_ip_likes, true ) ) {
			$post_ip_likes[] = $user_ip;
			// Update the meta data
			if ( 1 === $is_comment ) {
				update_comment_meta( $post_id, '_user_comment_IP', $post_ip_likes );
			} else {
				update_post_meta( $post_id, '_user_IP', $post_ip_likes );
			}
		}

		// Return the updated array of post IP likes
		return $post_ip_likes;
	}

	/**
	 * Get client IP address.
	 *
	 * @return string Client IP address.
	 */
	public static function lerm_client_ip() {
		// Define default IP address
		$ip = '0.0.0.0';

		// Check if client IP is available from different server variables
		if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) && ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = ( isset( $_SERVER['REMOTE_ADDR'] ) ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
		}

		// Validate the IP address format
		$ip = ( false === filter_var( $ip, FILTER_VALIDATE_IP ) ) ? '0.0.0.0' : $ip;

		return $ip;
	}

	/**
	 * Utility returns the button icon for "like" action
	 *
	 * @since    0.5
	 */
	public static function get_liked_icon() {
		/* If already using Font Awesome with your theme, replace svg with: <i class="fa fa-heart"></i> */
		$icon = '<span class="sl-icon"><svg role="img" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0" y="0" viewBox="0 0 128 128" enable-background="new 0 0 128 128" xml:space="preserve"><path id="heart-full" d="M124 20.4C111.5-7 73.7-4.8 64 19 54.3-4.9 16.5-7 4 20.4c-14.7 32.3 19.4 63 60 107.1C104.6 83.4 138.7 52.7 124 20.4z"/>&#9829;</svg></span>';
		return $icon;
	}

	/**
	 * Utility returns the button icon for "unlike" action
	 *
	 * @since    0.5
	 */
	public static function get_unliked_icon() {
		/* If already using Font Awesome with your theme, replace svg with: <i class="fa fa-heart-o"></i> */
		$icon = '<span class="sl-icon"><svg role="img" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0" y="0" viewBox="0 0 128 128" enable-background="new 0 0 128 128" xml:space="preserve"><path id="heart" d="M64 127.5C17.1 79.9 3.9 62.3 1 44.4c-3.5-22 12.2-43.9 36.7-43.9 10.5 0 20 4.2 26.4 11.2 6.3-7 15.9-11.2 26.4-11.2 24.3 0 40.2 21.8 36.7 43.9C124.2 62 111.9 78.9 64 127.5zM37.6 13.4c-9.9 0-18.2 5.2-22.3 13.8C5 49.5 28.4 72 64 109.2c35.7-37.3 59-59.8 48.6-82 -4.1-8.7-12.4-13.8-22.3-13.8 -15.9 0-22.7 13-26.4 19.2C60.6 26.8 54.4 13.4 37.6 13.4z"/>&#9829;</svg></span>';
		return $icon;
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
		if ( $number >= 1000 ) {
			if ( $number < 1000000 ) {
				$formatted = number_format( $number / 1000, $precision ) . 'K';
			} elseif ( $number < 1000000000 ) {
				$formatted = number_format( $number / 1000000, $precision ) . 'M';
			} else {
				$formatted = number_format( $number / 1000000000, $precision ) . 'B';
			}
		} else {
			$formatted = $number; // Number is less than 1000
		}

		// Remove unnecessary decimal places
		$formatted = rtrim( $formatted, '0.' );

		return $formatted;
	}

	/**
	 * Utility retrieves count plus count options,
	 * returns appropriate format based on options
	 *
	 * @since    0.5
	 */
	public static function get_like_count( $like_count ) {
		$like_text = __( 'Like', 'YourThemeTextDomain' );
		if ( is_numeric( $like_count ) && $like_count > 0 ) {
			$number = self::format_count( $like_count );
		} else {
			$number = $like_text;
		}
		$count = '<span class="sl-count">' . $number . '</span>';
		return $count;
	}


	public static function show_user_likes( $user ) {
		?>        
	<table class="form-table">
		<tr>
			<th><label for="user_likes"><?php _e( 'You Like:', 'YourThemeTextDomain' ); ?></label></th>
			<td>
				<?php
				$types      = get_post_types( array( 'public' => true ) );
				$args       = array(
					'numberposts' => -1,
					'post_type'   => $types,
					'meta_query'  => array(
						array(
							'key'     => '_user_liked',
							'value'   => $user->ID,
							'compare' => 'LIKE',
						),
					),
				);
				$sep        = '';
				$like_query = new \WP_Query( $args );
				if ( $like_query->have_posts() ) :
					?>
			<p>
					<?php
					while ( $like_query->have_posts() ) :
						$like_query->the_post();
							echo $sep;
						?>
			<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a>
						<?php
							$sep = ' &middot; ';
				endwhile;
					?>
			</p>
				<?php else : ?>
			<p><?php _e( 'You do not like anything yet.', 'YourThemeTextDomain' ); ?></p>
					<?php
				endif;
				wp_reset_postdata();
				?>
			</td>
		</tr>
	</table>
		<?php
	}


	/**
	 * Set the custom column for post like count in post list table.
	 *
	 * @param array $columns Array of columns for the post list table.
	 * @return array
	 */
	public static function set_post_columns( $columns ) {
		$columns['like'] = esc_html__( 'Like', 'lerm' );
		return $columns;
	}

	/**
	 * Output the post like count for the custom column in post list table.
	 *
	 * @param string $column Name of the current column being processed.
	 * @param int    $post_id ID of the current post being processed.
	 */
	public static function post_custom_column( $column, $post_id ) {
		switch ( $column ) {
			case 'like':
				$like_count = get_post_meta( $post_id, 'lerm_post_like', true );
				echo esc_html( $like_count );
				break;
		}
	}

	public static function post_like_meta_box() {
		// add_meta_box( 'post_like', esc_html__( 'Like', 'lerm' ), self::post_like_callback( self::$post_id ), 'post', 'side' );
	}

	private static function post_like_callback( $post_id ) {
		wp_nonce_field( 'lerm_save_like_data', 'lerm_post_like_meta_box_nonce' );
		$value = get_post_meta( $post_id, 'lerm_post_like', true );
		echo '<label for="lerm_like_field">' . esc_html__( 'Post Like Count', 'lerm' ) . '</label>';
		echo '<input type="text" id="lerm_like_field" name="lerm_like_field" value="' . esc_attr( $value ) . '" size="25" />';
	}
}
