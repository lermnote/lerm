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

	// Version for assets
	private const ASSET_VERSION = '1.0.0';
	// Base URI for theme assets
	private const LERM_URI = LERM_URI;

	/**
	 * Default constants.
	 *
	 * @since 2.1.0
	 * @var array $args Default value.
	 */
	private static $args = array(
		'enable_code_highlight' => true,
		'cdn_jquery'            => '',
	);

	private static $styles = array(
		'bootstrap'  => 'assets/css/bootstrap.min.css',
		'lerm_font'  => 'assets/css/lerm-icons.css',
		'animate'    => 'assets/css/animate.min.css',
		'solarized'  => 'assets/css/solarized-dark.min.css',
		'main_style' => 'assets/css/main.css',
	);

	public static $scripts = array(
		'bootstrap' => 'assets/js/bootstrap.bundle.min.js',
		'lazyload'  => 'assets/js/lazyload.min.js',
		'share'     => 'assets/js/social-share.min.js',
		'qrcode'    => 'assets/js/qrcode.min.js',
		'highlight' => 'assets/js/highlight.pack.js',
		'wow'       => 'assets/js/wow.min.js',
		'main-js'   => 'assets/js/main.js',
	);

	/**
	 * Constructor
	 *
	 * @param array $params Optional parameters.

	 * @return void
	 */
	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_assets_args', wp_parse_args( $params, self::$args ) );
		$this->hooks();
	}

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * Styles enqueue.
	 *
	 * @return void
	 */
	public static function enqueue_styles() {
		foreach ( self::$styles as $handle => $relative_path ) {
			$src = self::LERM_URI . $relative_path;
			wp_enqueue_style( $handle, $src, array(), self::ASSET_VERSION );
		}

		if ( is_singular( 'post' ) && self::$args['enable_code_highlight'] ) {
			wp_enqueue_style( 'solarized', self::$styles['solarized'], array(), self::ASSET_VERSION );
		}
	}

	/**
	 * Scripts enqueue.
	 *
	 * @return void
	 */
	public static function enqueue_scripts() {
		foreach ( self::$scripts as $handle => $relative_path ) {
			$src = self::LERM_URI . $relative_path;
			wp_register_script( $handle, $src, array(), self::ASSET_VERSION, true );
			wp_enqueue_script( $handle );

			// Apply defer or async for non-essential scripts
			if ( in_array( $handle, array( 'share', 'qrcode' ), true ) ) {
				wp_script_add_data( $handle, 'defer', true ); // Add defer for lazy loading
			}
		}

		if ( self::$args['cdn_jquery'] ) {
			wp_enqueue_script( 'jquery_cdn', self::$args['cdn_jquery'], array(), self::ASSET_VERSION, true );
		}

		if ( is_singular( 'post' ) ) {
			if ( self::$args['enable_code_highlight'] ) {
				wp_enqueue_script( 'highlight' );
			}
		}

		wp_localize_script( 'main-js', 'lermData', apply_filters( 'lerm_l10n_data', array() ) );

		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}
}
