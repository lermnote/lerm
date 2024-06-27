<?php // phpcs:disable WordPress.Files.FileName
/**
 * Trait Ajax class
 *
 * @package lerm
 */

namespace Lerm\Inc\Ajax;

abstract class BaseAjax {


	private const ACTION = '';

	private static $args = array();

	public function __construct( $args = array() ) {
		self::register( self::ACTION, 'ajax_handle', true );

		self::$args = wp_parse_args( $args, self::$args );
		add_filter( 'lerm_l10n_data', array( __CLASS__, 'ajax_l10n_data' ) );
	}
	/**
	 * Register ajax
	 *
	 * @param string $action
	 * @return void
	 */
	public static function register( $action, $callback, $public = true ) {
		// wp_ajax_{action} for registered users, wp_ajax_nopriv_{action} for not registered users
		add_action( 'wp_ajax_' . $action, array( __CLASS__, $callback ), 10, 1 );
		if ( $public ) {
			add_action( 'wp_ajax_nopriv_' . $action, array( __CLASS__, $callback ), 10, 1 );
		}
	}


	/**
	 * Handle ajax request
	 *
	 * @return void
	 */

	public static function ajax_handle() {}

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

			/**
		 * Generate AJAX localization data.
		 *
		 * @param array $l10n Existing localization data.
		 * @return array Localized data for AJAX requests.
		 */
	public static function ajax_l10n_data( $l10n ) {
		global $wp_query;
		$data = array(
			'posts'    => wp_json_encode( $wp_query->query_vars ), // everything about your loop is here.
			'loadmore' => __( 'Load more', 'lerm' ),
			'loading'  => '<i class="fa fa-spinner fa-spin me-1"></i>' . __( 'Loading...', 'lerm' ),
			'noposts'  => __( 'No older posts found', 'lerm' ),
		);
		$data = wp_parse_args( $data, $l10n );
		return $data;
	}
}
