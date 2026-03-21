<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

/**
 * WordPress sitemap configuration and customization.
 *
 * Provides control over WordPress core sitemaps, including URL limits,
 * post type and taxonomy inclusion/exclusion, and custom sitemap entry attributes.
 *
 * @package Lerm
 */
namespace Lerm\SEO;

use Lerm\Traits\Singleton;

class Sitemap {
	use Singleton;

	/**
	 * Default sitemap configuration.
	 *
	 * @var array Default settings for sitemap customization.
	 */
	public static $args = array(
		'sitemap_enable' => true,
		'max_urls'       => 6666,
		'post_type'      => array(),
		'taxonomy'       => array(),
		'post_exclude'   => array(),
		'page_exclude'   => array(),
	);

	/**
	 * Constructor.
	 *
	 * @param array $params Optional parameters to override default settings.
	 */
	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_sitemap_args', wp_parse_args( $params, self::$args ) );
		$this->hooks();
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public static function hooks() {
		if ( ! self::$args['sitemap_enable'] ) {
			add_filter( 'wp_sitemaps_enabled', '__return_false' );
			return;
		}

		add_filter( 'wp_sitemaps_posts_entry', array( __CLASS__, 'add_tags' ), 10, 2 );
		add_filter( 'wp_sitemaps_max_urls', array( __CLASS__, 'max_urls' ) );

		// Exclude specified post types
		if ( ! empty( self::$args['post_type'] ) ) {
			add_filter( 'wp_sitemaps_post_types', array( __CLASS__, 'remove_post_type' ) );
		}

		if ( ! empty( self::$args['taxonomy'] ) ) {
			add_filter( 'wp_sitemaps_taxonomies', array( __CLASS__, 'remove_taxonomies' ) );
		}

		// Remove users from sitemap (if 'users' is in post_type array)
		if ( in_array( 'users', (array) self::$args['post_type'], true ) ) {
			add_filter( 'wp_sitemaps_add_provider', array( __CLASS__, 'remove_user' ), 10, 2 );
		}

		// Exclude specific post/page IDs
		if ( ! empty( self::$args['post_exclude'] ) || ! empty( self::$args['page_exclude'] ) ) {
			add_filter( 'wp_sitemaps_posts_query_args', array( __CLASS__, 'exclude_post' ), 10, 2 );
		}
	}

	/**
	 * Enable or disable sitemap functionality.
	 *
	 * @param bool $enabled Current enabled state.
	 * @return bool Whether sitemap should be enabled.
	 */
	public function enable_sitemap( $enabled ): bool {
		return self::$args['sitemap_enable'] && $enabled;
	}

	/**
	 * Remove users provider from sitemap.
	 *
	 * @param mixed  $provider The sitemap provider object.
	 * @param string $name     The provider name.
	 * @return mixed|null Returns false for users provider, otherwise returns original provider.
	 */
	public function remove_user( $provider, string $name ) {
		return 'users' === $name ? false : $provider;
	}

	/**
	 * Remove specified post types from sitemap.
	 *
	 * @param array $post_type List of registered post types.
	 * @return array Filtered post types list.
	 */
	public static function remove_post_type( array $post_type ): array {
		foreach ( (array) self::$args['post_type'] as $key => $value ) {
			unset( $post_type[ $value ] );
		}
		return $post_type;
	}

	/**
	 * Remove specified taxonomies from sitemap.
	 *
	 * @param array $taxonomies List of registered taxonomies.
	 * @return array Filtered taxonomies list.
	 */
	public static function remove_taxonomies( array $taxonomies ): array {
		foreach ( self::$args['taxonomy'] as $key => $value ) {
			unset( $taxonomies[ $value ] );
		}
		return $taxonomies;
	}

	/**
	 * Exclude specific posts/pages from sitemap query.
	 *
	 * @param array  $args      Query arguments for sitemap posts.
	 * @param string $post_type The post type being queried.
	 * @return array Modified query arguments.
	 */
	public static function exclude_post( array $args, string $post_type ): array {
		$exclude = array();

		if ( 'post' === $post_type && ! empty( self::$args['post_exclude'] ) ) {
			$exclude = array_map( 'absint', (array) self::$args['post_exclude'] );
		}

		if ( 'page' === $post_type && ! empty( self::$args['page_exclude'] ) ) {
			$exclude = array_map( 'absint', (array) self::$args['page_exclude'] );
		}

		if ( ! empty( $exclude ) ) {
			$existing            = isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array();
			$args['post__not_in'] = array_unique( array_merge( $existing, $exclude ) );
		}

		return $args;
	}

	/**
	 * Set maximum URLs per sitemap.
	 *
	 * @return int Maximum number of URLs per sitemap file.
	 */
	public static function max_urls(): int {
		return (int) self::$args['max_urls'];
	}

	/**
	 * Add custom tags to sitemap entry.
	 *
	 * Sets last modification date, change frequency, and priority for each entry.
	 *
	 * @param array    $entry Sitemap entry data.
	 * @param \WP_Post $post  The post object.
	 * @return array Modified sitemap entry.
	 */
	public function add_tags( array $entry, \WP_Post $post ): array {
		$entry['lastmod']    = gmdate( DATE_W3C, (int) strtotime( $post->post_modified_gmt ) );
		$entry['changefreq'] = 'weekly';
		$entry['priority']   = '0.6';
		return $entry;
	}
}
