<?php // phpcs:disable WordPress.Files.FileName
/**
 * Lerm theme setup
 *
 * @package Lerm
 * @since 1.0.0
 */

declare( strict_types = 1 );

namespace Lerm\Core;

use Lerm\Traits\Singleton;

/**
 * Theme setup
 */
class Setup {
	use Singleton;

	/**
	 * Global options.
	 *
	 * @var array
	 */
	public static array $options = array();

	/**
	 * Default constants.
	 *
	 * @var array
	 */
	public static array $args = array(
		'excerpt_length'         => 100,
		'comment_excerpt_length' => 100,
		'content_width'          => 1440,
	);

	/**
	 * Constructor.
	 *
	 * @param array $params Optional parameters.
	 */
	public function __construct( array $params = array() ) {
		self::$args = (array) apply_filters( 'lerm_setup_args', wp_parse_args( $params, self::$args ) );
		self::hooks();
	}

	/**
	 * Hooks.
	 */
	public static function hooks(): void {
		add_action( 'after_setup_theme', array( __CLASS__, 'setup' ), 2 );

		add_filter( 'frontpage_template', array( __CLASS__, 'front_page_template' ), 15, 1 );
		add_filter( 'pre_option_link_manager_enabled', '__return_true' );

		add_filter( 'excerpt_length', array( __CLASS__, 'excerpt_length' ), 999 );
		add_filter( 'excerpt_more', array( __CLASS__, 'excerpt_more' ), 999 );
		add_filter( 'the_content', array( __CLASS__, 'remove_empty_p' ), 20 );

		add_action( 'widgets_init', array( __CLASS__, 'register_sidebar' ) );

		add_filter( 'tag_cloud_sort', array( __CLASS__, 'tag_cloud_sort' ), 10, 2 );
	}

	/**
	 * This function sets up support for various WordPress and framework functionality.
	 */
	public static function setup(): void {
		// Site title.
		add_theme_support( 'title-tag' );

		// Site logo.
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

		// Post thumbnails.
		add_theme_support( 'post-thumbnails' );
		set_post_thumbnail_size( 200, 128 );
		add_image_size( 'home-thumb', 180, 110, false );
		add_image_size( 'widget-thumb', 120, 110, true );
		add_image_size( 'featured-thumb', 800, 600, true );

		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'custom-background' );
		add_theme_support( 'custom-header' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'customize-selective-refresh-widgets' );

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

		// Make theme available for translation.
		load_theme_textdomain( LERM_DOMAIN, LERM_DIR . '/languages' );

		// Define a max content width to allow WordPress to properly resize your images.
		$GLOBALS['content_width'] = (int) apply_filters( 'content_width', self::$args['content_width'] );
	}
	/**
	 * Use front-page.php when Front page displays is set to a static page.
	 *
	 * @param string $template The template to be used.
	 * @return string The template to be used.
	 */
	public static function front_page_template( string $template ): string {
		return is_home() ? '' : $template;
	}

	/**
	 * Displays the optional excerpt length.
	 *
	 * @param int $length Current excerpt length.
	 * @return int New excerpt length.
	 */
	public static function excerpt_length( int $length ): int {
		return (int) self::$args['excerpt_length'] ?? $length;
	}

	/**
	 * Customize the excerpt more text.
	 *
	 * @param string $more Default more text.
	 * @return string Custom more text.
	 */
	public static function excerpt_more( string $more ): string {
		return '...';
	}

	/**
	 * Remove empty p tags from content.
	 *
	 * @param string $content Post content.
	 * @return string Cleaned content.
	 */
	public static function remove_empty_p( string $content ): string {
		return preg_replace( '/<p>\s*<\/p>/', '', $content );
	}

	/**
	 * Register sidebars.
	 */
	public static function register_sidebar(): void {
		register_sidebar(
			array(
				'name'          => __( 'Home Sidebar', 'lerm' ),
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
				'id'            => 'footer-sidebar',
				'description'   => __( 'Add widgets here to appear in footer area.', 'lerm' ),
				'before_widget' => '<section id="%1$s" class="footer-widget %2$s loading-animate fadeIn">',
				'after_widget'  => '</section>',
				'before_title'  => '<h4 class="footer-widget-title"><span class="wrap d-inline-block fa">',
				'after_title'   => '</span></h4>',
			)
		);
	}

	/**
	 * Custom tags cloud sort.
	 *
	 * @param array $args Tag cloud arguments.
	 * @return array Modified tag cloud arguments.
	 */
	public static function tag_cloud_sort( array $args ): array {
		$args['orderby'] = 'count';
		$args['order']   = 'DESC';
		return $args;
	}
}
