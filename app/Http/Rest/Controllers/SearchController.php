<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Http\Rest\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Lerm\Http\Rest\Middleware;
use Lerm\Http\Rest\Repository\SearchRepository;

/**
 * 实时搜索接口控制器
 *
 * GET /lerm/v1/search?q={query}&post_type={type}&per_page={n}
 *
 * 职责划分：
 *   - 参数验证、频率限制（Controller 层）
 *   - 查询执行 + 缓存管理（SearchRepository 层）
 *   - 关键词高亮（展示逻辑，留在 Controller）
 *   - 热词记录（shutdown 异步，不阻塞响应）
 *
 * 响应格式：
 * {
 *   "results": [
 *     {
 *       "id":        1,
 *       "title":     "文章<mark>标题</mark>（含高亮标签）",
 *       "excerpt":   "摘要...",
 *       "url":       "https://...",
 *       "thumbnail": "https://...",
 *       "date":      "2024-01-01",
 *       "category":  "分类名"
 *     }
 *   ],
 *   "total": 5
 * }
 *
 * @package Lerm\Http\Rest\Controllers
 */
final class SearchController {

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

		// ── 委托 Repository 执行查询（含缓存管理）────────────
		// SearchRepository::search() 内部通过 CacheRepository::remember()
		// 统一处理缓存读写，Controller 无需感知缓存细节。
		$result = SearchRepository::search( $keyword, $post_type, $per_page );
		// ── 关键词高亮（展示层逻辑，Repository 返回原始标题）─
		// 先 esc_html 再做正则替换，防止 XSS：
		// <mark> 标签是我们自己插入的，不来自用户输入。
		$safe_kw = preg_quote( $keyword, '/' );
		$results = array_map(
			static function ( array $item ) use ( $safe_kw ): array {
				$item['title'] = preg_replace(
					'/(' . $safe_kw . ')/iu',
					'<mark>$1</mark>',
					esc_html( $item['title'] )
				);
				return $item;
			},
			$result['items']
		);

		// ── 热词记录（shutdown 异步，不阻塞响应）─────────────
		add_action(
			'shutdown',
			static function () use ( $keyword ) {
				SearchRepository::record_keyword( $keyword );
			}
		);

		return new WP_REST_Response(
			array(
				'results' => $results,
				'total'   => $result['total'],
			),
			200
		);
	}
}
