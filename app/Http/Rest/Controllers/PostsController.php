<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Http\Rest\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Lerm\Http\Rest\Middleware;
/**
 * Posts list endpoint for archive/search/home "load more" requests.
 *
 * @package Lerm\Http\Rest\Controllers
 */
final class PostsController {
	private const INTEGER_QUERY_ARGS = array( 'cat', 'tag_id', 'posts_per_page', 'author', 'post_parent', 'year', 'monthnum', 'day' );
	private const TEXT_QUERY_ARGS    = array( 'category_name', 'tag', 'term', 'author_name', 'name', 's' );
	private const KEY_QUERY_ARGS     = array( 'taxonomy' );
	private const ALLOWED_ORDERBY    = array( 'date', 'modified', 'title', 'name', 'menu_order', 'comment_count', 'rand', 'id' );
	private const ALLOWED_ORDER      = array( 'ASC', 'DESC' );

	/**
	 * Handle the posts listing request.
	 */
	public static function handle( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$check = Middleware::rate_limit( 'posts_list', 60 );
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$page = self::resolve_page( $request );
		$args = array_merge(
			array(
				'post_status'         => 'publish',
				'posts_per_page'      => (int) get_option( 'posts_per_page', 10 ),
				'paged'               => $page,
				'ignore_sticky_posts' => true,
			),
			self::positive_int_args( $request, self::INTEGER_QUERY_ARGS ),
			self::text_args( $request, self::TEXT_QUERY_ARGS ),
			self::key_args( $request, self::KEY_QUERY_ARGS )
		);

		self::alias_positive_int( $request, 'per_page', 'posts_per_page', $args );
		self::alias_positive_int( $request, 'category', 'cat', $args );
		self::alias_positive_int( $request, 'tag', 'tag_id', $args );
		self::maybe_set_allowed( $args, 'orderby', sanitize_key( (string) $request->get_param( 'orderby' ) ), self::ALLOWED_ORDERBY );
		self::maybe_set_allowed( $args, 'order', strtoupper( sanitize_text_field( (string) $request->get_param( 'order' ) ) ), self::ALLOWED_ORDER );

		$post_type = self::normalize_post_type( sanitize_key( (string) $request->get_param( 'post_type' ) ) );
		if ( is_wp_error( $post_type ) ) {
			return $post_type;
		}

		if ( null !== $post_type ) {
			$args['post_type'] = $post_type;
		}

		$args  = (array) apply_filters( 'lerm_rest_posts_query_args', $args, $request );
		$query = new \WP_Query( $args );

		$format = apply_filters( 'lerm_posts_response_format', 'html' );
		$items  = 'json' === $format ? self::format_json( $query->posts ) : self::format_html( $query );

		$response = new WP_REST_Response(
			array(
				'content'     => $items,
				'total'       => (int) $query->found_posts,
				'total_pages' => (int) $query->max_num_pages,
				'page'        => $page,
				'paged'       => $page,
				'has_more'    => $page < $query->max_num_pages,
			),
			200
		);

		$response->header( 'Cache-Control', 'public, max-age=60' );

		return $response;
	}

	/**
	 * Resolve the current page, preferring the public "page" param.
	 */
	private static function resolve_page( WP_REST_Request $request ): int {
		$page = absint( $request->get_param( 'page' ) );

		if ( $page < 1 ) {
			$page = absint( $request->get_param( 'paged' ) );
		}

		return max( 1, $page );
	}

	/**
	 * Read positive integer query vars from the request.
	 *
	 * @param string[] $keys Allowed request keys.
	 */
	private static function positive_int_args( WP_REST_Request $request, array $keys ): array {
		$args = array();

		foreach ( $keys as $key ) {
			$value = absint( $request->get_param( $key ) );
			if ( $value > 0 ) {
				$args[ $key ] = $value;
			}
		}

		return $args;
	}

	/**
	 * Read non-empty text query vars from the request.
	 *
	 * @param string[] $keys Allowed request keys.
	 */
	private static function text_args( WP_REST_Request $request, array $keys ): array {
		$args = array();

		foreach ( $keys as $key ) {
			$value = $request->get_param( $key );
			if ( is_scalar( $value ) && '' !== (string) $value ) {
				$args[ $key ] = sanitize_text_field( (string) $value );
			}
		}

		return $args;
	}

	/**
	 * Read non-empty slug-like query vars from the request.
	 *
	 * @param string[] $keys Allowed request keys.
	 */
	private static function key_args( WP_REST_Request $request, array $keys ): array {
		$args = array();

		foreach ( $keys as $key ) {
			$value = sanitize_key( (string) $request->get_param( $key ) );
			if ( '' !== $value ) {
				$args[ $key ] = $value;
			}
		}

		return $args;
	}

	/**
	 * Copy a positive integer alias from request into the query args.
	 */
	private static function alias_positive_int( WP_REST_Request $request, string $source, string $target, array &$args ): void {
		$value = absint( $request->get_param( $source ) );
		if ( $value > 0 ) {
			$args[ $target ] = $value;
		}
	}

	/**
	 * Assign a value only when it matches an allowed list.
	 *
	 * @param string[] $allowed Allowed values.
	 */
	private static function maybe_set_allowed( array &$args, string $key, string $value, array $allowed ): void {
		if ( in_array( $value, $allowed, true ) ) {
			$args[ $key ] = $value;
		}
	}

	/**
	 * Render post list HTML using the theme loop template.
	 */
	private static function format_html( \WP_Query $query ): string {
		if ( ! $query->have_posts() ) {
			return '';
		}

		ob_start();
		while ( $query->have_posts() ) {
			$query->the_post();
			get_template_part( 'template-parts/post/content', get_post_format() );
		}
		wp_reset_postdata();

		return (string) ob_get_clean();
	}

	/**
	 * Normalize a post type value while allowing the special "any" keyword.
	 */
	private static function normalize_post_type( string $post_type ): string|WP_Error|null {
		$post_type = sanitize_key( $post_type );

		if ( '' === $post_type ) {
			return null;
		}

		if ( 'any' === $post_type ) {
			return 'any';
		}

		if ( ! post_type_exists( $post_type ) ) {
			return new WP_Error( 'invalid_post_type', __( 'Invalid post type.', 'lerm' ), array( 'status' => 400 ) );
		}

		return $post_type;
	}

	/**
	 * Return structured JSON data.
	 *
	 * @param \WP_Post[]|int[] $posts Posts to format.
	 */
	private static function format_json( array $posts ): array {
		$items = array();

		foreach ( $posts as $post ) {
			$post    = get_post( $post );
			$post_id = (int) $post->ID;

			$items[] = array(
				'id'        => $post_id,
				'title'     => esc_html( get_the_title( $post_id ) ),
				'excerpt'   => esc_html( wp_trim_words( get_the_excerpt( $post ), 30 ) ),
				'url'       => get_permalink( $post_id ),
				'thumbnail' => esc_url( (string) get_the_post_thumbnail_url( $post_id, 'home-thumb' ) ),
				'date'      => get_the_date( 'c', $post_id ),
				'author'    => esc_html( get_the_author_meta( 'display_name', $post->post_author ) ),
			);
		}

		return $items;
	}
}
