<?php if ( ! defined( 'ABSPATH' )  ) { die; } // Cannot access directly.

$prefix = 'lerm_taxonomy_options';

CSF::createTaxonomyOptions( $prefix, array(
	'taxonomy'  => 'category',
	'data_type' => 'serialize',
) );

CSF::createSection( $prefix, array(
	'fields' => array(
		array(
			'id'      => 'archive_color',
			'type'    => 'color_group',
			'title'   => 'Archive header color',
			'options' => array(
				'bg_color'   => __('Background', 'lerm'),
				'font_color' => __('Font', 'lerm'),
			),
			'default' => array(
				'bg_color'   => '#fff',
				'font_color' => '#5d6777',
			)
		),
		array(
			'id'    => 'archive_header_image',
			'type'  => 'media',
			'title' => 'Archive Background image',
			'url'   => false,
		),
  )
) );
