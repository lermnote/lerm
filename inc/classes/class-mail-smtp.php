<?php
/**
 * Custom Mail SMTP
 *
 * @package Lerm\Inc
 */

namespace Lerm\Inc;

use Lerm\Inc\Traits\Singleton;

class Mail_SMTP {

	use Singleton;

	public function __construct() {
		$this->hooks();
	}

	public function handle() {

	}

	public function hooks() {
		add_action( 'phpmailer_init', array( $this, 'mail_smtp' ) );
	}

	public function mail_smtp( $phpmailer ) {
		$phpmailer->From     = lerm_options( 'mail_options', 'from_email' );
		$phpmailer->FromName = lerm_options( 'mail_options', 'from_name' );

		$phpmailer->Host       = lerm_options( 'smtp_options', 'smtp_host' );
		$phpmailer->Port       = lerm_options( 'smtp_options', 'smtp_port' );
		$phpmailer->SMTPSecure = lerm_options( 'smtp_options', 'ssl_switcher' ) ? 'ssl' : '';

		$phpmailer->SMTPAuth = lerm_options( 'smtp_options', 'smtp_auth' );
		$phpmailer->Username = lerm_options( 'smtp_options', 'username' );
		$phpmailer->Password = lerm_options( 'smtp_options', 'pswd' );
		$phpmailer->IsSMTP();
	}
}
