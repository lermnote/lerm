<?php // phpcs:disable WordPress.Files.FileName

namespace Lerm\Inc\Core;

use Lerm\Inc\Traits\Singleton;
use Lerm\Inc\Traits\Hooker;

class Optimize {
	use singleton;
	use Hooker;

	public static $args = array(
		'gravatar_accel'   => 'disable',
		'admin_accel'      => false,
		'google_replace'   => 'disable',
		'disable_pingback' => false,
		'super_optimize'   => array(),
	);

	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_optimize_args', wp_parse_args( $params, self::$args ) );

		self::hooks( self::$args );
	}

	protected static function hooks( $args = array() ) {
		if ( ! in_array( $args['gravatar_accel'], array( 'disable', '' ), true ) ) {
			self::filters( array( 'um_user_avatar_url_filter', 'bp_gravatar_url', 'get_avatar_url' ), 'gravatar_replace', 100, 1 );
		}
		if ( 'https://cravatar.cn/avatar/' === $args['gravatar_accel'] ) {
			add_filter( 'user_profile_picture_description', array( __CLASS__, 'set_user_profile_picture_for_cravatar' ), 100, 1 );
			add_filter( 'avatar_defaults', array( __CLASS__, 'set_defaults_for_cravatar' ), 100, 1 );
		}
		if ( $args['admin_accel'] ) {
			add_action( 'init', array( __CLASS__, 'super_admin' ), 100, 1 );
			add_action( 'shutdown', array( __CLASS__, 'ob_buffer_end' ), 100, 1 );
		}
		if ( ! in_array( $args['google_replace'], array( 'disable', '' ), true ) ) {
			add_action( 'init', array( __CLASS__, 'googleapis_replace' ), 100, 1 );
			add_action( 'shutdown', array( __CLASS__, 'ob_buffer_end' ), 100, 1 );
		}

		if ( $args['disable_pingback'] ) {
			add_action( 'pre_ping', array( __CLASS__, 'disable_pingback' ) );
		}

		if ( is_array( $args['super_optimize'] ) && ! empty( $args['super_optimize'] ) ) {
			self::optimize( $args['super_optimize'] );
		}

		self::filters( array( 'nav_menu_css_class', 'nav_menu_item_id', 'page_css_class' ), 'remove_css_attributes', 100, 1 );
	}

	/**
	 * Clean up wp_head() from unused or unsecure stuff.
	 *
	 * @param array $args List of elements to be removed.
	 *
	 * @return void
	 */
	public static function optimize( $args = array() ) {
		// Remove head links.
		$actions = array( 'rsd_link', 'wlwmanifest_link', 'wp_generator', 'start_post_rel_link', 'index_rel_link', 'adjacent_posts_rel_link_wp_head', 'rel_canonical', 'wp_shortlink_wp_head' );
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
				add_filter( 'rest_authentication_errors', array( __CLASS__, 'rest_authorization_error' ) );
			}
			if ( in_array( 'remove_ver', $args, true ) ) {
				add_filter( 'script_loader_src', array( __CLASS__, 'remove_ver' ) );
				add_filter( 'style_loader_src', array( __CLASS__, 'remove_ver' ) );
			}

			if ( in_array( 'disable_emojis', $args, true ) ) {
				self::disable_emojis();
			}
			if ( in_array( 'remove_recent_comments_css', $args, true ) ) {
				add_filter( 'show_recent_comments_widget_style', '__return_false' );
			}
			if ( in_array( 'disable_oembed', $args, true ) ) {
				self::disable_oembed();
			}
			if ( in_array( 'remove_global_styles_render_svg', $args, true ) ) {
				self::remove_global_styles_render_svg();
			}
		}

		remove_action( 'embed_footer', 'print_embed_sharing_dialog' );
		remove_action( 'embed_footer', 'print_embed_sharing_icon' );
		add_action( 'embed_footer', array( __CLASS__, 'embed_custom_footer_style' ) );

	}
	protected static function disable_emojis() {
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

	protected static function disable_oembed() {
		remove_action( 'rest_api_init', 'wp_oembed_register_route' );
		remove_filter( 'rest_pre_serve_request', '_oembed_rest_pre_serve_request', 10, 4 );
		remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
		remove_filter( 'oembed_response_data', 'get_oembed_response_data_rich', 10, 4 );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		add_filter( 'embed_oembed_discover', '__return_false' );
		add_filter( 'rewrite_rules_array', array( __CLASS__, 'disable_embeds_rewrites' ) );
		add_filter( 'tiny_mce_plugins', array( __CLASS__, 'remove_wpembed_tinymce_plugins' ) );
	}

	protected static function remove_global_styles_render_svg() {
		remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
		remove_filter( 'render_block', 'wp_render_layout_support_flag' );
		// Uncomment these lines if you want to remove global styles completely
		// remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
		// remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
		// remove_filter('render_block', 'wp_render_duotone_support');
		// remove_filter('render_block', 'wp_restore_group_inner_container');
	}

	/**
	 * Remove JSON API links in header html.
	 *
	 * @return void
	 */
	public static function remove_rest_api() {
		remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
		remove_action( 'xml_rsd_apis', 'rest_output_rsd' );
		remove_action( 'template_redirect', 'rest_output_linkheader', 11 );
	}

	/**
	 * Remove the wpembed TinyMCE plugins.
	 *
	 * @param array $plugins List of TinyMCE plugins.
	 *
	 * @return array Updated list of TinyMCE plugins.
	 */
	public static function remove_wpembed_tinymce_plugins( $plugins ) {
		return array_diff( $plugins, array( 'wpembed' ) );
	}

	/**
	 * Disable embed rewrites.
	 *
	 * @param array $rules Rewrite rules.
	 *
	 * @return array Updated rewrite rules.
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
	 * @param array $plugins List of TinyMCE plugins.
	 *
	 * @return array Updated list of TinyMCE plugins.
	 */
	public static function remove_emojis_tinymce_plugins( $plugins ) {
		return array_diff( $plugins, array( 'wpemoji' ) );
	}

	/**
	 * Handle REST API authentication error.
	 *
	 * @return WP_Error Authentication error.
	 */
	public static function disable_rest_api() {
		return new \WP_Error(
			'rest_forbidden',
			__( 'REST API frobidden', 'lerm' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Remove version query string from scripts and styles.
	 *
	 * @param string $url The URL of the script or style.
	 *
	 * @return string Updated URL.
	 */
	public static function remove_ver( $url = '' ) {
		return $url ? remove_query_arg( 'ver', $url ) : false;
	}

	/**
	 * Add custom class item to reply links.
	 *
	 * @param string $class CSS class.
	 *
	 * @return string Updated CSS class.
	 */
	public static function replace_reply_link_class( $class ) {
		return str_replace( 'class=\'', 'class=\'btn btn-sm btn-custom ', $class );
	}

	/**
	 * Clean up menu attributes.
	 *
	 * @param array $attr Menu attributes.
	 *
	 * @return array Updated menu attributes.
	 */
	public static function remove_css_attributes( $attr ) {
		return is_array( $attr ) ? array_intersect( $attr, array( 'nav-item', 'active', 'dropdown', 'open', 'show' ) ) : array();
	}

	/**
	 * Style embed front-end.
	 *
	 * @return void
	 */

	public static function embed_custom_footer_style() {
		?>
		<style>
			.wp-embed-share {
				display: none;
			}
		</style>
		<?php
	}

	/**
	 * Removes self pings from the list of pings.
	 *
	 * @param array &$links List of ping URLs.
	 */
	public static function disable_pingback( array &$links ) {
		$home_url = home_url();

		// Filter out self ping URLs from the list
		$links = array_filter(
			$links,
			static function( string $link ) use ( $home_url ): bool {
				return strpos( $link, $home_url ) !== 0;
			}
		);
	}

	/**
	 * Replace WordPress gravatar URL.
	 *
	 * @param string $subject Gravatar URL.
	 *
	 * @return string Updated Gravatar URL.
	 */
	public static function gravatar_replace( $subject ) {
		$pattern = '/https?.*?\/avatar\//i';
		$replace = self::$args['gravatar_accel'];
		return preg_replace( $pattern, $replace, $subject );
	}

	/**
	 * Replace default avatar in WordPress discussion settings.
	 *
	 * @param array $avatar_defaults Default avatars.
	 *
	 * @return array Updated default avatars.
	 */
	public static function set_defaults_for_cravatar( $avatar_defaults ) {
		$avatar_defaults['gravatar_default'] = 'Cravatar avatar';
		return $avatar_defaults;
	}

	/**
	 * Replace profile picture upload URL in user profile.
	 *
	 * @return string Profile picture upload URL.
	 */
	public static function set_user_profile_picture_for_cravatar() {
		return '<a href="https://cravatar.cn" target="_blank">您可以在 Cravatar 修改您的资料图片</a>';
	}

	/**
	 * Replace WordPress core static file access links with public resource nodes.
	 *
	 * @return void
	 */
	public static function super_admin() {
		$pattern = '~' . home_url( '/' ) . '(wp-admin|wp-includes)/(css|js)/~';
		$replace = sprintf( 'https://wpstatic.cdn.haozi.net/%s/$1/$2/', $GLOBALS['wp_version'] );
		return self::replace( 'preg_replace', $pattern, $replace );
	}

	/**
	 * Replace Google services.
	 *
	 * @return void
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
	 * Replace buffer content.
	 *
	 * @param string $function Function to call for replacement.
	 * @param mixed  $regexp   Regular expression or search string.
	 * @param string $replace  Replacement string.
	 *
	 * @return void
	 */
	public static function replace( $function, $regexp, $replace ) {
		ob_start(
			function ( $buffer ) use ( $function, $regexp, $replace ) {
				return call_user_func( $function, $regexp, $replace, $buffer );
			}
		);
	}

	/**
	 * End output buffering.
	 *
	 * @return void
	 */
	public static function ob_buffer_end() {
		if ( ob_get_level() > 0 ) {
			ob_end_flush();
		}
	}
}
