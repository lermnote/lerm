<?php // phpcs:disable WordPress.Files.FileName

declare(strict_types=1);

namespace Lerm\Core;

use Lerm\Traits\Singleton;
use Lerm\Traits\Hooker;

/**
 * Class Optimizer
 *
 * 负责清理 WP head、替换外部资源、以及一组“优化”开关。
 */
final class Optimizer {
	use Singleton;
	use Hooker;

	/**
	 * Default configuration.
	 *
	 * @var array<string, mixed>
	 */
	protected static array $default_config = array(
		'gravatar_accel'   => 'disable',
		'admin_accel'      => false,
		'google_replace'   => 'disable',
		'disable_pingback' => false,
		'super_optimize'   => array(),
	);

	/** @var array<string, mixed> Current merged config. */
	protected static array $config = array();

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $params
	 */
	public function __construct( array $params = array() ) {
		self::$config = apply_filters( 'lerm_optimize_args', wp_parse_args( $params, self::$default_config ) );
		self::register_hooks( self::$config );
	}

	/**
	 * 注册所需的 Hook 与 Filter。
	 *
	 * @param array<string, mixed> $config
	 *
	 * @return void
	 */
	protected static function register_hooks( array $config = array() ): void {
		// gravatar 替换
		if ( ! in_array( $config['gravatar_accel'], array( 'disable', '' ), true ) ) {
			self::filters( array( 'um_user_avatar_url_filter', 'bp_gravatar_url', 'get_avatar_url' ), array( __CLASS__, 'replace_gravatar_url' ), 100, 1 );
		}

		// cravatar 特殊处理
		if ( 'https://cravatar.cn/avatar/' === $config['gravatar_accel'] ) {
			self::filter( 'user_profile_picture_description', array( __CLASS__, 'get_cravatar_profile_link' ), 100, 1 );
			self::filter( 'avatar_defaults', array( __CLASS__, 'add_cravatar_default' ), 100, 1 );
		}

		// admin 静态资源加速（输出缓冲替换）
		if ( $config['admin_accel'] ) {
			self::action( 'init', array( __CLASS__, 'replace_admin_static_urls' ), 100, 1 );
			self::action( 'shutdown', array( __CLASS__, 'flush_output_buffer' ), 100, 1 );
		}

		// Google 服务替换
		if ( ! in_array( $config['google_replace'], array( 'disable', '' ), true ) ) {
			self::action( 'init', array( __CLASS__, 'replace_google_services' ), 100, 1 );
			self::action( 'shutdown', array( __CLASS__, 'flush_output_buffer' ), 100, 1 );
		}

		// 关闭自我 pingback
		if ( $config['disable_pingback'] ) {
			self::action( 'pre_ping', array( __CLASS__, 'filter_out_self_pings' ) );
		}

		// 超级优化项
		if ( is_array( $config['super_optimize'] ) && ! empty( $config['super_optimize'] ) ) {
			self::apply_optimizations( $config['super_optimize'] );
		}

		// 清理菜单属性
		self::filters( array( 'nav_menu_css_class', 'nav_menu_item_id', 'page_css_class' ), array( __CLASS__, 'filter_menu_css_classes' ), 100, 1 );
	}

	/**
	 * 根据选项移除不需要的 head / 内置功能。
	 *
	 * @param array<int, string> $flags
	 *
	 * @return void
	 */
	public static function apply_optimizations( array $flags = array() ): void {
		$head_actions = array(
			'rsd_link',
			'wlwmanifest_link',
			'wp_generator',
			'start_post_rel_link',
			'index_rel_link',
			'adjacent_posts_rel_link_wp_head',
			'rel_canonical',
			'wp_shortlink_wp_head',
		);

		if ( ! empty( $flags ) ) {
			foreach ( $head_actions as $action ) {
				if ( in_array( $action, $flags, true ) ) {
					remove_action( 'wp_head', $action );
				}
			}

			if ( in_array( 'feed_links', $flags, true ) ) {
				remove_action( 'wp_head', 'feed_links', 2 );
				remove_action( 'wp_head', 'feed_links_extra', 3 );
			}

			if ( in_array( 'remove_rest_api', $flags, true ) ) {
				self::remove_rest_api_links();
			}

			if ( in_array( 'disable_rest_api', $flags, true ) ) {
				add_filter( 'rest_authentication_errors', array( __CLASS__, 'rest_authorization_error' ) );
			}

			if ( in_array( 'remove_ver', $flags, true ) ) {
				add_filter( 'script_loader_src', array( __CLASS__, 'strip_version_query' ) );
				add_filter( 'style_loader_src', array( __CLASS__, 'strip_version_query' ) );
			}

			if ( in_array( 'disable_emojis', $flags, true ) ) {
				self::disable_emojis();
			}

			if ( in_array( 'remove_recent_comments_css', $flags, true ) ) {
				add_filter( 'show_recent_comments_widget_style', '__return_false' );
			}

			if ( in_array( 'disable_oembed', $flags, true ) ) {
				self::disable_oembed();
			}

			if ( in_array( 'remove_global_styles_render_svg', $flags, true ) ) {
				self::remove_global_styles_svg();
			}
		}

		remove_action( 'embed_footer', 'print_embed_sharing_dialog' );
		remove_action( 'embed_footer', 'print_embed_sharing_icon' );
		add_action( 'embed_footer', array( __CLASS__, 'output_embed_footer_style' ) );
	}

	/* -----------------------------
	 * 下列为具体实现方法（snake_case 命名）
	 * ----------------------------*/

	public static function disable_emojis(): void {
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'embed_head', 'print_emoji_detection_script' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'emoji_svg_url', '__return_false' );
		add_filter( 'tiny_mce_plugins', array( __CLASS__, 'remove_emojis_tinymce_plugins' ) );
	}

	public static function disable_oembed(): void {
		remove_action( 'rest_api_init', 'wp_oembed_register_route' );
		remove_filter( 'rest_pre_serve_request', '_oembed_rest_pre_serve_request', 10, 4 );
		remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
		remove_filter( 'oembed_response_data', 'get_oembed_response_data_rich', 10, 4 );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		add_filter( 'embed_oembed_discover', '__return_false' );
		add_filter( 'rewrite_rules_array', array( __CLASS__, 'filter_disable_embeds_rewrites' ) );
		add_filter( 'tiny_mce_plugins', array( __CLASS__, 'remove_wpembed_tinymce_plugins' ) );
	}

	public static function remove_global_styles_svg(): void {
		remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
		remove_filter( 'render_block', 'wp_render_layout_support_flag' );
		// 如果想彻底移除全局样式，请取消下面注释
		// remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
		// remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
	}

	public static function remove_rest_api_links(): void {
		remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
		remove_action( 'xml_rsd_apis', 'rest_output_rsd' );
		remove_action( 'template_redirect', 'rest_output_linkheader', 11 );
	}

	public static function remove_wpembed_tinymce_plugins( array $plugins ): array {
		return array_diff( $plugins, array( 'wpembed' ) );
	}

	public static function filter_disable_embeds_rewrites( array $rules ): array {
		foreach ( $rules as $rule => $rewrite ) {
			if ( false !== strpos( $rewrite, 'embed=true' ) ) {
				unset( $rules[ $rule ] );
			}
		}
		return $rules;
	}

	public static function remove_emojis_tinymce_plugins( array $plugins ): array {
		return array_diff( $plugins, array( 'wpemoji' ) );
	}

	/**
	 * 当被用作 rest_authentication_errors 过滤器时，返回 WP_Error 以阻止未授权访问。
	 *
	 * @return \WP_Error
	 */
	public static function rest_authorization_error(): \WP_Error {
		return new \WP_Error(
			'rest_forbidden',
			__( 'REST API forbidden', 'lerm' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	public static function strip_version_query( string $url = '' ): string|false {
		return $url ? remove_query_arg( 'ver', $url ) : false;
	}

	public static function add_reply_button_classes( string $html ): string {
		return str_replace( "class='", "class='btn btn-sm btn-custom ", $html );
	}

	public static function filter_menu_css_classes( $attr ) {
		return is_array( $attr ) ? array_intersect( $attr, array( 'nav-item', 'active', 'dropdown', 'open', 'show' ) ) : array();
	}

	public static function output_embed_footer_style(): void {
		echo '<style>.wp-embed-share{display:none;}</style>';
	}

	public static function filter_out_self_pings( array &$links ): void {
		$home_url = home_url();
		$links    = array_filter(
			$links,
			static function ( string $link ) use ( $home_url ): bool {
				return strpos( $link, $home_url ) !== 0;
			}
		);
	}

	public static function replace_gravatar_url( string $subject ): string {
		$pattern = '/https?.*?\/avatar\//i';
		$replace = self::$config['gravatar_accel'] ?? self::$default_config['gravatar_accel'];
		return preg_replace( $pattern, $replace, $subject );
	}

	public static function add_cravatar_default( array $avatar_defaults ): array {
		$avatar_defaults['gravatar_default'] = 'Cravatar avatar';
		return $avatar_defaults;
	}

	public static function get_cravatar_profile_link(): string {
		return '<a href="https://cravatar.cn" target="_blank">您可以在 Cravatar 修改您的资料图片</a>';
	}

	public static function replace_admin_static_urls(): void {
		$pattern = '~' . home_url( '/' ) . '(wp-admin|wp-includes)/(css|js)/~';
		$replace = sprintf( 'https://wpstatic.cdn.haozi.net/%s/$1/$2/', $GLOBALS['wp_version'] );
		self::start_output_buffer_replace( 'preg_replace', $pattern, $replace );
	}

	public static function replace_google_services(): void {
		$services = array(
			'geekzu' => array( '//fonts.geekzu.org', '//gapis.geekzu.org/ajax', '//gapis.geekzu.org/g-fonts', '//gapis.geekzu.org/g-themes' ),
			'loli'   => array( '//fonts.loli.net', '//ajax.loli.net', '//gstatic.loli.net', '//themes.loli.net' ),
			'ustc'   => array( '//fonts.lug.ustc.edu.cn', '//ajax.lug.ustc.edu.cn', '//fonts-gstatic.lug.ustc.edu.cn', '//google-themes.lug.ustc.edu.cn' ),
		);

		$search  = array( '//fonts.googleapis.com', '//ajax.googleapis.com', '//fonts.gstatic.com', '//themes.googleusercontent.com' );
		$replace = $services[ self::$config['google_replace'] ] ?? null;

		if ( is_array( $replace ) ) {
			self::start_output_buffer_replace( 'str_replace', $search, $replace );
		}
	}

	/**
	 * 启动输出缓冲并在回调中执行替换。
	 *
	 * @param string $callback either 'preg_replace' or 'str_replace' etc.
	 * @param mixed  $pattern
	 * @param mixed  $replace
	 *
	 * @return void
	 */
	public static function start_output_buffer_replace( string $callback, $pattern, $replace ): void {
		ob_start(
			function ( $buffer ) use ( $callback, $pattern, $replace ) {
				return call_user_func( $callback, $pattern, $replace, $buffer );
			}
		);
	}

	public static function flush_output_buffer(): void {
		if ( ob_get_level() > 0 ) {
			ob_end_flush();
		}
	}
}
