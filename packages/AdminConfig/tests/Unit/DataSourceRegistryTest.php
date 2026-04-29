<?php
/**
 * Data source registry tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use InvalidArgumentException;
use Lerm\AdminConfig\Registry\DataSourceRegistry;
use Lerm\AdminConfig\Tests\Support\TestCase;

final class DataSourceRegistryTest extends TestCase {

	public function testRegistersAndResolvesNamedSources(): void {
		$registry = new DataSourceRegistry();

		$registry->register(
			'tone_presets',
			static function ( array $args ): array {
				$choices = array(
					'calm'  => 'Calm',
					'bold'  => 'Bold',
					'clean' => 'Clean',
				);

				if ( ! empty( $args['experimental'] ) ) {
					$choices['vivid'] = 'Vivid';
				}

				return $choices;
			}
		);

		$this->assertTrue( $registry->has( 'tone_presets' ) );
		$this->assertSame(
			array(
				'calm'  => 'Calm',
				'bold'  => 'Bold',
				'clean' => 'Clean',
				'vivid' => 'Vivid',
			),
			$registry->resolve( 'tone_presets', array( 'experimental' => true ) )
		);
	}

	public function testThrowsForUnknownSources(): void {
		$registry = new DataSourceRegistry();

		$this->assertThrows(
			InvalidArgumentException::class,
			static function () use ( $registry ): void {
				$registry->get( 'missing_source' );
			}
		);
	}
}
