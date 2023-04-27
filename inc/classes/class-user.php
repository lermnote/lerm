<?php

namespace Lerm\Inc;

/**
 */
class User {

	public static $args = array(
		'sitemap_enable' => true,
		'post_type'      => array(),
		'post_exclude'   => array(),
		'page_exclude'   => array(),
	);

	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_user_', wp_parse_args( $params, self::$args ) );
		self::hooks();
	}

	// instance
	public static function instance( $params = array() ) {
		return new self( $params );
	}

	public static function hooks() {
		// 处理前端登录请求
		add_action( 'wp_ajax_nopriv_ajax_login', array( __Class__, 'ajax_login' ) );
	}

	public static function ajax_login() {
		$username = $_POST['username'];
		$password = $_POST['password'];

		$user = wp_authenticate( $username, $password );
		if ( is_wp_error( $user ) ) {
			// 验证失败
			wp_send_json_error( array( 'message' => $user->get_error_message() ) );
		} else {
			// 验证成功
			wp_set_auth_cookie( $user->ID );
			wp_send_json_success();
		}
	}
	public static function user_profile(){

	}
}
