<?php // phpcs:disable WordPress.Files.FileName
/**
 * Trait Ajax class
 *
 * @package lerm
 */

namespace Lerm\Inc\Ajax;

class Ajax {

	/**
	 * Register ajax
	 *
	 * @param string $action
	 * @return void
	 */
	public static function register( $action, $public = true ) {
		// wp_ajax_{action} for registered users, wp_ajax_nopriv_{action} for not registered users
		add_action( 'wp_ajax_' . $action, array( __CLASS__, $action ), 10, 1 );
		if ( $public ) {
			add_action( 'wp_ajax_nopriv_' . $action, array( __CLASS__, $action ), 10, 1 );
		}
	}

	/**
	 * Verify request nonce
	 *
	 * @param string $action The nonce action name.
	 */
	public static function verify_nonce( $nonce_field, $nonce_action ) {
		if ( ! isset( $_POST[ $nonce_field ] ) || ! wp_verify_nonce( $_POST[ $nonce_field ], $nonce_action ) ) {
			self::error( 'Invalid nonce' );
		}
	}

	/**
	 * Wrapper function for sending success response
	 *
	 * @param mixed $data Data to send to response.
	 */
	public static function success( $data = null ) {
		wp_send_json_success( $data );
	}

	/**
	 * Wrapper function for sending error
	 *
	 * @param mixed $data Data to send to response.
	 */
	protected static function error( $data = null ) {
		wp_send_json_error( $data );
	}
}
