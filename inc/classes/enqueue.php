<?php // phpcs:disable WordPress.Files.FileName
/**
 * Enqueue theme styles and scripts here
 *
 * @package  Lerm\Inc
 */

namespace Lerm\Inc;

use Lerm\Inc\Traits\Singleton;

class Enqueue {

	use singleton;

	/**
	 * Default constants.

	 * @since 2.1.0
	 *
	 * @var array $args default value.
	 */
	public static $args = array(
		'enable_code_highlight' => true,
		'cdn_jquery'            => '',
	);


	/**
	 * Constructor
	 *
	 * @param array $params Optional parameters.

	 * @return void
	 */
	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_smtp_', wp_parse_args( $params, self::$args ) );
		self::hooks();
	}

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function hooks() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'styles' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'scripts' ) );
	}

	/**
	 * Styles enqueue.
	 *
	 * @return void
	 */
	public static function styles() {
		wp_enqueue_style( 'bootstrap', LERM_URI . 'assets/css/bootstrap.min.css', array(), '5.3' );
		wp_enqueue_style( 'lerm_font', LERM_URI . 'assets/css/lerm-font.min.css', array(), '1.0.0' );
		wp_enqueue_style( 'animate', LERM_URI . 'assets/css/animate.min.css', array(), '1.0.0' );
		if ( is_singular( 'post' ) && self::$args['enable_code_highlight'] ) {
			wp_enqueue_style( 'lerm_solarized', LERM_URI . 'assets/css/solarized-dark.min.css', array(), LERM_VERSION );
		}
		wp_enqueue_style( 'main_style', LERM_URI . 'assets/css/main.css', array(), LERM_VERSION );
	}

	/**
	 * Scripts enqueue.
	 *
	 * @return void
	 */
	public static function scripts() {
		global $wp_query;
		wp_register_script( 'bootstrap', LERM_URI . 'assets/js/bootstrap.bundle.min.js', array(), '5.3', true );
		wp_register_script( 'lazyload', LERM_URI . 'assets/js/lazyload.min.js', array(), '2.0.0', true );
		// wp_register_script( 'lightbox', 'https://ajax.googleapis.com/ajax/libs/prototype/1.7.1.0/prototype.js', array(), '1.7.1' );
		wp_register_script( 'share', LERM_URI . 'assets/js/social-share.min.js', array(), LERM_VERSION, true );
		wp_register_script( 'qrcode', LERM_URI . 'assets/js/qrcode.min.js', array(), '2.0', true );
		wp_register_script( 'highlight', LERM_URI . 'assets/js/highlight.pack.js', array(), '9.14.2', true );
		wp_register_script( 'main-js', LERM_URI . 'assets/js/main.js', array(), LERM_VERSION, true );
		wp_register_script( 'wow', LERM_URI . 'assets/js/wow.min.js', array(), LERM_VERSION, true );
		// wp_register_script( 'login_js', LERM_URI . 'assets/js/wow.min.js', array(), LERM_VERSION, true );
		// enqueue script.
		if ( self::$args['cdn_jquery'] ) {
			wp_enqueue_script( 'jquery_cdn', self::$args['cdn_jquery'], array(), LERM_VERSION, true );
		}
		wp_enqueue_script( 'bootstrap' );
		wp_enqueue_script( 'lazyload' );
		wp_enqueue_script( 'lightbox' );

		if ( is_singular( 'post' ) ) {
			wp_enqueue_script( 'qrcode' );
			wp_enqueue_script( 'share' );

			if ( self::$args['enable_code_highlight'] ) {
				wp_enqueue_script( 'highlight' );
			}
		}
		wp_enqueue_script( 'wow' );
		wp_localize_script(
			'main-js',
			'adminajax',
			array(
				'url'      => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'ajax_nonce' ),
				'noposts'  => __( 'No older posts found', 'lerm' ),
				'posts'    => wp_json_encode( $wp_query->query_vars ), // everything about your loop is here.
				'loadmore' => __( 'Load more', 'lerm' ),
				'loading'  => '<i class="fa fa-spinner fa-spin me-1"></i>' . __( 'Loading...', 'lerm' ),
				'loggedin' => is_user_logged_in(),
			)
		);
		wp_enqueue_script( 'main-js' );
		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}
}
