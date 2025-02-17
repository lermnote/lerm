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
require_once LERM_DIR . 'Inc/admin/codestar-framework.php';
require_once LERM_DIR . 'vendor/autoload.php';
Init::instance( get_option( 'lerm_theme_options' ) );

// // functions used for debug mail errors, log is stored at SERVER_ROOT_DIR/mail.log
// function smtplog_mailer_errors( $wp_error ) {
// 	global $wp_filesystem;
// 	WP_Filesystem();

// 	$file = ABSPATH . '/mail.log';

// 	$timestamp   = time();
// 	$currenttime = gmdate( 'Y-m-d H:i:s', $timestamp );
// 	$wp_filesystem->put_contents( $file, $currenttime . ' Mailer Error: ' . $wp_error->get_error_message() . "\n", FS_CHMOD_FILE );
// }
// add_action( 'wp_mail_failed', 'smtplog_mailer_errors', 10, 1 );
add_action( 'wp_ajax_load_page_content', 'handle_load_page_content' );
add_action( 'wp_ajax_nopriv_load_page_content', 'handle_load_page_content' );

function handle_load_page_content() {
	// 验证请求是否包含 URL 参数
	if ( ! isset( $_GET['url'] ) || empty( $_GET['url'] ) ) {
		wp_send_json_error( array( 'message' => 'Missing or invalid URL' ), 400 );
	}

	// 获取并清理 URL 参数
	$requested_url = esc_url_raw( $_GET['url'] );

	// 验证是否是同一站点的 URL（安全性考虑）
	if ( strpos( $requested_url, home_url() ) !== 0 ) {
		wp_send_json_error( array( 'message' => 'Unauthorized URL' ), 403 );
	}

	// 请求 URL 的内容
	$response = wp_remote_get( $requested_url );

	// 检查请求是否成功
	if ( is_wp_error( $response ) ) {
		wp_send_json_error( array( 'message' => $response->get_error_message() ), 500 );
	}

	$status_code = wp_remote_retrieve_response_code( $response );
	$body        = wp_remote_retrieve_body( $response );

	// 如果响应码不是 200，返回错误
	if ( 200 !== $status_code ) {
		wp_send_json_error(
			array(
				'message'     => 'Failed to fetch content',
				'status_code' => $status_code,
			),
			500
		);
	}

	// 提取 SEO 数据和目标内容
	$doc = new DOMDocument();
	libxml_use_internal_errors( true ); // 禁用 HTML 错误警告
	$doc->loadHTML( $body );
	libxml_clear_errors();

	// 提取标题
	$title         = '';
	$title_element = $doc->getElementsByTagName( 'title' );
	if ( $title_element->length > 0 ) {
		$title = $title_element->item( 0 )->textContent;
	}

	// 提取 meta 描述
	$meta_description = '';
	$meta_elements    = $doc->getElementsByTagName( 'meta' );
	foreach ( $meta_elements as $meta ) {
		if ( $meta->getAttribute( 'name' ) === 'description' ) {
			$meta_description = $meta->getAttribute( 'content' );
			break;
		}
	}
	// 提取 meta 关键字
	$meta_keywords = '';
	foreach ( $meta_elements as $meta ) {
		if ( $meta->getAttribute( 'name' ) === 'keywords' ) {
			$meta_keywords = $meta->getAttribute( 'content' );
			break;
		}
	}
	// 提取 div#page 内容
	$content        = '';
	$target_element = $doc->getElementById( 'page-ajax' );
	if ( $target_element ) {
		$content = $doc->saveHTML( $target_element );
	}

	// 返回 JSON 数据
	wp_send_json_success(
		array(
			'title'            => $title,
			'meta_description' => $meta_description,
			'meta_keywords'    => $meta_keywords,
			'content'          => $content,
		)
	);
}

function custom_add_rewrite_rules() {
	add_rewrite_tag( '%custom_action%', '([^&]+)' );
	// Add rewrite rule for login
	add_rewrite_rule( '^login/?$', 'index.php?custom_action=login', 'top' );

	// Add rewrite rule for register
	add_rewrite_rule( '^register/?$', 'index.php?custom_action=register', 'top' );

	// Add rewrite rule for forgot password
	add_rewrite_rule( '^forgot-password/?$', 'index.php?custom_action=forgot_password', 'top' );

}
add_action( 'init', 'custom_add_rewrite_rules' );

function custom_query_vars( $vars ) {
	$vars[] = 'custom_action';
	return $vars;
}
add_filter( 'query_vars', 'custom_query_vars' );
function custom_template_redirect() {
	$custom_page = get_query_var( 'custom_action' );
	if ( $custom_page ) {
		switch ( $custom_page ) {
			case 'login':
				include get_template_directory() . '/template-parts/account/form-login.php';
				exit;
			case 'register':
				include get_template_directory() . '/template-parts/account/form-regist.php';
				exit;
			case 'forgot_password':
				include get_template_directory() . '/template-parts/account/form-reset.php';
				exit;
		}
	}
}
add_action( 'template_redirect', 'custom_template_redirect' );
function custom_load_form_via_get() {
	$action = $_GET['action_type'] ?? ''; // 从 GET 参数获取 action_type

	if ( in_array( $action, array( 'login', 'regist', 'reset' ) ) ) {

		$template = get_template_directory() . "/template-parts/account/form-{$action}.php";

		if ( file_exists( $template ) ) {
			ob_start();
			include $template;
			$form = ob_get_clean();
			// 返回 JSON 数据
			wp_send_json_success(
				array(
					'content' => $form,
				)
			);
		} else {
			wp_die( 'Template not found.', 404 );
		}
	} else {
		wp_die( 'Invalid action.', 400 );
	}
	wp_die();// 确保脚本终止
}
add_action( 'wp_ajax_nopriv_load_form', 'custom_load_form_via_get' );
add_action( 'wp_ajax_load_form', 'custom_load_form_via_get' );
function custom_flush_rewrite_rules() {
	custom_add_rewrite_rules();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'custom_flush_rewrite_rules' );
