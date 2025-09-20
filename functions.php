<?php
/**
 * Functions and definitions
 *
 *  @package Lerm https://lerm.net
 * @date   2016-08-28 21:57:52
 * @since  lerm 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define theme constants.
define( 'BLOGNAME', get_bloginfo( 'name' ) );

// Text domain for translations.
if ( ! defined( 'LERM_DOMAIN' ) ) {
	define( 'LERM_DOMAIN', 'lerm' );
}
// Theme version.
if ( ! defined( 'LERM_VERSION' ) ) {
	$theme = wp_get_theme();
	define( 'LERM_VERSION', $theme->get( 'Version' ) ? $theme->get( 'Version' ) : '1.0.0' );
}

// Theme paths.
if ( ! defined( 'LERM_URI' ) ) {
	define( 'LERM_URI', trailingslashit( get_stylesheet_directory_uri() ) );
}
// Theme directory.
if ( ! defined( 'LERM_DIR' ) ) {
	define( 'LERM_DIR', trailingslashit( get_stylesheet_directory() ) );
}

// /* Load translations early */
function lerm_load_textdomain() {
	load_theme_textdomain( LERM_DOMAIN, LERM_DIR . 'languages' );
}
add_action( 'after_setup_theme', 'lerm_load_textdomain' );


$autoload = LERM_DIR . 'vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
} else {
	add_action(
		'admin_notices',
		function () use ( $autoload ) {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html( "Missing Composer autoload: {$autoload}. Please run composer install or include vendor files." )
			);
		}
	);
}

/* Initialize theme (defer to after_setup_theme to ensure WP is ready) */
add_action(
	'after_setup_theme',
	function () {
		$options = get_option( 'lerm_theme_options', array() );
		// Ensure Init class exists (autoload should provide it)
		if ( class_exists( '\Lerm\Init' ) ) {
			\Lerm\Init::instance( $options );
		}
	}
);

/* Include optional admin framework file if present */
$csf = LERM_DIR . 'src/Admin/codestar-framework.php';
if ( file_exists( $csf ) ) {
	require_once $csf;
}


/**
 * 从 assets/dist/manifest.json 获取构建后真实文件名
 * 返回完整 URL 或 false
 */
// function lerm_get_dist_file_url( $key ) {
// 	$dist_dir      = get_template_directory() . '/assets/dist';
// 	$manifest_path = $dist_dir . '/manifest.json';

// 	if ( ! file_exists( $manifest_path ) ) {
// 		return false;
// 	}

// 	$manifest = json_decode( file_get_contents( $manifest_path ), true );
// 	if ( ! is_array( $manifest ) || ! isset( $manifest[ $key ] ) ) {
// 		return false;
// 	}

// 	// 返回完整 URL
// 	return get_template_directory_uri() . '/assets/dist/' . $manifest[ $key ];
// }

// function lerm_enqueue_dist_assets() {
// 	if ( is_admin() ) {
// 		return;
// 	}

// 	// CSS
// 	$styles = lerm_get_dist_file_url( 'styles.css' );
// 	if ( $styles ) {
// 		wp_enqueue_style( 'lerm-styles', $styles, array(), null );
// 	}

// 	// JS
// 	$bundle = lerm_get_dist_file_url( 'bundle.js' );
// 	if ( $bundle ) {
// 		wp_enqueue_script( 'lerm-bundle', $bundle, array(), null, true );
// 	}
// }
// add_action( 'wp_enqueue_scripts', 'lerm_enqueue_dist_assets' );


// // functions used for debug mail errors, log is stored at SERVER_ROOT_DIR/mail.log
// function smtplog_mailer_errors( $wp_error ) {
//  global $wp_filesystem;
//  WP_Filesystem();

//  $file = ABSPATH . '/mail.log';

//  $timestamp   = time();
//  $currenttime = gmdate( 'Y-m-d H:i:s', $timestamp );
//  $wp_filesystem->put_contents( $file, $currenttime . ' Mailer Error: ' . $wp_error->get_error_message() . "\n", FS_CHMOD_FILE );
// }
// add_action( 'wp_mail_failed', 'smtplog_mailer_errors', 10, 1 );
// add_action( 'wp_ajax_load_page_content', 'handle_load_page_content' );
// add_action( 'wp_ajax_nopriv_load_page_content', 'handle_load_page_content' );


// function custom_add_rewrite_rules() {
//  add_rewrite_tag( '%custom_action%', '([^&]+)' );
//  // Add rewrite rule for login
//  add_rewrite_rule( '^login/?$', 'index.php?custom_action=login', 'top' );

//  // Add rewrite rule for register
//  add_rewrite_rule( '^register/?$', 'index.php?custom_action=register', 'top' );

//  // Add rewrite rule for forgot password
//  add_rewrite_rule( '^forgot-password/?$', 'index.php?custom_action=forgot_password', 'top' );

// }
// add_action( 'init', 'custom_add_rewrite_rules' );

// function custom_query_vars( $vars ) {
//  $vars[] = 'custom_action';
//  return $vars;
// }
// add_filter( 'query_vars', 'custom_query_vars' );
// function custom_template_redirect() {
//  $custom_page = get_query_var( 'custom_action' );
//  if ( $custom_page ) {
//      switch ( $custom_page ) {
//          case 'login':
//              include get_template_directory() . '/template-parts/account/form-login.php';
//              exit;
//          case 'register':
//              include get_template_directory() . '/template-parts/account/form-regist.php';
//              exit;
//          case 'forgot_password':
//              include get_template_directory() . '/template-parts/account/form-reset.php';
//              exit;
//      }
//  }
// }
// add_action( 'template_redirect', 'custom_template_redirect' );
// function custom_load_form_via_get() {
//  $action = $_GET['action_type'] ?? ''; // 从 GET 参数获取 action_type

//  if ( in_array( $action, array( 'login', 'regist', 'reset' ) ) ) {

//      $template = get_template_directory() . "/template-parts/account/form-{$action}.php";

//      if ( file_exists( $template ) ) {
//          ob_start();
//          include $template;
//          $form = ob_get_clean();
//          // 返回 JSON 数据
//          wp_send_json_success(
//              array(
//                  'content' => $form,
//              )
//          );
//      } else {
//          wp_die( 'Template not found.', 404 );
//      }
//  } else {
//      wp_die( 'Invalid action.', 400 );
//  }
//  wp_die();// 确保脚本终止
// }
// add_action( 'wp_ajax_nopriv_load_form', 'custom_load_form_via_get' );
// add_action( 'wp_ajax_load_form', 'custom_load_form_via_get' );
// function custom_flush_rewrite_rules() {
//  custom_add_rewrite_rules();
//  flush_rewrite_rules();
// }
// register_activation_hook( __FILE__, 'custom_flush_rewrite_rules' );
