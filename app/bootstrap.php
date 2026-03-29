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
use Lerm\Runtime\Optimizer;
use Lerm\SEO\Manager as SeoManager;
use Lerm\Mail\Smtp;
use Lerm\Update\Updater;
use Lerm\Http\Rest\Router;
use Lerm\Runtime\Lazyload;

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
$layout_options   = array();
$tracking_options = array();
$template_options = array();

if ( ! empty( $options ) ) {

	$enqueue_options = array(
		'enable_code_highlight' => (bool) ( $options['enable_code_highlight'] ?? true ),
	);

	$optimize_options = array(
		'super_gravatar'   => $options['super_gravatar'] ?? '',
		'super_admin'      => (bool) ( $options['super_admin'] ?? false ),
		'super_googleapis' => $options['super_googleapis'] ?? '',
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
		'sitemap_enable'     => (bool) ( $options['sitemap_enable'] ?? false ),
		'exclude_post_types' => (array) ( $options['exclude_post_types'] ?? array() ),
		'exclude_page'       => (array) ( $options['exclude_post'] ?? array() ),
		'exclude_post'       => (array) ( $options['exclude_page'] ?? array() ),
	);

	$custom_options = array(
		'large_logo'    => $options['large_logo']['id'] ?? '',
		'mobile_logo'   => $options['mobile_logo']['id'] ?? '',
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

	// 修正原拼写错误：frontend_login → frontend_login
	$login_options = array(
		'front_login_enable'  => (bool) ( $options['frontend_login'] ?? false ),
		'login_page_id'       => (int) ( $options['frontend_login_page'] ?? 0 ),
		'menu_login_item'     => $options['menu_login_item'] ?? '',
		'login_redirect_url'  => (bool) ( $options['login_redirect_url'] ?? false ) ? home_url( '/' ) : '',
		'logout_redirect_url' => $options['logout_redirect_url'] ?? home_url(),
	);

	$layout_options = array(
		'global_layout'   => (string) ( $options['global_layout'] ?? 'layout-2c-r' ),
		'layout_style'    => (string) ( $options['layout_style'] ?? '' ),
		'loading_animate' => (bool) ( $options['loading-animate'] ?? false ),
	);

	$tracking_options = array(
		'baidu_tongji' => (string) ( $options['baidu_tongji'] ?? '' ),
	);

	$template_options = array(
		'header_bg_color'       => (string) ( $options['header_bg_color'] ?? '#fff' ),
		'slide_position'        => (string) ( $options['slide_position'] ?? '' ),
		'slide_enable'          => (bool) ( $options['slide_enable'] ?? false ),
		'slide_images'          => (array) ( $options['slide_images'] ?? array() ),
		'slide_indicators'      => (bool) ( $options['slide_indicators'] ?? false ),
		'slide_control'         => (bool) ( $options['slide_control'] ?? false ),
		'icp_num'               => (string) ( $options['icp_num'] ?? '' ),
		'copyright'             => (string) ( $options['copyright'] ?? '' ),
		'author_bio'            => (bool) ( $options['author_bio'] ?? false ),
		'single_sidebar_select' => (string) ( $options['single_sidebar_select'] ?? 'home-sidebar' ),
		'blog_sidebar_select'   => (string) ( $options['blog_sidebar_select'] ?? 'home-sidebar' ),
		'front_page_sidebar'    => (string) ( $options['front_page_sidebar'] ?? 'home-sidebar' ),
		'page_sidebar'          => (string) ( $options['page_sidebar'] ?? 'home-sidebar' ),
		'breadcrumb_container'  => (string) ( $options['breadcrumb_container'] ?? 'nav' ),
		'breadcrumb_before'     => (string) ( $options['breadcrumb_before'] ?? '' ),
		'breadcrumb_after'      => (string) ( $options['breadcrumb_after'] ?? '' ),
		'breadcrumb_list_tag'   => (string) ( $options['breadcrumb_list_tag'] ?? 'ol' ),
		'breadcrumb_item_tag'   => (string) ( $options['breadcrumb_item_tag'] ?? 'li' ),
		'breadcrumb_separator'  => (string) ( $options['breadcrumb_separator'] ?? '/' ),
		'breadcrumb_front_show' => (bool) ( $options['breadcrumb_front_show'] ?? false ),
		'breadcrumb_show_title' => ! array_key_exists( 'breadcrumb_show_title', $options ) ? true : (bool) $options['breadcrumb_show_title'],
		'thumbnail_gallery'     => $options['thumbnail_gallery'] ?? '',
		'load_more'             => (bool) ( $options['load_more'] ?? false ),
		'related_posts'         => (bool) ( $options['related_posts'] ?? false ),
		'related_number'        => max( 1, (int) ( $options['related_number'] ?? 5 ) ),
		'single_top'            => (array) ( $options['single_top'] ?? array() ),
		'single_bottom'         => (array) ( $options['single_bottom'] ?? array() ),
		'summary_meta'          => (array) ( $options['summary_meta'] ?? array() ),
		'social_share'          => array_keys( array_filter( (array) ( $options['social_share'] ?? array() ) ) ),
		'blogname'              => (string) ( $options['blogname'] ?? '' ),
		'blogdesc'              => (string) ( $options['blogdesc'] ?? '' ),
		'narbar_align'          => (string) ( $options['narbar_align'] ?? 'justify-content-md-start' ),
		'narbar_search'         => (bool) ( $options['narbar_search'] ?? false ),
	);
}

// ---------------------------------------------------------------------------
// 3. 允许外部通过 filter 覆盖任意模块参数
// ---------------------------------------------------------------------------
$enqueue_options  = apply_filters( 'lerm_enqueue_options', $enqueue_options );
$optimize_options = apply_filters( 'lerm_optimize_options', $optimize_options );
$mail_options     = apply_filters( 'lerm_mail_options', $mail_options );
$seo_options      = apply_filters( 'lerm_seo_options', $seo_options );
$sitemap_options  = apply_filters( 'lerm_sitemap_options', $sitemap_options );
$custom_options   = apply_filters( 'lerm_custom_options', $custom_options );
$updater_options  = apply_filters( 'lerm_updater_options', $updater_options );
$login_options    = apply_filters( 'lerm_login_options', $login_options );

add_filter(
	'lerm_layout_options',
	static function ( $defaults ) use ( $layout_options ) {
		return wp_parse_args( $layout_options, (array) $defaults );
	}
);

add_filter(
	'lerm_tracking_options',
	static function ( $defaults ) use ( $tracking_options ) {
		return wp_parse_args( $tracking_options, (array) $defaults );
	}
);

add_filter(
	'lerm_template_options',
	static function ( $defaults ) use ( $template_options ) {
		return wp_parse_args( $template_options, (array) $defaults );
	}
);

// ---------------------------------------------------------------------------
// 4. 初始化各模块
// ---------------------------------------------------------------------------
Setup::instance(
	array(
		'excerpt_length'         => (int) ( $options['excerpt_length'] ?? 100 ),
		'comment_excerpt_length' => (int) ( $options['comment_excerpt_length'] ?? 100 ),
	)
);

Enqueue::instance( $enqueue_options );


// Runtime 优化 — 有配置才启用
if ( ! empty( $optimize_options ) ) {
	Optimizer::instance( $optimize_options );
}

//SeoManager::instance( array_merge( $seo_options, $sitemap_options ) );

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
if ( ! empty( $options['enable_cdn'] ) && ! empty( $options['off_new_url'] ) ) {
	add_action( 'template_redirect', 'Lerm\Runtime\do_ossdl_off_ob_start' );
}
if ( ! empty( $options['lazyload'] ) ) {
	Lazyload::instance();
}
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
