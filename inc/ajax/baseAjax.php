<?php // phpcs:disable WordPress.Files.FileName
/**
 * Trait Ajax class
 *
 * @package lerm
 */

namespace Lerm\Inc\Ajax;

abstract class BaseAjax {

	protected const AJAX_ACTION = '';
	protected const PUBLIC      = true;
	/**
	 * Constructor.
	 *
	 * @param array $params Optional. Arguments for the class.
	 */
	public function __construct( $params ) {
		if ( static::AJAX_ACTION ) {
			self::register( static::AJAX_ACTION, 'ajax_handle', static::PUBLIC );
		}
		add_filter( 'lerm_l10n_data', array( static::class, 'ajax_l10n_data' ) );
	}
	/**
	 * Register AJAX handlers.
	 *
	 * @param string $action The action name.
	 * @param string $callback The callback method.
	 * @param bool $public Whether the action is public.
	 */
	protected static function register( $action, $callback, $public = true ) {
		add_action( 'wp_ajax_' . $action, array( static::class, $callback ), 10, 1 );
		if ( $public ) {
			add_action( 'wp_ajax_nopriv_' . $action, array( static::class, $callback ), 10, 1 );
		}
	}

	/**
	 * AJAX handler for processing the action.
	 */
	abstract public static function ajax_handle();

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
		$data = array(
			'url'   => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'ajax_nonce' ),
		);
		$data = wp_parse_args( $data, $l10n );
		return $data;
	}
}
