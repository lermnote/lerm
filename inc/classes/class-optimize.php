<?php

namespace Lerm\Inc;

use Lerm\Inc\Traits\Hooker;

class Optimize {

	use hooker;

	public static $args = array(
		'gravatar_accel' => 'disable',
		'admin_accel'    => false,
		'google_replace' => 'disable',
		'super_optimize' => array(),
	);

	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_optimize_', wp_parse_args( $params, self::$args ) );
		self::optimize( self::$args['super_optimize'] );
		self::hooks();
	}

	// instance
	public static function instance( $params = array() ) {
		return new self( $params );
	}

	protected function hooks() {
		if ( ! in_array( self::$args['gravatar_accel'], array( 'disable', '' ), true ) ) {
			$this->filters( array( 'um_user_avatar_url_filter', 'bp_gravatar_url', 'get_avatar_url' ), 'gravatar_replace', 100, 1 );
		}
		if ( 'https://cravatar.cn/avatar/' === self::$args['gravatar_accel'] ) {
			add_filter( 'user_profile_picture_description', 'set_user_profile_picture_for_cravatar', 100, 1 );
			add_filter( 'avatar_defaults', 'set_defaults_for_cravatar', 100, 1 );
		}
		if ( self::$args['admin_accel'] ) {
			add_action( 'init', array( __NAMESPACE__ . '\Optimize', 'super_admin' ), 100, 1 );
			add_action( 'shutdown', array( __NAMESPACE__ . '\Optimize', 'ob_buffer_end' ), 100, 1 );
		}
		if ( ! in_array( self::$args['google_replace'], array( 'disable', '' ), true ) ) {
			add_action( 'init', array( __NAMESPACE__ . '\Optimize', 'googleapis_replace' ), 100, 1 );
			add_action( 'shutdown', array( __NAMESPACE__ . '\Optimize', 'ob_buffer_end' ), 100, 1 );
		}
		add_filter( 'frontpage_template', array( __NAMESPACE__ . '\Optimize', 'front_page_template' ), 15, 1 );
		add_filter( 'wp_tag_cloud', array( __NAMESPACE__ . '\Optimize', 'tag_cloud' ), 10, 1 );
		add_filter( 'pre_option_link_manager_enabled', '__return_true' );
		$this->filters( array( 'nav_menu_css_class', 'nav_menu_item_id', 'page_css_class' ), 'remove_css_attributes', 100, 1 );
	}

	/**
	 * Clean up wp_head() from unused or unsecure stuff.
	 *
	 * @return void
	 */
	public static function optimize( $args = array() ) {
		// Remove head links.
		$actions = array( 'rsd_link', 'wlwmanifest_link', 'wp_generator', 'start_post_rel_link', 'index_rel_link', 'adjacent_posts_rel_link_wp_head', 'rel_canonical' );
		if ( is_array( $args ) && ! empty( $args ) ) {
			foreach ( $actions as $value ) {
				if ( in_array( $value, $args, true ) ) {
					remove_action( 'wp_head', $value );
				}
			}
			// remove feed links in head
			if ( in_array( 'feed_links', $args, true ) ) {
				remove_action( 'wp_head', 'feed_links', 2 );
				remove_action( 'wp_head', 'feed_links_extra', 3 );
			}
			if ( in_array( 'remove_rest_api', $args, true ) ) {
				self::remove_rest_api();
			}
			if ( in_array( 'disable_rest_api', $args, true ) ) {
				add_filter( 'rest_authentication_errors', array( __NAMESPACE__ . '\Optimize', 'rest_authorization_error' ) );
			}
			if ( in_array( 'remove_ver', $args, true ) ) {
				add_filter( 'script_loader_src', array( __NAMESPACE__ . '\Optimize', 'remove_ver' ) );
				add_filter( 'style_loader_src', array( __NAMESPACE__ . '\Optimize', 'remove_ver' ) );
			}
			if ( in_array( 'wp_shortlink_wp_head', $args, true ) ) {
				remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );
			}
			if ( in_array( 'disable_emojis', $args, true ) ) {
				remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
				remove_action( 'admin_print_styles', 'print_emoji_styles' );
				remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
				remove_action( 'wp_print_styles', 'print_emoji_styles' );
				remove_action( 'embed_head', 'print_emoji_detection_script' );
				remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
				remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
				remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
				add_filter( 'emoji_svg_url', '__return_false' );
				add_filter( 'tiny_mce_plugins', array( __NAMESPACE__ . '\Optimize', 'remove_emojis_tinymce_plugins' ) );
			}
			if ( in_array( 'remove_recent_comments_css', $args, true ) ) {
				add_filter( 'show_recent_comments_widget_style', '__return_false' );
			}
			if ( in_array( 'disable_oembed', $args, true ) ) {
				remove_action( 'rest_api_init', 'wp_oembed_register_route' );
				remove_filter( 'rest_pre_serve_request', '_oembed_rest_pre_serve_request', 10, 4 );
				remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
				remove_filter( 'oembed_response_data', 'get_oembed_response_data_rich', 10, 4 );
				remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
				remove_action( 'wp_head', 'wp_oembed_add_host_js' );
				add_filter( 'embed_oembed_discover', '__return_false' );
				add_filter( 'rewrite_rules_array', array( __NAMESPACE__ . '\Optimize', 'disable_embeds_rewrites' ) );
				add_filter( 'tiny_mce_plugins', array( __NAMESPACE__ . '\Optimize', 'remove_wpembed_tinymce_plugins' ) );
			}
			if ( in_array( 'remove_global_styles_render_svg', $args, true ) ) {
				remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
				remove_filter( 'render_block', 'wp_render_layout_support_flag' );
				// 移除 SVG 和全局样式
				// remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
				// 删除添加全局内联样式的 wp_footer 操作
				// remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
				// 删除render_block 过滤器
				// remove_filter('render_block', 'wp_render_duotone_support');
				// remove_filter('render_block', 'wp_restore_group_inner_container');
			}
		}
	}

	/**
	 * Remove JSON API links in header html.
	 *
	 * @return array
	 */
	public static function remove_rest_api() {
		remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
		remove_action( 'xml_rsd_apis', 'rest_output_rsd' );
		remove_action( 'template_redirect', 'rest_output_linkheader', 11 );
	}

	/**
	 * Remove the wpembed TinyMCE plugins.
	 *
	 * @return array
	 */
	public static function remove_wpembed_tinymce_plugins( $plugins ) {
		return array_diff( $plugins, array( 'wpembed' ) );
	}

	/**
	 * Remove the wpembed TinyMCE plugins.
	 *
	 * @return array
	 */
	public static function disable_embeds_rewrites( $rules ) {
		foreach ( $rules as $rule => $rewrite ) {
			if ( false !== strpos( $rewrite, 'embed=true' ) ) {
				unset( $rules[ $rule ] );
			}
		}
		return $rules;
	}
	/**
	 * Remove the emojis TinyMCE plugins.
	 *
	 * @return array
	 */
	public static function remove_emojis_tinymce_plugins( $plugins ) {
		return array_diff( $plugins, array( 'wpemoji' ) );
	}

	/**
	 * Disable rest api.
	 *
	 * @return void
	 */
	public static function disable_rest_api() {
		return new WP_Error(
			'rest_forbidden',
			__( 'REST API frobidden', 'lerm' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Remove style and script version of urls.
	 *
	 * @param string $url
	 * @return $url
	 */
	public static function remove_ver( $url = '' ) {
		return $url ? remove_query_arg( 'ver', $url ) : false;
	}

	/**
	 * Use front-page.php when Front page displays is set to a static page.
	 *
	 * @param string $template front-page.php.
	 *
	 * @return string The template to be used: blank if is_home() is true (defaults to index.php), else $template.
	 */
	public static function front_page_template( $template ) {
		return is_home() ? '' : $template;
	}

	/**
	 * Add custom class item to replay links.
	 *
	 * @param string $class
	 * @return void
	 */
	public static function replace_reply_link_class( $class ) {
		return str_replace( 'class=\'', 'class=\'btn btn-sm btn-custom ', $class );
	}

	/**
	 * Custom tags cloud args.
	 *
	 * @param array $args
	 * @return string|string[] Tag cloud as a string or an array, depending on 'format' argument.
	 */
	public static function tag_cloud( $args ) {
		$args = array(
			'largest'  => 22,
			'smallest' => 8,
			'unit'     => 'pt',
			'number'   => 22,
			'orderby'  => 'count',
			'order'    => 'DESC',
		);
		$tags = get_tags();

		return wp_generate_tag_cloud( $tags, $args );
	}

	/**
	 * Clean up menu attributes.
	 *
	 * @param array $ver
	 * @return $ver
	 */
	public static function remove_css_attributes( $var ) {
		return is_array( $var ) ? array_intersect( $var, array( 'active', 'dropdown', 'open', 'show' ) ) : '';
	}

	/**
	 * Replace WordPress gravatar url
	 */
	public static function gravatar_replace( $subject ) {
		$pattern = '/https?.*?\/avatar\//i';
		$replace = self::$args['gravatar_accel'];
		return preg_replace( $pattern, $replace, $subject );
	}

	/**
	 * 替换 WordPress 讨论设置中的默认头像
	 */
	public static function set_defaults_for_cravatar( $avatar_defaults ) {
		$avatar_defaults['gravatar_default'] = 'Cravatar avatar';
		return $avatar_defaults;
	}

	/**
	 * 替换个人资料卡中的头像上传地址
	 */
	public static function set_user_profile_picture_for_cravatar() {
		return '<a href="https://cravatar.cn" target="_blank">您可以在 Cravatar 修改您的资料图片</a>';
	}

	/**
	 * 将WordPress核心所依赖的静态文件访问链接替换为公共资源节点
	 * 参考 wp-china-yes 插件
	 */
	public static function super_admin() {
		$pattern = '~' . home_url( '/' ) . '(wp-admin|wp-includes)/(css|js)/~';
		$replace = sprintf( 'https://a2.wp-china-yes.net/WordPress@%s/$1/$2/', $GLOBALS['wp_version'] );
		return self::replace( 'preg_replace', $pattern, $replace );
	}

	/**
	 * Replace Google services
	 */
	public static function googleapis_replace( $replace ) {
		$services = array(
			'geekzu' => array( '//fonts.geekzu.org', '//gapis.geekzu.org/ajax', '//gapis.geekzu.org/g-fonts', '//gapis.geekzu.org/g-themes' ),
			'loli'   => array( '//fonts.loli.net', '//ajax.loli.net', '//gstatic.loli.net', '//themes.loli.net' ),
			'ustc'   => array( '//fonts.lug.ustc.edu.cn', '//ajax.lug.ustc.edu.cn', '//fonts-gstatic.lug.ustc.edu.cn', '//google-themes.lug.ustc.edu.cn' ),
		);
		$search   = array( '//fonts.googleapis.com', '//ajax.googleapis.com', '//fonts.gstatic.com', '//themes.googleusercontent.com' );
		$replace  = $services[ self::$args['google_replace'] ];
		return self::replace( 'str_replace', $search, $replace );
	}

	/**
	 * Replace public function
	 * 参考 wp-china-yes 插件
	 */
	public static function replace( $function, $regexp, $replace ) {
		ob_start(
			function( $buffer ) use ( $function, $regexp, $replace ) {
				return call_user_func( $function, $regexp, $replace, $buffer );
			}
		);
	}
	public static function ob_buffer_end() {
		if ( ob_get_level() > 0 ) {
			ob_end_flush();
		}
	}
}
