<?php
/**
 * Block editor field matrix tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Framework\FieldTypes\AdvancedFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\AsyncFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\BuiltinFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\DesignFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\ExtendedPrimitiveFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\StructuredFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\ToolFieldTypes;
use Lerm\AdminConfig\Tests\Support\TestCase;

final class BlockEditorFieldMatrixTest extends TestCase {

	public function testFieldMatrixClassifiesEveryBuiltInFieldType(): void {
		$matrix     = self::field_matrix();
		$classified = array_values(
			array_unique(
				array_merge(
					$matrix['editable'],
					$matrix['read_only'],
					$matrix['phase_4']
				)
			)
		);
		$missing    = array_values( array_diff( self::built_in_field_types(), $classified ) );

		sort( $missing );

		$this->assertSame( array(), $missing );
	}

	public function testFieldMatrixStatusesDoNotOverlap(): void {
		$matrix = self::field_matrix();

		$this->assertSame( array(), array_values( array_intersect( $matrix['editable'], $matrix['read_only'] ) ) );
		$this->assertSame( array(), array_values( array_intersect( $matrix['editable'], $matrix['phase_4'] ) ) );
		$this->assertSame( array(), array_values( array_intersect( $matrix['read_only'], $matrix['phase_4'] ) ) );
	}

	/**
	 * @return array<int, string>
	 */
	private static function built_in_field_types(): array {
		$definitions = array_merge(
			BuiltinFieldTypes::definitions(),
			ExtendedPrimitiveFieldTypes::definitions(),
			StructuredFieldTypes::definitions(),
			AdvancedFieldTypes::definitions(),
			DesignFieldTypes::definitions(),
			AsyncFieldTypes::definitions(),
			ToolFieldTypes::definitions()
		);
		$types       = array_keys( $definitions );

		sort( $types );

		return $types;
	}

	/**
	 * @return array{editable: array<int, string>, read_only: array<int, string>, phase_4: array<int, string>}
	 */
	private static function field_matrix(): array {
		return array(
			'editable'  => self::field_matrix_section( 'Editable' ),
			'read_only' => self::field_matrix_section( 'Read-Only' ),
			'phase_4'   => self::field_matrix_section( 'Phase 4' ),
		);
	}

	/**
	 * @return array<int, string>
	 */
	private static function field_matrix_section( string $section ): array {
		$path    = dirname( __DIR__, 2 ) . '/docs/block-editor-field-matrix.md';
		$content = file_get_contents( $path );

		$this_section = preg_quote( $section, '/' );

		if ( ! is_string( $content ) || ! preg_match( '/^## ' . $this_section . '\R(?P<body>.*?)(?=^## |\z)/ms', $content, $match ) ) {
			self::fail( sprintf( 'Missing block editor field matrix section: %s.', $section ) );
		}

		$types = array();

		$lines = preg_split( '/\R/', $match['body'] );

		foreach ( false !== $lines ? $lines : array() as $line ) {
			$line = trim( $line );

			if ( ! str_starts_with( $line, '| `' ) ) {
				continue;
			}

			$cells = explode( '|', $line );

			preg_match_all( '/`([a-z0-9_]+)`/', $cells[1] ?? '', $matches );

			$types = array_merge( $types, $matches[1] );
		}

		$types = array_values( array_unique( $types ) );
		sort( $types );

		return $types;
	}
}
