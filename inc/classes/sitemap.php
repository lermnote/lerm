<?php

namespace Lerm\Inc;

/**
 */
class Sitemap {

	public static $args = array(
		'sitemap_enable' => true,
		'max_urls'       => 6666,
		'post_type'      => array(),
		'post_exclude'   => array(),
		'page_exclude'   => array(),
	);

	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_sitemap_', wp_parse_args( $params, self::$args ) );
		self::hooks();
	}

	// instance
	public static function instance( $params = array() ) {
		return new self( $params );
	}

	public static function hooks() {
		if ( self::$args['sitemap_enable'] ) {
			if ( is_array( self::$args['post_type'] ) && in_array( 'users', self::$args['post_type'], true ) ) {
				add_filter( 'wp_sitemaps_add_provider', array( __NAMESPACE__ . '\Sitemap', 'remove_user' ), 10, 2 );
			}

			add_filter( 'wp_sitemaps_post_types', array( __NAMESPACE__ . '\Sitemap', 'remove_post_type' ) );
			add_filter( 'wp_sitemaps_taxonomies', array( __NAMESPACE__ . '\Sitemap', 'remove_post_type' ) );

			if ( ( isset( self::$args['post_exclude'] ) && ! empty( self::$args['post_exclude'] ) ) || ( isset( self::$args['page_exclude'] ) && ! empty( self::$args['page_exclude'] ) ) ) {
				add_filter( 'wp_sitemaps_posts_query_args', array( __NAMESPACE__ . '\Sitemap', 'exclude_post' ), 10, 2 );
			}

			add_filter( 'wp_sitemaps_posts_entry', array( __NAMESPACE__ . '\Sitemap', 'add_tag' ), 10, 2 );

			add_filter( 'wp_sitemaps_max_urls', array( __NAMESPACE__ . '\Sitemap', 'max_urls' ) );

		} else {
			add_filter( 'wp_sitemaps_enabled', '__return_false' );
		}
	}

	public static function remove_user( $provider, $name ) {
		if ( 'users' === $name ) {
			return false;
		}
		return $provider;
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
		if ( 'post' !== $post_type ) {
			return $args;
		}
		$args['post__not_in'] = isset( $args['post__not_in'] ) ? $args['post__not_in'] : array();
		foreach ( self::$args['post_exclude'] as $key => $value ) {
			$args['post__not_in'][] = $value;
		}
		return $args;
	}

	public static function max_urls() {
		return self::$args['max_urls'];
	}

	public static function add_tag( $entry, $post ) {
		$entry['lastmod']    = gmdate( DATE_W3C, strtotime( $post->post_modified_gmt ) );// DATE_W3C = 'Y-m-d\TH:i:sO'
		$entry['changefreq'] = 'Daily';
		$entry['priority']   = '0.6';
		return $entry;
	}
}
