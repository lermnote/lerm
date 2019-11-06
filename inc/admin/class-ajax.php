<?php
/**
 * Ajax handle
 *
 * since 3.2
 */
class LermFetchApi {
	// Register ajax action
	public function register( $action ) {

		// wp_ajax_{action} for registered user
		add_action( 'wp_ajax_' . $action, array( $this, 'lerm_submit_ajax_comment' ) );

		// wp_ajax_nopriv_{action} for not registered users
		add_action( 'wp_ajax_nopriv_' . $action, array( $this, 'lerm_submit_ajax_comment' ) );
	}

	// Check ajax nonce
	public function check( $action, $query_arg, $die ) {

		return check_ajax_referer( $action, $query_arg, $die );

	}
	//Accept ajax submit
	public function lerm_submit_ajax_comment() {

		$this->check( 'ajax_nonce', 'security', '' );

	}
}
