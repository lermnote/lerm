<?php
/**
 * Tool and utility field definitions.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\FieldTypes;

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
					unset( $value, $field_name, $page );

					self::render_backup_tools_field( $field );
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

	/**
	 * @param array<string, mixed> $field
	 */
	private static function render_backup_tools_field( array $field ): void {
		$export_label = (string) ( $field['export_label'] ?? __( 'Export current settings', 'lerm' ) );
		$import_label = (string) ( $field['import_label'] ?? __( 'Import settings JSON', 'lerm' ) );
		$placeholder  = (string) ( $field['placeholder'] ?? __( '{ "example": "Paste a backup snapshot here" }', 'lerm' ) );

		echo '<div class="lerm-backup-tools">';
		echo '<div class="lerm-backup-tools__block">';
		echo '<div class="lerm-backup-tools__header">';
		echo '<strong>' . esc_html( $export_label ) . '</strong>';
		echo '<button type="button" class="button button-secondary" data-lerm-backup-export>' . esc_html__( 'Generate snapshot', 'lerm' ) . '</button>';
		echo '</div>';
		echo '<textarea class="large-text code lerm-backup-tools__export" rows="10" readonly data-lerm-backup-export-output></textarea>';
		echo '</div>';
		echo '<div class="lerm-backup-tools__block">';
		echo '<div class="lerm-backup-tools__header">';
		echo '<strong>' . esc_html( $import_label ) . '</strong>';
		echo '<button type="button" class="button button-primary" data-lerm-backup-import>' . esc_html__( 'Import snapshot', 'lerm' ) . '</button>';
		echo '</div>';
		echo '<textarea class="large-text code lerm-backup-tools__import" rows="10" data-lerm-backup-import-input placeholder="' . esc_attr( $placeholder ) . '"></textarea>';
		echo '</div>';
		echo '</div>';
	}
}
