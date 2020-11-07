<?php
/**
 * Ajax handle
 *
 * since 3.2
 */
class Lerm_Fetch_Api {
	// Register ajax action
	public function register( $action ) {

		// wp_ajax_{action} for registered user
		add_action( 'wp_ajax_' . $action, array( $this, $action ) );

		// wp_ajax_nopriv_{action} for not registered users
		add_action( 'wp_ajax_nopriv_' . $action, array( $this, $action ) );
	}

	// Check ajax nonce
	public function check( $action, $query_arg, $die ) {

		return check_ajax_referer( $action, $query_arg, $die );

	}

	//Accept ajax submit
	public function lerm_submit_ajax_comment() {
		if ( $this->check( 'ajax_nonce', 'security', false ) ) {
			ob_start();

		}
	}

	// if sucessed  send json data to response
	public function success() {

		wp_send_json_success( ob_get_clean() );

	}

	//if error ,send error message
	public function error( $error ) {

		wp_send_json_error( $error );

	}
}
