<?php
/**
 * Trait Ajax class
 *
 * @package lerm
 */

namespace Lerm\Inc\Traits;

use Lerm\Inc\Traits\Hooker;

trait Ajax {

	use Hooker;

	/**
	 * Register ajax action
	 *
	 * @param string $action
	 * @return void
	 */
	public function register( $action ) {
		// wp_ajax_{action} for registered users, wp_ajax_nopriv_{action} for not registered users
		add_action( 'wp_ajax_nopriv_' . $action, array( $this, $action ), 10, 1 );
		add_action( 'wp_ajax_' . $action, array( $this, $action ), 10, 1 );
	}

	/**
	 * Verify request nonce
	 *
	 * @param string $action The nonce action name.
	 */
	protected function verify_nonce( $action ) {

		if ( ! isset( $_REQUEST['security'] ) || ! \wp_verify_nonce( $_REQUEST['security'], $action ) ) {
			$this->error( __( 'Error: Invalid request', 'lerm' ) );
		}
	}

	/**
	 * Wrapper function for sending success response
	 *
	 * @param mixed $data Data to send to response.
	 */
	public function success( $data = null ) {
		$this->send_response( $data, true );
	}

	/**
	 * Wrapper function for sending error
	 *
	 * @param mixed $data Data to send to response.
	 */
	protected function error( $data = null ) {
		$this->send_response( $data, false );
	}

	/**
	 * Send AJAX response data.
	 *
	 * @param array   $data
	 * @param boolean $success
	 */
	private function send_response( $data, $success ) {
		$response = array( 'success' => $success );

		if ( is_string( $data ) ) {
			$response['data'] = $data;
		} else {
			$response = array_merge( $response, $data );
		}

		\wp_send_json( $response );
	}
}
