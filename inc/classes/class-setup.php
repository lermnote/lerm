<?php
/**
 * Lerm theme setup
 *
 * @package Lerm
 */

namespace Lerm\Inc;

class Setup {

	// Default constants
	public static $args = array(
		'optimize_options' => array(),
		'mail_options'     => array(),
		'carousel_options' => array(),
		'super_optimize'   => array(),
		'seo_options'      => array(),
		'sitemap_options'  => array(),
	);

	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_setup_', wp_parse_args( $params, self::$args ) );
		self::hooks();
	}

	// instance
	public static function instance( $params = array() ) {
		return new self( $params );
	}

	public static function hooks() {
		add_action( 'after_setup_theme', array( '\Lerm\Inc\Setup', 'setup' ), 2 );
		add_action( 'after_setup_theme', array( '\Lerm\Inc\Setup', 'content_width' ) );
		add_action( 'widgets_init', array( '\Lerm\Inc\Setup', 'register_sidebar' ) );
		add_action( 'widgets_init', array( '\Lerm\Inc\Setup', 'widgets' ) );
	}

	/**
	 * This function sets up support for various WordPress and framework functionality.
	 *
	 * @return void
	 */
	public static function setup() {

		Enqueue::instance();
		Comment_Walker::instance();
		Load_More::instance();

		// Automatically add feed links to <head>.
		add_theme_support( 'automatic-feed-links' );

		// site title
		add_theme_support( 'title-tag' );

		// site logo
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

		// Feature
		add_theme_support( 'post-thumbnails' );
		set_post_thumbnail_size( 200, 128 );
		add_image_size( 'home-thumb', 180, 110, true ); // 300 像素宽，无限的高

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
		// Add theme support for selective refresh for widgets.
		add_theme_support( 'customize-selective-refresh-widgets' );

		/**
		 * Make theme available for translation.
		 * Translations can be filed in the /languages/ directory
		 */
		load_theme_textdomain( DOMAIN, LERM_DIR . '/languages' );

		// Optimize options
		$params = array();
		if ( ! empty( self::$args['optimize_options'] ) ) {
			$params = self::$args['optimize_options'];
			Optimize::instance( $params );
		}

		// Mail SMTP options
		$params = array();
		if ( ! empty( self::$args['mail_options'] ) ) {
			$params = self::$args['mail_options'];
			SMTP::instance( $params );
		}

		// SEO options
		$params = array();
		if ( ! empty( self::$args['seo_options'] ) ) {
			$params = self::$args['seo_options'];
			SEO::instance( $params );
		}

		// Sitemap options
		$params = array();
		if ( ! empty( self::$args['sitemap_options'] ) ) {
			$params = self::$args['sitemap_options'];
			Sitemap::instance( $params );
		}
	}

	/**
	 * Get theme options
	 *
	 * @return void
	 */
	public static function get_options( $options = array() ) {
		$options = get_option( 'lerm_theme_options' );

		// optimize
		self::$args['optimize_options'] = array(
			'gravatar_accel' => $options['super_gravatar'],
			'admin_accel'    => $options['super_admin'],
			'google_replace' => $options['super_googleapis'],
			'super_optimize' => $options['super_optimize'],
		);

		// smtp
		self::$args['mail_options'] = array(
			'email_notice' => $options['email_notice'],
			'smtp_options' => $options['smtp_options'],
		);

		// seo
		self::$args['seo_options'] = array(
			'baidu_submit' => $options['baidu_submit'],
			'submit_url'   => $options['submit_url'],
			'submit_token' => $options['submit_token'],
			'post_urls'    => array(),
			'separator'    => $options['title_sep'],
			'keywords'     => array(),
			'description'  => array(),
		);

		// sitemap
		self::$args['sitemap_options'] = array(
			'sitemap_enable' => $options['sitemap_enable'],
			'post_type'      => $options['exclude_post_types'],
			'post_exclude'   => $options['exclude_post'],
			'page_exclude'   => $options['exclude_page'],
		);
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
				'before_widget' => '<section id="%1$s" class="card border-0 widget mb-3 %2$s loading-animate fadeIn">',
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
		register_widget( '\Lerm\Inc\Widgets\Popular_Posts' );
		register_widget( '\Lerm\Inc\Widgets\Recent_Posts' );
		register_widget( '\Lerm\Inc\Widgets\Recent_Comments' );
	}
}
