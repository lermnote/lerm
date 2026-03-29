<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Http\Rest\Controllers;

use Lerm\Http\Rest\Middleware;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Table of contents (TOC) REST controller.
 *
 * GET /lerm/v1/toc/{id}
 *
 * Parses H2 and H3 headings from post content and returns a nested structure
 * for front-end TOC navigation.
 *
 * Response shape:
 * {
 *   "toc": [
 *     {
 *       "id": "heading-slug",
 *       "text": "Heading text",
 *       "level": 2,
 *       "children": [
 *         { "id": "sub-heading", "text": "Subheading text", "level": 3, "children": [] }
 *       ]
 *     }
 *   ]
 * }
 *
 * @package Lerm\Http\Rest\Controllers
 */
final class TocController {

	private const CACHE_GROUP = 'lerm_toc';
	private const CACHE_TTL   = HOUR_IN_SECONDS;

	/**
	 * Handle TOC requests for a published post.
	 */
	public static function handle( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$post_id = absint( $request->get_param( 'id' ) );

		$check = Middleware::require_published_post( $post_id );
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$cache_key = "toc_{$post_id}";
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return new WP_REST_Response( array( 'toc' => $cached ), 200 );
		}

		$content = get_post_field( 'post_content', $post_id );
		$content = apply_filters( 'the_content', $content );
		$toc     = self::parse( (string) $content );

		wp_cache_set( $cache_key, $toc, self::CACHE_GROUP, self::CACHE_TTL );

		// Clear the cached TOC whenever the post is saved again.
		add_action(
			'save_post_' . $post_id,
			static function () use ( $cache_key ) {
				wp_cache_delete( $cache_key, self::CACHE_GROUP );
			}
		);

		return new WP_REST_Response( array( 'toc' => $toc ), 200 );
	}

	/**
	 * Parse heading nodes from HTML and build a nested tree.
	 *
	 * @return array<int, array{id: string, text: string, level: int, children: array}>
	 */
	private static function parse( string $content ): array {
		if ( empty( $content ) ) {
			return array();
		}

		// Match H2/H3 headings and capture an existing id attribute when present.
		if ( ! preg_match_all(
			'/<h([23])[^>]*(?:id=["\']([^"\']*)["\'])?[^>]*>(.*?)<\/h[23]>/is',
			$content,
			$matches,
			PREG_SET_ORDER
		) ) {
			return array();
		}

		$slug_count = array();
		$flat       = array();

		foreach ( $matches as $match ) {
			$level    = (int) $match[1];
			$id_attr  = $match[2] ?? '';
			$raw_text = wp_strip_all_tags( $match[3] );
			$text     = html_entity_decode( $raw_text, ENT_QUOTES, 'UTF-8' );

			// Build a stable slug from the existing id or from the heading text.
			if ( $id_attr ) {
				$slug = sanitize_title( $id_attr );
			} else {
				$slug = sanitize_title( $text );
			}

			// Ensure slugs stay unique within the current document.
			if ( isset( $slug_count[ $slug ] ) ) {
				++$slug_count[ $slug ];
				$slug .= '-' . $slug_count[ $slug ];
			} else {
				$slug_count[ $slug ] = 0;
			}

			$flat[] = array(
				'id'       => $slug,
				'text'     => esc_html( $text ),
				'level'    => $level,
				'children' => array(),
			);
		}

		return self::build_tree( $flat );
	}

	/**
	 * Convert a flat heading list into a nested tree.
	 *
	 * H2 nodes become roots, and subsequent H3 nodes are attached to the most
	 * recent H2 node. If no parent H2 exists, the H3 node is promoted to root.
	 *
	 * @param array<int, array{id: string, text: string, level: int, children: array}> $flat Flat heading list.
	 * @return array<int, array{id: string, text: string, level: int, children: array}>
	 */
	private static function build_tree( array $flat ): array {
		$tree             = array();
		$current_h2_index = -1;

		foreach ( $flat as $item ) {
			if ( 2 === $item['level'] ) {
				$tree[]           = $item;
				$current_h2_index = count( $tree ) - 1;
			} elseif ( $current_h2_index >= 0 ) {
				$tree[ $current_h2_index ]['children'][] = $item;
			} else {
				$tree[] = $item;
			}
		}

		return $tree;
	}
}
