<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

// phpcs:disable WordPress.Files.FileName
/**
 * Custom Mail SMTP (improved).
 *
 * @package Lerm
 */

namespace Lerm\Mail;

use Lerm\Traits\Singleton;
use PHPMailer\PHPMailer\PHPMailer;

final class Smtp {
	use Singleton;

	protected static ?string $wp_mail_from = null;

	public static array $args = array(
		'email_notice' => false,
		'smtp_options' => array(
			'from_email' => '',
			'from_name'  => '',
			'smtp_host'  => '',
			'smtp_port'  => 0,
			'ssl_enable' => 'tls',
			'smtp_auth'  => false,
			'username'   => '',
			'pwd'        => '',
		),
	);

	public function __construct( array $params = array() ) {
		self::$args = apply_filters( 'lerm_smtp_args', wp_parse_args( $params, self::$args ) );
		//var_dump( self::$args );
		//self::$args['smtp_options'] = $this->validate_smtp_options( self::$args['smtp_options'] );
		$this->hooks();
	}

	protected function hooks(): void {
		if ( ! empty( self::$args['email_notice'] ) ) {
			add_action( 'phpmailer_init', array( $this, 'mail_smtp' ), 100, 1 );
		}

		add_filter( 'wp_mail_from', array( $this, 'filter_mail_from_email' ), PHP_INT_MAX );
		add_filter( 'wp_mail_from_name', array( $this, 'filter_mail_from_name' ), PHP_INT_MAX );
	}

	public function mail_smtp( PHPMailer $phpmailer ): void {
		$smtp = self::$args['smtp_options'];

		if ( empty( $smtp['smtp_host'] ) || empty( $smtp['smtp_port'] ) ) {
			return;
		}

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$phpmailer->isSMTP();
		$phpmailer->Host       = $smtp['smtp_host'];
		$phpmailer->Port       = (int) $smtp['smtp_port'];
		$phpmailer->SMTPSecure = 'none' === $smtp['ssl_enable'] ? '' : $smtp['ssl_enable'];
		$phpmailer->SMTPAuth   = (bool) $smtp['smtp_auth'];

		if ( $phpmailer->SMTPAuth ) {
			$phpmailer->Username = $smtp['username'];
			$phpmailer->Password = $smtp['pwd'];
		}

		$phpmailer->SMTPAutoTLS = ( 'tls' === $smtp['ssl_enable'] );

		if ( ! empty( $smtp['timeout'] ) ) {
			$phpmailer->Timeout = (int) $smtp['timeout'];
		}
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	public function filter_mail_from_email( $wp_email ): string {
		$original           = is_string( $wp_email ) ? sanitize_email( $wp_email ) : '';
		self::$wp_mail_from = ! empty( $original ) ? $original : null;

		$from_email = self::$args['smtp_options']['from_email'] ?? '';
		$from_email = is_string( $from_email ) ? sanitize_email( $from_email ) : '';

		return ! empty( $from_email ) ? $from_email : ( $original ? $original : (string) $wp_email );
	}

	public function filter_mail_from_name( $name ): string {
		$from_name = self::$args['smtp_options']['from_name'] ?? '';
		$from_name = is_string( $from_name ) ? sanitize_text_field( $from_name ) : '';

		return ! empty( $from_name ) ? $from_name : ( is_string( $name ) ? $name : '' );
	}

	public static function get_original_wp_mail_from(): ?string {
		return self::$wp_mail_from;
	}

	protected function validate_smtp_options( array $opts ): array {

		$defaults = self::$args['smtp_options'];

		$opts = wp_parse_args( $opts, $defaults );

		$opts['from_email'] = is_string( $opts['from_email'] ) ? sanitize_email( $opts['from_email'] ) : '';
		$opts['from_name']  = is_string( $opts['from_name'] ) ? sanitize_text_field( $opts['from_name'] ) : '';
		$opts['smtp_host']  = is_string( $opts['smtp_host'] ) ? sanitize_text_field( $opts['smtp_host'] ) : '';
		$opts['smtp_port']  = (int) ( $opts['smtp_port'] ?? 0 );

		$ssl                = strtolower( (string) $opts['ssl_enable'] );
		$ssl                = in_array( $ssl, array( 'ssl', 'tls', 'none' ), true ) ? $ssl : 'tls';
		$opts['ssl_enable'] = $ssl;

		$opts['smtp_auth'] = ! empty( $opts['smtp_auth'] );
		$opts['username']  = is_string( $opts['username'] ) ? sanitize_text_field( $opts['username'] ) : '';
		$opts['pwd']       = isset( $opts['pwd'] ) ? (string) $opts['pwd'] : '';

		$opts['timeout'] = isset( $opts['timeout'] ) ? (int) $opts['timeout'] : 30;

		return $opts;
	}
}
