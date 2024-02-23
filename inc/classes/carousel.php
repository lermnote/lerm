<?php // phpcs:disable WordPress.Files.FileName
/**
 * Display lerm_slides on Home page
 *
 * @author lerm http://lerm.net
 * @date   2016-08-27
 * @since  lerm 2.0
 */
namespace Lerm\Inc;

use Lerm\Inc\Traits\Singleton;

class Carousel {

	use singleton;

	private static $args = array(
		'slide_enable' => false,
		'slides'       => array(),
		'indicators'   => false,
		'control'      => false,
		'animation'    => 'carousel-fade',
	);

	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_slide_', wp_parse_args( $params, self::$args ) );

		if ( self::$args['slide_enable'] ) {
			self::render();
		}
	}

	public static function render( $args = array() ) {
		$slides    = self::$args['slides'];
		$indicator = array();
		$carousel  = array();

		if ( ! empty( $slides ) ) {
			foreach ( $slides as $key => $slide ) {

				if ( ! self::is_array_empty( $slide['image'] ) ) {
					$active      = ( 0 === $key ) ? ' active' : '';
					$indicator[] = sprintf( '<button type="button" data-bs-target="#lermSlides" data-bs-slide-to="%s" class="%s" aria-current="true"></button>', $key, $active );
					$image       = sprintf( '<img class="d-block img-fluid w-100 rounded slider " src="%1$s" alt="%2$s" width="%3$s" height="%4$s">', $slide['image']['url'], $slide['title'], $slide['image']['width'], $slide['image']['height'] );
					$link        = sprintf( '<a href="%1$s" title="%2$s">%3$s</a>', $slide['url'], $slide['title'], $image );
					$title       = sprintf( '<div class="carousel-caption d-none d-md-block">%1$s%2$s</div>', $slide['title'] ? '<h5>' . $slide['title'] . '</h5>' : '', $slide['description'] ? '<p>' . $slide['description'] . '</p>' : '' );
					$carousel[]  = sprintf( '<div class="carousel-item%1$s" data-bs-interval="10000">%2$s%3$s</div>', $active, $slide['url'] ? $link : $image, $title );
				}
			}

			$output   = array();
			$output[] = '<div id="lermSlides" class="carousel slide ' . esc_attr( self::$args['animation'] ) . ' mb-3" data-bs-ride="carousel">';

			if ( self::$args['indicators'] ) {
				$output[] = '<div class="carousel-indicators mb-0">';
				$output[] = implode( '', $indicator );
				$output[] = '</div>';
			}

			$output[] = '<div class="carousel-inner">';
			$output[] = implode( '', $carousel );
			$output[] = '</div>';

			if ( self::$args['control'] ) {
				$output[] = '<button class="carousel-control-prev d-none d-md-flex" type="button" data-bs-target="#lermSlides" data-bs-slide="prev">';
				$output[] = '<span class="carousel-control-prev-icon" aria-hidden="true"></span>';
				$output[] = '<span class="visually-hidden">Previous</span>';
				$output[] = '</button>';

				$output[] = '<button class="carousel-control-next d-none d-md-flex" type="button" data-bs-target="#lermSlides" data-bs-slide="next">';
				$output[] = '<span class="carousel-control-next-icon" aria-hidden="true"></span>';
				$output[] = '<span class="visually-hidden">Next</span>';
				$output[] = '</button>';
			}

			$output[] = '</div>';

			echo implode( '', $output );//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	protected static function is_array_empty( $array ) {
		$non_empty = array_filter(
			$array,
			function ( $item ) {
				return ! empty( $item );
			}
		);
		return empty( $non_empty );
	}
}
