<?php
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

		public function __construct( string $code = '', string $message = '' ) {
			if ( '' !== $code ) {
				$this->errors[ $code ] = array( $message );
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
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ): bool {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( '__' ) ) {
	function __( string $text ): string {
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

if ( ! function_exists( '_deprecated_function' ) ) {
	function _deprecated_function( string $function_name, string $version, string $replacement = '' ): void {
		$GLOBALS['lerm_admin_config_deprecated'][] = array(
			'function'    => $function_name,
			'version'     => $version,
			'replacement' => $replacement,
		);
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
		unset( $capability, $args );

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
		return false;
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
