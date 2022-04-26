<?php
/**
 * Display lerm_slides on Home page
 *
 * @author lerm http://lerm.net
 * @date   2016-08-27
 * @since  lerm 2.0
 */
namespace Lerm\Inc;

class Carousel {

	public static $args = array(
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

	// instance
	public static function instance( $params = array() ) {
		return new self( $params );
	}

	public static function render( $args = array() ) {
		self::filter_carousel_item( self::$args['slides'] );

		$indicator = '';
		$carousel  = '';

		if ( is_array( self::$args['slides'] ) && ! empty( self::$args['slides'] ) ) {
			foreach ( self::$args['slides'] as $key => $slide ) {

				$active     = ( 0 === $key ) ? ' active' : '';
				$indicator .= sprintf( '<button type="button" data-bs-target="#lermSlides" data-bs-slide-to="%s" class="%s" aria-current="true"></button>', $key, $active );
				$image      = sprintf( '<img class="d-block img-fluid w-100 rounded slider " src="%1$s" alt="%2$s" width="%3$s" height="%4$s">', $slide['image']['url'], $slide['title'], $slide['image']['width'], $slide['image']['height'] );
				$link       = sprintf( '<a href="%1$s" title="%2$s">%3$s</a>', $slide['url'], $slide['title'], $image );
				$title      = sprintf( '<div class="carousel-caption d-none d-md-block">%1$s%2$s</div>', $slide['title'] ? '<h5>' . $slide['title'] . '</h5>' : '', $slide['description'] ? '<p>' . $slide['description'] . '</p>' : '' );
				$carousel  .= sprintf( '<div class="carousel-item%1$s" data-bs-interval="10000">%2$s%3$s</div>', $active, $slide['url'] ? $link : $image, $title );
			}
			?>
			<div id="lermSlides" class="carousel slide <?php echo esc_attr( self::$args['animation'] ); ?> mb-3" data-bs-ride="carousel">
			<?php if ( self::$args['indicators'] ) { ?>
					<div class="carousel-indicators mb-0">
						<?php echo $indicator; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				<?php } ?>
				<div class="carousel-inner">
				<?php echo $carousel; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php if ( self::$args['control'] ) { ?>
					<button class="carousel-control-prev d-none d-md-flex" type="button" data-bs-target="#lermSlides" data-bs-slide="prev">
						<span class="carousel-control-prev-icon" aria-hidden="true"></span>
						<span class="visually-hidden">Previous</span>
					</button>
					<button class="carousel-control-next d-none d-md-flex" type="button" data-bs-target="#lermSlides" data-bs-slide="next">
						<span class="carousel-control-next-icon" aria-hidden="true"></span>
						<span class="visually-hidden">Next</span>
					</button>
				<?php } ?>
			</div>
			<?php
		}
	}

	protected static function filter_carousel_item( $slides = array() ) {
		if ( is_array( $slides ) && ! empty( $slides ) ) {
			foreach ( $slides as $key => $slide ) {
				if ( self::is_array_empty( $slide ) ) {
					unset( $slides[ $key ] );
				}
			}
			self::$args['slides'] = $slides;
		}
	}

	protected static function is_array_empty( $array ) {
		if ( is_array( $array ) && ! empty( $array ) ) {
			$tmp = array_shift( $array );
			if ( ! self::is_array_empty( $array ) || ! self::is_array_empty( $tmp ) ) {
				return false;
			}
			return true;
		}
		if ( empty( $array ) ) {
			return true;
		}
		return false;
	}
}
