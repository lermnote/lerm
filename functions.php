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
	define( 'LERM_VERSION', wp_get_theme()->get( 'Version' ) ? wp_get_theme()->get( 'Version' ) : '1.0.0' );
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
		if ( is_admin() && file_exists( $lerm_csf ) ) {
			require_once $lerm_csf;  // options.config.php 里的 __('lerm') 现在安全
		}
	},
	2
);
if ( ! function_exists( 'lerm_options' ) ) {
	function lerm_options( string $id, string $tag = '', $default_value = '' ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound
		// Fetch the theme options array from the database
		static $options = null;
		if ( null === $options ) {
			$options = (array) get_option( 'lerm_theme_options', array() );
		}

		// Check if the main option ID exists in the options array
		if ( ! array_key_exists( $id, $options ) ) {
			return $default_value;
		}

		$option_value = $options[ $id ];

		// If the option value is an array and a tag is specified, return the tagged value or default
		if ( is_array( $option_value ) && '' !== $tag ) {
			return $options[ $id ][ $tag ] ?? $default_value;
		}

		// Return the option value (either array or single value)
		return $option_value;
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
				'narbar_align'          => 'justify-content-md-start',
				'narbar_search'         => false,
			)
		);
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
