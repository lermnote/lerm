<?php // phpcs:disable WordPress.Files.FileName
/**
 * Trait Ajax class
 *
 * @package lerm
 */

namespace Lerm\Inc\Ajax;

abstract class BaseAjax {


	protected const AJAX_ACTION = '';

	/**
	 * 前端发送 nonce 的字段名（默认 security）
	 */
	protected const NONCE_FIELD = 'security';

	protected const PUBLIC = true;

	/**
	 * Constructor.
	 *
	 * @param array $params Optional. Arguments for the class.
	 */
	public function __construct() {
		$action = static::AJAX_ACTION;
		if ( empty( $action ) ) {
			throw new \RuntimeException( sprintf( '%s must define AJAX_ACTION constant.', get_called_class() ) );
		}

		// 注册登录用户钩子
		add_action( "wp_ajax_{$action}", array( static::class, 'ajax_handle' ) );

		// 可选注册未登录钩子
		if ( static::PUBLIC ) {
			add_action( "wp_ajax_nopriv_{$action}", array( static::class, 'ajax_handle' ) );
		}

		// 为前端本地化提供基础信息（子类可以再补充）
		add_filter( 'lerm_l10n_data', array( static::class, 'ajax_l10n_data' ) );
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
	 * @param  array $l10n Existing localization data.
	 * @return array Localized data for AJAX requests.
	 */
	public static function ajax_l10n_data( $l10n ) {
		$action = static::AJAX_ACTION;
		$data   = array(
			// 'rest_url' => rest_url('lerm/v1'),
			// 'ajax_url'      => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'ajax_nonce' ),
			'loggedin' => is_user_logged_in(),
		);
		$data   = wp_parse_args( $data, $l10n );
		return $data;
	}
}
