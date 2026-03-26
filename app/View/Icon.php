<?php // phpcs:disable WordPress.Files.FileName
/**
 * Icon helper functions for Lerm theme.
 *
 * 本主题的图标系统使用自定义字体（lerm-font.css），class 格式为 .fa .fa-{slug}。
 * 原 SVG_Icons 类路径已删除（类从未存在），全部改为字体图标输出。
 *
 * 加载方式：通过 composer.json autoload.files 全局加载（见下方说明）。
 *
 * composer.json 需要添加：
 *   "autoload": {
 *     "files": [
 *       "app/View/Layout.php",
 *       "app/View/Icon.php",          ← 新增这行
 *       "app/Support/Utilities.php"
 *     ]
 *   }
 *
 * 可用图标（来自 assets/resources/css/lerm-font.css）：
 *   weibo, wechat, qq, qzone, douban, linkedin, facebook, twitter,
 *   google-plus, github, rss, envelope, link, heart, star, eye,
 *   comment, comments, share, external, calendar, tag, tags,
 *   search, user, home, edit, cog, cogs, trash, download ...
 *
 * @package Lerm
 */

namespace Lerm\View;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// =============================================================================
// 图标常量：slug → CSS class（统一映射，方便日后批量修改）
// =============================================================================

/**
 * 社交平台 slug → lerm-font CSS class 映射表。
 * key 与后台选项 social_share 的 options key 完全对应。
 */
const SOCIAL_ICON_MAP = array(
	'weibo'       => 'fa fa-weibo',
	'wechat'      => 'fa fa-wechat',
	'qq'          => 'fa fa-qq',
	'qzone'       => 'fa fa-qzone',
	'douban'      => 'fa fa-douban',
	'linkedin'    => 'fa fa-linkedin',
	'facebook'    => 'fa fa-facebook',
	'twitter'     => 'fa fa-twitter',
	'google_plus' => 'fa fa-google-plus',
	'github'      => 'fa fa-github',
	'rss'         => 'fa fa-rss',
	'envelope'    => 'fa fa-envelope',
);

/**
 * 通用 UI 图标 slug → lerm-font CSS class 映射表。
 */
const UI_ICON_MAP = array(
	'link'     => 'fa fa-link',
	'search'   => 'fa fa-search',
	'heart'    => 'fa fa-heart',
	'star'     => 'fa fa-star',
	'user'     => 'fa fa-user',
	'home'     => 'fa fa-home',
	'eye'      => 'fa fa-eye',
	'comment'  => 'fa fa-comment',
	'comments' => 'fa fa-comments',
	'share'    => 'fa fa-share',
	'tag'      => 'fa fa-tag',
	'tags'     => 'fa fa-tags',
	'calendar' => 'fa fa-calendar',
	'edit'     => 'fa fa-edit',
	'external' => 'fa fa-external',
	'download' => 'fa fa-download',
	'rss'      => 'fa fa-rss',
	'cog'      => 'fa fa-cog',
	'trash'    => 'fa fa-trash',
);

// =============================================================================
// 核心函数
// =============================================================================

if ( ! function_exists( __NAMESPACE__ . '\\get_icon' ) ) {
	/**
	 * 返回图标 HTML（<i> 标签）。
	 *
	 * 用法：
	 *   echo Lerm\View\get_icon( 'link' );
	 *   echo Lerm\View\get_icon( 'weibo', 'social' );
	 *
	 * @param string $slug    图标 slug
	 * @param string $type    'ui'（默认）或 'social'
	 * @param string $extra   额外 CSS class，如 'me-1 text-danger'
	 * @return string  <i> HTML 或空字符串（slug 未注册时）
	 */
	function get_icon( string $slug, string $type = 'ui', string $extra = '' ): string {
		$map = ( 'social' === $type ) ? SOCIAL_ICON_MAP : UI_ICON_MAP;

		if ( ! isset( $map[ $slug ] ) ) {
			// 未在映射表中：允许直接传 fa-xxx 格式作为兜底
			$class = sanitize_html_class( $slug );
			if ( empty( $class ) ) {
				return '';
			}
		} else {
			$class = $map[ $slug ];
		}

		if ( ! empty( $extra ) ) {
			$class .= ' ' . esc_attr( trim( $extra ) );
		}

		return sprintf( '<i class="%s" aria-hidden="true"></i>', esc_attr( $class ) );
	}
}

// =============================================================================
// 向后兼容包装（biography.php 等旧调用方继续可用）
// =============================================================================

if ( ! function_exists( 'lerm_get_icon_svg' ) ) {
	/**
	 * 返回 UI 图标 HTML。
	 * 函数名保留 "svg" 以兼容旧调用，实际输出字体图标 <i> 标签。
	 *
	 * 用法（biography.php）：
	 *   lerm_get_icon_svg( 'link' )
	 *
	 * @param string $icon 图标 slug
	 * @param int    $size 忽略（字体图标通过 CSS 控制大小）
	 * @return string
	 */
	function lerm_get_icon_svg( string $icon, int $size = 24 ): string {
		return \Lerm\View\get_icon( $icon, 'ui' );
	}
}

if ( ! function_exists( 'lerm_get_social_icon_svg' ) ) {
	/**
	 * 返回社交图标 HTML。
	 *
	 * @param string $icon 平台 slug（如 'weibo'）
	 * @param int    $size 忽略
	 * @return string
	 */
	function lerm_get_social_icon_svg( string $icon, int $size = 24 ): string {
		return \Lerm\View\get_icon( $icon, 'social' );
	}
}

if ( ! function_exists( 'lerm_get_social_link_svg' ) ) {
	/**
	 * 根据 URL 检测平台并返回对应图标 HTML。
	 * 未匹配时返回通用 link 图标。
	 *
	 * @param string $uri  URL
	 * @param int    $size 忽略
	 * @return string
	 */
	function lerm_get_social_link_svg( string $uri, int $size = 24 ): string {
		if ( empty( $uri ) ) {
			return '';
		}

		$host      = strtolower( (string) wp_parse_url( $uri, PHP_URL_HOST ) );
		$uri_lower = strtolower( $uri );

		// URL → 平台 slug 映射
		$url_map = array(
			'weibo.com'       => 'weibo',
			'weibo.cn'        => 'weibo',
			'weixin'          => 'wechat',
			'wechat'          => 'wechat',
			'qq.com'          => 'qq',
			'qzone.qq.com'    => 'qzone',
			'douban.com'      => 'douban',
			'linkedin.com'    => 'linkedin',
			'facebook.com'    => 'facebook',
			'twitter.com'     => 'twitter',
			'x.com'           => 'twitter',
			'plus.google.com' => 'google_plus',
			'github.com'      => 'github',
		);

		foreach ( $url_map as $pattern => $slug ) {
			if ( false !== strpos( $host, $pattern ) || false !== strpos( $uri_lower, $pattern ) ) {
				return \Lerm\View\get_icon( $slug, 'social' );
			}
		}

		// 未匹配：通用链接图标
		return \Lerm\View\get_icon( 'link', 'ui' );
	}
}

// =============================================================================
// 导航菜单社交图标注入
// =============================================================================

if ( ! function_exists( 'lerm_nav_menu_social_icons' ) ) {
	/**
	 * 在 social 菜单位置的每个菜单项末尾注入平台图标。
	 * 挂载到 walker_nav_menu_start_el filter。
	 *
	 * @param string   $item_output 菜单项 HTML
	 * @param \WP_Post $item        菜单项对象
	 * @param int      $depth       深度
	 * @param object   $args        wp_nav_menu() 参数
	 * @return string
	 */
	function lerm_nav_menu_social_icons( string $item_output, \WP_Post $item, int $depth, object $args ): string {
		if ( empty( $args->theme_location ) || 'social' !== $args->theme_location ) {
			return $item_output;
		}

		$url = isset( $item->url ) ? trim( $item->url ) : '';
		if ( '' === $url ) {
			return $item_output;
		}

		$icon = lerm_get_social_link_svg( $url );

		// 替换 link_after 或插入到 </a> 之前
		$link_after = $args->link_after ?? '';
		if ( '' !== $link_after && false !== strpos( $item_output, $link_after ) ) {
			$item_output = str_replace( $link_after, '</span>' . $icon, $item_output );
		} else {
			$pos = strripos( $item_output, '</a>' );
			if ( false !== $pos ) {
				$item_output = substr_replace( $item_output, $icon . '</a>', $pos, 4 );
			} else {
				$item_output .= $icon;
			}
		}

		return $item_output;
	}
}
add_filter( 'walker_nav_menu_start_el', 'lerm_nav_menu_social_icons', 10, 4 );

// =============================================================================
// 社交分享图标组
// =============================================================================

if ( ! function_exists( 'lerm_social_icons' ) ) {
	/**
	 * 输出社交分享图标组。
	 *
	 * 接受后台 social_share 选项返回的数组（已勾选项的 slug 列表），
	 * 也接受 slug => url 关联数组。
	 *
	 * 用法：
	 *   lerm_social_icons( lerm_options('social_share') );
	 *   lerm_social_icons( array('weibo' => 'https://weibo.com/share/...', 'qq') );
	 *
	 * @param array $icons slug 列表或 slug => url 关联数组
	 */
	function lerm_social_icons( array $icons = array( 'weibo', 'wechat', 'qq' ) ): void {
		if ( empty( $icons ) ) {
			return;
		}

		// 标准化为 slug => url（url 可为空）
		$normalized = array();
		foreach ( $icons as $k => $v ) {
			if ( is_int( $k ) ) {
				$normalized[ (string) $v ] = '';
			} else {
				$normalized[ $k ] = (string) $v;
			}
		}

		$items = array();
		foreach ( $normalized as $slug => $url ) {
			$icon = \Lerm\View\get_icon( $slug, 'social' );
			if ( '' === $icon ) {
				continue; // 不认识的 slug 直接跳过
			}

			/* translators: %s: social platform name */
			$aria = esc_attr( sprintf( __( 'Share on %s', 'lerm' ), ucfirst( str_replace( '_', ' ', $slug ) ) ) );

			$href  = '#';
			$extra = '';
			if ( ! empty( $url ) ) {
				$href  = esc_url( $url );
				$extra = ' target="_blank" rel="noopener noreferrer"';
			}

			$items[] = sprintf(
				'<a class="social-share-icon icon-%1$s btn-light btn-sm" href="%2$s" aria-label="%3$s"%4$s>%5$s</a>',
				esc_attr( $slug ),
				esc_attr( $href ),
				$aria,
				$extra,
				$icon
			);
		}

		if ( empty( $items ) ) {
			return;
		}

		$output = '<div class="social-share d-flex justify-content-center gap-1">'
			. implode( "\n", $items )
			. '</div>';

		$output = apply_filters( 'lerm_social_icons_output', $output, $normalized );

		echo wp_kses_post( $output );
	}
}
