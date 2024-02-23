<?php // phpcs:disable WordPress.Files.FileName
/**
 * Custom Mail SMTP
 *
 * @package Lerm\Inc
 */

namespace Lerm\Inc;

use Lerm\Inc\Traits\Singleton;

class SMTP {

	use singleton;

	/**
	 * This attribute will hold the "original" WP from email address passed to the wp_mail_from filter,
	 * that is not equal to the default email address.
	 *
	 * It should hold an email address set via the wp_mail_from filter, before we might overwrite it.
	 *
	 * @since 2.1.0
	 *
	 * @var string
	 */
	protected static $wp_mail_from;

	/**
	 * Default constants.

	 * @since 2.1.0
	 *
	 * @var array $args default value.
	 */

	public static $args = array(
		'email_notice' => false,
		'smtp_options' => array(
			'from_email' => '',
			'from_name'  => '',
			'smtp_host'  => '',
			'smtp_port'  => '',
			'ssl_enable' => 'tls',
			'smtp_auth'  => '',
			'username'   => '',
			'pwd'        => '',
		),
	);

	/**
	 * Constructor
	 *
	 * @param array $params Optional parameters.
	 *
	 * @return void
	 */
	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_smtp_', wp_parse_args( $params, self::$args ) );
		self::hooks();
	}

	/**
	 * Hooks
	 *
	 * @return void
	 */
	public static function hooks() {
		if ( self::$args['email_notice'] ) {
			add_action( 'phpmailer_init', array( __CLASS__, 'mail_smtp' ), 100, 1 );
		}

		// High priority number tries to ensure our plugin code executes last and respects previous hooks, if not forced.
		add_filter( 'wp_mail_from', array( __CLASS__, 'filter_mail_from_email' ), PHP_INT_MAX );
		add_filter( 'wp_mail_from_name', array( __CLASS__, 'filter_mail_from_name' ), PHP_INT_MAX );
	}

	/**
	 * Mail_smtp
	 *
	 * @param phpmailer $phpmailer It's passed by reference, so no need to return anything.
	 *
	 * @return void
	 */
	public static function mail_smtp( $phpmailer ) {
		$smtp = self::$args['smtp_options'];

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$phpmailer->IsSMTP();

		$phpmailer->Host = $smtp['smtp_host'];
		$phpmailer->Port = $smtp['smtp_port'];

		// Set the SMTPSecure value, if set to none, leave this blank. Possible values: 'ssl', 'tls', ''.
		$phpmailer->SMTPSecure = $smtp['ssl_enable'];

		$phpmailer->SMTPAuth = $smtp['smtp_auth'] ? true : false;

		$phpmailer->Username = $smtp['username'];
		$phpmailer->Password = $smtp['pswd'];
	}

	/**
	 * Filter_mail_from_email
	 *
	 * @param string $wp_email The email address passed by the filter.
	 *
	 * @return string
	 */
	public static function filter_mail_from_email( $wp_email ) {

		$from_email = self::$args['smtp_options']['from_email'];

		// Save the "original" set WP email from address for later use.
		self::$wp_mail_from = filter_var( $wp_email, FILTER_VALIDATE_EMAIL );

		// Return FROM EMAIL if forced in settings.
		if ( ! empty( $from_email ) ) {
			return $from_email;
		}

		return ! empty( $from_email ) ? $from_email : $wp_email;
	}

	/**
	 * Modify the sender name that is used for sending emails.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name The from name passed through the filter.
	 *
	 * @return string
	 */
	public static function filter_mail_from_name( $name ) {

		$from_name = self::$args['smtp_options']['from_name'];

		if ( ! empty( $from_name ) ) {
			return $from_name;
		}

		return $name;
	}
}
