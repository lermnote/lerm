<?php
/**
 * Admin page config passed to the AdminConfig JavaScript runtime.
 *
 * i18n strings are no longer passed via wp_localize_script — they are handled
 * client-side via @wordpress/i18n __() calls and loaded by
 * wp_set_script_translations(). See resources/i18n/index.js and
 * OptionsPage::enqueue_assets().
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class I18nStrings {

	/**
	 * Returns runtime config for the admin page JS.
	 *
	 * Only returns non-translatable config data (URLs, nonces, editor settings).
	 * Translatable strings are handled client-side via @wordpress/i18n.
	 *
	 * @param mixed $code_editor_settings Code editor settings returned by wp_enqueue_code_editor().
	 * @return array<string, mixed>
	 */
	public static function for_admin_page( $code_editor_settings ): array {
		return array(
			'restUrl'    => rest_url( 'lerm-admin-config/v1/' ),
			'restNonce'  => wp_create_nonce( 'wp_rest' ),
			'codeEditor' => $code_editor_settings,
		);
	}
}
