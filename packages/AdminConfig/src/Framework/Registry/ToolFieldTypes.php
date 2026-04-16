<?php
/**
 * Tool and utility field definitions.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Registry;

use Lerm\AdminConfig\Framework\Admin\OptionsPage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ToolFieldTypes {

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions(): array {
		return array(
			'backup_tools' => array(
				'render'   => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
					$page->render_backup_tools_field( $field );
				},
				'sanitize' => static function () {
					return '';
				},
				'persist'  => false,
				'client'   => array(
					'control' => 'backup_tools',
				),
			),
		);
	}
}
