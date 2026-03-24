<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Http\Rest\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Lerm\Http\Rest\Middleware;

/**
 * 文章目录（TOC）接口控制器
 *
 * GET /lerm/v1/toc/{id}
 *
 * 解析文章内容中的 H2/H3 标题，返回树形结构供前端渲染目录导航。
 *
 * 返回格式：
 * {
 *   "toc": [
 *     {
 *       "id": "heading-slug",
 *       "text": "标题文字",
 *       "level": 2,
 *       "children": [
 *         { "id": "sub-heading", "text": "子标题", "level": 3, "children": [] }
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

		// 文章更新时清除缓存
		add_action(
			'save_post_' . $post_id,
			static function () use ( $post_id, $cache_key ) {
				wp_cache_delete( $cache_key, self::CACHE_GROUP );
			}
		);

		return new WP_REST_Response( array( 'toc' => $toc ), 200 );
	}

	/**
	 * 解析 HTML 中的标题节点，构建嵌套树
	 *
	 * @return array<int, array{id: string, text: string, level: int, children: array}>
	 */
	private static function parse( string $content ): array {
		if ( empty( $content ) ) {
			return array();
		}

		// 匹配 H2/H3，捕获已有 id 属性或将从文字生成
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

			// 生成唯一 slug
			if ( $id_attr ) {
				$slug = sanitize_title( $id_attr );
			} else {
				$slug = sanitize_title( $text );
			}

			// 防止重复 slug
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
	 * 将扁平数组转换为嵌套树（H2 为根，H3 为子节点）
	 *
	 * @param array<int, array{id: string, text: string, level: int, children: array}> $flat
	 * @return array
	 */
	private static function build_tree( array $flat ): array {
		$tree             = array();
		$current_h2_index = -1;

		foreach ( $flat as $item ) {
			if ( 2 === $item['level'] ) {
				$tree[]           = $item;
				$current_h2_index = count( $tree ) - 1;
			} elseif ( $current_h2_index >= 0 ) {
				// H3 挂在最近的 H2 下
				$tree[ $current_h2_index ]['children'][] = $item;
			} else {
				// 没有父级 H2，提升为根节点
				$tree[] = $item;
			}
		}

		return $tree;
	}
}
