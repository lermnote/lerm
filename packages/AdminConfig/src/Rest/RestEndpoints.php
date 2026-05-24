<?php
/**
 * Register AdminConfig REST endpoints.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Rest;

use Lerm\AdminConfig\Rest\Controllers\SchemaController;
use Lerm\AdminConfig\Rest\Support\ResponseFactory;
use Lerm\AdminConfig\WordPress\Runtime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RestEndpoints {

	private const NAMESPACE = 'lerm-admin-config/v1';

	/**
	 * REST routes are process-global, while package integrations can own
	 * isolated runtimes. Keep weak runtime references so long-lived test and
	 * worker processes do not retain stale runtime instances between requests.
	 *
	 * @var array<int, \WeakReference>
	 */
	private static array $runtimes = array();

	public function __construct(
		private Runtime $runtime
	) {
	}

	public function register(): void {
		self::$runtimes[ spl_object_id( $this->runtime ) ] = \WeakReference::create( $this->runtime );

		add_action( 'rest_api_init', array( self::class, 'register_routes' ) );
	}

	public static function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/schemas',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'schemas' ),
				'permission_callback' => array( self::class, 'can_list_schemas' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/schemas/(?P<id>[a-z0-9_-]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'schema_document' ),
				'permission_callback' => array( self::class, 'can_access_schema' ),
				'args'                => self::schema_args(),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/schemas/(?P<id>[a-z0-9_-]+)/values',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( self::class, 'values' ),
					'permission_callback' => array( self::class, 'can_access_schema' ),
					'args'                => self::schema_args(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( self::class, 'save' ),
					'permission_callback' => array( self::class, 'can_access_schema' ),
					'args'                => self::schema_args(),
				),
			)
		);

		foreach ( self::canonical_mutation_routes() as $route => $callback ) {
			register_rest_route(
				self::NAMESPACE,
				'/schemas/(?P<id>[a-z0-9_-]+)/' . $route,
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( self::class, $callback ),
					'permission_callback' => array( self::class, 'can_access_schema' ),
					'args'                => self::schema_args(),
				)
			);
		}

		register_rest_route(
			self::NAMESPACE,
			'/schemas/(?P<id>[a-z0-9_-]+)/export',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'export' ),
				'permission_callback' => array( self::class, 'can_access_schema' ),
				'args'                => self::schema_args(),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/schemas/(?P<id>[a-z0-9_-]+)/data-source',
			self::data_source_route_args()
		);
	}

	public static function schemas( \WP_REST_Request $request ): \WP_REST_Response {
		$schemas = array();

		foreach ( array_reverse( self::live_runtimes(), true ) as $runtime ) {
			foreach ( ( new SchemaController( $runtime ) )->schema_summaries( $request ) as $summary ) {
				$schema_id = sanitize_key( (string) ( $summary['id'] ?? '' ) );

				if ( '' !== $schema_id && ! isset( $schemas[ $schema_id ] ) ) {
					$schemas[ $schema_id ] = $summary;
				}
			}
		}

		return ResponseFactory::success(
			array(
				'schemas' => array_values( $schemas ),
			)
		);
	}

	public static function schema_document( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		return self::dispatch( $request, 'schema_document' );
	}

	public static function values( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		return self::dispatch( $request, 'values' );
	}

	public static function save( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		return self::dispatch( $request, 'save' );
	}

	public static function reset( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		return self::dispatch( $request, 'reset' );
	}

	public static function import( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		return self::dispatch( $request, 'import' );
	}

	public static function export( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		return self::dispatch( $request, 'export' );
	}

	public static function data_source( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		return self::dispatch( $request, 'data_source' );
	}

	public static function can_access_schema( \WP_REST_Request $request ): bool|\WP_Error {
		$runtime = self::runtime_for_request( $request );

		if ( null === $runtime ) {
			return self::schema_not_found_error();
		}

		return ( new SchemaController( $runtime ) )->can_access_schema( $request );
	}

	public static function can_list_schemas( \WP_REST_Request $request ): bool {
		unset( $request );

		return current_user_can( 'manage_options' );
	}

	/**
	 * @return array<string, string>
	 */
	private static function canonical_mutation_routes(): array {
		return array(
			'reset'  => 'reset',
			'import' => 'import',
		);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function data_source_route_args(): array {
		return array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'data_source' ),
				'permission_callback' => array( self::class, 'can_access_schema' ),
				'args'                => self::schema_args(),
			),
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'data_source' ),
				'permission_callback' => array( self::class, 'can_access_schema' ),
				'args'                => self::schema_args(),
			),
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private static function schema_args(): array {
		return array(
			'id' => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_key',
			),
		);
	}

	private static function dispatch( \WP_REST_Request $request, string $method ): \WP_REST_Response|\WP_Error {
		$runtime = self::runtime_for_request( $request );

		if ( null === $runtime ) {
			return self::schema_not_found_error();
		}

		return ( new SchemaController( $runtime ) )->{$method}( $request );
	}

	private static function runtime_for_request( \WP_REST_Request $request ): ?Runtime {
		return self::runtime_for_schema( (string) $request->get_param( 'id' ) );
	}

	public static function runtime_for_schema( string $schema_id ): ?Runtime {
		$schema_id = sanitize_key( $schema_id );

		if ( '' === $schema_id ) {
			return null;
		}

		foreach ( array_reverse( self::$runtimes, true ) as $runtime_id => $runtime_ref ) {
			$runtime = $runtime_ref->get();

			if ( null === $runtime ) {
				unset( self::$runtimes[ $runtime_id ] );
				continue;
			}

			if ( $runtime->has( $schema_id ) ) {
				return $runtime;
			}
		}

		return null;
	}

	/**
	 * @return array<int, Runtime>
	 */
	private static function live_runtimes(): array {
		$runtimes = array();

		foreach ( self::$runtimes as $runtime_id => $runtime_ref ) {
			$runtime = $runtime_ref->get();

			if ( null === $runtime ) {
				unset( self::$runtimes[ $runtime_id ] );
				continue;
			}

			$runtimes[] = $runtime;
		}

		return $runtimes;
	}

	private static function schema_not_found_error(): \WP_Error {
		return ResponseFactory::error(
			'schema_not_found',
			esc_html__( 'The requested schema was not found.', 'lerm' ),
			404
		);
	}
}
