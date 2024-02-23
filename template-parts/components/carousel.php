<?php
if ( is_home() || is_front_page() || ! is_paged() ) {
	\Lerm\Inc\Carousel::instance(
		array(
			'slide_enable' => lerm_options( 'slide_enable' ),
			'slides'       => lerm_options( 'slide_images' ),
			'indicators'   => lerm_options( 'slide_indicators' ),
			'control'      => lerm_options( 'slide_control' ),
			'position'     => lerm_options( 'slide_position' ),
		)
	);
}
