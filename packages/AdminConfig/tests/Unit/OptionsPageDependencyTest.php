<?php
/**
 * Options page dependency rendering tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Framework\Admin\OptionsPage;
use Lerm\AdminConfig\Framework\Contracts\AssetResolver;
use Lerm\AdminConfig\Framework\FieldTypes\BuiltinFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\ExtendedPrimitiveFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\Tests\Support\TestCase;

final class OptionsPageDependencyTest extends TestCase {

	public function testRenderFieldUsesCanonicalDependencyMetadata(): void {
		$definition = $this->dependency_definition();
		$page       = $this->options_page( $definition );
		$fields     = $definition['sections']['general']['fields'];

		$controller = $this->render_field( $page, $fields[0], array( 'feature_enabled' => 0 ) );
		$hidden     = $this->render_field( $page, $fields[1], array( 'feature_enabled' => 0 ) );
		$visible    = $this->render_field( $page, $fields[1], array( 'feature_enabled' => 1 ) );

		$this->assertStringContainsString( 'data-lerm-controller="1"', $controller );
		$this->assertStringContainsString( 'data-dependency-field="feature_enabled"', $hidden );
		$this->assertStringContainsString( 'data-dependency-operator="=="', $hidden );
		$this->assertStringContainsString( 'data-dependency-value="1"', $hidden );
		$this->assertStringContainsString( ' hidden', $hidden );
		$this->assertStringNotContainsString( ' hidden', $visible );
	}

	public function testRenderFieldSerializesArrayDependencyValues(): void {
		$definition = $this->dependency_definition();
		$page       = $this->options_page( $definition );
		$field      = $definition['sections']['general']['fields'][3];
		$output     = $this->render_field(
			$page,
			$field,
			array(
				'channels' => array( 'newsletter' ),
			)
		);

		$this->assertStringContainsString( 'data-dependency-field="channels"', $output );
		$this->assertStringContainsString( 'data-dependency-operator="in"', $output );
		$this->assertStringContainsString( 'data-dependency-value="[&quot;newsletter&quot;,&quot;rss&quot;]"', $output );
		$this->assertStringNotContainsString( ' hidden', $output );
	}

	public function testBuiltInChoiceControlsCanDriveDependencies(): void {
		$definition = $this->choice_controller_definition();
		$page       = $this->options_page( $definition );
		$fields     = $definition['sections']['general']['fields'];
		$values     = array(
			'accent_color' => '#2271b1',
			'channels'     => array( 'newsletter' ),
			'flags'        => array( 'beta' ),
		);

		$this->assertStringContainsString( 'data-lerm-controller="1"', $this->render_field( $page, $fields[0], $values ) );
		$this->assertStringContainsString( 'data-lerm-controller="1"', $this->render_field( $page, $fields[1], $values ) );
		$this->assertStringContainsString( 'data-lerm-controller="1"', $this->render_field( $page, $fields[2], $values ) );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function dependency_definition(): array {
		return array(
			'id'       => 'unit_dependency_render',
			'store'    => array(
				'type' => 'option',
				'key'  => 'unit_dependency_render',
			),
			'sections' => array(
				'general' => array(
					'fields' => array(
						array(
							'id'      => 'feature_enabled',
							'type'    => 'text',
							'label'   => 'Feature enabled',
							'default' => 0,
						),
						array(
							'id'         => 'accent_color',
							'type'       => 'text',
							'label'      => 'Accent color',
							'default'    => '#2271b1',
							'dependency' => array( 'feature_enabled', '==', true ),
						),
						array(
							'id'      => 'channels',
							'type'    => 'text',
							'label'   => 'Channels',
							'default' => array(),
						),
						array(
							'id'         => 'newsletter_teaser',
							'type'       => 'text',
							'label'      => 'Newsletter teaser',
							'default'    => '',
							'dependency' => array( 'channels', 'in', array( 'newsletter', 'rss' ) ),
						),
					),
				),
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function choice_controller_definition(): array {
		return array(
			'id'       => 'unit_choice_dependency_render',
			'store'    => array(
				'type' => 'option',
				'key'  => 'unit_choice_dependency_render',
			),
			'sections' => array(
				'general' => array(
					'fields' => array(
						array(
							'id'      => 'accent_color',
							'type'    => 'color',
							'label'   => 'Accent color',
							'default' => '#2271b1',
						),
						array(
							'id'      => 'channels',
							'type'    => 'checkbox_list',
							'label'   => 'Channels',
							'choices' => array(
								'newsletter' => 'Newsletter',
								'rss'        => 'RSS',
							),
							'default' => array(),
						),
						array(
							'id'      => 'flags',
							'type'    => 'checkbox',
							'label'   => 'Flags',
							'choices' => array(
								'beta'  => 'Beta',
								'quiet' => 'Quiet',
							),
							'default' => array(),
						),
						array(
							'id'         => 'color_dependent',
							'type'       => 'text',
							'label'      => 'Color dependent',
							'default'    => '',
							'dependency' => array( 'accent_color', '==', '#2271b1' ),
						),
						array(
							'id'         => 'channel_dependent',
							'type'       => 'text',
							'label'      => 'Channel dependent',
							'default'    => '',
							'dependency' => array( 'channels', 'in', array( 'newsletter' ) ),
						),
						array(
							'id'         => 'flag_dependent',
							'type'       => 'text',
							'label'      => 'Flag dependent',
							'default'    => '',
							'dependency' => array( 'flags', 'in', array( 'beta' ) ),
						),
					),
				),
			),
		);
	}

	/**
	 * @param array<string, mixed> $definition
	 */
	private function options_page( array $definition ): OptionsPage {
		$field_types = new FieldTypeRegistry();
		foreach ( array_merge( BuiltinFieldTypes::definitions(), ExtendedPrimitiveFieldTypes::definitions() ) as $type => $field_type_definition ) {
			$field_types->register( (string) $type, $field_type_definition );
		}

		$store    = new OptionStore( $definition, $field_types );
		$resolver = new class() implements AssetResolver {
			public function url( string $filename ): string {
				return 'https://example.test/assets/' . ltrim( $filename, '/' );
			}

			public function version(): string {
				return 'unit-version';
			}
		};

		return new OptionsPage( $definition, $store, $field_types, $resolver, false );
	}

	/**
	 * @param array<string, mixed> $field
	 * @param array<string, mixed> $values
	 */
	private function render_field( OptionsPage $page, array $field, array $values ): string {
		ob_start();

		try {
			$page->render_field( $field, $values, 'general' );

			return (string) ob_get_clean();
		} catch ( \Throwable $throwable ) {
			ob_end_clean();
			throw $throwable;
		}
	}
}
