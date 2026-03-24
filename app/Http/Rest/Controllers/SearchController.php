<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Http\Rest\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Lerm\Http\Rest\Middleware;
use Lerm\Helpers\Image;

/**
 * 实时搜索接口控制器
 *
 * GET /lerm/v1/search?q={query}&post_type={type}&per_page={n}
 *
 * 返回格式：
 * {
 *   "results": [
 *     {
 *       "id": 1,
 *       "title": "文章标题",
 *       "excerpt": "摘要...",
 *       "url": "https://...",
 *       "thumbnail": "https://..."
 *     }
 *   ],
 *   "total": 5
 * }
 *
 * @package Lerm\Http\Rest\Controllers
 */
final class SearchController {

	private const CACHE_GROUP = 'lerm_search';
	private const CACHE_TTL   = 5 * MINUTE_IN_SECONDS;

	/**
	 * 处理搜索请求
	 *
	 * 频率限制：每 IP 每分钟最多 30 次
	 */
	public static function handle( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$check = Middleware::rate_limit( 'search', 30 );
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$keyword   = (string) $request->get_param( 'q' );
		$post_type = (string) $request->get_param( 'post_type' );
		$per_page  = absint( $request->get_param( 'per_page' ) );

		// 验证 post_type 合法性（只允许已注册的类型）
		if ( ! post_type_exists( $post_type ) ) {
			return new WP_Error(
				'invalid_post_type',
				__( '无效的文章类型', 'lerm' ),
				array( 'status' => 400 )
			);
		}

		// 关键词太短，直接返回空（避免全表扫描）
		if ( mb_strlen( $keyword ) < 2 ) {
			return new WP_REST_Response(
				array(
					'results' => array(),
					'total'   => 0,
				),
				200
			);
		}

		// 缓存 key 基于参数组合
		$cache_key = 'search_' . md5( $keyword . $post_type . $per_page );
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return new WP_REST_Response( $cached, 200 );
		}

		$query = new \WP_Query(
			array(
				'post_type'           => $post_type,
				'post_status'         => 'publish',
				's'                   => $keyword,
				'posts_per_page'      => $per_page,
				'no_found_rows'       => false,
				'ignore_sticky_posts' => true,
				'fields'              => 'ids', // 先拿 ID，减少内存
			)
		);

		$results = array();
		foreach ( $query->posts as $post_id ) {
			$post = get_post( (int) $post_id );
			if ( ! $post ) {
				continue;
			}

			// 摘要：优先 post_excerpt，否则截取正文
			$excerpt = $post->post_excerpt
				? $post->post_excerpt
				: wp_trim_words( wp_strip_all_tags( $post->post_content ), 20, '...' );

			// 缩略图
			$thumbnail = '';
			if ( has_post_thumbnail( $post_id ) ) {
				$thumbnail = (string) get_the_post_thumbnail_url( $post_id, 'home-thumb' );
			}

			// 高亮关键词（转义后再替换，防止 XSS）
			$safe_keyword    = preg_quote( $keyword, '/' );
			$title_plain     = get_the_title( $post_id );
			$title_highlight = preg_replace(
				'/(' . $safe_keyword . ')/iu',
				'<mark>$1</mark>',
				esc_html( $title_plain )
			);

			$results[] = array(
				'id'        => $post_id,
				'title'     => $title_highlight,
				'excerpt'   => esc_html( mb_substr( $excerpt, 0, 100 ) ),
				'url'       => get_permalink( $post_id ),
				'thumbnail' => esc_url( $thumbnail ),
			);
		}

		$data = array(
			'results' => $results,
			'total'   => $query->found_posts,
		);

		wp_cache_set( $cache_key, $data, self::CACHE_GROUP, self::CACHE_TTL );

		return new WP_REST_Response( $data, 200 );
	}
}
