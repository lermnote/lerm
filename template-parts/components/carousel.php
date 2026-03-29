<?php
use Lerm\View\Carousel;

$template_options = lerm_get_template_options();

if ( is_home() || is_front_page() || ! is_paged() ) {
	Carousel::instance(
		array(
			'slide_enable' => $template_options['slide_enable'],
			'slides'       => $template_options['slide_images'],
			'indicators'   => $template_options['slide_indicators'],
			'control'      => $template_options['slide_control'],
			'position'     => $template_options['slide_position'],
		)
	);
}
