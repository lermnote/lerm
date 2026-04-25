<?php
/**
 * Register AdminConfig REST endpoints.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Rest;

use Lerm\AdminConfig\Rest\Controllers\SchemaController;
use Lerm\AdminConfig\WordPress\Runtime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RestEndpoints {

	private const NAMESPACE = 'lerm-admin-config/v1';

	public function __construct(
		private Runtime $runtime
	) {
	}

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		$controller = new SchemaController( $this->runtime );

		register_rest_route(
			self::NAMESPACE,
			'/schema/(?P<id>[a-z0-9_-]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $controller, 'schema' ),
				'permission_callback' => array( $controller, 'can_access_schema' ),
				'args'                => $this->schema_args(),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/schema/(?P<id>[a-z0-9_-]+)/values',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $controller, 'values' ),
				'permission_callback' => array( $controller, 'can_access_schema' ),
				'args'                => $this->schema_args(),
			)
		);

		foreach ( $this->mutation_routes() as $route => $callback ) {
			register_rest_route(
				self::NAMESPACE,
				'/schema/(?P<id>[a-z0-9_-]+)/' . $route,
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $controller, $callback ),
					'permission_callback' => array( $controller, 'can_access_schema' ),
					'args'                => $this->schema_args(),
				)
			);
		}

		register_rest_route(
			self::NAMESPACE,
			'/schema/(?P<id>[a-z0-9_-]+)/export',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $controller, 'export' ),
				'permission_callback' => array( $controller, 'can_access_schema' ),
				'args'                => $this->schema_args(),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/schema/(?P<id>[a-z0-9_-]+)/data-source',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $controller, 'data_source' ),
					'permission_callback' => array( $controller, 'can_access_schema' ),
					'args'                => $this->schema_args(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $controller, 'data_source' ),
					'permission_callback' => array( $controller, 'can_access_schema' ),
					'args'                => $this->schema_args(),
				),
			)
		);
	}

	/**
	 * @return array<string, string>
	 */
	private function mutation_routes(): array {
		return array(
			'save'   => 'save',
			'reset'  => 'reset',
			'import' => 'import',
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function schema_args(): array {
		return array(
			'id' => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_key',
			),
		);
	}
}
