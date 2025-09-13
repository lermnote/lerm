<?php // phpcs:disable WordPress.Files.FileName
/**
 * Lerm theme setup (refactored)
 *
 * - uses top-level `use` imports for REST controllers and legacy Ajax fallbacks
 * - keeps backward-compatible fallback behavior (REST preferred, Ajax fallback)
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm;

use Lerm\Traits\Singleton;

use Lerm\Core\Setup;
use Lerm\Core\Enqueue;
use Lerm\Core\CommentWalker;
use Lerm\Core\Optimizer;
use Lerm\Helpers\{Seo, Smtp, Sitemap, Updater, Customizer, OpenGraph};
use Lerm\Http\{PostLikeController, LoadMoreController, CommentController, PageController};

class Init {
	use Singleton;

	/**
	 * Default constants.
	 *
	 * @var array $args
	 */
	public static $args = array(
		'optimize_options'    => array(),
		'mail_options'        => array(),
		'carousel_options'    => array(),
		'super_optimize'      => array(),
		'seo_options'         => array(),
		'sitemap_options'     => array(),
		'custom_options'      => array(),
		'updater_options'     => array(),
		'front_login_options' => array(),
	);

	/**
	 * Constructor
	 *
	 * @param array $params Optional parameters.
	 *
	 * @return void
	 */
	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_init_args', wp_parse_args( $params, self::$args ) );

		self::set_options( self::$args );
		self::get_options( self::$args );
	}

	/**
	 * Set theme options.
	 *
	 * @param array $options Theme options.
	 *
	 * @return void
	 */
	private static function set_options( $options ) {
		if ( empty( $options ) ) {
			return;
		}
		// Set optimize options
		self::$args['optimize_options'] = array(
			'gravatar_accel'   => $options['super_gravatar'] ?? '',
			'admin_accel'      => (bool) ( $options['super_admin'] ?? false ),
			'google_replace'   => $options['super_googleapis'] ?? '',
			'super_optimize'   => $options['super_optimize'] ?? array(),
			'disable_pingback' => (bool) ( $options['disable_pingback'] ?? false ),
		);

		// Set mail options.
		self::$args['mail_options'] = array(
			'email_notice' => $options['email_notice'] ?? '',
			'smtp_options' => $options['smtp_options'] ?? array(),
		);

		// Set SEO options.
		self::$args['seo_options'] = array(
			'baidu_submit' => $options['baidu_submit'] ?? '',
			'submit_url'   => $options['submit_url'] ?? '',
			'submit_token' => $options['submit_token'] ?? '',
			'post_urls'    => array(),
			'separator'    => $options['title_sep'] ?? '',
			'html_slug'    => $options['html_slug'] ?? '',
			'keywords'     => array(),
			'description'  => array(),
		);

		//Set sitemap options.
		self::$args['sitemap_options'] = array(
			'sitemap_enable' => (bool) ( $options['sitemap_enable'] ?? false ),
			'post_type'      => (array) ( $options['exclude_post_types'] ?? array() ),
			'post_exclude'   => (array) ( $options['exclude_post'] ?? array() ),
			'page_exclude'   => (array) ( $options['exclude_page'] ?? array() ),
		);

		// custom options.
		self::$args['custom_options'] = array(
			'large_logo'    => $options['large_logo'] ?? '',
			'mobile_logo'   => $options['mobile_logo'] ?? '',
			'content_width' => $options['content_width'] ?? '',
			'sidebar_width' => $options['sidebar_width'] ?? '',
			'custom_css'    => $options['custom_css'] ?? '',
		);

		// Set updater options.
		self::$args['updater_options'] = array(
			'name' => 'Lerm',                     // Theme Name.
			'repo' => 'lermnote/lerm',            // Theme repository.
			'slug' => 'lerm',                     // Theme Slug.
			'url'  => 'https://wplemon.com/gridd', // Theme URL.
			'ver'  => wp_get_theme()->get( 'Version' ), // Theme Version.
		);

		/*
		 * Front login options.
		 * NOTE: accept both 'frontend_lgoin' (legacy typo) and 'frontend_login' for backward compatibility.
		 */
		$front_login_enable = $options['frontend_login'] ?? $options['frontend_lgoin'] ?? false;
		$front_login_page   = $options['frontend_login_page'] ?? $options['frontend_lgoin_page'] ?? 0;

		self::$args['front_login_options'] = array(
			'front_login_enable'  => (bool) $front_login_enable,
			'login_page_id'       => $front_login_page,
			'menu_login_item'     => $options['menu_login_item'] ?? '',
			'login_redirect_url'  => $options['login_redirect_url'] ?? '',
			'logout_redirect_url' => $options['logout_redirect_url'] ?? home_url(),
		);
	}
	/**
	 * Get theme options and instantiate services / controllers.
	 *
	 * Prefer REST controllers if available; fallback to legacy Ajax handlers for compatibility.
	 *
	 * @param array $args Optional parameters.
	 *
	 * @return void
	 */
	public static function get_options( $args ) {
		// Core initialization (keeps previous behavior)
		if ( class_exists( Setup::class ) ) {
			Setup::instance();
		}

		if ( class_exists( Enqueue::class ) ) {
			Enqueue::instance();
		}

		if ( class_exists( CommentWalker::class ) ) {
			CommentWalker::instance();
		}

		// Optimize options.
		if ( ! empty( $args['optimize_options'] ) && class_exists( Optimizer::class ) ) {
			Optimizer::instance( (array) $args['optimize_options'] );
		}

		// SEO options.
		if ( ! empty( $args['seo_options'] ) && class_exists( Seo::class ) ) {
			Seo::instance( (array) $args['seo_options'] );
		}

		// Mail SMTP options.
		if ( ! empty( $args['mail_options'] ) && class_exists( Smtp::class ) ) {
			Smtp::instance( (array) $args['mail_options'] );
		}

		// Sitemap options.
		if ( ! empty( $args['sitemap_options'] ) && class_exists( Sitemap::class ) ) {
			Sitemap::instance( (array) $args['sitemap_options'] );
		}

		// Custom options.
		if ( ! empty( $args['custom_options'] ) && class_exists( Customizer::class ) ) {
			Customizer::instance( (array) $args['custom_options'] );
		}

		// Theme updater options.
		if ( ! empty( $args['updater_options'] ) && class_exists( Updater::class ) ) {
			Updater::instance( (array) $args['updater_options'] );
		}

		/*
		 * Instantiate controllers:
		 * - Prefer REST controllers (PostLikeRestController, LoadMoreRestController, CommentRestController)
		 * - If not present (for backward compatibility), fallback to legacy Ajax handlers
		 *
		 * Use class_exists with ::class so autoloader can be triggered.
		 */
		if ( class_exists( PostLikeController::class ) ) {
			PostLikeController::instance();
		}

		if ( class_exists( LoadMoreController::class ) ) {
			LoadMoreController::instance();
		}

		if ( class_exists( CommentController::class ) ) {
			CommentController::instance();
		}
		// if ( class_exists( PageController::class ) ) {
		//  PageController::instance();
		// }

		// OpenGraph (helper) - only instantiate if present
		if ( class_exists( OpenGraph::class ) ) {
			OpenGraph::instance();
		}
	}
}
