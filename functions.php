<?php
/**
 * Functions and definitions
 *
 *  @package Lerm https://lerm.net
 * @date   2016-08-28 21:57:52
 * @since  lerm 1.0
 */

use Lerm\Inc\Init;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

define( 'DOMAIN', 'lerm' );

// Theme vision.
define( 'LERM_VERSION', wp_get_theme()->get( 'Version' ) );

// Define blog name.
define( 'BLOGNAME', get_bloginfo( 'name' ) );

// Directory URI to the theme folder.
if ( ! defined( 'LERM_URI' ) ) {
	define( 'LERM_URI', trailingslashit( get_template_directory_uri() ) );
}

// Directory path to the theme folder.
if ( ! defined( 'LERM_DIR' ) ) {
	define( 'LERM_DIR', trailingslashit( get_template_directory() ) );
}

/**
 * Requre admin framework
 */
require_once LERM_DIR . 'inc/admin/codestar-framework.php';
require_once __DIR__ . '/vendor/autoload.php';
// require_once LERM_DIR . 'inc/misc/thumbnail.php';
Init::instance( get_option( 'lerm_theme_options' ) );




// functions used for debug mail errors, log is stored at SERVER_ROOT_DIR/mail.log
function smtplog_mailer_errors( $wp_error ) {
	global $wp_filesystem;
	WP_Filesystem();

	$file = ABSPATH . '/mail.log';

	$timestamp   = time();
	$currenttime = gmdate( 'Y-m-d H:i:s', $timestamp );
	$wp_filesystem->put_contents( $file, $currenttime . ' Mailer Error: ' . $wp_error->get_error_message() . "\n", FS_CHMOD_FILE );
}
add_action( 'wp_mail_failed', 'smtplog_mailer_errors', 10, 1 );


// function get_the_image( $args = array() ) {

// 	$image = new \Lerm\Inc\Misc\Get_The_Image( $args );

// 	return $image->get_image();
// }
