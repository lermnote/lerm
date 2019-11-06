<?php
/**
* @author lerm http://lerm.net
* @date    2016-08-27
* @since   lerm 2.0
*
* Display lerm_slides on Home page
*/
function lerm_carousel() {
	global $lerm;
	$item_posit = 0;
	$indicator  = '';
	$carousel   = '';
	if ( $lerm['slide_switcher'] && isset( $lerm['lerm_slides'] ) && ! empty( $lerm['lerm_slides'] ) ) {
		$output = '<div id="lermCarouselIndicators" class="carousel slide" data-ride="carousel">';
		foreach ( $lerm['lerm_slides'] as $key => $item ) {
			$title = $item['title'];
			$desc  = $item['description'];
			$url   = $item['url'];
			$image = $item['image'];
			if ( ! empty( $image['url'] ) && ! $image['url'] == '' ) {
				++$item_posit;
				$active     = ( 1 == $item_posit ) ? 'active' : '';
				$indicator .= sprintf( '<li data-target="#lermCarouselIndicators" data-slide-to="%s" class="%s"></li>', ( $item_posit - 1 ), $active );
				$carousel  .= sprintf( '<div class="carousel-item %1$s"><a href="%2$s"><img class="slider" src="%3$s" alt="%4$s"></a><div class="container"><div class="carousel-caption"><h3>%4$s</h3><p>%5$s</p></div></div></div>', $active, $url, $image['url'], $title, $desc );
			}
		}
		$output .= sprintf( '<ol class="carousel-indicators">%s</ol><div class="carousel-inner" role="listbox">%s</div>', $indicator, $carousel );
		$output .= '<a class="carousel-control-prev d-none d-lg-flex" href="#lermCarouselIndicators" role="button" data-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="sr-only">Previous</span></a>';
		$output .= '<a class="carousel-control-next d-none d-lg-flex" href="#lermCarouselIndicators" role="button" data-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="sr-only">Next</span></a>';
		$output .= '</div>';
		echo $output;
	}
}

class Lerm_Carousel {
	public function __construct( $args = array() ) {
		$defaults = array(
			'show'     => true,
			'position' => '',
			'animate'  => 'left',
			'height'   => '',
			'width'    => '',
		);
		// Parse the arguments with the deaults.
		$this->args = apply_filters( 'breadcrumb_trail_args', wp_parse_args( $args, $defaults ) );
		$this->get_carousel();
	}
	public function get_carousel() {
		global $lerm;
		$index     = 0;
		$indicator = '';
		$carousel  = '';
		foreach ( $lerm['lerm_slides'] as $item ) {
			$title = $item['title'];
			$desc  = $item['description'];
			$url   = $item['url'];
			$image = $item['image'];
			if ( ! empty( $image['url'] ) && '' !== $image['url'] ) {
				$active     = ( 0 === $index ) ? 'active' : '';
				$indicator .= sprintf( '<li data-target="#lermCarouselIndicators" data-slide-to="%s" class="%s"></li>', $index, $active );
				$carousel  .= sprintf( '<div class="carousel-item %1$s"><a href="%2$s"><img class="slider" src="%3$s" alt="%4$s"></a><div class="container"><div class="carousel-caption"><h3>%4$s</h3><p>%5$s</p></div></div></div>', $active, $url, $image['url'], $title, $desc );
				++$index;
			}
		}
	}
	public function set_carousel() {

	}
}
