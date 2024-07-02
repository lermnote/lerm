<?php // phpcs:disable WordPress.Files.FileName
/**
 * Lerm theme setup
 *
 * @package Lerm
 */

namespace Lerm\Inc;

use Lerm\Inc\Traits\Singleton;

class Init {

	use Singleton;
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
		self::$args = apply_filters( 'lerm_init_args', wp_parse_args( $params, self::$args ) );
		self::set_options( $params );
		self::get_options( self::$args );
	}

	/**
	 * Get theme options.
	 *
	 * @param array $options Optional parameters.
	 *
	 * @return void
	 */
	public static function get_options( $args ) {

		Core\Setup::instance();
		Core\Enqueue::instance();

		// Optimize options.
		$params = array();
		if ( ! empty( $args['optimize_options'] ) ) {
			$params = $args['optimize_options'];
			Core\Optimize::instance( $params );
		}

		Ajax\PostLike::instance();
		Ajax\LoadMore::instance();
		Ajax\AjaxComment::instance();

		// SEO options.
		$params = array();
		if ( ! empty( $args['seo_options'] ) ) {
			$params = $args['seo_options'];
			Misc\SEO::instance( $params );
		}

		// Mail SMTP options.
		$params = array();
		if ( ! empty( $args['mail_options'] ) ) {
			$params = $args['mail_options'];
			Misc\SMTP::instance( $params );
		}

		// Sitemap options.
		$params = array();
		if ( ! empty( $args['sitemap_options'] ) ) {
			$params = $args['sitemap_options'];
			Misc\Sitemap::instance( $params );
		}

		// Theme update.
		$params = array();
		if ( ! empty( $args['updater_options'] ) ) {
			$params = $args['updater_options'];
			Misc\Updater::instance( $params );
		}

		// Lazyload::instance();
		// User::instance();
		// Image::instance();
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
			'gravatar_accel' => $options['super_gravatar'] ?? '',
			'admin_accel'    => $options['super_admin'] ?? '',
			'google_replace' => $options['super_googleapis'] ?? '',
			'super_optimize' => $options['super_optimize'] ?? '',
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

		// Set updater options.
		self::$args['updater_options'] = array(
			'name' => 'Lerm',                     // Theme Name.
			'repo' => 'lermnote/lerm',            // Theme repository.
			'slug' => 'lerm',                     // Theme Slug.
			'url'  => 'https://wplemon.com/gridd', // Theme URL.
			'ver'  => wp_get_theme()->get( 'Version' ), // Theme Version.
		);
	}
}
