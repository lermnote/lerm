<?php // phpcs:disable Squiz.Classes.ClassFileName, Generic.Files.OneObjectStructurePerFile
/**
 * Lightweight bootstrap for package-local tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

if ( file_exists( dirname( __DIR__ ) . '/vendor/autoload.php' ) ) {
	require_once dirname( __DIR__ ) . '/vendor/autoload.php';
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}

if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', true );
}

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {

		/**
		 * @var array<string, array<int, string>>
		 */
		private array $errors = array();

		/**
		 * @var array<string, mixed>
		 */
		private array $error_data = array();

		/**
		 * @param mixed $data Error data.
		 */
		public function __construct( string $code = '', string $message = '', $data = '' ) {
			if ( '' !== $code ) {
				$this->errors[ $code ] = array( $message );

				if ( '' !== $data ) {
					$this->error_data[ $code ] = $data;
				}
			}
		}

		/**
		 * @return array<string, array<int, string>>
		 */
		public function errors(): array {
			return $this->errors;
		}

		/**
		 * @return array<int, string>
		 */
		public function get_error_messages(): array {
			$messages = array();

			foreach ( $this->errors as $group ) {
				foreach ( $group as $message ) {
					$messages[] = $message;
				}
			}

			return $messages;
		}

		public function get_error_code(): string {
			return (string) array_key_first( $this->errors );
		}

		/**
		 * @return mixed
		 */
		public function get_error_data( string $code = '' ) {
			$code = '' !== $code ? $code : $this->get_error_code();

			return $this->error_data[ $code ] ?? null;
		}
	}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request {

		/**
		 * @var array<string, mixed>
		 */
		private array $params;

		/**
		 * @var array<string, mixed>
		 */
		private array $json_params;

		/**
		 * @param array<string, mixed> $attributes Request attributes.
		 */
		public function __construct( string $method = '', string $route = '', array $attributes = array() ) {
			unset( $method, $route, $attributes );

			$this->params      = array();
			$this->json_params = array();
		}

		/**
		 * @return mixed
		 */
		public function get_param( string $key ) {
			return $this->params[ $key ] ?? $this->json_params[ $key ] ?? null;
		}

		/**
		 * @return array<string, mixed>
		 */
		public function get_params(): array {
			return array_merge( $this->json_params, $this->params );
		}

		/**
		 * @return array<string, mixed>
		 */
		public function get_json_params(): array {
			return $this->json_params;
		}

		/**
		 * @param mixed $value Param value.
		 */
		public function set_param( string $key, $value ): void {
			$this->params[ $key ] = $value;
		}

		/**
		 * @param array<string, mixed> $params JSON body params.
		 */
		public function set_json_params( array $params ): void {
			$this->json_params = $params;
		}
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response {

		/**
		 * @param mixed $data Response data.
		 */
		public function __construct(
			private $data = null,
			private int $status = 200
		) {
		}

		/**
		 * @return mixed
		 */
		public function get_data() {
			return $this->data;
		}

		/**
		 * @param mixed $data Response data.
		 */
		public function set_data( $data ): void {
			$this->data = $data;
		}

		public function get_status(): int {
			return $this->status;
		}

		public function set_status( int $status ): void {
			$this->status = $status;
		}
	}
}

if ( ! class_exists( 'WP_REST_Server' ) ) {
	class WP_REST_Server {
		public const READABLE  = 'GET';
		public const CREATABLE = 'POST';
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ): bool {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'rest_ensure_response' ) ) {
	function rest_ensure_response( $response ): WP_REST_Response {
		if ( $response instanceof WP_REST_Response ) {
			return $response;
		}

		return new WP_REST_Response( $response );
	}
}

if ( ! function_exists( 'register_rest_route' ) ) {
	function register_rest_route( string $rest_namespace, string $route, array $args ): bool {
		$GLOBALS['lerm_admin_config_rest_routes'][] = array(
			'namespace' => $rest_namespace,
			'route'     => $route,
			'args'      => $args,
		);

		return true;
	}
}

if ( ! function_exists( '__' ) ) {
	function __( string $text ): string {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( string $text ): string {
		return $text;
	}
}

if ( ! function_exists( '_doing_it_wrong' ) ) {
	function _doing_it_wrong( string $function_name, string $message, string $version ): void {
		$GLOBALS['lerm_admin_config_doing_it_wrong'][] = array(
			'function' => $function_name,
			'message'  => $message,
			'version'  => $version,
		);
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $value, int $flags = 0, int $depth = 512 ): string|false {
		return json_encode( $value, $flags, $depth );
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( string $option, $fallback = false ) {
		return $GLOBALS['lerm_admin_config_options'][ $option ] ?? $fallback;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( string $option, $value ): bool {
		$previous = $GLOBALS['lerm_admin_config_options'][ $option ] ?? null;

		$GLOBALS['lerm_admin_config_options'][ $option ] = $value;

		return $previous !== $value;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( string $option ): bool {
		if ( ! array_key_exists( $option, $GLOBALS['lerm_admin_config_options'] ?? array() ) ) {
			return false;
		}

		unset( $GLOBALS['lerm_admin_config_options'][ $option ] );

		return true;
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ): string {
		$key = is_scalar( $key ) ? strtolower( (string) $key ) : '';
		return preg_replace( '/[^a-z0-9_\-]/', '', $key ) ?? '';
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $title ): string {
		$title = is_scalar( $title ) ? strtolower( trim( (string) $title ) ) : '';
		$title = preg_replace( '/[^a-z0-9]+/', '-', $title ) ?? '';
		return trim( $title, '-' );
	}
}

if ( ! function_exists( 'absint' ) ) {
	function absint( $value ): int {
		return max( 0, (int) $value );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $text ): string {
		return is_scalar( $text ) ? trim( (string) $text ) : '';
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $text ): string {
		return is_scalar( $text ) ? trim( (string) $text ) : '';
	}
}

if ( ! function_exists( 'sanitize_hex_color' ) ) {
	function sanitize_hex_color( $color ): string {
		$color = is_scalar( $color ) ? trim( (string) $color ) : '';
		if ( '' === $color ) {
			return '';
		}

		if ( ! str_starts_with( $color, '#' ) ) {
			$color = '#' . $color;
		}

		return preg_match( '/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', $color ) ? strtolower( $color ) : '';
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ): string {
		return is_scalar( $url ) ? trim( (string) $url ) : '';
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( string $value ): string {
		return rtrim( $value, '/\\' ) . '/';
	}
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( string $plugin_file ): string {
		return 'https://example.test/plugins/' . basename( dirname( $plugin_file ) ) . '/';
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, array $defaults = array() ): array {
		$parsed = is_array( $args ) ? $args : array();
		return array_merge( $defaults, $parsed );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return $value;
	}
}

if ( ! function_exists( 'check_ajax_referer' ) ) {
	function check_ajax_referer( string $action, string $query_arg = '_ajax_nonce' ): bool {
		$GLOBALS['lerm_admin_config_ajax_nonce_checks'][] = array(
			'action' => $action,
			'arg'    => $query_arg,
		);

		return true;
	}
}

if ( ! function_exists( 'wp_send_json_success' ) ) {
	function wp_send_json_success( $data = null, ?int $status_code = null, int $flags = 0 ): void {
		unset( $flags );

		$GLOBALS['lerm_admin_config_json_response'] = array(
			'success' => true,
			'data'    => $data,
			'status'  => $status_code ?? 200,
		);

		throw new RuntimeException( 'wp_send_json' );
	}
}

if ( ! function_exists( 'wp_send_json_error' ) ) {
	function wp_send_json_error( $data = null, ?int $status_code = null, int $flags = 0 ): void {
		unset( $flags );

		$GLOBALS['lerm_admin_config_json_response'] = array(
			'success' => false,
			'data'    => $data,
			'status'  => $status_code ?? 200,
		);

		throw new RuntimeException( 'wp_send_json' );
	}
}

if ( ! function_exists( 'get_template_directory_uri' ) ) {
	function get_template_directory_uri(): string {
		return 'https://example.test/theme';
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$GLOBALS['lerm_admin_config_actions'][ $hook ][] = array(
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( string $hook, ...$args ): void {
		if ( empty( $GLOBALS['lerm_admin_config_actions'][ $hook ] ) ) {
			return;
		}

		foreach ( $GLOBALS['lerm_admin_config_actions'][ $hook ] as $listener ) {
			call_user_func_array( $listener['callback'], $args );
		}
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( string $capability, ...$args ): bool {
		unset( $args );

		$allowed = $GLOBALS['lerm_admin_config_current_user_can'] ?? true;

		if ( is_array( $allowed ) && array_key_exists( $capability, $allowed ) ) {
			return (bool) $allowed[ $capability ];
		}

		if ( is_bool( $allowed ) ) {
			return $allowed;
		}

		return true;
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin(): bool {
		return (bool) ( $GLOBALS['lerm_admin_config_is_admin'] ?? false );
	}
}

if ( ! function_exists( 'is_multisite' ) ) {
	function is_multisite(): bool {
		return (bool) ( $GLOBALS['lerm_admin_config_is_multisite'] ?? false );
	}
}

spl_autoload_register(
	static function ( string $class_name ): void {
		$prefixes = array(
			'Lerm\\AdminConfig\\Tests\\' => __DIR__ . '/',
			'Lerm\\AdminConfig\\'        => dirname( __DIR__ ) . '/src/',
		);

		foreach ( $prefixes as $prefix => $base_dir ) {
			if ( ! str_starts_with( $class_name, $prefix ) ) {
				continue;
			}

			$relative = str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) );
			$file     = $base_dir . $relative . '.php';

			if ( is_file( $file ) ) {
				require_once $file;
			}

			return;
		}
	}
);
