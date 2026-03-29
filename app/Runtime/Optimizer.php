<?php // phpcs:disable WordPress.Files.FileName

declare(strict_types=1);

namespace Lerm\Runtime;

use Lerm\Traits\Hooker;
use Lerm\Traits\Singleton;

/**
 * Theme runtime optimizer.
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
		'super_gravatar'    => 'disable',
		'super_admin'       => false,
		'super_googleapis'  => 'disable',
		'disable_pingback'  => false,
		'super_optimize'    => array(),
	);

	/** @var array<string, mixed> Current merged config. */
	protected static array $config = array();

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $params Runtime parameters.
	 */
	public function __construct( array $params = array() ) {
		self::$config = apply_filters( 'lerm_optimize_args', wp_parse_args( $params, self::$default_config ) );
		self::register_hooks( self::$config );
	}

	/**
	 * Register hooks based on the active configuration.
	 *
	 * @param array<string, mixed> $config Runtime configuration.
	 */
	protected static function register_hooks( array $config = array() ): void {
		// Replace Gravatar avatar URLs with the selected mirror.
		if ( ! in_array( $config['super_gravatar'], array( 'disable', '' ), true ) ) {
			self::filters( array( 'um_user_avatar_url_filter', 'bp_gravatar_url', 'get_avatar_url' ), array( __CLASS__, 'replace_gravatar_url' ), 100, 1 );
		}

		// Apply Cravatar-specific helpers when that mirror is selected.
		if ( 'https://cravatar.cn/avatar/' === $config['super_gravatar'] ) {
			self::filter( 'user_profile_picture_description', array( __CLASS__, 'get_cravatar_profile_link' ), 100, 1 );
			self::filter( 'avatar_defaults', array( __CLASS__, 'add_cravatar_default' ), 100, 1 );
		}

		if ( $config['super_admin'] ) {
			self::action( 'init', array( __CLASS__, 'replace_admin_static_urls' ), 100, 1 );
			self::action( 'shutdown', array( __CLASS__, 'flush_output_buffer' ), 100, 1 );
		}

		// Replace Google-hosted assets with the selected mirror.
		if ( ! in_array( $config['super_googleapis'], array( 'disable', '' ), true ) ) {
			self::action( 'init', array( __CLASS__, 'replace_google_services' ), 100, 1 );
			self::action( 'shutdown', array( __CLASS__, 'flush_output_buffer' ), 100, 1 );
		}

		// Disable self pingbacks if requested.
		if ( $config['disable_pingback'] ) {
			self::action( 'pre_ping', array( __CLASS__, 'filter_out_self_pings' ) );
		}

		// Apply optional front-end optimizations.
		if ( is_array( $config['super_optimize'] ) && ! empty( $config['super_optimize'] ) ) {
			self::apply_optimizations( $config['super_optimize'] );
		}

		// Clean up menu classes on every request.
		self::filters( array( 'nav_menu_css_class', 'nav_menu_item_id', 'page_css_class' ), array( __CLASS__, 'filter_menu_css_classes' ), 100, 1 );
	}

	/**
	 * Remove optional head tags and built-in features.
	 *
	 * @param array<int, string> $flags Enabled optimization flags.
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

	/**
	 * Disable emoji-related assets and TinyMCE plugins.
	 */
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

	/**
	 * Disable oEmbed routes, discovery links, and editor plugins.
	 */
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

	/**
	 * Remove global style SVG filters.
	 */
	public static function remove_global_styles_svg(): void {
		remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
		remove_filter( 'render_block', 'wp_render_layout_support_flag' );
		// Uncomment these lines if you want to remove global styles completely.
		// remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
		// remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
	}

	/**
	 * Remove REST API links from the document head and headers.
	 */
	public static function remove_rest_api_links(): void {
		remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
		remove_action( 'xml_rsd_apis', 'rest_output_rsd' );
		remove_action( 'template_redirect', 'rest_output_linkheader', 11 );
	}

	/**
	 * Remove the wpembed TinyMCE plugin.
	 *
	 * @param array<int, string> $plugins Registered plugins.
	 * @return array<int, string>
	 */
	public static function remove_wpembed_tinymce_plugins( array $plugins ): array {
		return array_diff( $plugins, array( 'wpembed' ) );
	}

	/**
	 * Remove embed rewrites.
	 *
	 * @param array<string, string> $rules Rewrite rules.
	 * @return array<string, string>
	 */
	public static function filter_disable_embeds_rewrites( array $rules ): array {
		foreach ( $rules as $rule => $rewrite ) {
			if ( false !== strpos( $rewrite, 'embed=true' ) ) {
				unset( $rules[ $rule ] );
			}
		}

		return $rules;
	}

	/**
	 * Remove the wpemoji TinyMCE plugin.
	 *
	 * @param array<int, string> $plugins Registered plugins.
	 * @return array<int, string>
	 */
	public static function remove_emojis_tinymce_plugins( array $plugins ): array {
		return array_diff( $plugins, array( 'wpemoji' ) );
	}

	/**
	 * Block unauthorized REST access when this method is used as a filter.
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

	/**
	 * Remove the `ver` query argument from asset URLs.
	 *
	 * @param string $url Asset URL.
	 */
	public static function strip_version_query( string $url = '' ): string|false {
		return $url ? remove_query_arg( 'ver', $url ) : false;
	}

	/**
	 * Add theme button classes to comment reply links.
	 *
	 * @param string $html Reply button HTML.
	 */
	public static function add_reply_button_classes( string $html ): string {
		return str_replace( "class='", "class='btn btn-sm btn-custom ", $html );
	}

	/**
	 * Keep only the menu classes used by the theme.
	 *
	 * @param mixed $attr Menu attributes.
	 * @return array<int, string>
	 */
	public static function filter_menu_css_classes( $attr ) {
		return is_array( $attr ) ? array_intersect( $attr, array( 'nav-item', 'active', 'dropdown', 'open', 'show' ) ) : array();
	}

	/**
	 * Hide the default embed share UI.
	 */
	public static function output_embed_footer_style(): void {
		echo '<style>.wp-embed-share{display:none;}</style>';
	}

	/**
	 * Remove links that point back to the current site.
	 *
	 * @param array<int, string> $links Pingback links.
	 */
	public static function filter_out_self_pings( array &$links ): void {
		$home_url = home_url();
		$links    = array_filter(
			$links,
			static function ( string $link ) use ( $home_url ): bool {
				return strpos( $link, $home_url ) !== 0;
			}
		);
	}

	/**
	 * Replace Gravatar avatar URLs with the selected mirror.
	 *
	 * @param string $subject HTML or URL content.
	 */
	public static function replace_gravatar_url( string $subject ): string {
		$pattern = '/https?.*?\/avatar\//i';
		$replace = self::$config['super_gravatar'] ?? self::$default_config['super_gravatar'];
		return preg_replace( $pattern, $replace, $subject );
	}

	/**
	 * Register Cravatar as the default avatar label.
	 *
	 * @param array<string, string> $avatar_defaults Avatar choices.
	 * @return array<string, string>
	 */
	public static function add_cravatar_default( array $avatar_defaults ): array {
		$avatar_defaults['gravatar_default'] = 'Cravatar avatar';
		return $avatar_defaults;
	}

	/**
	 * Provide a profile link for users managing avatars on Cravatar.
	 */
	public static function get_cravatar_profile_link(): string {
		return sprintf(
			'<a href="https://cravatar.cn" target="_blank">%s</a>',
			esc_html__( 'You can update your profile picture on Cravatar.', 'lerm' )
		);
	}

	/**
	 * Replace WordPress admin static asset URLs with a mirror.
	 */
	public static function replace_admin_static_urls(): void {
		$pattern = '~' . home_url( '/' ) . '(wp-admin|wp-includes)/(css|js)/~';
		$replace = sprintf( 'https://wpstatic.cdn.haozi.net/%s/$1/$2/', $GLOBALS['wp_version'] );
		self::start_output_buffer_replace( 'preg_replace', $pattern, $replace );
	}

	/**
	 * Replace Google-hosted assets with the selected mirror.
	 */
	public static function replace_google_services(): void {
		$services = array(
			'geekzu' => array( '//fonts.geekzu.org', '//gapis.geekzu.org/ajax', '//gapis.geekzu.org/g-fonts', '//gapis.geekzu.org/g-themes' ),
			'loli'   => array( '//fonts.loli.net', '//ajax.loli.net', '//gstatic.loli.net', '//themes.loli.net' ),
			'ustc'   => array( '//fonts.lug.ustc.edu.cn', '//ajax.lug.ustc.edu.cn', '//fonts-gstatic.lug.ustc.edu.cn', '//google-themes.lug.ustc.edu.cn' ),
		);

		$search  = array( '//fonts.googleapis.com', '//ajax.googleapis.com', '//fonts.gstatic.com', '//themes.googleusercontent.com' );
		$replace = $services[ self::$config['super_googleapis'] ] ?? null;

		if ( is_array( $replace ) ) {
			self::start_output_buffer_replace( 'str_replace', $search, $replace );
		}
	}

	/**
	 * Start an output buffer and apply a replacement callback to the response.
	 *
	 * @param string $callback Callback name such as `preg_replace` or `str_replace`.
	 * @param mixed  $pattern Pattern or search value.
	 * @param mixed  $replace Replacement value.
	 */
	public static function start_output_buffer_replace( string $callback, $pattern, $replace ): void {
		ob_start(
			function ( $buffer ) use ( $callback, $pattern, $replace ) {
				return call_user_func( $callback, $pattern, $replace, $buffer );
			}
		);
	}

	/**
	 * Flush the output buffer when one is active.
	 */
	public static function flush_output_buffer(): void {
		if ( ob_get_level() > 0 ) {
			ob_end_flush();
		}
	}
}
