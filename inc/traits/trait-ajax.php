<?php
/**
 * Trait Ajax class
 *
 * @package lerm
 */

namespace Lerm\Inc\Traits;

use  Lerm\Inc\Traits\Hooker;

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
			$this->error( __( 'Error: Nonce verification failed', 'lerm' ) );
		}
	}

	/**
	 * Wrapper function for sending success response
	 *
	 * @param mixed $data Data to send to response.
	 */
	public function success( $data = null ) {
		$this->send( $data );
	}

	/**
	 * Wrapper function for sending error
	 *
	 * @param mixed $data Data to send to response.
	 */
	protected function error( $data = null ) {
		$this->send( $data, false );
	}

	/**
	 * Send AJAX response data.
	 *
	 * @param array   $data
	 * @param boolean $success
	 */
	private function send( $data, $success = true ) {

		if ( is_string( $data ) ) {
			$data = $success ? array( 'data' => $data ) : array( 'error' => $data );
		}
		$data['success'] = isset( $data['success'] ) ? $data['success'] : $success;
		\wp_send_json( $data );
	}
}
