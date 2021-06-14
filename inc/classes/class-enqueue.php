<?php
/**
 * Enqueue theme styles and scripts here
 *
 * @package  Lerm\Inc
 */

namespace Lerm\Inc;

use Lerm\Inc\Traits\Singleton;

class Enqueue {

	use Singleton;

	public function __construct() {
		$this->hooks();
	}

	public function hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
	}

	public function styles() {
		wp_enqueue_style( 'bootstrap', LERM_URI . 'assets/css/bootstrap.min.css', array(), '4.4.1' );
		wp_enqueue_style( 'lerm_font', LERM_URI . 'assets/css/lerm-font.min.css', array(), '1.0.0' );
		wp_enqueue_style( 'animate', LERM_URI . 'assets/css/animate.min.css', array(), '1.0.0' );
		if ( is_singular( 'post' ) && lerm_options( 'enable_code_highlight' ) ) {
			wp_enqueue_style( 'lerm_solarized', LERM_URI . 'assets/css/solarized-dark.min.css', array(), LERM_VERSION );
		}
		wp_enqueue_style( 'lerm_style', get_stylesheet_uri(), array(), LERM_VERSION );
		wp_enqueue_style( 'main', LERM_URI . 'assets/css/main.css', array(), '1.0.0' );
	}
	public function scripts() {
		wp_register_script( 'jquery-min', LERM_URI . 'assets/js/jquery.min.js', array(), '3.1.0', true );
		wp_register_script( 'bootstrap', LERM_URI . 'assets/js/bootstrap.min.js', array(), '4.3.1', true );
		wp_register_script( 'lazyload', LERM_URI . 'assets/js/lazyload.min.js', array(), '2.0.0', true );
		wp_register_script( 'lightbox', LERM_URI . 'assets/js/ekko-lightbox.min.js', array(), '2.0.0', true );
		wp_register_script( 'share', LERM_URI . 'assets/js/social-share.min.js', array(), LERM_VERSION, true );
		wp_register_script( 'qrcode', LERM_URI . 'assets/js/qrcode.min.js', array(), '2.0', true );
		wp_register_script( 'highlight', LERM_URI . 'assets/js/highlight.pack.js', array(), '9.14.2', true );
		wp_register_script( 'lerm_js', LERM_URI . 'assets/js/lerm.min.js', array(), LERM_VERSION, true );
		wp_register_script( 'wow_js', LERM_URI . 'assets/js/wow.min.js', array(), LERM_VERSION, true );
		// enqueue script
		if ( lerm_options( 'cdn_jquery' ) ) {
			wp_enqueue_script( 'jquery_cdn', lerm_options( 'cdn_jquery' ), array(), LERM_VERSION, true );
		} else {
			wp_enqueue_script( 'jquery-min' );
		}
		wp_enqueue_script( 'bootstrap' );
		wp_enqueue_script( 'lazyload' );
		wp_enqueue_script( 'lightbox' );

		if ( is_singular( 'post' ) ) {
			wp_enqueue_script( 'qrcode' );
			wp_enqueue_script( 'share' );

			if ( lerm_options( 'enable_code_highlight' ) ) {
				wp_enqueue_script( 'highlight' );
			}
		}
		wp_enqueue_script( 'wow_js' );
		wp_localize_script(
			'lerm_js',
			'adminajax',
			array(
				'url'      => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'ajax_nonce' ),
				'noposts'  => __( 'No older posts found', 'lerm' ),
				'loadmore' => __( 'Load more', 'lerm' ),
				'loading'  => '<i class="fa fa-spinner fa-spin me-1"></i>' . __( 'Loading...', 'lerm' ),
				'loggedin' => is_user_logged_in(),
			)
		);
		wp_enqueue_script( 'lerm_js' );
		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}
}
