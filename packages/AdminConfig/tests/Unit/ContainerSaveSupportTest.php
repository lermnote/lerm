<?php
/**
 * Shared native container save helper tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Framework\FieldTypes\BuiltinFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\Tests\Support\TestCase;
use Lerm\AdminConfig\WordPress\Support\ContainerSaveSupport;
use Lerm\AdminConfig\WordPress\Support\ValidationFlash;

final class ContainerSaveSupportTest extends TestCase {

	public function testSubmittedValuesReadPostedPayloadByStorageKey(): void {
		$store = new OptionStore(
			array(
				'id'    => 'container_save_submitted_values',
				'store' => array(
					'type' => 'option',
					'key'  => 'container_save_submitted_values',
				),
			),
			$this->field_types()
		);

		$_POST[ $store->storage_key() ] = array(
			'title' => 'Profile card',
		);

		$this->assertSame(
			array(
				'title' => 'Profile card',
			),
			ContainerSaveSupport::submitted_values( $store )
		);
	}

	public function testPersistStoresValidationFlashForInvalidSubmission(): void {
		$field_types = $this->field_types();
		$field_types->register_validator(
			'text',
			static function ( array $field, $value ) {
				unset( $field );

				if ( '' === (string) $value ) {
					return new \WP_Error( 'required', 'Value is required.' );
				}

				return $value;
			}
		);

		$store     = new OptionStore(
			array(
				'id'       => 'container_save_validation',
				'sections' => array(
					'general' => array(
						'fields' => array(
							array(
								'id'   => 'headline',
								'type' => 'text',
							),
						),
					),
				),
			),
			$field_types
		);
		$submitted = array(
			'headline' => '',
		);

		ContainerSaveSupport::persist(
			'profile',
			'container_save_validation',
			'42',
			$store,
			$submitted,
			static fn ( OptionStore $resolved_store, array $payload ): bool => $resolved_store->save_all( $payload ),
			'Validation failed.',
			'Save failed.'
		);

		$this->assertSame(
			array(
				'class'     => 'notice-error',
				'message'   => 'Validation failed.',
				'errors'    => array(
					'headline' => array( 'Value is required.' ),
				),
				'submitted' => $submitted,
			),
			ValidationFlash::consume( 'profile', 'container_save_validation', '42' )
		);
	}

	public function testPersistClearsExistingFlashAfterSuccessfulSave(): void {
		$store = new OptionStore(
			array(
				'id'       => 'container_save_success',
				'sections' => array(
					'general' => array(
						'fields' => array(
							array(
								'id'   => 'headline',
								'type' => 'text',
							),
						),
					),
				),
			),
			$this->field_types()
		);

		ValidationFlash::store(
			'comment',
			'container_save_success',
			'18',
			array(
				'class'   => 'notice-error',
				'message' => 'Old message.',
			)
		);

		ContainerSaveSupport::persist(
			'comment',
			'container_save_success',
			'18',
			$store,
			array(
				'headline' => 'Updated',
			),
			static fn ( OptionStore $resolved_store, array $payload ): bool => $resolved_store->save_all( $payload ),
			'Validation failed.',
			'Save failed.'
		);

		$this->assertNull( ValidationFlash::consume( 'comment', 'container_save_success', '18' ) );
	}

	private function field_types(): FieldTypeRegistry {
		$field_types = new FieldTypeRegistry();

		foreach ( BuiltinFieldTypes::definitions() as $type => $definition ) {
			$field_types->register( (string) $type, $definition );
		}

		return $field_types;
	}
}
