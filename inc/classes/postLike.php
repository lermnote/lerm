<?php
/**
 * Post like button ajax handler functions.
 *
 * @author Lerm https://www.hanost.com
 * @since  Lerm 2.0
 */
namespace Lerm\Inc;

use Lerm\Inc\Traits\Ajax;

class PostLike {
	use Ajax;

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
		self::$args = wp_parse_args( $params, self::$args );
			// User Profile List
		add_action( 'show_user_profile', array( __CLASS__, 'show_user_likes' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'show_user_likes' ) );
	}

	public static function register() {
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, array( __CLASS__, 'ajax_handler' ) );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( __CLASS__, 'ajax_handler' ) );
	}
	/**
	 * Get an instance of the Post_Like class.
	 *
	 * @param array $params Optional. Arguments for the class.
	 * @return Post_Like
	 */
	public static function instance( $params = array() ) {
		return new self( $params );
	}

	/**
	 * Callback function for processing the post like button AJAX request.
	 */
	public static function ajax_handler() {

		if ( ! wp_verify_nonce( $_REQUEST['security'], 'ajax_nonce' ) ) {
			\wp_die( esc_html__( 'Invalid nonce', 'lerm' ) );
		}

		// Test if javascript is disabled
		$disabled = ( isset( $_REQUEST['disabled'] ) && true === $_REQUEST['disabled'] ) ? true : false;
		// Test if this is a comment
		$is_comment = ( isset( $_REQUEST['is_comment'] ) && $_REQUEST['is_comment'] == 1 ) ? 1 : 0;
		// Base variables
		$post_id    = ( isset( $_REQUEST['post_id'] ) && is_numeric( $_REQUEST['post_id'] ) ) ? $_REQUEST['post_id'] : '';
		$result     = array();
		$post_users = null;
		$like_count = 0;

			// Get plugin options
		if ( '' !== $post_id ) {
			$count = ( 1 === $is_comment ) ? get_comment_meta( $post_id, '_comment_like_count', true ) : get_post_meta( $post_id, '_post_like_count', true ); // like count
			$count = ( isset( $count ) && is_numeric( $count ) ) ? $count : 0;
			if ( ! self::already_liked( $post_id, $is_comment ) ) { // Like the post
				if ( is_user_logged_in() ) { // user is logged in
					$user_id    = get_current_user_id();
					$post_users = self::post_user_likes( $user_id, $post_id, $is_comment );
					if ( 1 === $is_comment ) { // Update User & Comment
						$user_like_count = get_user_option( '_comment_like_count', $user_id );
						$user_like_count = ( isset( $user_like_count ) && is_numeric( $user_like_count ) ) ? $user_like_count : 0;
						update_user_option( $user_id, '_comment_like_count', ++$user_like_count );
						if ( $post_users ) {
							update_comment_meta( $post_id, '_user_comment_liked', $post_users );
						}
					} else {
						// Update User & Post
						$user_like_count = get_user_option( '_user_like_count', $user_id );
						$user_like_count = ( isset( $user_like_count ) && is_numeric( $user_like_count ) ) ? $user_like_count : 0;
						update_user_option( $user_id, '_user_like_count', ++$user_like_count );
						if ( $post_users ) {
							update_post_meta( $post_id, '_user_liked', $post_users );
						}
					}
				} else {
					// user is anonymous
					$user_ip    = self::lerm_client_ip();
					$post_users = self::post_ip_likes( $user_ip, $post_id, $is_comment );
					// Update Post
					if ( $post_users ) {
						if ( 1 === $is_comment ) {
							update_comment_meta( $post_id, '_user_comment_IP', $post_users );
						} else {
							update_post_meta( $post_id, '_user_IP', $post_users );
						}
					}
				}
				$like_count         = ++$count;
				$response['status'] = 'liked';
				$response['icon']   = self::get_liked_icon();
			} else {
				// Unlike the post
				if ( is_user_logged_in() ) { // user is logged in
					$user_id    = get_current_user_id();
					$post_users = self::post_user_likes( $user_id, $post_id, $is_comment );
					// Update User
					if ( 1 === $is_comment ) {
						$user_like_count = get_user_option( '_comment_like_count', $user_id );
						$user_like_count = ( isset( $user_like_count ) && is_numeric( $user_like_count ) ) ? $user_like_count : 0;
						if ( $user_like_count > 0 ) {
							update_user_option( $user_id, '_comment_like_count', --$user_like_count );
						}
					} else {
						$user_like_count = get_user_option( '_user_like_count', $user_id );
						$user_like_count = ( isset( $user_like_count ) && is_numeric( $user_like_count ) ) ? $user_like_count : 0;
						if ( $user_like_count > 0 ) {
							update_user_option( $user_id, '_user_like_count', --$user_like_count );
						}
					}
					// Update Post
					if ( $post_users ) {
						$uid_key = array_search( $user_id, $post_users, true );
						unset( $post_users[ $uid_key ] );
						if ( 1 === $is_comment ) {
							update_comment_meta( $post_id, '_user_comment_liked', $post_users );
						} else {
							update_post_meta( $post_id, '_user_liked', $post_users );
						}
					}
				} else { // user is anonymous
					$user_ip    = self::lerm_client_ip();
					$post_users = self::post_ip_likes( $user_ip, $post_id, $is_comment );
					// Update Post
					if ( $post_users ) {
						$uip_key = array_search( $user_ip, $post_users, true );
						unset( $post_users[ $uip_key ] );
						if ( 1 === $is_comment ) {
							update_comment_meta( $post_id, '_user_comment_IP', $post_users );
						} else {
							update_post_meta( $post_id, '_user_IP', $post_users );
						}
					}
				}
				$like_count         = ( $count > 0 ) ? --$count : 0; // Prevent negative number
				$response['status'] = 'unliked';
				$response['icon']   = self::get_unliked_icon();
			}
			if ( 1 === $is_comment ) {
				update_comment_meta( $post_id, '_comment_like_count', $like_count );
				update_comment_meta( $post_id, '_comment_like_modified', date( 'Y-m-d H:i:s' ) );
			} else {
				update_post_meta( $post_id, '_post_like_count', $like_count );
				update_post_meta( $post_id, '_post_like_modified', date( 'Y-m-d H:i:s' ) );
			}
			$response['count']   =  $like_count ;
			$response['testing'] = $is_comment;
			if ( true === $disabled ) {
				if ( 1 === $is_comment ) {
					wp_safe_redirect( get_permalink( get_the_ID() ) );
					exit();
				} else {
					wp_safe_redirect( get_permalink( $post_id ) );
					exit();
				}
			} else {
				wp_send_json( $response );
			}
		}
	}
	/**
	 * Utility to test if the post is already liked
	 *
	 * @since    0.5
	 */
	public static function already_liked( $post_id, $is_comment ) {
		$post_users = null;
		$user_id    = null;
		if ( is_user_logged_in() ) { // user is logged in
			$user_id         = get_current_user_id();
			$post_meta_users = ( 1 === $is_comment ) ? get_comment_meta( $post_id, '_user_comment_liked' ) : get_post_meta( $post_id, '_user_liked' );
			if ( count( $post_meta_users ) != 0 ) {
				$post_users = $post_meta_users[0];
			}
		} else { // user is anonymous
			$user_id         = self::lerm_client_ip();
			$post_meta_users = ( 1 === $is_comment ) ? get_comment_meta( $post_id, '_user_comment_IP' ) : get_post_meta( $post_id, '_user_IP' );
			if ( count( $post_meta_users ) != 0 ) { // meta exists, set up values
				$post_users = $post_meta_users[0];
			}
		}
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
	 * Utility retrieves post meta user likes (user id array),
	 * then adds new user id to retrieved array
	 *
	 * @since    0.5
	 */
	public static function post_user_likes( $user_id, $post_id, $is_comment ) {
		$post_users      = '';
		$post_meta_users = ( 1 === $is_comment ) ? get_comment_meta( $post_id, '_user_comment_liked' ) : get_post_meta( $post_id, '_user_liked' );
		if ( count( $post_meta_users ) != 0 ) {
			$post_users = $post_meta_users[0];
		}
		if ( ! is_array( $post_users ) ) {
			$post_users = array();
		}
		if ( ! in_array( $user_id, $post_users, true ) ) {
			$post_users[ 'user-' . $user_id ] = $user_id;
		}
		return $post_users;
	}

	/**
	 * Utility retrieves post meta ip likes (ip array),
	 * then adds new ip to retrieved array
	 *
	 * @since    0.5
	 */
	public static function post_ip_likes( $user_ip, $post_id, $is_comment ) {
		$post_users      = '';
		$post_meta_users = ( 1 === $is_comment ) ? get_comment_meta( $post_id, '_user_comment_IP' ) : get_post_meta( $post_id, '_user_IP' );
		// Retrieve post information
		if ( count( $post_meta_users ) != 0 ) {
			$post_users = $post_meta_users[0];
		}
		if ( ! is_array( $post_users ) ) {
			$post_users = array();
		}
		if ( ! in_array( $user_ip, $post_users, true ) ) {
			$post_users[ 'ip-' . $user_ip ] = $user_ip;
		}
		return $post_users;
	}


	/**
	 * Get the IP address of the current browser.
	 *
	 * @since    3.2.8
	 */
	public static function lerm_client_ip() {
		if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) && ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = ( isset( $_SERVER['REMOTE_ADDR'] ) ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
		}
		$ip = ( false === filter_var( $ip, FILTER_VALIDATE_IP ) ) ? '0.0.0.0' : filter_var( $ip, FILTER_VALIDATE_IP );

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
	 * Utility function to format the button count,
	 * appending "K" if one thousand or greater,
	 * "M" if one million or greater,
	 * and "B" if one billion or greater (unlikely).
	 * $precision = how many decimal points to display (1.25K)
	 *
	 * @since    0.5
	 */
	public static function sl_format_count( $number ) {
		$precision = 2;
		if ( $number >= 1000 && $number < 1000000 ) {
			$formatted = number_format( $number / 1000, $precision ) . 'K';
		} elseif ( $number >= 1000000 && $number < 1000000000 ) {
			$formatted = number_format( $number / 1000000, $precision ) . 'M';
		} elseif ( $number >= 1000000000 ) {
			$formatted = number_format( $number / 1000000000, $precision ) . 'B';
		} else {
			$formatted = $number; // Number is less than 1000
		}
		$formatted = str_replace( '.00', '', $formatted );
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
			$number = self::sl_format_count( $like_count );
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
