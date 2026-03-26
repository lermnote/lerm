<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Http\Rest\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Lerm\Http\Rest\Middleware;

/**
 * 文章列表接口控制器（无限滚动）
 *
 * GET /lerm/v1/posts?page=1&per_page=10&category=0&tag=0&post_type=post
 *
 * 返回渲染好的 HTML 片段 + 分页信息，前端直接插入 DOM。
 * 如需返回纯 JSON 数据，可通过 filter `lerm_posts_response_format` 切换。
 *
 * @package Lerm\Http\Rest\Controllers
 */
final class PostsController {

	/**
	 * 处理文章列表请求
	 *
	 * 频率限制：每 IP 每分钟最多 60 次
	 */
	public static function handle( WP_REST_Request $request ): WP_REST_Response|WP_Error {

		$check = Middleware::rate_limit( 'posts_list', 60 );
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$page      = absint( $request->get_param( 'page' ) );
		$per_page  = absint( $request->get_param( 'per_page' ) );
		$category  = absint( $request->get_param( 'category' ) );
		$tag       = absint( $request->get_param( 'tag' ) );
		$post_type = sanitize_key( (string) $request->get_param( 'post_type' ) );

		if ( ! post_type_exists( $post_type ) ) {
			return new WP_Error( 'invalid_post_type', __( '无效的文章类型', 'lerm' ), array( 'status' => 400 ) );
		}

		$args = array(
			'post_type'           => $post_type,
			'post_status'         => 'publish',
			'posts_per_page'      => $per_page,
			'paged'               => $page,
			'ignore_sticky_posts' => true,
		);

		if ( $category > 0 ) {
			$args['cat'] = $category;
		}

		if ( $tag > 0 ) {
			$args['tag_id'] = $tag;
		}

		// 允许外部扩展查询参数
		$args  = (array) apply_filters( 'lerm_rest_posts_query_args', $args, $request );
		$query = new \WP_Query( $args );

		$format = apply_filters( 'lerm_posts_response_format', 'html' );

		if ( 'json' === $format ) {
			$items = self::format_json( $query->posts );
		} else {
			$items = self::format_html( $query );
		}

		$response = new WP_REST_Response(
			array(
				'items'       => $items,
				'total'       => (int) $query->found_posts,
				'total_pages' => (int) $query->max_num_pages,
				'page'        => $page,
				'has_more'    => $page < $query->max_num_pages,
			),
			200
		);

		// 缓存控制头（CDN 友好）
		$response->header( 'Cache-Control', 'public, max-age=60' );

		return $response;
	}

	/**
	 * 渲染 HTML 片段（使用主题模板）
	 */
	private static function format_html( \WP_Query $query ): string {
		if ( ! $query->have_posts() ) {
			return '';
		}

		ob_start();
		while ( $query->have_posts() ) {
			$query->the_post();
			// 优先使用主题模板，否则降级为内联输出
			$template = locate_template( array( 'template-parts/content.php', 'template-parts/content-post.php' ) );
			if ( $template ) {
				load_template( $template, false );
			} else {
				// 降级：内联输出基础卡片
				printf(
					'<article class="summary mb-3 p-0 p-md-3">
						<h2><a href="%s">%s</a></h2>
						<p>%s</p>
					</article>',
					esc_url( get_permalink() ),
					esc_html( get_the_title() ),
					esc_html( wp_trim_words( get_the_excerpt(), 30 ) )
				);
			}
		}
		wp_reset_postdata();

		return (string) ob_get_clean();
	}

	/**
	 * 返回结构化 JSON 数据
	 *
	 * @param \WP_Post[]|int[] $posts
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
