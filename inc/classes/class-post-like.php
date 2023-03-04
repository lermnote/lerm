<?php
/**
 * Post like button ajax handler functions.
 *
 * @author Lerm https://www.hanost.com
 * @since  Lerm 2.0
 */
namespace Lerm\Inc;

use Lerm\Inc\Traits\Ajax;
class Post_Like {
	use Ajax;

	private static $args;

	public function __construct( $params = array() ) {
		$this->register( 'post_like' );
		add_filter( 'manage_post_posts_columns', array( __NAMESPACE__ . '\Post_Like', 'set_post_columns' ) );
		add_action( 'manage_post_posts_custom_column', array( __NAMESPACE__ . '\Post_Like', 'post_custom_column' ), 10, 2 );
		add_action( 'add_meta_boxes', array( __NAMESPACE__ . '\Post_Like', 'post_like_meta_box' ) );
		self::$args = wp_parse_args( $params, self::$args );
	}

	public static function instance( $params = array() ) {
		return new self( $params );
	}

	public function post_like() {
		$nonce = sanitize_text_field( $_POST['security'] );
		if ( ! wp_verify_nonce( $nonce, 'ajax_nonce' ) ) {
			$this->error( __( 'Invalid nonce', 'lerm' ) );
		}

		$id = absint( $_POST['post_ID'] );

		if ( isset( $_COOKIE[ 'post_like_' . $id ] ) ) {
			$this->error( __( 'Already liked', 'lerm' ) );
		}

		$like_count = (int) get_post_meta( $id, 'lerm_post_like', true );
		$like_count++;
		update_post_meta( $id, 'lerm_post_like', $like_count );

		setcookie( 'post_like_' . $id, $id, time() + 604800, '/', ( 'localhost' !== $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : false ), true, true );

		$response = array(
			'success' => true,
			'data'    => $like_count,
		);
		$this->success( $response );
	}

	public static function set_post_columns( $columns ) {
		$columns['like'] = esc_html__( 'Like', 'lerm' );
		return $columns;
	}

	public static function post_custom_column( $column, $post_id ) {
		switch ( $column ) {
			case 'like':
				$like_count = get_post_meta( $post_id, 'lerm_post_like', true );
				echo esc_html( $like_count );
				break;
		}
	}

	public static function post_like_meta_box() {
		add_meta_box( 'post_like', esc_html__( 'Like', 'lerm' ), self::post_like_callback, 'post', 'side' );
	}

	private static function post_like_callback( $post ) {
		wp_nonce_field( 'lerm_save_like_data', 'lerm_post_like_meta_box_nonce' );
		$value = get_post_meta( $post->ID, 'lerm_post_like', true );
		echo '<label for="lerm_like_field">' . esc_html__( 'Post Like Count', 'lerm' ) . '</label>';
		echo '<input type="text" id="lerm_like_field" name="lerm_like_field" value="' . esc_attr( $value ) . '" size="25" />';
	}
}
