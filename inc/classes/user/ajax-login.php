<?php

/**
 * Ajax Login
 */

// Prevent direct access to this file.
defined( 'WPINC' ) || die();

// Consider changing these.
const LERM_MENU_LOCATION     = 'primary';
const LERM_LOGIN_RETRY_PAUSE = 5; // secs
const LERM_SPINNER_FILE_NAME = 'tail-spin.svg';

// You probably don't need to change these.
const LERM_LOGIN_ACTION         = 'lerm-login';
const LERM_LOGIN_FORM_FILE_NAME = 'login.php';

/**
 * Listen for incoming login requests from the Ajax form.
 */
function lerm_init() {
	add_action( 'wp_ajax_nopriv_' . LERM_LOGIN_ACTION, 'lerm_try_to_login' );
}
add_action( 'init', 'lerm_init' );


/**
 * Get the IP address of the current browser.
 *
 * @since    3.2.8
 */
function lerm_client_ip() {
	if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) && ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ip = ( isset( $_SERVER['REMOTE_ADDR'] ) ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
	}
	$ip = ( false === filter_var( $ip, FILTER_VALIDATE_IP ) ) ? '0.0.0.0' : filter_var( $ip, FILTER_VALIDATE_IP );

	return $ip;
}

/**
 * A convenience function that lets us disable the login menu item under
 * certain conditions.
 */
function lerm_is_login_menu_required() {
	$is_required = ! is_user_logged_in();

	// You can put extra conditions in here.
	// if ($some_test == true) {
	// $is_required = false;
	// }

	return $is_required;
}

function lerm_front_door_url() {
	// Get the URL for the site's main login page, with the redirect
	// (after login) set to the page we're currently viewing.
	// This is useful for mobile devices where the Ajax login form might
	// not be available.
	$login_page_url = wp_login_url( home_url( $_SERVER['REQUEST_URI'] ) );

	// If WooCommerce is installed, use the my-account page as the frontdoor,
	// so we get a nice front-end login form.
	if ( function_exists( 'wc_get_account_endpoint_url' ) ) {
		$frontdoor_url = wc_get_account_endpoint_url( 'dashboard' );
	}

	return $frontdoor_url;
}

/**
 * We add our assets to every page of the site, because the primay
 * nav menu is probably on every page.
 */
function lerm_enqueue_scripts() {
	if ( lerm_is_login_menu_required() ) {
		$base_uri = get_stylesheet_directory_uri();
		$version  = wp_get_theme()->get( 'Version' );

		// Enqueue our main JavaScript file.
		wp_enqueue_script( 'lerm', $base_uri . '/assets/js/ajax-login.js', array(), $version, true );

		// Pass some settings and variables to the browser in a
		// JavaScript global variable called lermData.
		wp_localize_script(
			'lerm',
			'lermData',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'user_nonce' => wp_create_nonce( 'user_nonce' ),
				'action'     => LERM_LOGIN_ACTION,
				'frontDoor'  => lerm_front_door_url(),
				'spinnerUrl' => $base_uri . '/inc/user/' . LERM_SPINNER_FILE_NAME,
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'lerm_enqueue_scripts' );

/**
 * Render the login menu item and form. $items is a string that we're going
 * to append our HTML to.
 */
function lerm_wp_nav_menu_items( $items, $args ) {
	// The file name of the login form (PHP) we're going to "include".
	$file_name = __DIR__ . '/templates/' . LERM_LOGIN_FORM_FILE_NAME;

	if ( ! lerm_is_login_menu_required() ) {
		// We're already logged in so we don't need a login form.
	} elseif ( $args->theme_location != LERM_MENU_LOCATION ) {
		// These aren't the menu item's you're looking for.
	} elseif ( ! is_file( $file_name ) ) {
		$items .= sprintf(
			'<li class="menu-item"><a class="menu-link"><strong>%s</strong></a></li>',
			LERM_LOGIN_FORM_FILE_NAME
		);
	} else {
		// Start rendering the HTML for the menu item.
		$outer_classes = array(
			'menu-item',
			'menu-item-has-children',
			'menu-item-login',
		);
		$items        .= sprintf( '<li class="%s">', esc_attr( implode( ' ', $outer_classes ) ) );

		// The login menu item. You can change the "Login" label here.
		$items .= sprintf(
			'<a href="%s">%s</a>',
			esc_url( lerm_front_door_url() ),
			esc_html__( 'Login', 'wp-tutorials' )
		);

		// Start rendering a sub menu to hold the login form.
		$sub_menu_classes = array(
			'sub-menu',
			'lerm-container',
		);
		$items           .= sprintf( '<ul class="%s">', esc_attr( implode( ' ', $sub_menu_classes ) ) );

		// Include the login form PHP/HTML file.
		ob_start();
		include $file_name;
		$items .= ob_get_clean();

		$items .= '</ul>'; // .sub-menu

		$items .= '</li>'; // .menu-item
	}

	return $items;
}
add_filter( 'wp_nav_menu_items', 'lerm_wp_nav_menu_items', 10, 2 );

/**
 * The main function will try to log in to the site by sanitising and
 * authenticating $_POST['username'] and $_POST['password']
 */
function lerm_try_to_login() {

	// If we can't determine the client's IP address then something is very
	// wrong - possibly a hack attempt. Don't do anything.

	$client_ip_address = lerm_client_ip();
	if ( empty( $client_ip_address ) ) {
		wp_die();
	}
	if ( ! wp_verify_nonce( $_POST['security'], 'user_nonce' ) ) {
		\wp_die();
	}

	$client_key = 'login_attempt_' . $client_ip_address;

	$credentials['user_login']    = sanitize_text_field( $_POST['username'] );
	$credentials['user_password'] = sanitize_text_field( $_POST['password'] );
	$credentials['remember']      = isset( $_POST['rememberme'] ) ? true : false;

	$user = wp_signon( $credentials, false );

	$status_code = 200;
	$response    = array(
		'loggedin' => false,
		'message'  => __( 'Incorrect username or password.', 'lerm' ),
	);

	if ( empty( $credentials['user_login'] ) ) {
		$response['message'] = __( 'The username field is empty.', 'lerm' );
	} elseif ( empty( $credentials['user_password'] ) ) {
		$response['message'] = __( 'The password field is empty.', 'lerm' );
	} elseif ( get_transient( $client_key ) !== false ) {
		$response['message'] = __( 'Slow down a bit.', 'lerm' );
	}

	if ( is_a( $user, 'WP_User' ) ) {
		// Logged in OK.
		$response['loggedin'] = true;
		$response['message']  = __( 'Logedin successful,redirecting…', 'lerm' );

		wp_send_json_success( $response, $status_code );
	} else {
		// 验证失败
		set_transient( $client_key, '1', LERM_LOGIN_RETRY_PAUSE );
		wp_send_json_error( $response, $status_code );
	}
}
