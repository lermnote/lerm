<?php // phpcs:disable WordPress.Files.FileName
/**
 * Class Sitemap
 *
 * @package Lerm\Inc
 */

namespace Lerm\Inc;

use Lerm\Inc\Traits\Singleton;

class Sitemap {

	use singleton;

	/**
	 * Default arguments.
	 *
	 * @var array
	 */
	public static $args = array(
		'sitemap_enable' => true,
		'max_urls'       => 6666,
		'post_type'      => array(),
		'post_exclude'   => array(),
		'page_exclude'   => array(),
	);

	/**
	 * Constructor.
	 *
	 * @param array $params
	 */
	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_sitemap_', wp_parse_args( $params, self::$args ) );
		self::hooks();
	}

	/**
	 * Initialize hooks.
	 */
	public static function hooks() {
		if ( self::$args['sitemap_enable'] ) {

			add_filter( 'wp_sitemaps_posts_entry', array( __CLASS__, 'add_tag' ), 10, 2 );
			add_filter( 'wp_sitemaps_max_urls', array( __CLASS__, 'max_urls' ) );

			if ( is_array( self::$args['post_type'] ) && in_array( 'users', self::$args['post_type'], true ) ) {
				add_filter( 'wp_sitemaps_add_provider', array( __CLASS__, 'remove_user' ), 10, 2 );
			}

			add_filter( 'wp_sitemaps_post_types', array( __CLASS__, 'remove_post_type' ) );
			add_filter( 'wp_sitemaps_taxonomies', array( __CLASS__, 'remove_post_type' ) );

			if ( ! empty( self::$args['post_exclude'] ) || ! empty( self::$args['page_exclude'] ) ) {
				add_filter( 'wp_sitemaps_posts_query_args', array( __CLASS__, 'exclude_post' ), 10, 2 );
			}
		} else {
			add_filter( 'wp_sitemaps_enabled', '__return_false' );
		}
	}

	/**
	 * Enable sitemap.
	 *
	 * @param bool $enabled
	 * @return bool
	 */
	public function enable_sitemap( $enabled ) {
		return self::$args['sitemap_enable'] && $enabled;
	}

	/**
	 * Remove user from sitemap.
	 *
	 * @param mixed  $provider
	 * @param string $name
	 * @return mixed
	 */
	public function remove_user( $provider, $name ) {
		return 'users' === $name ? false : $provider;
	}

	public static function remove_post_type( $post_type ) {
		if ( isset( self::$args['post_type'] ) && ! empty( self::$args['post_type'] ) ) {
			foreach ( self::$args['post_type'] as $key => $value ) {
				unset( $post_type[ $value ] );
			}
		}
		return $post_type;
	}

	public static function remove_taxonomies( $taxonomies ) {
		if ( isset( self::$args['taxonomy'] ) && ! empty( self::$args['taxonomy'] ) ) {
			foreach ( self::$args['taxonomy'] as $key => $value ) {
				unset( $taxonomies[ $value ] );
			}
		}
		return $taxonomies;
	}

	public static function exclude_post( $args, $post_type ) {
		if ( 'post' !== $post_type || empty( self::$args['post_exclude'] ) ) {
			return $args;
		}

		$args['post__not_in'] = array_merge(
			isset( $args['post__not_in'] ) ? $args['post__not_in'] : array(),
			self::$args['post_exclude']
		);

		return $args;
	}

	public static function max_urls() {
		return self::$args['max_urls'];
	}

	/**
	 * Add tags to sitemap entry.
	 *
	 * @param array    $entry
	 * @param \WP_Post $post
	 * @return array
	 */
	public function add_tags( $entry, $post ) {
		$entry['lastmod']    = gmdate( DATE_W3C, strtotime( $post->post_modified_gmt ) );
		$entry['changefreq'] = 'Daily';
		$entry['priority']   = '0.6';
		return $entry;
	}
}
