<?php
/**
 * Lerm theme setup
 *
 * @package Lerm
 */

namespace Lerm\Inc;

use Lerm\Inc\Traits\Singleton;

class THEME_SETUP {

	use Singleton;

	public function __construct() {
		Init::get_instance();
		Enqueue::get_instance();
		Comment_Walker::get_instance();
		Carousel::get_instance();
		Mail::get_instance();
        Thumbnail::get_instance();
		$this->hooks();
	}

	public function hooks() {
		add_action( 'after_setup_theme', [ $this, 'setup' ], 2 );
		add_action( 'after_setup_theme', [ $this, 'content_width' ] );
		add_action( 'widgets_init', [ $this, 'register_sidebar' ] );
		add_action( 'widgets_init', [ $this, 'widgets' ] );
	}

	/**
	 * This function sets up support for various WordPress and framework functionality.
	 *
	 * @return void
	 */
	public function setup() {
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
	}

	/**
	 * Define a max content width to allow WordPress to properly resize your images
	 *
	 * @return void
	 */
	public function content_width() {
		$GLOBALS['content_width'] = apply_filters( 'content_width', 1440 );
	}

	/**
	 * Register sidebar.
	 *
	 * @return void
	 */
	public function register_sidebar() {
		register_sidebar(
			array(
				'name'          => __( 'HomePage Sidebar', 'lerm' ),
				'id'            => 'home-sidebar',
				'description'   => __( 'Add widgets here to appear in your sidebar.', 'lerm' ),
				'before_widget' => '<section id="%1$s" class="card border-0 widget mb-3 %2$s loading-animate fadeInUp">',
				'after_widget'  => '</section>',
				'before_title'  => '<h4 class="widget-title card-header"><span class="wrap d-inline-block fa">',
				'after_title'   => '</span></h4>',
			)
		);
		$widgets = lerm_options( 'register_sidebars' );
		if ( isset( $widgets ) && ! empty( $widgets ) ) {
			foreach ( $widgets as $key => $value ) {
				$sidebar_id = 'sidebar' . preg_replace( '/ /', '', Chinese_to_PY::getPY( $value['sidebar_title'], 'all' ) );
				if ( ! empty( $value['sidebar_title'] ) ) {
					register_sidebar(
						array(
							'name'          => $value['sidebar_title'],
							'id'            => $sidebar_id,
							'description'   => __( 'Cutsom register sidebar', 'lerm' ),
							'before_widget' => '<section id="%1$s" class="card widget mb-3 %2$s">',
							'after_widget'  => '</section>',
							'before_title'  => '<h4 class="widget-title card-header"><span class="wrap d-inline-block fa">',
							'after_title'   => '</span></h4>',
						)
					);
				}
			}
		}
		$footer_sidebars_count = (int) lerm_options( 'footer_sidebars_count' );
		register_sidebars(
			$footer_sidebars_count,
			array(
				/* translators: %d: number of sidebar*/
				'name'          => __( 'Sidebar %d', 'lerm' ),
				'id'            => 'footer-sidebar',
				'description'   => 'Sidebar show  ',
				'class'         => '',
				'before_widget' => '<section id="%1$s" class="footer-widget %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h4 class="footer-widget-title">',
				'after_title'   => '</h4>',
			)
		);
	}

	/**
	 * Register custom widgets
	 *
	 * @return void
	 */
	public function widgets() {
		register_widget( '\Lerm\Inc\Widgets\Popular_Posts' );
		register_widget( '\Lerm\Inc\Widgets\Recent_Posts' );
		register_widget( '\Lerm\Inc\Widgets\Recent_Comments' );
	}
}
