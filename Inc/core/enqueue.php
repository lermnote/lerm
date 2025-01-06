<?php // phpcs:disable WordPress.Files.FileName
/**
 * Enqueue theme styles and scripts here
 *
 * @package  Lerm\Inc
 */

namespace Lerm\Inc\Core;

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

	public static $styles = array(
		'bootstrap'  => LERM_URI . 'assets/css/bootstrap.min.css',
		'lerm_font'  => LERM_URI . 'assets/css/lerm-icons.css',
		'animate'    => LERM_URI . 'assets/css/animate.min.css',
		'solarized'  => LERM_URI . 'assets/css/solarized-dark.min.css',
		'main_style' => LERM_URI . 'assets/css/main.css',
	);

	public static $scripts = array(
		'bootstrap' => LERM_URI . 'assets/js/bootstrap.bundle.min.js',
		'lazyload'  => LERM_URI . 'assets/js/lazyload.min.js',
		'share'     => LERM_URI . 'assets/js/social-share.min.js',
		'qrcode'    => LERM_URI . 'assets/js/qrcode.min.js',
		'highlight' => LERM_URI . 'assets/js/highlight.pack.js',
		'wow'       => LERM_URI . 'assets/js/wow.min.js',
		'main-js'   => LERM_URI . 'assets/js/main.js',
	);
	/**
	 * Constructor
	 *
	 * @param array $params Optional parameters.

	 * @return void
	 */
	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_assets_args', wp_parse_args( $params, self::$args ) );
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
		foreach ( self::$styles as $handle => $src ) {
			wp_enqueue_style( $handle, $src, array(), '1.0.0' );
		}

		if ( is_singular( 'post' ) && self::$args['enable_code_highlight'] ) {
			wp_enqueue_style( 'solarized', self::$styles['solarized'], array(), LERM_VERSION );
		}
	}

	/**
	 * Scripts enqueue.
	 *
	 * @return void
	 */
	public static function scripts() {
		foreach ( self::$scripts as $handle => $src ) {
			wp_register_script( $handle, $src, array(), '1.0.0', true );
		}

		if ( self::$args['cdn_jquery'] ) {
			wp_enqueue_script( 'jquery_cdn', self::$args['cdn_jquery'], array(), LERM_VERSION, true );
		}

		wp_enqueue_script( 'bootstrap' );
		wp_enqueue_script( 'lazyload' );

		if ( is_singular( 'post' ) ) {
			wp_enqueue_script( 'qrcode' );
			wp_enqueue_script( 'share' );

			if ( self::$args['enable_code_highlight'] ) {
				wp_enqueue_script( 'highlight' );
			}
		}

		wp_localize_script( 'main-js', 'lermData', apply_filters( 'lerm_l10n_data', array() ) );
		wp_enqueue_script( 'wow' );
		wp_enqueue_script( 'main-js' );

		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}
}
