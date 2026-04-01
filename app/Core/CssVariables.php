<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Core;

/**
 * CSS 自定义属性（Design Token）输出器
 *
 * 将后台主题选项转换为 :root { --lerm-*: … } 变量，inline 注入到
 * main_style 样式表之后。前端 CSS 统一引用变量，不再散落硬编码色值。
 *
 * 变量命名规范：
 *   --lerm-{category}-{property}
 *   e.g. --lerm-color-primary, --lerm-font-size-base, --lerm-radius-card
 *
 * 暗色模式：在 [data-theme="dark"] 选择器下输出同名变量的暗色值，
 * 前端 CSS 无需任何改动，切换主题只需改 <html data-theme="dark">。
 *
 * @package Lerm\Core
 */
final class CssVariables {

	/**
	 * 所有已解析的选项值，由 bootstrap.php 传入。
	 *
	 * @var array<string, mixed>
	 */
	private static array $opts = array();

	/**
	 * 初始化：存储选项并挂钩。
	 *
	 * @param array<string, mixed> $options 来自 lerm_theme_options 的完整选项数组。
	 */
	public static function init( array $options ): void {
		self::$opts = $options;
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'output' ), 22 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'output_editor' ), 22 );
	}

	// ──────────────────────────────────────────────────────────────────────────
	// Public output
	// ──────────────────────────────────────────────────────────────────────────

	/**
	 * 前台：输出 :root 变量块 + 可选的 [data-theme="dark"] 块。
	 */
	public static function output(): void {
		$css = self::build_root_block( self::resolve_tokens() );

		$dark = self::resolve_dark_tokens();
		if ( ! empty( $dark ) ) {
			$css .= self::build_selector_block( '[data-theme="dark"]', $dark );
		}

		// 系统级暗色媒体查询（当用户选择 "跟随系统" 时追加）
		$dark_default = self::$opts['dark_mode_default'] ?? 'system';
		if ( 'system' === $dark_default && ! empty( $dark ) ) {
			$css .= '@media(prefers-color-scheme:dark){' . self::build_selector_block( ':root:not([data-theme="light"])', $dark ) . '}';
		}

		wp_add_inline_style( 'main_style', $css );
	}

	/**
	 * 后台编辑器：只输出最基础的品牌色，供 Block Editor 预览使用。
	 */
	public static function output_editor(): void {
		if ( ! wp_script_is( 'wp-edit-blocks', 'enqueued' ) ) {
			return;
		}
		$tokens = array_intersect_key(
			self::resolve_tokens(),
			array_flip( array( '--lerm-color-primary', '--lerm-color-primary-hover', '--lerm-color-link', '--lerm-font-family-base', '--lerm-font-size-base' ) )
		);
		if ( $tokens ) {
			wp_add_inline_style( 'wp-edit-blocks', self::build_root_block( $tokens ) );
		}
	}

	// ──────────────────────────────────────────────────────────────────────────
	// Token resolvers
	// ──────────────────────────────────────────────────────────────────────────

	/**
	 * 将后台选项解析为 [ '--lerm-var-name' => 'value' ] 映射表。
	 * 只包含有效（非空）值，默认值在 CSS 文件的 :root 里已声明。
	 *
	 * @return array<string, string>
	 */
	private static function resolve_tokens(): array {
		$o = self::$opts;
		$t = array();

		// ── 品牌色 ────────────────────────────────────────────────────────────
		self::map_link_color( $t, $o['primary_color'] ?? array(), 'primary' );
		self::map_link_color( $t, $o['link_color']    ?? array(), 'link' );

		// ── 背景色 ────────────────────────────────────────────────────────────
		self::set( $t, '--lerm-bg-body',    self::bg( $o['body_background']    ?? array() ) );
		self::set( $t, '--lerm-bg-card',    self::bg( $o['content_background'] ?? array() ) );
		self::set( $t, '--lerm-bg-header',  $o['header_bg_color'] ?? '' );

		// ── Header 边框 ───────────────────────────────────────────────────────
		self::map_border( $t, $o['site_header_border'] ?? array(), '--lerm-header-border' );

		// ── 导航 ──────────────────────────────────────────────────────────────
		self::map_link_color( $t, $o['navbar_link_color']   ?? array(), 'nav' );
		self::map_color_pair( $t, $o['navbar_active_color'] ?? array(), 'nav-active' );
		// Consumer rules reference --lerm-nav-active-color; add alias from the color_pair 'color' slot.
		$nav_active_c = $o['navbar_active_color']['color'] ?? '';
		self::set( $t, '--lerm-nav-active-color', $nav_active_c );
		self::map_spacing( $t, $o['navbar_item_padding']    ?? array(), '--lerm-nav-link' );

		// ── 小工具标题 ────────────────────────────────────────────────────────
		self::map_color_pair( $t, $o['widget_header_color'] ?? array(), 'widget-header', true );

		// ── 页脚 ──────────────────────────────────────────────────────────────
		self::map_color_pair( $t, $o['footer_widget_color'] ?? array(), 'footer-widgets' );
		self::map_color_pair( $t, $o['footer_bar_color']    ?? array(), 'footer-bar' );

		// ── 按钮 ──────────────────────────────────────────────────────────────
		self::map_color_pair( $t, $o['btn_primary']       ?? array(), 'btn', true );
		self::map_color_pair( $t, $o['btn_primary_hover'] ?? array(), 'btn-hover', true );

		// ── 排版 ──────────────────────────────────────────────────────────────
		self::map_typography( $t, $o['body_typography'] ?? array(), 'base' );
		self::map_typography( $t, $o['menu_typography'] ?? array(), 'nav' );

		// ── 布局 ──────────────────────────────────────────────────────────────
		self::set( $t, '--lerm-content-width', self::pct( $o['content_width'] ?? '' ) );
		self::set( $t, '--lerm-sidebar-width', self::pct( $o['sidebar_width'] ?? '' ) );
		self::set( $t, '--lerm-container-max', self::px( $o['site_width']     ?? '' ) );
		self::set( $t, '--lerm-box-width',     self::px( $o['boxed_width']    ?? '' ) );
		self::set( $t, '--lerm-box-outer-bg',  $o['outside_bg_color'] ?? '' );
		self::set( $t, '--lerm-box-inner-bg',  $o['inner_bg_color']   ?? '' );

		// ── 阅读进度条 ────────────────────────────────────────────────────────
		self::set( $t, '--lerm-progress-color',  $o['reading_progress_color']  ?? '' );
		self::set( $t, '--lerm-progress-height', self::px( $o['reading_progress_height'] ?? '' ) );

		return array_filter( $t, static fn( $v ) => '' !== $v && null !== $v );
	}

	/**
	 * 暗色模式下需要覆盖的变量子集。
	 * 目前仅覆盖背景和文字，颜色值为固定的暗色设计默认值，
	 * 后续可以在后台增加暗色专属颜色选项进一步扩展。
	 *
	 * @return array<string, string>
	 */
	private static function resolve_dark_tokens(): array {
		if ( empty( self::$opts['dark_mode_enable'] ) ) {
			return array();
		}

		return apply_filters(
			'lerm_dark_mode_tokens',
			array(
				'--lerm-bg-body'          => '#1a1b1e',
				'--lerm-bg-card'          => '#25262b',
				'--lerm-bg-header'        => '#1f2023',
				'--lerm-color-text'       => '#c1c2c5',
				'--lerm-color-text-muted' => '#909296',
				'--lerm-color-border'     => '#373a40',
				'--lerm-footer-widgets-bg' => '#141517',
				'--lerm-footer-bar-bg'    => '#101113',
			)
		);
	}

	// ──────────────────────────────────────────────────────────────────────────
	// CSS builders
	// ──────────────────────────────────────────────────────────────────────────

	/**
	 * 输出 :root { … } 块。
	 *
	 * @param array<string, string> $tokens
	 */
	private static function build_root_block( array $tokens ): string {
		return self::build_selector_block( ':root', $tokens );
	}

	/**
	 * 输出 {selector} { … } 块。
	 *
	 * @param string                $selector CSS 选择器
	 * @param array<string, string> $tokens   变量名 => 值
	 */
	private static function build_selector_block( string $selector, array $tokens ): string {
		if ( empty( $tokens ) ) {
			return '';
		}
		$lines = array();
		foreach ( $tokens as $var => $value ) {
			$lines[] = $var . ':' . $value;
		}
		return $selector . '{' . implode( ';', $lines ) . '}';
	}

	// ──────────────────────────────────────────────────────────────────────────
	// Mapping helpers — translate CSF field structures to CSS variable entries
	// ──────────────────────────────────────────────────────────────────────────

	/**
	 * link_color 字段 → primary/hover/active/focus 变量组。
	 *
	 * @param array<string, string> $tokens  引用写入
	 * @param array<string, string> $value   CSF link_color 值
	 * @param string                $prefix  变量中段，如 'primary'/'link'/'nav'
	 */
	private static function map_link_color( array &$tokens, array $value, string $prefix ): void {
		self::set( $tokens, "--lerm-color-{$prefix}",        $value['color']  ?? '' );
		self::set( $tokens, "--lerm-color-{$prefix}-hover",  $value['hover']  ?? '' );
		self::set( $tokens, "--lerm-color-{$prefix}-active", $value['active'] ?? '' );
		self::set( $tokens, "--lerm-color-{$prefix}-focus",  $value['focus']  ?? '' );
	}

	/**
	 * color_pair 字段 → text/bg/border 变量组。
	 *
	 * @param array<string, string> $tokens
	 * @param array<string, string> $value   CSF color_pair 值
	 * @param string                $prefix  变量中段
	 * @param bool                  $border  是否包含 border_color
	 */
	private static function map_color_pair( array &$tokens, array $value, string $prefix, bool $border = false ): void {
		self::set( $tokens, "--lerm-{$prefix}-color",  $value['color']            ?? '' );
		self::set( $tokens, "--lerm-{$prefix}-bg",     $value['background_color'] ?? '' );
		if ( $border ) {
			self::set( $tokens, "--lerm-{$prefix}-border", $value['border_color'] ?? '' );
		}
	}

	/**
	 * typography 字段 → font-family/size/weight/color/line-height 变量组。
	 *
	 * @param array<string, string> $tokens
	 * @param array<string, string> $value   CSF typography 值
	 * @param string                $prefix  变量中段，如 'base'/'nav'
	 */
	private static function map_typography( array &$tokens, array $value, string $prefix ): void {
		$family = $value['font-family'] ?? '';
		if ( $family ) {
			// 加引号并追加安全回退字体
			$family = '"' . esc_attr( $family ) . '", sans-serif';
		}
		$size = $value['font-size'] ?? '';
		$unit = $value['unit']      ?? 'px';
		if ( $size ) {
			$size = $size . $unit;
		}
		self::set( $tokens, "--lerm-font-family-{$prefix}", $family );
		self::set( $tokens, "--lerm-font-size-{$prefix}",   $size );
		self::set( $tokens, "--lerm-font-weight-{$prefix}", $value['font-weight']  ?? '' );
		self::set( $tokens, "--lerm-color-text-{$prefix}",  $value['color']        ?? '' );
		self::set( $tokens, "--lerm-line-height-{$prefix}", $value['line-height']  ?? '' );
	}

	/**
	 * border 字段 → shorthand 变量（仅完整四边时输出）。
	 *
	 * @param array<string, string> $tokens
	 * @param array<string, string> $value   CSF border 值
	 * @param string                $var_prefix 如 '--lerm-header-border'
	 */
	private static function map_border( array &$tokens, array $value, string $var_prefix ): void {
		if ( empty( $value ) ) {
			return;
		}
		$style = $value['style'] ?? 'solid';
		$color = $value['color'] ?? '';
		foreach ( array( 'top', 'bottom', 'left', 'right' ) as $side ) {
			$width = $value[ $side ] ?? '';
			if ( '' !== $width && '' !== $color ) {
				$tokens[ "{$var_prefix}-{$side}" ] = $width . 'px ' . $style . ' ' . $color;
			}
		}
	}

	/**
	 * spacing 字段 → padding shorthand 变量。
	 *
	 * @param array<string, string> $tokens
	 * @param array<string, string> $value   CSF spacing 值
	 * @param string                $var_prefix 如 '--lerm-nav-link'
	 */
	private static function map_spacing( array &$tokens, array $value, string $var_prefix ): void {
		if ( empty( $value ) ) {
			return;
		}
		$unit = $value['unit'] ?? 'rem';
		$top    = isset( $value['top'] )    ? $value['top'] . $unit    : '';
		$right  = isset( $value['right'] )  ? $value['right'] . $unit  : '0';
		$bottom = isset( $value['bottom'] ) ? $value['bottom'] . $unit : '';
		$left   = isset( $value['left'] )   ? $value['left'] . $unit   : '0';

		if ( $top || $bottom ) {
			$tokens[ "{$var_prefix}-padding-y" ] = ( $top ?: $bottom );
		}
		if ( $right || $left ) {
			$tokens[ "{$var_prefix}-padding-x" ] = ( $right ?: $left );
		}
	}

	// ──────────────────────────────────────────────────────────────────────────
	// Value formatters
	// ──────────────────────────────────────────────────────────────────────────

	/** background 字段 → background-color 值。 */
	private static function bg( array $value ): string {
		return $value['background-color'] ?? '';
	}

	/** 数值加 px 单位（空值直接返回空）。 */
	private static function px( $value ): string {
		$v = (string) $value;
		if ( '' === $v || '0' === $v ) {
			return $v;
		}
		return $v . 'px';
	}

	/** 数值加 % 单位（空值返回空）。 */
	private static function pct( $value ): string {
		$v = (string) $value;
		if ( '' === $v ) {
			return '';
		}
		return $v . '%';
	}

	/**
	 * 安全写入一个 token（空字符串跳过）。
	 *
	 * @param array<string, string> $tokens 引用写入
	 * @param string                $name   变量名（含 -- 前缀）
	 * @param string                $value  CSS 值
	 */
	private static function set( array &$tokens, string $name, string $value ): void {
		$value = trim( $value );
		if ( '' !== $value ) {
			$tokens[ $name ] = $value;
		}
	}
}
