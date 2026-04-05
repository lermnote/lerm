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
use Lerm\Core\CssVariables;
use Lerm\Runtime\Optimizer;
use Lerm\SEO\Manager as SeoManager;
use Lerm\Mail\Smtp;
use Lerm\Update\Updater;
use Lerm\Http\Rest\Router;
use Lerm\Runtime\Lazyload;
use Lerm\OptionsFramework\Framework as OptionsFramework;
use Lerm\OptionsFramework\Integrations\LermTheme\OptionsPageDefinition;

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
		'enable_code_highlight'     => (bool) ( $options['enable_code_highlight'] ?? true ),
		// Header behaviour
		'sticky_header'             => (bool) ( $options['sticky_header'] ?? false ),
		'sticky_header_shrink'      => (bool) ( $options['sticky_header_shrink'] ?? false ),
		'transparent_header'        => (bool) ( $options['transparent_header'] ?? false ),
		// Reading progress bar
		'reading_progress'          => (bool) ( $options['reading_progress'] ?? false ),
		// Back-to-top button
		'back_to_top'               => (bool) ( $options['back_to_top'] ?? true ),
		'back_to_top_threshold'     => (int) ( $options['back_to_top_threshold'] ?? 400 ),
		// Dark mode
		'dark_mode_enable'          => (bool) ( $options['dark_mode_enable'] ?? false ),
		'dark_mode_default'         => (string) ( $options['dark_mode_default'] ?? 'system' ),
		'dark_mode_toggle_position' => (string) ( $options['dark_mode_toggle_position'] ?? 'navbar' ),
		// QQ live chat
		'qq_chat_enable'            => (bool) ( $options['qq_chat_enable'] ?? false ),
		'qq_chat_number'            => (string) ( $options['qq_chat_number'] ?? '' ),
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
		'baidu_submit'         => $options['baidu_submit'] ?? '',
		'submit_url'           => $options['submit_url'] ?? '',
		'submit_token'         => $options['submit_token'] ?? '',
		'separator'            => $options['title_sep'] ?? '',
		'html_slug'            => $options['html_slug'] ?? '',
		'keywords'             => array_values(
			array_filter(
				array_map(
					'trim',
					explode( ',', (string) ( $options['keywords'] ?? '' ) )
				)
			)
		),
		'description'          => (string) ( $options['seo_description'] ?? '' ),
		'title_structure'      => (array) ( $options['title_structure'] ?? array() ),
		'post_title_structure' => (array) ( $options['post_title_structure'] ?? array() ),
		'page_title_structure' => (array) ( $options['page_title_structure'] ?? array() ),
	);

	$sitemap_options = array(
		'sitemap_enable' => ! array_key_exists( 'sitemap_enable', $options ) ? true : (bool) $options['sitemap_enable'],
		'post_type'      => array_values(
			array_intersect(
				(array) ( $options['exclude_post_types'] ?? array() ),
				array( 'page', 'post', 'users' )
			)
		),
		'taxonomy'       => array_values(
			array_intersect(
				(array) ( $options['exclude_post_types'] ?? array() ),
				array( 'category', 'post_tag', 'format' )
			)
		),
		'page_exclude'   => (array) ( $options['exclude_page'] ?? array() ),
		'post_exclude'   => (array) ( $options['exclude_post'] ?? array() ),
		'exclude_categories' => (array) ( $options['exclude_categories'] ?? array() ),
		'exclude_tags'       => (array) ( $options['exclude_tags'] ?? array() ),
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
		'loading_animate' => (bool) ( $options['loading_animate'] ?? false ),
	);

	$tracking_options = array(
		'baidu_tongji' => (string) ( $options['baidu_tongji'] ?? '' ),
	);

	$template_options = array(
		'footer_menus'              => (int) ( $options['footer_menus'] ?? 0 ),
		'header_bg_color'           => (string) ( $options['header_bg_color'] ?? '#fff' ),
		'slide_position'            => (string) ( $options['slide_position'] ?? '' ),
		'slide_enable'              => (bool) ( $options['slide_enable'] ?? false ),
		'slide_images'              => (array) ( $options['slide_images'] ?? array() ),
		'slide_indicators'          => (bool) ( $options['slide_indicators'] ?? false ),
		'slide_control'             => (bool) ( $options['slide_control'] ?? false ),
		'head_scripts'              => (string) ( $options['head_scripts'] ?? '' ),
		'footer_scripts'            => (string) ( $options['footer_scripts'] ?? '' ),
		'icp_num'                   => (string) ( $options['icp_num'] ?? '' ),
		'copyright'                 => (string) ( $options['copyright'] ?? '' ),
		'author_bio'                => (bool) ( $options['author_bio'] ?? false ),
		'post_navigation'           => ! array_key_exists( 'post_navigation', $options ) ? true : (bool) $options['post_navigation'],
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
		'summary_or_full'           => (string) ( $options['summary_or_full'] ?? 'content_summary' ),
		'show_thumbnail'            => ! array_key_exists( 'show_thumbnail', $options ) ? true : (bool) $options['show_thumbnail'],
		'thumbnail_gallery'         => $options['thumbnail_gallery'] ?? array(),
		'load_more'                 => (bool) ( $options['load_more'] ?? false ),
		'related_posts'             => (bool) ( $options['related_posts'] ?? false ),
		'related_number'            => max( 1, (int) ( $options['related_number'] ?? 5 ) ),
		'single_top'                => (array) ( $options['single_top'] ?? array() ),
		'single_bottom'             => (array) ( $options['single_bottom'] ?? array() ),
		'summary_meta'              => (array) ( $options['summary_meta'] ?? array() ),
		'social_share'              => (array) ( $options['social_share'] ?? array() ),
		'blogname'                  => (string) ( $options['blogname'] ?? '' ),
		'blogdesc'                  => (string) ( $options['blogdesc'] ?? '' ),
		'navbar_align'              => (string) ( $options['navbar_align'] ?? 'justify-content-md-start' ),
		'navbar_search'             => (bool) ( $options['navbar_search'] ?? false ),
		'dark_mode_enable'          => (bool) ( $options['dark_mode_enable'] ?? false ),
		'dark_mode_toggle_position' => (string) ( $options['dark_mode_toggle_position'] ?? 'navbar' ),
		'reading_progress_height'   => max( 1, (int) ( $options['reading_progress_height'] ?? 3 ) ),
		'back_to_top_threshold'     => max( 100, (int) ( $options['back_to_top_threshold'] ?? 400 ) ),
		'qq_chat_enable'            => (bool) ( $options['qq_chat_enable'] ?? false ),
		'qq_chat_number'            => (string) ( $options['qq_chat_number'] ?? '' ),
		'toc_enable'                => (bool) ( $options['toc_enable'] ?? false ),
		'toc_min_headings'          => max( 1, (int) ( $options['toc_min_headings'] ?? 3 ) ),
		'toc_position'              => (string) ( $options['toc_position'] ?? 'before_content' ),
		'toc_collapsed'             => (bool) ( $options['toc_collapsed'] ?? false ),
		'post_likes_enable'         => ! array_key_exists( 'post_likes_enable', $options ) ? true : (bool) $options['post_likes_enable'],
		'comment_likes_enable'      => ! array_key_exists( 'comment_likes_enable', $options ) ? true : (bool) $options['comment_likes_enable'],
		'post_views_enable'         => ! array_key_exists( 'post_views_enable', $options ) ? true : (bool) $options['post_views_enable'],
		'share_show_count'          => (bool) ( $options['share_show_count'] ?? false ),
		'ladding_animate'           => (bool) ( $options['ladding_animate'] ?? false ),
		'post_copyright_enable'     => ! array_key_exists( 'post_copyright_enable', $options ) ? true : (bool) $options['post_copyright_enable'],
		'post_copyright_text'       => (string) ( $options['post_copyright_text'] ?? '' ),
		'search_placeholder'        => (string) ( $options['search_placeholder'] ?? '' ),
		'share_position'            => (string) ( $options['share_position'] ?? 'bottom' ),
		'comments_enable'           => ! array_key_exists( 'comments_enable', $options ) ? true : (bool) $options['comments_enable'],
		'comments_require_login'    => (bool) ( $options['comments_require_login'] ?? false ),
		'comments_per_page'         => max( 1, (int) ( $options['comments_per_page'] ?? 20 ) ),
		'comment_avatar_size'       => max( 24, (int) ( $options['comment_avatar_size'] ?? 48 ) ),
		'comment_show_cravatar_tip' => ! isset( $options['comment_show_cravatar_tip'] ) || ! empty( $options['comment_show_cravatar_tip'] ),
		'comment_placeholder'       => (string) ( $options['comment_placeholder'] ?? __( 'Leave a comment...', 'lerm' ) ),
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
		'social_rss'                => ! isset( $options['social_rss'] ) || ! empty( $options['social_rss'] ),
		'social_profiles_position'  => (array) ( $options['social_profiles_position'] ?? array( 'footer', 'author_bio' ) ),
		'social_open_new_tab'       => ! isset( $options['social_open_new_tab'] ) || ! empty( $options['social_open_new_tab'] ),
		'404_title'                 => (string) ( $options['404_title'] ?? '' ),
		'404_message'               => (string) ( $options['404_message'] ?? '' ),
		'404_button_text'           => (string) ( $options['404_button_text'] ?? '' ),
		'404_button_url'            => (string) ( $options['404_button_url'] ?? '' ),
		'404_image_id'              => (string) ( $options['404_image']['id'] ?? '' ),
		'404_show_search'           => ! isset( $options['404_show_search'] ) || ! empty( $options['404_show_search'] ),
	);
}
if ( is_admin() ) {
	OptionsFramework::instance()->mount_options_page( OptionsPageDefinition::definition() );
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

// CSS 自定义属性（Design Token）— 始终初始化，只在有值时输出变量
CssVariables::init( $options );

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

if ( ! empty( $options['register_sidebars'] ) ) {
	add_action(
		'widgets_init',
		static function () use ( $options ) {
			foreach ( (array) $options['register_sidebars'] as $sidebar ) {
				if ( ! is_array( $sidebar ) ) {
					continue;
				}

				$title = trim( (string) ( $sidebar['sidebar_title'] ?? '' ) );

				if ( '' === $title ) {
					continue;
				}

				$sidebar_id = sanitize_title( $title ) . '-sidebar';

				register_sidebar(
					array(
						'name'          => $title,
						'id'            => $sidebar_id,
						'description'   => sprintf( __( 'Custom sidebar: %s', 'lerm' ), $title ),
						'before_widget' => '<section id="%1$s" class="card widget mb-3 %2$s loading-animate animate__fadeIn">',
						'after_widget'  => '</section>',
						'before_title'  => '<h4 class="widget-title card-header border-bottom-0"><span class="wrap d-inline-block fa">',
						'after_title'   => '</span></h4>',
					)
				);
			}
		},
		20
	);
}

if ( ! empty( $options['search_filter'] ) || ! empty( $options['cat_exclude'] ) ) {
	add_action(
		'pre_get_posts',
		static function ( \WP_Query $query ) use ( $options ) {
			if ( is_admin() || ! $query->is_main_query() ) {
				return;
			}

			if ( ! empty( $options['search_filter'] ) && $query->is_search() ) {
				$query->set( 'post_type', array( 'post' ) );
			}

			if ( ! empty( $options['cat_exclude'] ) && $query->is_home() ) {
				$query->set( 'category__not_in', array_map( 'absint', (array) $options['cat_exclude'] ) );
			}
		}
	);
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
