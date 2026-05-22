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

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ): string {
		return htmlspecialchars( is_scalar( $text ) ? (string) $text : '', ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr__' ) ) {
	function esc_attr__( string $text ): string {
		return esc_attr( $text );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ): string {
		return htmlspecialchars( is_scalar( $text ) ? (string) $text : '', ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( string $text ): string {
		return $text;
	}
}

if ( ! function_exists( 'wp_kses' ) ) {
	function wp_kses( string $content, array $allowed_html ): string {
		unset( $allowed_html );

		return $content;
	}
}

if ( ! function_exists( 'esc_textarea' ) ) {
	function esc_textarea( $text ): string {
		return htmlspecialchars( is_scalar( $text ) ? (string) $text : '', ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'checked' ) ) {
	function checked( $checked, $current = true, bool $display = true ): string {
		$result = (string) $checked === (string) $current ? ' checked="checked"' : '';

		if ( $display ) {
			echo $result;
		}

		return $result;
	}
}

if ( ! function_exists( 'selected' ) ) {
	function selected( $selected, $current = true, bool $display = true ): string {
		$result = (string) $selected === (string) $current ? ' selected="selected"' : '';

		if ( $display ) {
			echo $result;
		}

		return $result;
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

if ( ! function_exists( '_deprecated_function' ) ) {
	function _deprecated_function( string $function_name, string $version, ?string $replacement = null ): void {
		$GLOBALS['lerm_admin_config_deprecated'][] = array(
			'function'    => $function_name,
			'version'     => $version,
			'replacement' => $replacement,
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

if ( ! function_exists( 'sanitize_html_class' ) ) {
	function sanitize_html_class( $classname, string $fallback = '' ): string {
		$classname = is_scalar( $classname ) ? (string) $classname : '';
		$sanitized = preg_replace( '/[^A-Za-z0-9_\-]/', '', $classname ) ?? '';

		return '' !== $sanitized ? $sanitized : $fallback;
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

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ): string {
		return esc_attr( is_scalar( $url ) ? trim( (string) $url ) : '' );
	}
}

if ( ! function_exists( 'wp_get_attachment_image_url' ) ) {
	function wp_get_attachment_image_url( int $attachment_id, string $size = 'thumbnail' ) {
		if ( $attachment_id <= 0 ) {
			return false;
		}

		return 'https://example.test/uploads/' . $size . '/' . (string) $attachment_id . '.jpg';
	}
}

if ( ! function_exists( 'wp_get_attachment_url' ) ) {
	function wp_get_attachment_url( int $attachment_id ) {
		if ( $attachment_id <= 0 ) {
			return false;
		}

		return 'https://example.test/uploads/full/' . (string) $attachment_id . '.jpg';
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $content ): string {
		return is_scalar( $content ) ? (string) $content : '';
	}
}

if ( ! function_exists( 'wp_editor' ) ) {
	function wp_editor( string $content, string $editor_id, array $settings = array() ): void {
		$textarea_name = is_scalar( $settings['textarea_name'] ?? null ) ? (string) $settings['textarea_name'] : $editor_id;
		$rows          = is_scalar( $settings['textarea_rows'] ?? null ) ? (string) $settings['textarea_rows'] : '6';

		printf(
			'<textarea id="%1$s" name="%2$s" rows="%3$s">%4$s</textarea>',
			esc_attr( $editor_id ),
			esc_attr( $textarea_name ),
			esc_attr( $rows ),
			esc_textarea( $content )
		);
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

if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( string $path = '' ): string {
		return 'https://example.test/wp-admin/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'network_admin_url' ) ) {
	function network_admin_url( string $path = '' ): string {
		return 'https://example.test/wp-admin/network/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'rest_url' ) ) {
	function rest_url( string $path = '' ): string {
		return 'https://example.test/wp-json/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( string $action ): string {
		return 'nonce-' . $action;
	}
}

if ( ! function_exists( 'wp_enqueue_code_editor' ) ) {
	function wp_enqueue_code_editor( array $args ) {
		unset( $args );

		return array( 'codemirror' => true );
	}
}

if ( ! function_exists( 'wp_enqueue_media' ) ) {
	function wp_enqueue_media(): void {
		$GLOBALS['lerm_admin_config_media_enqueued'] = true;
	}
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style( string $handle, string $src = '', array $deps = array(), $ver = false ): void {
		$GLOBALS['lerm_admin_config_enqueued_styles'][ $handle ] = array(
			'src'          => $src,
			'dependencies' => $deps,
			'version'      => $ver,
		);
	}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script( string $handle, string $src = '', array $deps = array(), $ver = false, $args = false ): void {
		$GLOBALS['lerm_admin_config_enqueued_scripts'][ $handle ] = array(
			'src'          => $src,
			'dependencies' => $deps,
			'version'      => $ver,
			'args'         => $args,
		);
	}
}

if ( ! function_exists( 'wp_add_inline_script' ) ) {
	function wp_add_inline_script( string $handle, string $data, string $position = 'after' ): bool {
		$GLOBALS['lerm_admin_config_inline_scripts'][ $handle ][] = array(
			'data'     => $data,
			'position' => $position,
		);

		return true;
	}
}

if ( ! function_exists( 'wp_localize_script' ) ) {
	function wp_localize_script( string $handle, string $object_name, array $l10n ): bool {
		$GLOBALS['lerm_admin_config_localized_scripts'][ $handle ][ $object_name ] = $l10n;

		return true;
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

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( string $transient, $value, int $expiration ): bool {
		unset( $expiration );

		$GLOBALS['lerm_admin_config_transients'][ $transient ] = $value;
		return true;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( string $transient ) {
		return $GLOBALS['lerm_admin_config_transients'][ $transient ] ?? false;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( string $transient ): bool {
		unset( $GLOBALS['lerm_admin_config_transients'][ $transient ] );
		return true;
	}
}

if ( ! function_exists( 'check_ajax_referer' ) ) {
	function check_ajax_referer( string $action, string $query_arg = '_ajax_nonce' ): bool {
		unset( $action, $query_arg );

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

if ( ! function_exists( 'get_current_screen' ) ) {
	function get_current_screen() {
		return $GLOBALS['lerm_admin_config_current_screen'] ?? null;
	}
}

if ( ! function_exists( 'get_post_type' ) ) {
	function get_post_type( int $post_id ) {
		unset( $post_id );

		return $GLOBALS['lerm_admin_config_current_post_type'] ?? '';
	}
}

if ( ! function_exists( 'use_block_editor_for_post_type' ) ) {
	function use_block_editor_for_post_type( string $post_type ): bool {
		$setting = $GLOBALS['lerm_admin_config_use_block_editor_for_post_type'] ?? false;

		if ( is_array( $setting ) && array_key_exists( $post_type, $setting ) ) {
			return (bool) $setting[ $post_type ];
		}

		return is_bool( $setting ) ? $setting : false;
	}
}

if ( ! function_exists( 'use_block_editor_for_post' ) ) {
	function use_block_editor_for_post( $post ): bool {
		$post_type = is_object( $post ) && isset( $post->post_type ) && is_scalar( $post->post_type )
			? (string) $post->post_type
			: '';

		return '' !== $post_type && use_block_editor_for_post_type( $post_type );
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

if ( ! function_exists( 'add_meta_box' ) ) {
	function add_meta_box( string $id, string $title, callable $callback, $screen = null, string $context = 'advanced', string $priority = 'default', ?array $callback_args = null ): void {
		$GLOBALS['lerm_admin_config_meta_boxes'][] = array(
			'id'            => $id,
			'title'         => $title,
			'callback'      => $callback,
			'screen'        => $screen,
			'context'       => $context,
			'priority'      => $priority,
			'callback_args' => $callback_args,
		);
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
		$GLOBALS['lerm_admin_config_filters'][ $hook ][ $priority ][] = array(
			'callback'      => $callback,
			'accepted_args' => $accepted_args,
		);

		return true;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( string $hook, $value, ...$args ) {
		if ( empty( $GLOBALS['lerm_admin_config_filters'][ $hook ] ) ) {
			return $value;
		}

		ksort( $GLOBALS['lerm_admin_config_filters'][ $hook ] );

		foreach ( $GLOBALS['lerm_admin_config_filters'][ $hook ] as $callbacks ) {
			foreach ( $callbacks as $listener ) {
				$accepted_args = (int) $listener['accepted_args'];
				$filter_args   = array_slice( array_merge( array( $value ), $args ), 0, max( 1, $accepted_args ) );
				$value         = call_user_func_array( $listener['callback'], $filter_args );
			}
		}

		return $value;
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

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id(): int {
		return (int) ( $GLOBALS['lerm_admin_config_current_user_id'] ?? 1 );
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
