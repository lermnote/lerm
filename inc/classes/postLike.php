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
	public function post_like() {
		$nonce = sanitize_text_field( $_POST['security'] );

		if ( ! wp_verify_nonce( $nonce, 'ajax_nonce' ) ) {
			wp_send_json_error( esc_html__( 'Invalid nonce', 'lerm' ), 403 );
		}

		$post_id = $_POST['post_ID'] ? absint( $_POST['post_ID'] ) : 0;

		if ( 0 === $post_id ) {
			wp_send_json_error( esc_html__( 'Invalid post ID', 'lerm' ), 400 );
		}

		if ( isset( $_COOKIE[ 'post_like_' . $post_id ] ) ) {
			wp_send_json_error( esc_html__( 'Already liked', 'lerm' ), 400 );
		}

		$like_count = (int) get_post_meta( $post_id, 'lerm_post_like', true );
		$like_count++;
		update_post_meta( $post_id, 'lerm_post_like', $like_count );

		setcookie( 'post_like_' . $post_id, $post_id, time() + 604800, '/', esc_url( $_SERVER['HTTP_HOST'] ), true, true );
		self::$post_id = $post_id;

		wp_send_json_success( $like_count );
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
		//add_meta_box( 'post_like', esc_html__( 'Like', 'lerm' ), self::post_like_callback( self::$post_id ), 'post', 'side' );
	}

	private static function post_like_callback( $post_id ) {
		wp_nonce_field( 'lerm_save_like_data', 'lerm_post_like_meta_box_nonce' );
		$value = get_post_meta( $post_id, 'lerm_post_like', true );
		echo '<label for="lerm_like_field">' . esc_html__( 'Post Like Count', 'lerm' ) . '</label>';
		echo '<input type="text" id="lerm_like_field" name="lerm_like_field" value="' . esc_attr( $value ) . '" size="25" />';
	}
}
