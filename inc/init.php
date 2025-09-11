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

namespace Lerm\Inc;

use Lerm\Inc\Traits\Singleton;

// Preferred REST controllers (short import names for readability)
use Lerm\Inc\Rest\PostLikeRestController;
use Lerm\Inc\Rest\LoadMoreRestController;
use Lerm\Inc\Rest\CommentRestController;

// Legacy Ajax fallbacks (aliased to avoid name collisions and improve clarity)
use Lerm\Inc\Ajax\PostLike as AjaxPostLike;
use Lerm\Inc\Ajax\LoadMore as AjaxLoadMore;
use Lerm\Inc\Ajax\AjaxComment as AjaxComment;
use Lerm\Inc\Ajax\AjaxRegist as AjaxRegist;
use Lerm\Inc\Ajax\AjaxReset as AjaxReset;
use Lerm\Inc\Ajax\UserProfile as AjaxUserProfile;

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
		self::set_options( $params );
		self::get_options( self::$args );
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
		Core\Setup::instance();
		Core\Enqueue::instance();

		// Optimize options.
		$params = array();
		if ( ! empty( $args['optimize_options'] ) ) {
			$params = $args['optimize_options'];
			Core\Optimize::instance( $params );
		}

		// SEO options.
		$params = array();
		if ( ! empty( $args['seo_options'] ) ) {
			$params = $args['seo_options'];
			Misc\Seo::instance( $params );
		}

		// Mail SMTP options.
		$params = array();
		if ( ! empty( $args['mail_options'] ) ) {
			$params = $args['mail_options'];
			Misc\Smtp::instance( $params );
		}

		// Sitemap options.
		$params = array();
		if ( ! empty( $args['sitemap_options'] ) ) {
			$params = $args['sitemap_options'];
			Misc\Sitemap::instance( $params );
		}

		// Custom options.
		$params = array();
		if ( ! empty( $args['custom_options'] ) ) {
			$params = $args['custom_options'];
			Misc\Custom::instance( $params );
		}

		// Theme updater options.
		$params = array();
		if ( ! empty( $args['updater_options'] ) ) {
			$params = $args['updater_options'];
			Misc\Updater::instance( $params );
		}

		// Front login.
		// $params = array();
		// if ( ! empty( $args['front_login_options'] ) ) {
		// 	$params = $args['front_login_options'];
		// 	Ajax\AjaxLogin::instance( $params );
		// }

		/*
		 * Instantiate controllers:
		 * - Prefer REST controllers (PostLikeRestController, LoadMoreRestController, CommentRestController)
		 * - If not present (for backward compatibility), fallback to legacy Ajax handlers
		 *
		 * Use class_exists with ::class so autoloader can be triggered.
		 */
		if ( class_exists( PostLikeRestController::class ) ) {
			PostLikeRestController::instance();
		} elseif ( class_exists( AjaxPostLike::class ) ) {
			AjaxPostLike::instance();
		}

		if ( class_exists( LoadMoreRestController::class ) ) {
			LoadMoreRestController::instance();
		} elseif ( class_exists( AjaxLoadMore::class ) ) {
			AjaxLoadMore::instance();
		}

		if ( class_exists( CommentRestController::class ) ) {
			CommentRestController::instance();
		} elseif ( class_exists( AjaxComment::class ) ) {
			AjaxComment::instance();
		}

		// Keep other legacy Ajax services (these didn't have REST replacements in this pass).
		// if ( class_exists( AjaxRegist::class ) ) {
		// 	AjaxRegist::instance();
		// } else {
		// 	// fallback string check in case autoload alias not available
		// 	if ( class_exists( '\\Lerm\\Inc\\Ajax\\AjaxRegist' ) ) {
		// 		\Lerm\Inc\Ajax\AjaxRegist::instance();
		// 	}
		// }

		// if ( class_exists( AjaxReset::class ) ) {
		// 	AjaxReset::instance();
		// } else {
		// 	if ( class_exists( '\\Lerm\\Inc\\Ajax\\AjaxReset' ) ) {
		// 		\Lerm\Inc\Ajax\AjaxReset::instance();
		// 	}
		// }

		// Other core components (unchanged)
		Core\CommentWalker::instance();

		// if ( class_exists( AjaxUserProfile::class ) ) {
		// 	AjaxUserProfile::instance();
		// } else {
		// 	if ( class_exists( '\\Lerm\\Inc\\Ajax\\UserProfile' ) ) {
		// 		\Lerm\Inc\Ajax\UserProfile::instance();
		// 	}
		// }

		Misc\OpenGraph::instance();
	}

	/**
	 * Set theme options.
	 *
	 * @param array $options Theme options.
	 *
	 * @return void
	 */
	private static function set_options( $options ) {
		if ( ! isset( $options ) || empty( $options ) ) {
			return;
		}

		// Set optimize options
		self::$args['optimize_options'] = array(
			'gravatar_accel'   => $options['super_gravatar'] ?? '',
			'admin_accel'      => $options['super_admin'] ?? '',
			'google_replace'   => $options['super_googleapis'] ?? '',
			'super_optimize'   => $options['super_optimize'] ?? '',
			'disable_pingback' => $options['disable_pingback'] ?? '',
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
			'sitemap_enable' => $options['sitemap_enable'] ?? '',
			'post_type'      => $options['exclude_post_types'] ?? array(),
			'post_exclude'   => $options['exclude_post'] ?? array(),
			'page_exclude'   => $options['exclude_page'] ?? array(),
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

		// Front login options.
		self::$args['front_login_options'] = array(
			'front_login_enable'  => $options['frontend_lgoin'],
			'login_page_id'       => $options['frontend_lgoin_page'],
			'menu_login_item'     => $options['menu_login_item'],
			'login_redirect_url'  => '',
			'logout_redirect_url' => 'home_url()',
		);
	}
}
