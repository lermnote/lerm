<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Core;

/**
 * CSS 自定义属性统一输出层
 *
 * 解决三套 CSS 变量系统并行的问题：
 *   1. --lerm-*          主题 Design Token（权威源，来自后台选项）
 *   2. --bs-*            Bootstrap 5 组件变量（表单/卡片/手风琴等读这套）
 *   3. --wp--preset--*   WordPress/theme.json 预设变量（区块编辑器读这套）
 *
 * 架构：Options → --lerm-*（权威） → --bs-* 桥接 → --wp--preset--* 桥接
 * 只维护一套选项，三套消费方自动同步。
 *
 * @package Lerm\Core
 */
final class CssVariables {

	/** @var array<string, mixed> */
	private static array $opts = array();

	private const CACHE_KEY = 'lerm_css_vars_v1';

	public static function init( array $options ): void {
		self::$opts = $options;
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'output' ), 22 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'output_editor' ), 22 );
		add_filter( 'wp_theme_json_data_theme', array( __CLASS__, 'filter_theme_json' ) );

		add_action( 'update_option_lerm_theme_options', array( __CLASS__, 'flush_cache' ) );
	}

	/**
	 * 清除 CSS 变量缓存。
	 * 挂在选项保存钩子上，也可由子主题或插件在需要时手动调用。
	 */
	public static function flush_cache(): void {
		delete_transient( self::CACHE_KEY );
	}

	// ── Public output ─────────────────────────────────────────────────────────

	public static function output(): void {
		$cached = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? false : get_transient( self::CACHE_KEY );
		if ( false !== $cached ) {
			wp_add_inline_style( 'main_style', $cached );
			return;
		}
		$lerm = self::resolve_lerm_tokens();
		$all  = array_merge( $lerm, self::resolve_bs_bridge( $lerm ), self::resolve_wp_bridge( $lerm ) );
		$css  = self::build_block( ':root', $all );

		$dark = self::resolve_dark_tokens();
		if ( ! empty( $dark ) ) {
			$css .= self::build_block( '[data-theme="dark"]', $dark );
			if ( 'system' === ( self::$opts['dark_mode_default'] ?? 'system' ) ) {
				$css .= '@media(prefers-color-scheme:dark){' .
					self::build_block( ':root:not([data-theme="light"])', $dark ) . '}';
			}
		}

		set_transient( self::CACHE_KEY, $css, 12 * HOUR_IN_SECONDS );
		wp_add_inline_style( 'main_style', $css );
	}

	public static function output_editor(): void {
		if ( ! wp_script_is( 'wp-edit-blocks', 'enqueued' ) ) {
			return;
		}
		$lerm = self::resolve_lerm_tokens();
		$keys = array(
			'--lerm-color-primary',
			'--lerm-color-link',
			'--lerm-bg-body',
			'--lerm-bg-card',
			'--lerm-color-text',
			'--lerm-font-family-base',
			'--lerm-font-size-base',
		);
		$sub  = array_intersect_key( $lerm, array_flip( $keys ) );
		if ( $sub ) {
			wp_add_inline_style( 'wp-edit-blocks', self::build_block( ':root', $sub ) );
		}
	}

	/**
	 * 动态把后台选项颜色注入 theme.json 调色盘，
	 * 让区块编辑器拾色器面板也显示用户当前保存的颜色。
	 */
	public static function filter_theme_json( $theme_json ) {
		$o = self::$opts;
		$u = array();

		$primary = $o['primary_color']['color'] ?? '';
		if ( $primary ) {
			$u['primary']      = $primary;
			$u['primary-dark'] = $o['primary_color']['hover'] ?? $primary;
		}
		$bg_body = $o['body_background']['background-color'] ?? '';
		if ( $bg_body ) {
			$u['background'] = $bg_body;
			$u['surface']    = $bg_body;
		}
		$bg_card = $o['content_background']['background-color'] ?? '';
		if ( $bg_card ) {
			$u['surface'] = $bg_card;
		}

		$text = $o['body_typography']['color'] ?? '';
		if ( $text ) {
			$u['foreground']       = $text;
			$u['foreground-muted'] = $text;
		}
		$link = $o['link_color']['color'] ?? '';
		if ( $link ) {
			$u['accent'] = $link;
		}

		if ( empty( $u ) ) {
			return $theme_json;
		}

		$data    = $theme_json->get_data();
		$palette = $data['settings']['color']['palette'] ?? array();
		foreach ( $palette as &$entry ) {
			$slug = $entry['slug'] ?? '';
			if ( isset( $u[ $slug ] ) ) {
				$entry['color'] = $u[ $slug ];
			}
		}
		unset( $entry );
		$data['settings']['color']['palette'] = $palette;
		$theme_json->update_with( $data );
		return $theme_json;
	}

	// ── Token resolvers ───────────────────────────────────────────────────────

	/** Layer 1: --lerm-* Design Tokens from options. Only non-empty values. */
	private static function resolve_lerm_tokens(): array {
		$o = self::$opts;
		$t = array();

		self::map_link_color( $t, $o['primary_color'] ?? array(), 'primary' );
		self::map_link_color( $t, $o['link_color'] ?? array(), 'link' );

		self::set( $t, '--lerm-bg-body', self::bg( $o['body_background'] ?? array() ) );
		self::set( $t, '--lerm-bg-card', self::bg( $o['content_background'] ?? array() ) );
		self::set( $t, '--lerm-bg-header', $o['header_bg_color'] ?? '' );

		self::map_border( $t, $o['site_header_border'] ?? array(), '--lerm-header-border' );

		self::map_link_color( $t, $o['navbar_link_color'] ?? array(), 'nav' );
		self::map_color_pair( $t, $o['navbar_active_color'] ?? array(), 'nav-active' );
		self::set( $t, '--lerm-nav-active-color', $o['navbar_active_color']['color'] ?? '' );
		self::map_spacing( $t, $o['navbar_item_padding'] ?? array(), '--lerm-nav-link' );

		self::map_color_pair( $t, $o['widget_header_color'] ?? array(), 'widget-header', true );
		self::map_color_pair( $t, $o['footer_widget_color'] ?? array(), 'footer-widgets' );
		self::map_color_pair( $t, $o['footer_bar_color'] ?? array(), 'footer-bar' );
		self::map_color_pair( $t, $o['btn_primary'] ?? array(), 'btn', true );
		self::map_color_pair( $t, $o['btn_primary_hover'] ?? array(), 'btn-hover', true );

		self::map_typography( $t, $o['body_typography'] ?? array(), 'base' );
		self::map_typography( $t, $o['menu_typography'] ?? array(), 'nav' );

		// Bridge: 带前缀的具体排版变量 → consumer 规则实际引用的泛化名。
		// body { color: var(--lerm-color-text) } 读泛化名；
		// map_typography('base') 生成的是 --lerm-color-text-base（带前缀）。
		// 两者之间需要显式桥接，否则选项修改对 body 文字色不生效。
		if ( isset( $t['--lerm-color-text-base'] ) ) {
			$t['--lerm-color-text'] = $t['--lerm-color-text-base'];
			// muted 无独立选项时回退到正文色
			if ( ! isset( $t['--lerm-color-text-muted'] ) ) {
				$t['--lerm-color-text-muted'] = $t['--lerm-color-text-base'];
			}
		}

		self::set( $t, '--lerm-content-width', self::pct( $o['content_width'] ?? '' ) );
		self::set( $t, '--lerm-sidebar-width', self::pct( $o['sidebar_width'] ?? '' ) );
		self::set( $t, '--lerm-container-max', self::px( $o['site_width'] ?? '' ) );
		self::set( $t, '--lerm-box-width', self::px( $o['boxed_width'] ?? '' ) );
		self::set( $t, '--lerm-box-outer-bg', $o['outside_bg_color'] ?? '' );
		self::set( $t, '--lerm-box-inner-bg', $o['inner_bg_color'] ?? '' );

		self::set( $t, '--lerm-progress-color', $o['reading_progress_color'] ?? '' );
		self::set( $t, '--lerm-progress-height', self::px( $o['reading_progress_height'] ?? '' ) );

		return array_filter( $t, static fn( $v ) => '' !== $v && null !== $v );
	}

	/**
	 * Layer 2: Bootstrap 5 variable bridge.
	 *
	 * Bootstrap 组件内部读 --bs-* 变量。把关键 --bs-* 指向 --lerm-* 后，
	 * 所有 Bootstrap 组件（表单、卡片、手风琴、徽章、下拉菜单等）
	 * 都自动跟随后台选项变化，无需逐一编写 consumer 规则。
	 */
	private static function resolve_bs_bridge( array $lerm ): array {
		$b = array();

		// 正文颜色 & 背景
		if ( isset( $lerm['--lerm-color-text'] ) ) {
			$b['--bs-body-color']     = 'var(--lerm-color-text)';
			$b['--bs-emphasis-color'] = 'var(--lerm-color-text)';
		}
		if ( isset( $lerm['--lerm-bg-body'] ) ) {
			$b['--bs-body-bg'] = 'var(--lerm-bg-body)';
		}

		// 链接
		if ( isset( $lerm['--lerm-color-link'] ) ) {
			$b['--bs-link-color'] = 'var(--lerm-color-link)';
		}
		if ( isset( $lerm['--lerm-color-link-hover'] ) ) {
			$b['--bs-link-hover-color'] = 'var(--lerm-color-link-hover)';
		}

		// 主色（.btn-primary、.badge-primary 等）
		if ( isset( $lerm['--lerm-color-primary'] ) ) {
			$b['--bs-primary'] = 'var(--lerm-color-primary)';
			$rgb               = self::hex_to_rgb_triplet( $lerm['--lerm-color-primary'] );
			if ( $rgb ) {
				$b['--bs-primary-rgb'] = $rgb;
			}
		}

		// 卡片背景
		if ( isset( $lerm['--lerm-bg-card'] ) ) {
			$b['--bs-card-bg'] = 'var(--lerm-bg-card)';
		}

		// 边框
		if ( isset( $lerm['--lerm-color-border'] ) ) {
			$b['--bs-border-color']             = 'var(--lerm-color-border)';
			$b['--bs-border-color-translucent'] = 'var(--lerm-color-border)';
		}

		// 导航栏颜色变量（Bootstrap .navbar 通过 --bs-navbar-color 派发）
		if ( isset( $lerm['--lerm-color-nav'] ) ) {
			$b['--bs-navbar-color']        = 'var(--lerm-color-nav)';
			$b['--bs-navbar-hover-color']  = 'var(--lerm-color-nav-hover, var(--lerm-color-primary))';
			$b['--bs-navbar-active-color'] = 'var(--lerm-nav-active-color, var(--lerm-color-primary))';
		}

		// 下拉菜单背景
		if ( isset( $lerm['--lerm-bg-header'] ) ) {
			$b['--bs-dropdown-bg'] = 'var(--lerm-bg-header)';
		}

		// 排版
		if ( isset( $lerm['--lerm-font-family-base'] ) ) {
			$b['--bs-body-font-family'] = 'var(--lerm-font-family-base)';
			$b['--bs-font-sans-serif']  = 'var(--lerm-font-family-base)';
		}
		if ( isset( $lerm['--lerm-font-size-base'] ) ) {
			$b['--bs-body-font-size'] = 'var(--lerm-font-size-base)';
		}
		if ( isset( $lerm['--lerm-font-weight-base'] ) ) {
			$b['--bs-body-font-weight'] = 'var(--lerm-font-weight-base)';
		}
		if ( isset( $lerm['--lerm-line-height-base'] ) ) {
			$b['--bs-body-line-height'] = 'var(--lerm-line-height-base)';
		}

		return $b;
	}

	/**
	 * Layer 3: WordPress / theme.json preset variable bridge.
	 *
	 * WP 从 theme.json 生成 --wp--preset--color--* 并在 <head> 输出，
	 * theme.json styles 及区块 CSS 读这套变量取色。
	 * 桥接后：Block / WP 规则 → --wp--preset--color--xxx → --lerm-xxx → 用户选项。
	 */
	private static function resolve_wp_bridge( array $lerm ): array {
		$w = array();

		if ( isset( $lerm['--lerm-color-primary'] ) ) {
			$w['--wp--preset--color--primary']      = 'var(--lerm-color-primary)';
			$w['--wp--preset--color--primary-dark'] = 'var(--lerm-color-primary-hover, var(--lerm-color-primary))';
			$w['--wp--preset--color--accent']       = 'var(--lerm-color-primary)';
		}
		if ( isset( $lerm['--lerm-bg-body'] ) ) {
			$w['--wp--preset--color--background'] = 'var(--lerm-bg-body)';
			$w['--wp--preset--color--header-bg']  = 'var(--lerm-bg-header, var(--lerm-bg-body))';
		}
		if ( isset( $lerm['--lerm-bg-card'] ) ) {
			$w['--wp--preset--color--surface']        = 'var(--lerm-bg-card)';
			$w['--wp--preset--color--background-alt'] = 'var(--lerm-bg-card)';
		}
		if ( isset( $lerm['--lerm-color-text'] ) ) {
			$w['--wp--preset--color--foreground']       = 'var(--lerm-color-text)';
			$w['--wp--preset--color--foreground-muted'] = 'var(--lerm-color-text-muted, var(--lerm-color-text))';
		}
		if ( isset( $lerm['--lerm-color-border'] ) ) {
			$w['--wp--preset--color--border'] = 'var(--lerm-color-border)';
		}
		if ( isset( $lerm['--lerm-footer-widgets-bg'] ) ) {
			$w['--wp--preset--color--footer-bg'] = 'var(--lerm-footer-widgets-bg)';
		}
		if ( isset( $lerm['--lerm-footer-widgets-color'] ) ) {
			$w['--wp--preset--color--footer-text'] = 'var(--lerm-footer-widgets-color)';
		}
		if ( isset( $lerm['--lerm-font-family-base'] ) ) {
			$w['--wp--preset--font-family--system'] = 'var(--lerm-font-family-base)';
		}

		return $w;
	}

	/** 暗色模式覆盖（三套变量同步）。 */
	private static function resolve_dark_tokens(): array {
		if ( empty( self::$opts['dark_mode_enable'] ) ) {
			return array();
		}

		$dark = apply_filters(
			'lerm_dark_mode_tokens',
			array(
				'--lerm-bg-body'           => '#1a1b1e',
				'--lerm-bg-card'           => '#25262b',
				'--lerm-bg-header'         => '#1f2023',
				'--lerm-color-text'        => '#c1c2c5',
				'--lerm-color-text-muted'  => '#909296',
				'--lerm-color-border'      => '#373a40',
				'--lerm-footer-widgets-bg' => '#141517',
				'--lerm-footer-bar-bg'     => '#101113',
			)
		);

		$o    = self::$opts;
		$dark = apply_filters(
			'lerm_dark_mode_tokens',
			array(
				'--lerm-bg-body'           => $o['dark_bg_body'] ?? '#1a1b1e',
				'--lerm-bg-card'           => $o['dark_bg_card'] ?? '#25262b',
				'--lerm-bg-header'         => $o['dark_bg_header'] ?? '#1f2023',
				'--lerm-color-text'        => $o['dark_text'] ?? '#c1c2c5',
				'--lerm-color-text-muted'  => $o['dark_text_muted'] ?? '#909296',
				'--lerm-color-border'      => $o['dark_border'] ?? '#373a40',
				'--lerm-footer-widgets-bg' => $o['dark_footer_bg'] ?? '#141517',
				'--lerm-footer-bar-bg'     => $o['dark_bar_bg'] ?? '#101113',
			)
		);

		// 为暗色背景也生成 RGB 三元组，供 Bootstrap 透明度计算
		$bg_rgb = self::hex_to_rgb_triplet( $dark['--lerm-bg-body'] );
		if ( $bg_rgb ) {
			$dark['--bs-body-bg-rgb'] = $bg_rgb;
		}
		$text_rgb = self::hex_to_rgb_triplet( $dark['--lerm-color-text'] );
		if ( $text_rgb ) {
			$dark['--bs-body-color-rgb']     = $text_rgb;
			$dark['--bs-emphasis-color-rgb'] = $text_rgb;
		}

		// Bootstrap bridge for dark mode
		$dark['--bs-body-bg']        = 'var(--lerm-bg-body)';
		$dark['--bs-body-color']     = 'var(--lerm-color-text)';
		$dark['--bs-card-bg']        = 'var(--lerm-bg-card)';
		$dark['--bs-border-color']   = 'var(--lerm-color-border)';
		$dark['--bs-dropdown-bg']    = 'var(--lerm-bg-header)';
		$dark['--bs-emphasis-color'] = 'var(--lerm-color-text)';

		// WP preset bridge for dark mode
		$dark['--wp--preset--color--background'] = 'var(--lerm-bg-body)';
		$dark['--wp--preset--color--surface']    = 'var(--lerm-bg-card)';
		$dark['--wp--preset--color--foreground'] = 'var(--lerm-color-text)';
		$dark['--wp--preset--color--border']     = 'var(--lerm-color-border)';

		return $dark;
	}

	// ── CSS builder ───────────────────────────────────────────────────────────

	private static function build_block( string $selector, array $tokens ): string {
		if ( empty( $tokens ) ) {
			return '';
		}
		$lines = array();
		foreach ( $tokens as $var => $value ) {
			$lines[] = $var . ':' . $value;
		}
		return $selector . '{' . implode( ';', $lines ) . '}';
	}

	// ── CSF field → CSS var mappers ───────────────────────────────────────────

	private static function map_link_color( array &$t, array $v, string $prefix ): void {
		self::set( $t, "--lerm-color-{$prefix}", $v['color'] ?? '' );
		self::set( $t, "--lerm-color-{$prefix}-hover", $v['hover'] ?? '' );
		self::set( $t, "--lerm-color-{$prefix}-active", $v['active'] ?? '' );
		self::set( $t, "--lerm-color-{$prefix}-focus", $v['focus'] ?? '' );
	}

	private static function map_color_pair( array &$t, array $v, string $prefix, bool $border = false ): void {
		self::set( $t, "--lerm-{$prefix}-color", $v['color'] ?? '' );
		self::set( $t, "--lerm-{$prefix}-bg", $v['background_color'] ?? '' );
		if ( $border ) {
			self::set( $t, "--lerm-{$prefix}-border", $v['border_color'] ?? '' );
		}
	}

	private static function map_typography( array &$t, array $v, string $prefix ): void {
		$family = $v['font-family'] ?? '';
		if ( $family ) {
			$family = '"' . esc_attr( $family ) . '", sans-serif';
		}
		$size = $v['font-size'] ?? '';
		if ( $size ) {
			$size = $size . ( $v['unit'] ?? 'px' );
		}
		self::set( $t, "--lerm-font-family-{$prefix}", $family );
		self::set( $t, "--lerm-font-size-{$prefix}", $size );
		self::set( $t, "--lerm-font-weight-{$prefix}", $v['font-weight'] ?? '' );
		self::set( $t, "--lerm-color-text-{$prefix}", $v['color'] ?? '' );
		self::set( $t, "--lerm-line-height-{$prefix}", $v['line-height'] ?? '' );
	}

	private static function map_border( array &$t, array $v, string $prefix ): void {
		if ( empty( $v ) ) {
			return;
		}
		$style = $v['style'] ?? 'solid';
		$color = $v['color'] ?? '';
		foreach ( array( 'top', 'bottom', 'left', 'right' ) as $side ) {
			$width = $v[ $side ] ?? '';
			if ( '' !== $width && '' !== $color ) {
				$t[ "{$prefix}-{$side}" ] = $width . 'px ' . $style . ' ' . $color;
			}
		}
	}

	private static function map_spacing( array &$t, array $v, string $prefix ): void {
		if ( empty( $v ) ) {
			return;
		}
		$unit   = $v['unit'] ?? 'rem';
		$top    = isset( $v['top'] ) ? $v['top'] . $unit : '';
		$right  = isset( $v['right'] ) ? $v['right'] . $unit : '0';
		$bottom = isset( $v['bottom'] ) ? $v['bottom'] . $unit : '';
		$left   = isset( $v['left'] ) ? $v['left'] . $unit : '0';
		if ( $top || $bottom ) {
			$t[ "{$prefix}-padding-y" ] = ( $top ?: $bottom );
		}
		if ( $right || $left ) {
			$t[ "{$prefix}-padding-x" ] = ( $right ?: $left );
		}
	}

	// ── Value formatters ──────────────────────────────────────────────────────

	private static function bg( array $value ): string {
		return $value['background-color'] ?? '';
	}

	private static function px( $value ): string {
		$v = (string) $value;
		return ( '' === $v || '0' === $v ) ? $v : $v . 'px';
	}

	private static function pct( $value ): string {
		$v = (string) $value;
		return '' === $v ? '' : $v . '%';
	}

	/** #rrggbb or #rgb → "R,G,B" for Bootstrap's --bs-xxx-rgb vars. */
	private static function hex_to_rgb_triplet( string $hex ): string {
		$hex = ltrim( trim( $hex ), '#' );
		if ( strlen( $hex ) === 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( strlen( $hex ) !== 6 || ! ctype_xdigit( $hex ) ) {
			return '';
		}
		return implode( ',', array_map( 'hexdec', str_split( $hex, 2 ) ) );
	}

	private static function set( array &$tokens, string $name, string $value ): void {
		$value = trim( $value );
		if ( '' !== $value ) {
			$tokens[ $name ] = $value;
		}
	}
}
