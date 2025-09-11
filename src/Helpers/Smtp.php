<?php
declare( strict_types=1 ); // 强制类型

// phpcs:disable WordPress.Files.FileName
/**
 * Custom Mail SMTP (improved)
 *
 * @package Lerm\Inc
 */

namespace Lerm\Helpers;

use Lerm\Traits\Singleton;
use PHPMailer\PHPMailer\PHPMailer;

if ( ! class_exists( __NAMESPACE__ . '\\Smtp' ) ) {
	final class Smtp {
		use Singleton;

		/**
		 * Holds the original WP from email address passed to the wp_mail_from filter.
		 *
		 * @since 2.1.0
		 *
		 * @var string|null
		 */
		protected static ?string $wp_mail_from = null;

		/**
		 * Default constants.
		 *
		 * @since 2.1.0
		 *
		 * @var array $args Default values.
		 */
		public static array $args = array(
			'email_notice' => false,
			'smtp_options' => array(
				'from_email' => '',
				'from_name'  => '',
				'smtp_host'  => '',
				'smtp_port'  => 0,
				'ssl_enable' => 'tls', // 'ssl' | 'tls' | 'none'
				'smtp_auth'  => false,
				'username'   => '',
				'pwd'        => '',
			),
		);

		/**
		 * Constructor
		 *
		 * @param array $params Optional parameters.
		 */
		public function __construct( array $params = array() ) {
			// Merge and allow filters to override defaults.
			self::$args = apply_filters( 'lerm_smtp_', wp_parse_args( $params, self::$args ) );

			// Normalize & sanitize incoming config early.
			self::$args['smtp_options'] = $this->validate_smtp_options( self::$args['smtp_options'] );

			$this->hooks();
		}

		/**
		 * Hooks
		 *
		 * Sets up the hooks for the SMTP configuration.
		 *
		 * @return void
		 */
		protected function hooks(): void {
			// Only initialize PHPMailer hook if email_notice enabled.
			if ( ! empty( self::$args['email_notice'] ) ) {
				add_action( 'phpmailer_init', array( $this, 'mail_smtp' ), 100, 1 );
			}

			// High priority ensures we run last and respect previous hooks unless forced.
			add_filter( 'wp_mail_from', array( $this, 'filter_mail_from_email' ), PHP_INT_MAX );
			add_filter( 'wp_mail_from_name', array( $this, 'filter_mail_from_name' ), PHP_INT_MAX );
		}

		/**
		 * Configure PHPMailer to use SMTP.
		 *
		 * @param PHPMailer $phpmailer PHPMailer instance passed by reference.
		 *
		 * @return void
		 */
		public function mail_smtp( PHPMailer $phpmailer ): void {
			$smtp = self::$args['smtp_options'];

			// Basic validation: host & port required for SMTP.
			if ( empty( $smtp['smtp_host'] ) || empty( $smtp['smtp_port'] ) ) {
				// If missing required data, do nothing — other plugins/filters may handle mail.
				return;
			}

			// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$phpmailer->isSMTP(); // 使用 SMTP

			$phpmailer->Host = $smtp['smtp_host'];
			$phpmailer->Port = (int) $smtp['smtp_port'];

			// PHPMailer expects empty string for no encryption.
			$phpmailer->SMTPSecure = 'none' === $smtp['ssl_enable'] ? '' : $smtp['ssl_enable'];

			// If smtp_auth is truthy, enable auth and set credentials.
			$phpmailer->SMTPAuth = (bool) $smtp['smtp_auth'];

			if ( $phpmailer->SMTPAuth ) {
				$phpmailer->Username = $smtp['username'];
				// NOTE: Do NOT log/echo password anywhere.
				$phpmailer->Password = $smtp['pwd'];
			}

			// If TLS, allow auto-TLS specifically.
			$phpmailer->SMTPAutoTLS = ( 'tls' === $smtp['ssl_enable'] );

			// Optional: set a sensible timeout (seconds) if provided by config.
			if ( ! empty( $smtp['timeout'] ) ) {
				$phpmailer->Timeout = (int) $smtp['timeout'];
			}
			// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		}

		/**
		 * Filter the 'from' email address used in wp_mail.
		 *
		 * @param string $wp_email The email address passed by the filter.
		 *
		 * @return string Filtered email address.
		 */
		public function filter_mail_from_email( $wp_email ): string {
			$original = is_string( $wp_email ) ? sanitize_email( $wp_email ) : '';

			// Save the validated "original" WP email from address for later use.
			self::$wp_mail_from = ! empty( $original ) ? $original : null;

			// Use forced "from_email" from settings when valid; otherwise fall back to WP-provided.
			$from_email = self::$args['smtp_options']['from_email'] ?? '';

			$from_email = is_string( $from_email ) ? sanitize_email( $from_email ) : '';

			return ! empty( $from_email ) ? $from_email : ( $original ? $original : (string) $wp_email );
		}

		/**
		 * Filter the 'from' name used in wp_mail.
		 *
		 * @param string $name The from name passed through the filter.
		 *
		 * @return string Filtered name.
		 */
		public function filter_mail_from_name( $name ): string {
			$from_name = self::$args['smtp_options']['from_name'] ?? '';

			$from_name = is_string( $from_name ) ? sanitize_text_field( $from_name ) : '';

			return ! empty( $from_name ) ? $from_name : ( is_string( $name ) ? $name : '' );
		}

		/**
		 * Return the original WP 'from' address that was present when wp_mail called.
		 *
		 * @return string|null
		 */
		public static function get_original_wp_mail_from(): ?string {
			return self::$wp_mail_from;
		}

		/**
		 * Validate & sanitize smtp_options array.
		 *
		 * - Ensures expected keys exist.
		 * - Sanitizes email/name/host/username fields.
		 * - Normalizes ssl_enable to 'ssl'|'tls'|'none'.
		 *
		 * @param array $opts Input options (untrusted).
		 *
		 * @return array Sanitized options.
		 */
		protected function validate_smtp_options( array $opts ): array {
			$defaults = self::$args['smtp_options'];

			$opts = wp_parse_args( $opts, $defaults );

			// Sanitizations:
			$opts['from_email'] = is_string( $opts['from_email'] ) ? sanitize_email( $opts['from_email'] ) : '';
			$opts['from_name']  = is_string( $opts['from_name'] ) ? sanitize_text_field( $opts['from_name'] ) : '';
			$opts['smtp_host']  = is_string( $opts['smtp_host'] ) ? sanitize_text_field( $opts['smtp_host'] ) : '';
			$opts['smtp_port']  = (int) ( $opts['smtp_port'] ?? 0 );

			// ssl_enable normalization.
			$ssl                = strtolower( (string) $opts['ssl_enable'] );
			$ssl                = in_array( $ssl, array( 'ssl', 'tls', 'none' ), true ) ? $ssl : 'tls';
			$opts['ssl_enable'] = $ssl;

			$opts['smtp_auth'] = ! empty( $opts['smtp_auth'] ) ? true : false;
			$opts['username']  = is_string( $opts['username'] ) ? sanitize_text_field( $opts['username'] ) : '';
			// password kept raw (cannot be sanitized into something else) but ensure it's a string
			$opts['pwd'] = isset( $opts['pwd'] ) ? (string) $opts['pwd'] : '';

			// Optional: timeout (seconds)
			$opts['timeout'] = isset( $opts['timeout'] ) ? (int) $opts['timeout'] : 30;

			return $opts;
		}
	}
}
