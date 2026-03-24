<?php // phpcs:disable WordPress.Files.FileName
/**
 * Bootstrap the theme.
 *
 * 原 Init.php 的选项解析与模块初始化逻辑已合并至此文件。
 * Init 类被废弃，不再需要。
 *
 * @package Lerm
 */

declare( strict_types=1 );

use Lerm\Core\Setup;
use Lerm\Core\Enqueue;
use Lerm\Core\Customizer;
use Lerm\Core\CommentWalker;
use Lerm\Runtime\Optimizer;
use Lerm\SEO\Manager as SeoManager;
use Lerm\Mail\Smtp;
use Lerm\Update\Updater;
use Lerm\Http\Rest\Router;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// 1. 读取主题选项
// ---------------------------------------------------------------------------

$options = get_option( 'lerm_theme_options', array() );

// ---------------------------------------------------------------------------
// 2. 选项映射：将扁平的 option 数组解析为各模块所需的参数结构
// ---------------------------------------------------------------------------

$optimize_options = array();
$mail_options     = array();
$seo_options      = array();
$sitemap_options  = array();
$custom_options   = array();
$updater_options  = array();
$login_options    = array();

if ( ! empty( $options ) ) {

	$optimize_options = array(
		'gravatar_accel'   => $options['super_gravatar'] ?? '',
		'admin_accel'      => (bool) ( $options['super_admin'] ?? false ),
		'google_replace'   => $options['super_googleapis'] ?? '',
		'super_optimize'   => (array) ( $options['super_optimize'] ?? array() ),
		'disable_pingback' => (bool) ( $options['disable_pingback'] ?? false ),
	);

	$mail_options = array(
		'email_notice' => $options['email_notice'] ?? '',
		'smtp_options' => (array) ( $options['smtp_options'] ?? array() ),
	);

	$seo_options = array(
		'baidu_submit' => $options['baidu_submit'] ?? '',
		'submit_url'   => $options['submit_url'] ?? '',
		'submit_token' => $options['submit_token'] ?? '',
		'separator'    => $options['title_sep'] ?? '',
		'html_slug'    => $options['html_slug'] ?? '',
		'keywords'     => array(),
		'description'  => '',
	);

	$sitemap_options = array(
		'sitemap_enable' => (bool) ( $options['sitemap_enable'] ?? false ),
		'post_type'      => (array) ( $options['exclude_post_types'] ?? array() ),
		'post_exclude'   => (array) ( $options['exclude_post'] ?? array() ),
		'page_exclude'   => (array) ( $options['exclude_page'] ?? array() ),
	);

	$custom_options = array(
		'large_logo'    => $options['large_logo'] ?? '',
		'mobile_logo'   => $options['mobile_logo'] ?? '',
		'content_width' => $options['content_width'] ?? '',
		'sidebar_width' => $options['sidebar_width'] ?? '',
		'custom_css'    => $options['custom_css'] ?? '',
	);

	$updater_options = array(
		'name' => 'Lerm',
		'repo' => 'lermnote/lerm',
		'slug' => 'lerm',
		'url'  => 'https://lerm.net',
		'ver'  => wp_get_theme()->get( 'Version' ),
	);

	// 修正原拼写错误：frontend_lgoin → frontend_login
	$login_options = array(
		'front_login_enable'  => (bool) ( $options['frontend_login'] ?? false ),
		'login_page_id'       => (int) ( $options['frontend_login_page'] ?? 0 ),
		'menu_login_item'     => $options['menu_login_item'] ?? '',
		'login_redirect_url'  => $options['login_redirect_url'] ?? '',
		'logout_redirect_url' => $options['logout_redirect_url'] ?? home_url(),
	);
}

// ---------------------------------------------------------------------------
// 3. 允许外部通过 filter 覆盖任意模块参数
// ---------------------------------------------------------------------------

$optimize_options = apply_filters( 'lerm_optimize_options', $optimize_options );
$mail_options     = apply_filters( 'lerm_mail_options', $mail_options );
$seo_options      = apply_filters( 'lerm_seo_options', $seo_options );
$sitemap_options  = apply_filters( 'lerm_sitemap_options', $sitemap_options );
$custom_options   = apply_filters( 'lerm_custom_options', $custom_options );
$updater_options  = apply_filters( 'lerm_updater_options', $updater_options );
$login_options    = apply_filters( 'lerm_login_options', $login_options );

// ---------------------------------------------------------------------------
// 4. 初始化各模块
// ---------------------------------------------------------------------------

// Core — 始终加载，无条件
Setup::instance();
Enqueue::instance();
CommentWalker::instance();

// Runtime 优化 — 有配置才启用
if ( ! empty( $optimize_options ) ) {
	Optimizer::instance( $optimize_options );
}

SeoManager::instance( array_merge( $seo_options, $sitemap_options ) );

// 自定义样式/Logo — 有配置才启用
if ( ! empty( $custom_options ) ) {
	Customizer::instance( $custom_options );
}

// SMTP 邮件 — 有配置才启用
if ( ! empty( $mail_options ) ) {
	Smtp::instance( $mail_options );
}

// 主题更新器 — 有配置才启用
if ( ! empty( $updater_options ) ) {
	Updater::instance( $updater_options );
}

// ---------------------------------------------------------------------------
// 5. REST API 路由 & Ajax Handler
// ---------------------------------------------------------------------------

Router::register();

// 本地头像替换 Gravatar（原 UserProfile::lerm_get_avatar，与 Ajax 无关，放这里更合适）
add_filter(
	'pre_get_avatar',
	static function ( $avatar, $id_or_email, $args ) {
		static $cache = array();
		$user         = false;
		if ( is_numeric( $id_or_email ) ) {
			$user = get_user_by( 'id', (int) $id_or_email );
		} elseif ( $id_or_email instanceof \WP_User ) {
			$user = $id_or_email;
		} elseif ( $id_or_email instanceof \WP_Comment ) {
			$user = get_user_by( 'id', (int) $id_or_email->user_id );
		}
		if ( ! $user ) {
			return $avatar;
		}
		if ( ! isset( $cache[ $user->ID ] ) ) {
			$cache[ $user->ID ] = (int) get_user_meta( $user->ID, 'avatar_id', true );
		}
		$avatar_id = $cache[ $user->ID ];
		if ( ! $avatar_id ) {
			return $avatar;
		}
		$url = wp_get_attachment_image_url( $avatar_id, 'thumbnail' );
		if ( ! $url ) {
			return $avatar;
		}
		return sprintf(
			'<img alt="%1$s" src="%2$s" class="avatar avatar-%3$d photo" height="%4$d" width="%5$d" loading="lazy" />',
			esc_attr( $args['alt'] ?? '' ),
			esc_url( $url ),
			(int) ( $args['size'] ?? 96 ),
			(int) ( $args['height'] ?? 96 ),
			(int) ( $args['width'] ?? 96 )
		);
	},
	10,
	3
);

// ---------------------------------------------------------------------------
// 6. 前台登录模块（可选）
// ---------------------------------------------------------------------------

if ( ! empty( $login_options['front_login_enable'] ) ) {
	// 导航栏登录菜单项
	if ( ! empty( $login_options['menu_login_item'] ) ) {
		\Lerm\View\NavMenu::init( $login_options );
	}
	do_action( 'lerm_front_login_init', $login_options );
}
