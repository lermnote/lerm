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
		$this->actions( [ 'wp_ajax_' . $action, 'wp_ajax_nopriv_' . $action ], $action );
	}

	/**
	 * Verify request nonce
	 *
	 * @param string $action The nonce action name.
	 */
	protected function verify_nonce( $action ) {

		if ( ! wp_verify_nonce( $_REQUEST['security'], $action ) ) {
			$this->error( __( 'Error: Nonce verification failed', 'rank-math' ) );
		}
	}

	/**
	 * Wrapper function for sending success response
	 *
	 * @param mixed $data Data to send to response.
	 */
	protected function success( $data = null ) {
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
	 * @param array $data
	 * @param boolean $success
	 */
	private function send( $data, $success = true ) {

		if ( is_string( $data ) ) {
			$data = $success ? [ 'message' => $data ] : [ 'error' => $data ];
		}
		$data['success'] = isset( $data['success'] ) ? $data['success'] : $success;
	}
}
