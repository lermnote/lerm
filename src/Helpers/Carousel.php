<?php // phpcs:disable WordPress.Files.FileName
namespace Lerm\Helpers;

use Lerm\Traits\Singleton;

/**
 * Carousel renderer (Bootstrap 5)
 */
class Carousel {

	use Singleton;

	/**
	 * Default args.
	 *
	 * @var array
	 */
	private static $args = array(
		'slide_enable' => false,
		'slides'       => array(),
		'indicators'   => false,
		'control'      => false,
		'animation'    => 'carousel-fade',
	);

	/**
	 * Constructor.
	 *
	 * @param array $params Optional args.
	 */
	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_slide_args', wp_parse_args( $params, self::$args ) );

		if ( ! empty( self::$args['slide_enable'] ) ) {
			self::render();
		}
	}

	/**
	 * Render the carousel.
	 *
	 * @param array $args Optional override args.
	 */
	public static function render( $args = array() ) {
		$args   = wp_parse_args( $args, self::$args );
		$slides = is_array( $args['slides'] ) ? $args['slides'] : array();

		if ( empty( $slides ) ) {
			return;
		}

		$animation          = self::sanitize_animation( $args['animation'] );
		$indicators_enabled = ! empty( $args['indicators'] );
		$controls_enabled   = ! empty( $args['control'] );

		$indicator_html = array();
		$carousel_html  = array();

		foreach ( $slides as $key => $slide ) {

			if ( ! is_array( $slide ) ) {
				continue;
			}

			$image = isset( $slide['image'] ) && is_array( $slide['image'] ) ? $slide['image'] : array();

			// skip slides without a usable image url.
			if ( empty( $image['url'] ) ) {
				continue;
			}

			$active = ( 0 === $key ) ? ' active' : '';

			// Indicator (only build if indicators might be output)
			/* translators: %d: Slide number (1-based). */
			$aria_label       = esc_attr( sprintf( __( 'Slide %d', 'lerm' ), $key + 1 ) );
			$indicator_html[] = sprintf(
				'<button type="button" data-bs-target="#lermSlides" data-bs-slide-to="%d" class="%s" %s aria-label="%s"></button>',
				intval( $key ),
				$active,
				$active ? 'aria-current="true"' : '',
				$aria_label
			);

			// Image tag
			$img_alt    = isset( $image['alt'] ) ? $image['alt'] : ( isset( $slide['title'] ) ? $slide['title'] : '' );
			$img_width  = isset( $image['width'] ) ? $image['width'] : '';
			$img_height = isset( $image['height'] ) ? $image['height'] : '';

			$image_tag = sprintf(
				'<img class="d-block img-fluid w-100 rounded slider" src="%s" alt="%s"%s%s loading="lazy">',
				esc_url( $image['url'] ),
				esc_attr( $img_alt ),
				$img_width ? ' width="' . esc_attr( $img_width ) . '"' : '',
				$img_height ? ' height="' . esc_attr( $img_height ) . '"' : ''
			);

			// Wrap in link if provided
			$link = ( ! empty( $slide['url'] ) ) ? sprintf(
				'<a href="%s" title="%s">%s</a>',
				esc_url( $slide['url'] ),
				esc_attr( isset( $slide['title'] ) ? $slide['title'] : '' ),
				$image_tag
			) : $image_tag;

			// Caption
			$caption = '';
			if ( ! empty( $slide['title'] ) || ! empty( $slide['description'] ) ) {
				$caption  = '<div class="carousel-caption d-none d-md-block">';
				$caption .= ! empty( $slide['title'] ) ? '<h5>' . esc_html( $slide['title'] ) . '</h5>' : '';
				$caption .= ! empty( $slide['description'] ) ? '<p>' . esc_html( $slide['description'] ) . '</p>' : '';
				$caption .= '</div>';
			}

			$carousel_html[] = sprintf(
				'<div class="carousel-item%s" data-bs-interval="10000">%s%s</div>',
				$active,
				$link,
				$caption
			);
		}

		// Nothing to show
		if ( empty( $carousel_html ) ) {
			return;
		}

		$output   = array();
		$output[] = '<div id="lermSlides" class="carousel slide ' . esc_attr( $animation ) . ' mb-3" data-bs-ride="carousel">';

		if ( $indicators_enabled ) {
			$output[] = '<div class="carousel-indicators mb-0">';
			$output[] = implode( '', $indicator_html );
			$output[] = '</div>';
		}

		$output[] = '<div class="carousel-inner">';
		$output[] = implode( '', $carousel_html );
		$output[] = '</div>';

		if ( $controls_enabled ) {
			$output[] = '<button class="carousel-control-prev d-none d-md-flex" type="button" data-bs-target="#lermSlides" data-bs-slide="prev">';
			$output[] = '<span class="carousel-control-prev-icon" aria-hidden="true"></span>';
			$output[] = '<span class="visually-hidden">' . esc_html__( 'Previous', 'lerm' ) . '</span>';
			$output[] = '</button>';

			$output[] = '<button class="carousel-control-next d-none d-md-flex" type="button" data-bs-target="#lermSlides" data-bs-slide="next">';
			$output[] = '<span class="carousel-control-next-icon" aria-hidden="true"></span>';
			$output[] = '<span class="visually-hidden">' . esc_html__( 'Next', 'lerm' ) . '</span>';
			$output[] = '</button>';
		}

		$output[] = '</div>';

		$html = implode( '', $output );

		// allow only specific tags + attributes so data-* & aria-* survive
		$allowed = array(
			'div'    => array(
				'id'               => true,
				'class'            => true,
				'data-bs-ride'     => true,
				'data-bs-interval' => true,
			),
			'button' => array(
				'type'           => true,
				'class'          => true,
				'data-bs-target' => true,
				'data-bs-slide'  => true,
				'aria-current'   => true,
				'aria-label'     => true,
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

		echo wp_kses( $html, $allowed ); // safe output
	}

	/**
	 * Return true if array is empty or not an array or contains only empty values.
	 *
	 * @param mixed $candidate Candidate.
	 * @return bool
	 */
	protected static function is_array_empty( $candidate ) {
		if ( ! is_array( $candidate ) ) {
			return true;
		}
		$non_empty = array_filter(
			$candidate,
			function ( $item ) {
				return ! empty( $item );
			}
		);
		return empty( $non_empty );
	}

	/**
	 * Ensure animation is an allowed/whitelisted class.
	 *
	 * @param string $animation Input animation.
	 * @return string Sanitized animation class.
	 */
	protected static function sanitize_animation( $animation ) {
		$allowed   = array(
			'carousel-fade',
			'', // no animation
		);
		$animation = (string) $animation;
		return in_array( $animation, $allowed, true ) ? $animation : '';
	}
}
