<?php
/**
 * Base class for real-WordPress integration tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Integration;

use Lerm\AdminConfig\Framework\Framework;
use Lerm\AdminConfig\WordPress\Runtime;
use PHPUnit\Framework\TestCase;

abstract class WpIntegrationTestCase extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		Runtime::reset_instance();
		Framework::reset_instance();

		$this->set_admin_context();
	}

	protected function tearDown(): void {
		Runtime::reset_instance();
		Framework::reset_instance();

		parent::tearDown();
	}

	protected function set_admin_context( string $screen = 'dashboard' ): void {
		if ( function_exists( 'set_current_screen' ) ) {
			set_current_screen( $screen );
		}

		$admin_user = get_user_by( 'login', 'admin' );

		if ( $admin_user instanceof \WP_User ) {
			wp_set_current_user( (int) $admin_user->ID );
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	protected static function make_store_schema( string $schema_id, string $store_type, string $store_key ): array {
		return array(
			'id'        => $schema_id,
			'title'     => 'Integration Schema',
			'container' => array(
				'type' => 'options_page',
			),
			'store'     => array(
				'type' => $store_type,
				'key'  => $store_key,
			),
			'sections'  => array(
				'general' => array(
					'title'  => 'General',
					'fields' => array(
						array(
							'id'      => 'note',
							'type'    => 'text',
							'label'   => 'Note',
							'default' => '',
						),
					),
				),
			),
		);
	}
}
