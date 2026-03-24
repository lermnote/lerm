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
// 3. Admin 框架（Codestar Framework）
//    定义 lerm_options() 及后台选项面板，必须在 bootstrap 之前加载
// -----------------------------------------------------------------------------

$lerm_csf = LERM_DIR . 'app/Http/Admin/codestar-framework.php';

if ( file_exists( $lerm_csf ) ) {
	require_once $lerm_csf;
}

// -----------------------------------------------------------------------------
// 4. 翻译
// -----------------------------------------------------------------------------

add_action(
	'after_setup_theme',
	static function () {
		load_theme_textdomain( LERM_DOMAIN, LERM_DIR . 'languages' );
	},
	1  // 优先级设最早，确保后续模块初始化时翻译已就绪
);

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
