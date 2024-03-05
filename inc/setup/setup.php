<?php // phpcs:disable WordPress.Files.FileName
/**
 * Lerm theme setup
 *
 * @package Lerm
 */

namespace Lerm\Inc;

use Lerm\Inc\User\User;
use Lerm\Inc\Traits\Singleton;

/**
 * Theme setup
 */
class Setup {

	use singleton;

	/**
	 * Global options.
	 *
	 * @var array $options
	 */
	public static $options = array();

	/**
	 * Default constants.
	 *
	 * @var array $args
	 */
	public static $args = array(
		'optimize_options' => array(),
		'mail_options'     => array(),
		'carousel_options' => array(),
		'super_optimize'   => array(),
		'seo_options'      => array(),
		'sitemap_options'  => array(),
	);

	/**
	 * Constructor
	 *
	 * @param array $params Optional parameters.
	 *
	 * @return void
	 */
	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_setup_', wp_parse_args( $params, self::$args ) );
		self::hooks();
	}

	/**
	 * Hooks
	 *
	 * @return void
	 */
	public static function hooks() {
		add_action( 'after_setup_theme', array( __CLASS__, 'setup' ), 2 );
		add_action( 'after_setup_theme', array( __CLASS__, 'content_width' ) );
		add_action( 'widgets_init', array( __CLASS__, 'register_sidebar' ) );
		add_action( 'widgets_init', array( __CLASS__, 'widgets' ) );
		add_filter( 'excerpt_length', array( __CLASS__, 'excerpt_length' ), 999 );
		add_filter( 'comment_excerpt_length', array( __CLASS__, 'comment_excerpt_length' ), 999 );
	}

	/**
	 * This function sets up support for various WordPress and framework functionality.
	 *
	 * @return void
	 */
	public static function setup() {

		// site title.
		add_theme_support( 'title-tag' );

		// site logo.
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 50,
				'flex-width'  => true,
				'flex-height' => true,
				'uploads'     => true,
				'header-text' => array( 'site-title', 'site-description' ),
			)
		);

		// Adds core WordPress HTML5 support.
		add_theme_support(
			'html5',
			array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'script', 'style' )
		);

		// Add support for full and wide align images.
		add_theme_support( 'align-wide' );

		// Feature.
		add_theme_support( 'post-thumbnails' );
		set_post_thumbnail_size( 200, 128 );
		add_image_size( 'home-thumb', 180, 110, false ); // 300 像素宽，无限的高
		add_image_size( 'widget-thumb', 120, 110, true ); // 300 像素宽，无限的高
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'custom-background' );
		add_theme_support( 'custom-header' );

		// Add support for link color control.
		add_theme_support( 'link-color' );

		// Post formats.
		add_theme_support(
			'post-formats',
			array( 'aside', 'audio', 'chat', 'image', 'gallery', 'link', 'quote', 'status', 'video' )
		);

		// Registers nav menu locations.
		register_nav_menus(
			array(
				'primary' => __( 'Primary', 'lerm' ),
				'mobile'  => __( 'Mobile', 'lerm' ),
				'social'  => __( 'Social Links Menu', 'lerm' ),
				'footer'  => __( 'Footer Menu', 'lerm' ),
			)
		);

		// Automatically add feed links to <head>.
		add_theme_support( 'automatic-feed-links' );

		// Add theme support for selective refresh for widgets.
		add_theme_support( 'customize-selective-refresh-widgets' );

		/**
		 * Make theme available for translation.
		 * Translations can be filed in the /languages/ directory
		 */
		load_theme_textdomain( DOMAIN, LERM_DIR . '/languages' );
	}

	/**
	 * Define a max content width to allow WordPress to properly resize your images
	 *
	 * @return void
	 */
	public static function content_width() {
		$GLOBALS['content_width'] = apply_filters( 'content_width', 1440 );
	}

	/**
	 * Displays the optional excerpt.
	 *
	 * @param array $length Optional parameters.
	 *
	 * @since Lerm 2.0
	 */
	public static function excerpt_length( $length ) {
		$length = lerm_options( 'excerpt_length' );
		return $length;
	}

	/**
	 * Displays the optional excerpt.
	 *
	 * @param array $length Optional parameters.
	 *
	 * @since Lerm 2.0
	 */
	public static function comment_excerpt_length( $length ) {
		$length = lerm_options( 'comment_excerpt_length' ) ? lerm_options( 'comment_excerpt_length' ) : 120;
		return $length;
	}

	/**
	 * Register sidebar.
	 *
	 * @return void
	 */
	public static function register_sidebar() {
		register_sidebar(
			array(
				'name'          => __( 'HomePage Sidebar', 'lerm' ),
				'id'            => 'home-sidebar',
				'description'   => __( 'Add widgets here to appear in your sidebar.', 'lerm' ),
				'before_widget' => '<section id="%1$s" class="card widget mb-3 %2$s loading-animate fadeIn">',
				'after_widget'  => '</section>',
				'before_title'  => '<h4 class="widget-title card-header border-bottom-0"><span class="wrap d-inline-block fa">',
				'after_title'   => '</span></h4>',
			)
		);

		register_sidebar(
			array(
				'name'          => __( 'Footer Sidebar', 'lerm' ),
				'id'            => 'footer-sidebar-right',
				'description'   => __( 'Add widgets here to appear in your sidebar.', 'lerm' ),
				'before_widget' => '<section id="%1$s" class="footer-widget %2$s loading-animate fadeIn">',
				'after_widget'  => '</section>',
				'before_title'  => '<h4 class="footer-widget-title"><span class="wrap d-inline-block fa">',
				'after_title'   => '</span></h4>',
			)
		);
	}

	/**
	 * Register custom widgets
	 *
	 * @return void
	 */
	public static function widgets() {
		// register_widget( '\Lerm\Inc\Widgets\Popular_Posts' );
		// register_widget( '\Lerm\Inc\Widgets\Recent_Posts' );
		// register_widget( '\Lerm\Inc\Widgets\Recent_Comments' );
	}
}