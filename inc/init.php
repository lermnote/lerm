<?php // phpcs:disable WordPress.Files.FileName
/**
 * Lerm theme setup
 *
 * @package Lerm
 */

namespace Lerm\Inc;

use Lerm\Inc\User\User;
use Lerm\Inc\Traits\Singleton;

class Init {

	use Singleton;

	/**
	 * Default constants.
	 *
	 * @var array $args
	 */
	public static $args = array();

	/**
	 * Constructor
	 *
	 * @param array $params Optional parameters.
	 *
	 * @return void
	 */
	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_init_args', wp_parse_args( $params, self::$args ) );
		self::hooks();
	}

	/**
	 * Get theme options.
	 *
	 * @param array $options Optional parameters.
	 *
	 * @return void
	 */
	public static function get_options( $options = array() ) {

		if ( ! isset( $options ) || empty( $options ) ) {
			return;
		}

		// optimize.
		self::$args['optimize_options'] = array(
			'gravatar_accel' => $options['super_gravatar'],
			'admin_accel'    => $options['super_admin'],
			'google_replace' => $options['super_googleapis'],
			'super_optimize' => $options['super_optimize'],
		);

		// smtp.
		self::$args['mail_options'] = array(
			'email_notice' => $options['email_notice'],
			'smtp_options' => $options['smtp_options'],
		);

		// seo.
		self::$args['seo_options'] = array(
			'baidu_submit' => $options['baidu_submit'],
			'submit_url'   => $options['submit_url'],
			'submit_token' => $options['submit_token'],
			'post_urls'    => array(),
			'separator'    => $options['title_sep'],
			'html_slug'    => $options['html_slug'],
			'keywords'     => array(),
			'description'  => array(),
		);

		// sitemap.
		self::$args['sitemap_options'] = array(
			'sitemap_enable' => $options['sitemap_enable'],
			'post_type'      => $options['exclude_post_types'],
			'post_exclude'   => $options['exclude_post'],
			'page_exclude'   => $options['exclude_page'],
		);

		// sitemap.
		// todo
		self::$args['updater_options'] = array(
			'name' => 'Lerm',                     // Theme Name.
			'repo' => 'lermnote/lerm',             // Theme repository.
			'slug' => 'lerm',                     // Theme Slug.
			'url'  => 'https://wplemon.com/gridd', // Theme URL.
			'ver'  => wp_get_theme()->get( 'Version' ), // Theme Version.
		);

		// Optimize options.
		$params = array();
		if ( ! empty( self::$args['optimize_options'] ) ) {
			$params = self::$args['optimize_options'];
			Optimize::instance( $params );
		}

		// Mail SMTP options.
		$params = array();
		if ( ! empty( self::$args['mail_options'] ) ) {
			$params = self::$args['mail_options'];
			SMTP::instance( $params );
		}

		// SEO options.
		$params = array();
		if ( ! empty( self::$args['seo_options'] ) ) {
			$params = self::$args['seo_options'];
			SEO::instance( $params );
		}

		// Sitemap options.
		$params = array();
		if ( ! empty( self::$args['sitemap_options'] ) ) {
			$params = self::$args['sitemap_options'];
			Sitemap::instance( $params );
		}

		// Theme update.
		$params = array();
		if ( ! empty( self::$args['updater_options'] ) ) {
			$params = self::$args['updater_options'];
			Updater::instance( $params );
		}

		Enqueue::instance();
		AjaxComment::instance();
		LoadMore::instance();
		PostLike::instance();
		Lazyload::instance();
		User::instance();
		Image::instance();
	}
}
