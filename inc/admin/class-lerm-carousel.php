<?php
/*
 * @Author: your name
 * @Date: 2019-12-11 21:58:10
 * @LastEditTime: 2020-08-19 21:47:20
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: \lerm\inc\admin\class-lerm-carousel.php
 */
/**
 * @author lerm http://lerm.net
 * @date   2016-08-27
 * @since  lerm 2.0
 *
 * Display lerm_slides on Home page
 */

function lerm_carousel( $args = array() ) {
	$carousel = new Lerm_Carousel( $args );
	return $carousel->render();
}
class Lerm_Carousel {

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
				$indicator .= sprintf( '<li data-target="#lermSlides" data-slide-to="%s" class="%s"></li>', $key, $active );
				$image      = sprintf( '<img class="d-block img-fluid w-100 rounded slider " src="%1$s" alt="%2$s" width="%3$s" height="%4$s">', $slide['image']['url'], $slide['title'], $slide['image']['width'], $slide['image']['height'] );
				$link       = sprintf( '<a href="%1$s" title="%2$s">%3$s</a>', $slide['url'], $slide['title'], $image );
				$title      = sprintf( '<div class="carousel-caption">%1$s%2$s</div>', $slide['title'] ? '<h5>' . $slide['title'] . '</h5>' : '', $slide['description'] ? '<p>' . $slide['description'] . '</p>' : '' );
				$carousel  .= sprintf( '<div class="carousel-item%1$s">%2$s%3$s</div>', $active, $slide['url'] ? $link : $image, $title );
			}
			?>
			<div id="lermSlides" class="carousel slide carousel-fade mb-3" data-ride="carousel">
				<?php if ( $this->args['indicators'] ) { ?>
					<ol class="carousel-indicators mb-0">
						<?php echo $indicator; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</ol>
				<?php } ?>
				<div class="carousel-inner">
					<?php echo $carousel; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<?php if ( $this->args['control_arrows'] ) { ?>
					<a class="carousel-control-prev d-none d-md-flex" href="#lermSlides" role="button" data-slide="prev">
						<span class="carousel-control-prev-icon" aria-hidden="true"></span>
						<span class="screen-reader-text">Previous</span>
					</a>
					<a class="carousel-control-next d-none d-md-flex" href="#lermSlides" role="button" data-slide="next">
						<span class="carousel-control-next-icon" aria-hidden="true"></span>
						<span class="screen-reader-text">Next</span>
					</a>
				<?php } ?>
			</div>
			<?php
		}
	}
}
