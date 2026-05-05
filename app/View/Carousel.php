<?php // phpcs:disable WordPress.Files.FileName
namespace Lerm\View;

use Lerm\Traits\Singleton;

/**
 * Carousel renderer (Bootstrap 5).
 *
 * Usage:
 *   Carousel::instance( $params );   // 实例化并自动渲染（slide_enable=true 时）
 *   Carousel::render( $params );     // 直接静态渲染，无需实例化
 *
 * @package Lerm
 */
class Carousel {

	use Singleton;

	/** 允许的动画 class 白名单。 */
	private const ALLOWED_ANIMATIONS = array( 'carousel-fade', '' );

	/** 实例持有的参数（非静态，避免多次调用互相污染）。 */
	private array $args;

	/** 默认参数。 */
	private static array $defaults = array(
		'slide_enable' => false,
		'slides'       => array(),
		'indicators'   => false,
		'control'      => false,
		'animation'    => 'carousel-fade',
	);

	/** wp_kses 允许的标签与属性。 */
	private const ALLOWED_HTML = array(
		'div'    => array(
			'id'               => true,
			'class'            => true,
			'data-bs-ride'     => true,
			'data-bs-interval' => true,
		),
		'button' => array(
			'type'             => true,
			'class'            => true,
			'data-bs-target'   => true,
			'data-bs-slide'    => true,
			'data-bs-slide-to' => true,
			'aria-current'     => true,
			'aria-label'       => true,
		),
		'img'    => array(
			'src'     => true,
			'alt'     => true,
			'width'   => true,
			'height'  => true,
			'class'   => true,
			'loading' => true,
		),
		'a'      => array(
			'href'  => true,
			'title' => true,
			'class' => true,
		),
		'span'   => array(
			'class'       => true,
			'aria-hidden' => true,
		),
		'h5'     => array(),
		'p'      => array(),
	);

	// ── 构造器 ────────────────────────────────────────────────────────────────

	/**
	 * @param array $params 覆盖默认参数的选项数组。
	 */
	public function __construct( array $params = array() ) {
		$this->args = apply_filters(
			'lerm_slide_args',
			wp_parse_args( $params, self::$defaults )
		);

		if ( ! empty( $this->args['slide_enable'] ) ) {
			$this->output();
		}
	}

	// ── 公开 API ──────────────────────────────────────────────────────────────

	/**
	 * 静态渲染入口（无需先实例化）。
	 *
	 * @param array $params 完整参数数组。
	 */
	public static function render( array $params = array() ): void {
		$args = apply_filters(
			'lerm_slide_args',
			wp_parse_args( $params, self::$defaults )
		);
		self::build_and_echo( $args );
	}

	// ── 私有渲染 ──────────────────────────────────────────────────────────────

	/**
	 * 从实例 args 输出轮播（由构造器调用）。
	 */
	private function output(): void {
		self::build_and_echo( $this->args );
	}

	/**
	 * 核心渲染逻辑：构建并输出轮播 HTML。
	 *
	 * @param array $args 完整参数数组。
	 */
	private static function build_and_echo( array $args ): void {
		$slides = is_array( $args['slides'] ) ? array_values( $args['slides'] ) : array();
		if ( empty( $slides ) ) {
			return;
		}

		$animation       = self::sanitize_animation( (string) ( $args['animation'] ?? '' ) );
		$show_indicators = ! empty( $args['indicators'] );
		$show_controls   = ! empty( $args['control'] );

		$indicator_items = array();
		$slide_items     = array();
		$render_index    = 0; // 仅计已成功渲染的帧，与 data-bs-slide-to 对应

		foreach ( $slides as $slide ) {
			if ( ! is_array( $slide ) ) {
				continue;
			}

			$image = isset( $slide['image'] ) && is_array( $slide['image'] ) ? $slide['image'] : array();
			if ( empty( $image['url'] ) ) {
				continue; // 跳过无效帧，同时不递增 render_index
			}

			$is_first = ( 0 === $render_index );
			$active   = $is_first ? ' active' : '';

			// ── Indicator ──────────────────────────────────────────────────
			/* translators: %d: Slide number (1-based). */
			$aria_label        = esc_attr( sprintf( __( 'Slide %d', 'lerm' ), $render_index + 1 ) );
			$indicator_items[] = sprintf(
				'<button type="button" data-bs-target="#lermSlides" data-bs-slide-to="%d" class="%s"%s aria-label="%s"></button>',
				$render_index,
				trim( $active ),
				$is_first ? ' aria-current="true"' : '',
				$aria_label
			);

			// ── Image ──────────────────────────────────────────────────────
			$alt    = (string) ( $image['alt'] ?? $slide['title'] ?? '' );
			$width  = ! empty( $image['width'] ) ? ' width="' . esc_attr( (string) $image['width'] ) . '"' : '';
			$height = ! empty( $image['height'] ) ? ' height="' . esc_attr( (string) $image['height'] ) . '"' : '';

			$image_tag = sprintf(
				'<img class="d-block img-fluid w-100 rounded slider" src="%s" alt="%s"%s%s loading="lazy">',
				esc_url( $image['url'] ),
				esc_attr( $alt ),
				$width,
				$height
			);

			// ── Optional link wrapper ──────────────────────────────────────
			$inner = ! empty( $slide['url'] )
				? sprintf(
					'<a href="%s" title="%s">%s</a>',
					esc_url( $slide['url'] ),
					esc_attr( (string) ( $slide['title'] ?? '' ) ),
					$image_tag
				)
				: $image_tag;

			// ── Optional caption ───────────────────────────────────────────
			$caption = '';
			if ( ! empty( $slide['title'] ) || ! empty( $slide['description'] ) ) {
				$caption = '<div class="carousel-caption d-none d-md-block">'
					. ( ! empty( $slide['title'] ) ? '<h5>' . esc_html( $slide['title'] ) . '</h5>' : '' )
					. ( ! empty( $slide['description'] ) ? '<p>' . esc_html( $slide['description'] ) . '</p>' : '' )
					. '</div>';
			}

			$slide_items[] = sprintf(
				'<div class="carousel-item%s" data-bs-interval="10000">%s%s</div>',
				$active,
				$inner,
				$caption
			);

			++$render_index;
		}

		if ( empty( $slide_items ) ) {
			return;
		}

		// ── Assemble ───────────────────────────────────────────────────────
		$out   = array();
		$out[] = '<div id="lermSlides" class="carousel slide ' . esc_attr( $animation ) . ' mb-3" data-bs-ride="carousel">';

		if ( $show_indicators ) {
			$out[] = '<div class="carousel-indicators mb-0">' . implode( '', $indicator_items ) . '</div>';
		}

		$out[] = '<div class="carousel-inner">' . implode( '', $slide_items ) . '</div>';

		if ( $show_controls ) {
			$out[] = '<button class="carousel-control-prev d-none d-md-flex" type="button" data-bs-target="#lermSlides" data-bs-slide="prev">'
				. '<span class="carousel-control-prev-icon" aria-hidden="true"></span>'
				. '<span class="visually-hidden">' . esc_html__( 'Previous', 'lerm' ) . '</span>'
				. '</button>';

			$out[] = '<button class="carousel-control-next d-none d-md-flex" type="button" data-bs-target="#lermSlides" data-bs-slide="next">'
				. '<span class="carousel-control-next-icon" aria-hidden="true"></span>'
				. '<span class="visually-hidden">' . esc_html__( 'Next', 'lerm' ) . '</span>'
				. '</button>';
		}

		$out[] = '</div><!-- #lermSlides -->';

		echo wp_kses( implode( '', $out ), self::ALLOWED_HTML );
	}

	// ── 辅助方法 ──────────────────────────────────────────────────────────────

	/**
	 * 校验动画 class，不在白名单内则返回空字符串。
	 *
	 * @param string $animation 输入值。
	 * @return string
	 */
	private static function sanitize_animation( string $animation ): string {
		return in_array( $animation, self::ALLOWED_ANIMATIONS, true ) ? $animation : '';
	}
}
