<?php
/**
 * Lerm Theme — functions.php
 *
 * 职责：
 *   1. 定义主题常量
 *   2. 加载 Composer autoload
 *   3. 加载 Admin 框架（CSF）
 *   4. 在 after_setup_theme 钩子内启动 bootstrap
 *
 * 不应放在这里的内容：模块初始化、选项解析、hook 注册 —— 这些全在 bootstrap.php。
 *
 * @package Lerm
 */

// 防止直接访问
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// 1. 主题常量（必须在 autoload 之前定义，bootstrap 内部会用到）
// -----------------------------------------------------------------------------

if ( ! defined( 'LERM_DOMAIN' ) ) {
	define( 'LERM_DOMAIN', 'lerm' );
}

if ( ! defined( 'LERM_DIR' ) ) {
	define( 'LERM_DIR', trailingslashit( get_template_directory() ) );
}

if ( ! defined( 'LERM_URI' ) ) {
	define( 'LERM_URI', trailingslashit( get_template_directory_uri() ) );
}

if ( ! defined( 'LERM_VERSION' ) ) {
	define( 'LERM_VERSION', wp_get_theme()->get( 'Version' ) ? wp_get_theme()->get( 'Version' ) : '5.0.0' );
}

// -----------------------------------------------------------------------------
// 2. Composer autoload
// -----------------------------------------------------------------------------

$lerm_autoload = LERM_DIR . 'vendor/autoload.php';

if ( file_exists( $lerm_autoload ) ) {
	require_once $lerm_autoload;
} else {
	// autoload 缺失时在后台显示错误提示，前台正常渲染避免白屏
	add_action(
		'admin_notices',
		static function () use ( $lerm_autoload ) {
			printf(
				'<div class="notice notice-error"><p><strong>Lerm Theme:</strong> %s</p></div>',
				esc_html(
					sprintf(
						/* translators: %s: file path */
						__( 'Composer autoload not found: %s. Please run `composer install`.', 'lerm' ),
						$lerm_autoload
					)
				)
			);
		}
	);
}

// -----------------------------------------------------------------------------
// 3. 翻译
// -----------------------------------------------------------------------------

add_action(
	'after_setup_theme',
	static function () {
		load_theme_textdomain( LERM_DOMAIN, LERM_DIR . 'languages' );
	},
	1  // 优先级设最早，确保后续模块初始化时翻译已就绪
);

// -----------------------------------------------------------------------------
// 4. Admin 框架（Codestar Framework）
//    定义 lerm_options() 及后台选项面板，必须在 bootstrap 之前加载
// -----------------------------------------------------------------------------

$lerm_csf = LERM_DIR . 'app/Http/Admin/codestar-framework.php';
add_action(
	'after_setup_theme',
	function () use ( $lerm_csf ) {
		if ( file_exists( $lerm_csf ) ) {
			require_once $lerm_csf;  // options.config.php 里的 __('lerm') 现在安全
		}
	},
	2
);
if ( ! function_exists( 'lerm_options' ) ) {
	function lerm_options( string $id, string $tag = '', $default_value = '' ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound
		return \Lerm\Options\ThemeOptionsRepository::instance()->get( $id, $tag, $default_value );
	}
}

if ( ! function_exists( 'lerm_get_template_options' ) ) {
	/**
	 * Get template-facing theme options prepared by bootstrap.
	 *
	 * @return array<string, mixed>
	 */
	function lerm_get_template_options(): array {
		return apply_filters(
			'lerm_template_options',
			array(
				'header_bg_color'       => '#fff',
				'slide_position'        => '',
				'slide_enable'          => false,
				'slide_images'          => array(),
				'slide_indicators'      => false,
				'slide_control'         => false,
				'footer_menus'          => 0,
				'icp_num'               => '',
				'copyright'             => '',
				'author_bio'            => false,
				'single_sidebar_select' => 'home-sidebar',
				'blog_sidebar_select'   => 'home-sidebar',
				'front_page_sidebar'    => 'home-sidebar',
				'page_sidebar'          => 'home-sidebar',
				'breadcrumb_container'  => 'nav',
				'breadcrumb_before'     => '',
				'breadcrumb_after'      => '',
				'breadcrumb_list_tag'   => 'ol',
				'breadcrumb_item_tag'   => 'li',
				'breadcrumb_separator'  => '/',
				'breadcrumb_front_show' => false,
				'breadcrumb_show_title' => true,
				'thumbnail_gallery'     => '',
				'load_more'             => false,
				'related_posts'         => false,
				'related_number'        => 5,
				'single_top'            => array(),
				'single_bottom'         => array(),
				'summary_meta'          => array(),
				'social_share'          => array(),
				'blogname'              => '',
				'blogdesc'              => '',
				'navbar_align'          => 'justify-content-md-end',
				'navbar_search'         => false,
				'search_results_per_page' => 5,
				'search_placeholder'    => '',
				'comment_avatar_size'   => 48,
				'comment_show_cravatar_tip' => true,
				'social_profiles_position' => array( 'footer', 'author_bio' ),
				'social_open_new_tab'   => true,
			)
		);
	}
}

if ( ! function_exists( 'lerm_get_frontend_auth_page_url' ) ) {
	/**
	 * Build the frontend auth page URL for a given tab.
	 */
	function lerm_get_frontend_auth_page_url( string $tab = 'login' ): string {
		$page_id = (int) lerm_options( 'frontend_login_page', '', 0 );
		$url     = $page_id > 0 ? get_permalink( $page_id ) : wp_login_url();

		if ( ! $url ) {
			$url = home_url( '/' );
		}

		if ( ! in_array( $tab, array( 'login', 'regist', 'register', 'reset' ), true ) ) {
			$tab = 'login';
		}

		if ( in_array( $tab, array( 'regist', 'register', 'reset' ), true ) ) {
			$url = add_query_arg( 'tab', 'register' === $tab ? 'regist' : $tab, $url );
		}

		return esc_url_raw( $url );
	}
}

if ( ! function_exists( 'lerm_get_frontend_account_page_url' ) ) {
	/**
	 * Get the configured frontend account page URL.
	 */
	function lerm_get_frontend_account_page_url(): string {
		$page_id = (int) lerm_options( 'frontend_user_center_page', '', 0 );
		$url     = $page_id > 0 ? get_permalink( $page_id ) : home_url( '/' );

		return esc_url_raw( $url ? $url : home_url( '/' ) );
	}
}

if ( ! function_exists( 'lerm_social_profile_links' ) ) {
	/**
	 * Render configured social profile links.
	 *
	 * @param array  $opts Theme options array.
	 * @param bool   $new_tab Whether links should open in a new tab.
	 * @param string $container_class CSS classes for the container wrapper.
	 */
	function lerm_social_profile_links(
		array $opts,
		bool $new_tab = true,
		string $container_class = 'lerm-social-links d-flex gap-2 justify-content-center flex-wrap'
	): void {
		$target = $new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';
		$links  = array(
			'social_weibo'     => array( 'fa fa-weibo', 'Weibo' ),
			'social_wechat'    => array( 'fa fa-weixin', 'WeChat' ),
			'social_qq'        => array( 'fa fa-qq', 'QQ' ),
			'social_bilibili'  => array( 'lerm-icon-bilibili', 'Bilibili' ),
			'social_zhihu'     => array( 'lerm-icon-zhihu', 'Zhihu' ),
			'social_douban'    => array( 'lerm-icon-douban', 'Douban' ),
			'social_github'    => array( 'fa fa-github', 'GitHub' ),
			'social_twitter'   => array( 'fa fa-twitter', 'X / Twitter' ),
			'social_linkedin'  => array( 'fa fa-linkedin', 'LinkedIn' ),
			'social_instagram' => array( 'fa fa-instagram', 'Instagram' ),
			'social_youtube'   => array( 'fa fa-youtube-play', 'YouTube' ),
			'social_email'     => array( 'fa fa-envelope', 'Email' ),
		);
		$html   = '';

		foreach ( $links as $key => $meta ) {
			$url = trim( (string) ( $opts[ $key ] ?? '' ) );
			if ( '' === $url ) {
				continue;
			}

			if ( 'social_email' === $key && ! str_starts_with( $url, 'mailto:' ) ) {
				$url = 'mailto:' . $url;
			}

			$href = esc_url( $url );
			if ( '' === $href ) {
				continue;
			}

			$html .= sprintf(
				'<a class="social-link" href="%1$s"%2$s aria-label="%3$s"><i class="%4$s" aria-hidden="true"></i></a>',
				$href,
				$target,
				esc_attr( $meta[1] ),
				esc_attr( $meta[0] )
			);
		}

		if ( ! empty( $opts['social_rss'] ) ) {
			$html .= sprintf(
				'<a class="social-link" href="%1$s"%2$s aria-label="RSS"><i class="fa fa-rss" aria-hidden="true"></i></a>',
				esc_url( get_feed_link() ),
				$target
			);
		}

		if ( $html ) {
			printf(
				'<div class="%1$s">%2$s</div>',
				esc_attr( $container_class ),
				$html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- individual links are escaped above.
			);
		}
	}
}
// -----------------------------------------------------------------------------
// 5. 主题启动（bootstrap）
//    挂载到 after_setup_theme，确保 WP 核心函数全部可用
// -----------------------------------------------------------------------------
add_action(
	'after_setup_theme',
	static function () {
		require_once LERM_DIR . 'app/bootstrap.php';
	},
	10
);
