<?php // phpcs:disable WordPress.Files.FileName
/**
 * Bootstrap the theme.
 *
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
use Lerm\Core\OptionsHooks;

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
$enqueue_options  = array();
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
		'exclude_page'       => (array) ( $options['exclude_page'] ?? array() ),
		'exclude_post'       => (array) ( $options['exclude_post'] ?? array() ),
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
		'allow_registration'  => (bool) ( $options['frontend_regist'] ?? false ) && (bool) ( $options['users_can_register'] ?? false ),
		'users_can_register'  => (bool) ( $options['users_can_register'] ?? false ),
		'default_role'        => (string) ( $options['default_role'] ?? get_option( 'default_role', 'subscriber' ) ),
		'default_login_page'  => (bool) ( $options['default_login_page'] ?? false ),
		'front_user_center'   => (bool) ( $options['front_user_center'] ?? false ),
		'login_page_id'       => (int) ( $options['frontend_login_page'] ?? 0 ),
		'account_page_url'    => lerm_get_frontend_account_page_url(),
		'menu_login_item'     => $options['menu_login_item'] ?? '',
		'login_redirect_url'  => (bool) ( $options['login_redirect_url'] ?? false ) ? home_url( '/' ) : lerm_get_frontend_account_page_url(),
		'logout_redirect_url' => (bool) ( $options['logout_redirect_url'] ?? false ) ? home_url( '/' ) : lerm_get_frontend_auth_page_url( 'login' ),
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
		'header_bg_color'           => (string) ( $options['header_bg_color'] ?? '#fff' ),
		'slide_position'            => (string) ( $options['slide_position'] ?? '' ),
		'slide_enable'              => (bool) ( $options['slide_enable'] ?? false ),
		'slide_images'              => (array) ( $options['slide_images'] ?? array() ),
		'slide_indicators'          => (bool) ( $options['slide_indicators'] ?? false ),
		'slide_control'             => (bool) ( $options['slide_control'] ?? false ),
		'footer_menus'              => (int) ( $options['footer_menus'] ?? 0 ),
		'icp_num'                   => (string) ( $options['icp_num'] ?? '' ),
		'copyright'                 => (string) ( $options['copyright'] ?? '' ),
		'author_bio'                => (bool) ( $options['author_bio'] ?? false ),
		'single_sidebar_select'     => (string) ( $options['single_sidebar_select'] ?? 'home-sidebar' ),
		'blog_sidebar_select'       => (string) ( $options['blog_sidebar_select'] ?? 'home-sidebar' ),
		'front_page_sidebar'        => (string) ( $options['front_page_sidebar'] ?? 'home-sidebar' ),
		'page_sidebar'              => (string) ( $options['page_sidebar'] ?? 'home-sidebar' ),
		'breadcrumb_container'      => (string) ( $options['breadcrumb_container'] ?? 'nav' ),
		'breadcrumb_before'         => (string) ( $options['breadcrumb_before'] ?? '' ),
		'breadcrumb_after'          => (string) ( $options['breadcrumb_after'] ?? '' ),
		'breadcrumb_list_tag'       => (string) ( $options['breadcrumb_list_tag'] ?? 'ol' ),
		'breadcrumb_item_tag'       => (string) ( $options['breadcrumb_item_tag'] ?? 'li' ),
		'breadcrumb_separator'      => (string) ( $options['breadcrumb_separator'] ?? '/' ),
		'breadcrumb_front_show'     => (bool) ( $options['breadcrumb_front_show'] ?? false ),
		'breadcrumb_show_title'     => ! array_key_exists( 'breadcrumb_show_title', $options ) ? true : (bool) $options['breadcrumb_show_title'],
		'thumbnail_gallery'         => $options['thumbnail_gallery'] ?? '',
		'load_more'                 => (bool) ( $options['load_more'] ?? false ),
		'related_posts'             => (bool) ( $options['related_posts'] ?? false ),
		'related_number'            => max( 1, (int) ( $options['related_number'] ?? 5 ) ),
		'single_top'                => (array) ( $options['single_top'] ?? array() ),
		'single_bottom'             => (array) ( $options['single_bottom'] ?? array() ),
		'summary_meta'              => (array) ( $options['summary_meta'] ?? array() ),
		'social_share'              => array_keys( array_filter( (array) ( $options['social_share'] ?? array() ) ) ),
		'blogname'                  => (string) ( $options['blogname'] ?? '' ),
		'blogdesc'                  => (string) ( $options['blogdesc'] ?? '' ),
		'navbar_align'              => (string) ( $options['navbar_align'] ?? 'justify-content-md-end' ),
		'navbar_search'             => (bool) ( $options['navbar_search'] ?? false ),
		'sticky_header'             => (bool) ( $options['sticky_header'] ?? false ),
		'sticky_header_shrink'      => (bool) ( $options['sticky_header_shrink'] ?? false ),
		'transparent_header'        => (bool) ( $options['transparent_header'] ?? false ),
		'reading_progress'          => (bool) ( $options['reading_progress'] ?? false ),
		'reading_progress_color'    => (string) ( $options['reading_progress_color'] ?? '#0084ba' ),
		'reading_progress_height'   => (int) ( $options['reading_progress_height'] ?? 3 ),
		'back_to_top'               => ! isset( $options['back_to_top'] ) ? true : (bool) $options['back_to_top'],
		'back_to_top_threshold'     => (int) ( $options['back_to_top_threshold'] ?? 400 ),
		'qq_chat_enable'            => (bool) ( $options['qq_chat_enable'] ?? false ),
		'qq_chat_number'            => (string) ( $options['qq_chat_number'] ?? '' ),

		// ── Appearance › Dark mode ───────────────────────────────────────────
		'dark_mode_enable'          => (bool) ( $options['dark_mode_enable'] ?? false ),
		'dark_mode_default'         => (string) ( $options['dark_mode_default'] ?? 'system' ),
		'dark_mode_toggle_position' => (string) ( $options['dark_mode_toggle_position'] ?? 'navbar' ),

		// ── System › Custom scripts ─────────────────────────────────────────
		'head_scripts'              => $options['head_scripts'] ?? '',
		'footer_scripts'            => $options['footer_scripts'] ?? '',

		// ── Content › Post features ─────────────────────────────────────────
		'toc_enable'                => (bool) ( $options['toc_enable'] ?? false ),
		'toc_min_headings'          => (int) ( $options['toc_min_headings'] ?? 3 ),
		'toc_position'              => (string) ( $options['toc_position'] ?? 'before_content' ),
		'toc_collapsed'             => (bool) ( $options['toc_collapsed'] ?? false ),
		'post_likes_enable'         => (bool) ( $options['post_likes_enable'] ?? true ),
		'comment_likes_enable'      => (bool) ( $options['comment_likes_enable'] ?? true ),
		'post_views_enable'         => (bool) ( $options['post_views_enable'] ?? true ),
		'views_unique_only'         => (bool) ( $options['views_unique_only'] ?? true ),
		'share_position'            => (string) ( $options['share_position'] ?? 'bottom' ),
		'share_show_count'          => (bool) ( $options['share_show_count'] ?? false ),
		'post_copyright_enable'     => ! isset( $options['post_copyright_enable'] ) ? true : (bool) $options['post_copyright_enable'],
		'post_copyright_text'       => (string) ( $options['post_copyright_text'] ?? '' ),
		'search_results_per_page'   => (int) ( $options['search_results_per_page'] ?? 10 ),
		'search_placeholder'        => (string) ( $options['search_placeholder'] ?? '' ),

		// ── Content › Comments ──────────────────────────────────────────────
		'comments_enable'           => ! isset( $options['comments_enable'] ) ? true : (bool) $options['comments_enable'],
		'comments_require_login'    => (bool) ( $options['comments_require_login'] ?? false ),
		'comment_moderation'        => (bool) ( $options['comment_moderation'] ?? false ),
		'comments_per_page'         => (int) ( $options['comments_per_page'] ?? 20 ),
		'comment_nesting_depth'     => (int) ( $options['comment_nesting_depth'] ?? 3 ),
		'comment_form_fields'       => (array) ( $options['comment_form_fields'] ?? array( 'name', 'email' ) ),
		'comment_placeholder'       => (string) ( $options['comment_placeholder'] ?? '' ),
		'comment_min_length'        => (int) ( $options['comment_min_length'] ?? 10 ),
		'comment_max_length'        => (int) ( $options['comment_max_length'] ?? 2000 ),
		'comment_avatar_size'       => (int) ( $options['comment_avatar_size'] ?? 48 ),
		'comment_show_cravatar_tip' => ! isset( $options['comment_show_cravatar_tip'] ) ? true : (bool) $options['comment_show_cravatar_tip'],

		// ── Content › 404 page ──────────────────────────────────────────────
		'404_title'                 => (string) ( $options['404_title'] ?? '' ),
		'404_message'               => (string) ( $options['404_message'] ?? '' ),
		'404_button_text'           => (string) ( $options['404_button_text'] ?? '' ),
		'404_button_url'            => (string) ( $options['404_button_url'] ?? '' ),
		'404_image'                 => (array) ( $options['404_image'] ?? array() ),
		'404_show_search'           => ! isset( $options['404_show_search'] ) ? true : (bool) $options['404_show_search'],

		// ── Community › Social profiles ─────────────────────────────────────
		'social_weibo'              => (string) ( $options['social_weibo'] ?? '' ),
		'social_wechat'             => (string) ( $options['social_wechat'] ?? '' ),
		'social_qq'                 => (string) ( $options['social_qq'] ?? '' ),
		'social_bilibili'           => (string) ( $options['social_bilibili'] ?? '' ),
		'social_zhihu'              => (string) ( $options['social_zhihu'] ?? '' ),
		'social_douban'             => (string) ( $options['social_douban'] ?? '' ),
		'social_github'             => (string) ( $options['social_github'] ?? '' ),
		'social_twitter'            => (string) ( $options['social_twitter'] ?? '' ),
		'social_linkedin'           => (string) ( $options['social_linkedin'] ?? '' ),
		'social_instagram'          => (string) ( $options['social_instagram'] ?? '' ),
		'social_youtube'            => (string) ( $options['social_youtube'] ?? '' ),
		'social_email'              => (string) ( $options['social_email'] ?? '' ),
		'social_rss'                => ! isset( $options['social_rss'] ) ? true : (bool) $options['social_rss'],
		'social_profiles_position'  => (array) ( $options['social_profiles_position'] ?? array( 'footer', 'author_bio' ) ),
		'social_open_new_tab'       => ! isset( $options['social_open_new_tab'] ) ? true : (bool) $options['social_open_new_tab'],
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

// Wire options into WordPress core behaviour (comments, TOC, search, etc.)
OptionsHooks::instance( $template_options );

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
	add_filter(
		'pre_option_users_can_register',
		static fn() => ! empty( $login_options['allow_registration'] ) ? '1' : '0'
	);
	add_filter(
		'pre_option_default_role',
		static fn() => ! empty( $login_options['default_role'] ) ? $login_options['default_role'] : 'subscriber'
	);

	add_filter(
		'lerm_login_redirect_url',
		static fn( $default ) => ! empty( $login_options['login_redirect_url'] ) ? $login_options['login_redirect_url'] : $default
	);

	add_filter(
		'login_url',
		static function ( $login_url, $redirect ) {
			$url = lerm_get_frontend_auth_page_url( 'login' );

			if ( ! empty( $redirect ) ) {
				$url = add_query_arg( 'redirect_to', (string) $redirect, $url );
			}

			return $url;
		},
		10,
		2
	);

	add_filter(
		'lostpassword_url',
		static function ( $lostpassword_url, $redirect ) {
			$url = lerm_get_frontend_auth_page_url( 'reset' );

			if ( ! empty( $redirect ) ) {
				$url = add_query_arg( 'redirect_to', (string) $redirect, $url );
			}

			return $url;
		},
		10,
		2
	);

	if ( ! empty( $login_options['allow_registration'] ) ) {
		add_filter(
			'register_url',
			static fn() => lerm_get_frontend_auth_page_url( 'regist' )
		);
	}

	add_filter(
		'logout_redirect',
		static function ( $redirect_to ) use ( $login_options ) {
			return ! empty( $login_options['logout_redirect_url'] )
				? $login_options['logout_redirect_url']
				: $redirect_to;
		},
		10
	);

	if ( ! empty( $login_options['default_login_page'] ) ) {
		add_action(
			'login_init',
			static function () use ( $login_options ) {
				$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( (string) $_REQUEST['action'] ) ) : 'login';

				if ( in_array( $action, array( 'logout', 'rp', 'resetpass', 'postpass' ), true ) ) {
					return;
				}

				$tab = 'login';

				if ( in_array( $action, array( 'lostpassword', 'retrievepassword' ), true ) ) {
					$tab = 'reset';
				} elseif ( 'register' === $action && ! empty( $login_options['allow_registration'] ) ) {
					$tab = 'regist';
				}

				wp_safe_redirect( lerm_get_frontend_auth_page_url( $tab ) );
				exit;
			}
		);
	}
	// 导航栏登录菜单项
	if ( ! empty( $login_options['menu_login_item'] ) ) {
		\Lerm\View\NavMenu::init( $login_options );
	}
	do_action( 'lerm_front_login_init', $login_options );
}
