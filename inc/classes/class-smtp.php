<?php
/**
 * Custom Mail SMTP
 *
 * @package Lerm\Inc
 */

namespace Lerm\Inc;

class SMTP {

	public static $args = array(
		'email_notice' => false,
		'smtp_options' => array(
			'from_email' => '',
			'from_name'  => '',
			'smtp_host'  => '',
			'smtp_port'  => '',
			'ssl_enable' => true,
			'smtp_auth'  => '',
			'username'   => '',
			'pwd'       => '',
		),
	);

	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_smtp_', wp_parse_args( $params, self::$args ) );
		self::hooks();
	}

	// instance
	public static function instance( $params = array() ) {
		return new self( $params );
	}

	public static function hooks() {
		if ( self::$args['email_notice'] ) {
			add_action( 'phpmailer_init', array( __NAMESPACE__ . '\SMTP', 'mail_smtp' ), 100, 1 );
		}
	}

	public static function mail_smtp( $phpmailer ) {
		$smtp = self::$args['smtp_options'];

		$phpmailer->From     = $smtp['from_email'];
		$phpmailer->FromName = $smtp['from_name'];

		$phpmailer->Host       = $smtp['smtp_host'];
		$phpmailer->Port       = $smtp['smtp_port'];
		$phpmailer->SMTPSecure = $smtp['ssl_enable'] ? 'ssl' : '';

		$phpmailer->SMTPAuth = $smtp['smtp_auth'];
		$phpmailer->Username = $smtp['username'];
		$phpmailer->Password = $smtp['pwd'];
		$phpmailer->IsSMTP();
	}
}
