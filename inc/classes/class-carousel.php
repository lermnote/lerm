<?php
/**
 * @author lerm http://lerm.net
 * @date   2016-08-27
 * @since  lerm 2.0
 *
 * Display lerm_slides on Home page
 */
namespace Lerm\Inc;

use Lerm\Inc\Traits\Singleton;

class Carousel {

	use Singleton;

	public $slides = array();

	public function __construct( $args = array() ) {
		$defaults = array(
			'slides'         => lerm_options( 'lerm_slides' ),
			'indicators'     => lerm_options( 'slide_indicators' ),
			'control_arrows' => lerm_options( 'slide_control' ),
		);
		// Parse the arguments with the deaults.
		$this->args = apply_filters( 'lerm_slide_', wp_parse_args( $args, $defaults ) );
		$this->get();
	}

	private function get() {
		$all_slides = $this->args['slides'];
		$sort       = 0;
		if ( $all_slides ) {
			foreach ( $all_slides as $slide ) {
				if ( ! empty( $slide['image']['id'] ) ) {
					$slides[ $sort ] = $slide;
					$sort++;
				}
			}
			$this->slides = $slides;
		}
	}

	public function render() {
		$indicator = '';
		$carousel  = '';
		if ( $this->slides ) {
			foreach ( $this->slides as $key => $slide ) {
				$active     = ( 0 === $key ) ? ' active' : '';
				$indicator .= sprintf( '<button type="button" data-bs-target="#lermSlides" data-bs-slide-to="%s" class="%s" aria-current="true"></button>', $key, $active );
				$image      = sprintf( '<img class="d-block img-fluid w-100 rounded slider " src="%1$s" alt="%2$s" width="%3$s" height="%4$s">', $slide['image']['url'], $slide['title'], $slide['image']['width'], $slide['image']['height'] );
				$link       = sprintf( '<a href="%1$s" title="%2$s">%3$s</a>', $slide['url'], $slide['title'], $image );
				$title      = sprintf( '<div class="carousel-caption d-none d-md-block">%1$s%2$s</div>', $slide['title'] ? '<h5>' . $slide['title'] . '</h5>' : '', $slide['description'] ? '<p>' . $slide['description'] . '</p>' : '' );
				$carousel  .= sprintf( '<div class="carousel-item%1$s" data-bs-interval="10000">%2$s%3$s</div>', $active, $slide['url'] ? $link : $image, $title );
			}
			?>
			<div id="lermSlides" class="carousel slide carousel-fade mb-3" data-bs-ride="carousel">
				<?php if ( $this->args['indicators'] ) { ?>
					<div class="carousel-indicators mb-0">
						<?php echo $indicator; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				<?php } ?>
				<div class="carousel-inner">
					<?php echo $carousel; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<?php if ( $this->args['control_arrows'] ) { ?>
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
}
