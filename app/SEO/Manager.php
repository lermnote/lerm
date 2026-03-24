<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\SEO;

use Lerm\Traits\Singleton;

/**
 * SEO Manager — 统一初始化入口
 *
 * 负责根据配置决定启用哪些子模块，替代 bootstrap.php 中直接调用
 * MetaTags::instance() 和 Opengraph::instance() 的方式。
 *
 * bootstrap.php 中只需：
 *   Manager::instance( array_merge( $seo_options, $sitemap_options ) );
 *
 * @package Lerm\SEO
 */
final class Manager {

	use Singleton;

	/**
	 * @param array<string, mixed> $params 合并后的 seo + sitemap 选项
	 */
	public function __construct( array $params = array() ) {
		$params = apply_filters( 'lerm_seo_manager_args', $params );

		// 1. meta keywords / description / title separator / html slug
		MetaTags::instance( $params );

		// 2. Open Graph + Twitter Card
		OpenGraph::instance( $params );

		// 3. 百度主动推送（可选）
		if ( ! empty( $params['baidu_submit'] ) ) {
			BaiduSubmit::instance( $params );
		}

		// 4. Sitemap（可选）
		if ( isset( $params['sitemap_enable'] ) ) {
			Sitemap::instance( $params );
		}
	}
}
