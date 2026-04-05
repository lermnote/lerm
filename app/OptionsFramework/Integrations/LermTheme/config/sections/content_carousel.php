<?php // phpcs:disable WordPress.Files.FileName

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'title'       => __( 'Content / Carousel', 'lerm' ),
	'description' => __( 'Homepage hero slider settings that were still only available in the legacy CSF carousel tab.', 'lerm' ),
	'fields'      => array(
		array(
			'id'          => 'slide_enable',
			'type'        => 'switcher',
			'label'       => __( 'Enable homepage slider', 'lerm' ),
			'description' => __( 'Shows the carousel block on the homepage when slides exist.', 'lerm' ),
			'group'       => __( 'Carousel', 'lerm' ),
			'default'     => false,
		),
		array(
			'id'               => 'slide_position',
			'type'             => 'button_set',
			'label'            => __( 'Slider position', 'lerm' ),
			'description'      => __( 'Controls how wide the homepage slider should be rendered.', 'lerm' ),
			'group'            => __( 'Carousel', 'lerm' ),
			'default'          => 'under_navbar',
			'choices'          => array(
				'under_navbar'  => __( 'Under navbar', 'lerm' ),
				'under_primary' => __( 'Content width', 'lerm' ),
				'full_width'    => __( 'Full width', 'lerm' ),
			),
			'dependency_field' => 'slide_enable',
			'dependency_value' => '1',
		),
		array(
			'id'               => 'slide_indicators',
			'type'             => 'switcher',
			'label'            => __( 'Show indicators', 'lerm' ),
			'description'      => __( 'Displays the small dots beneath the carousel.', 'lerm' ),
			'group'            => __( 'Carousel', 'lerm' ),
			'default'          => false,
			'dependency_field' => 'slide_enable',
			'dependency_value' => '1',
		),
		array(
			'id'               => 'slide_control',
			'type'             => 'switcher',
			'label'            => __( 'Show previous / next controls', 'lerm' ),
			'group'            => __( 'Carousel', 'lerm' ),
			'default'          => false,
			'dependency_field' => 'slide_enable',
			'dependency_value' => '1',
		),
		array(
			'id'               => 'slide_images',
			'type'             => 'group',
			'label'            => __( 'Slides', 'lerm' ),
			'description'      => __( 'Each slide can include an image, title, optional link, and caption text.', 'lerm' ),
			'group'            => __( 'Slides', 'lerm' ),
			'default'          => array(),
			'button_text'      => __( 'Add slide', 'lerm' ),
			'dependency_field' => 'slide_enable',
			'dependency_value' => '1',
			'fields'           => array(
				array(
					'id'          => 'image',
					'type'        => 'media',
					'label'       => __( 'Slide image', 'lerm' ),
					'description' => __( 'Main slide image displayed in the carousel.', 'lerm' ),
					'default'     => array(),
				),
				array(
					'id'          => 'title',
					'type'        => 'text',
					'label'       => __( 'Caption title', 'lerm' ),
					'default'     => '',
					'placeholder' => __( 'Hero title', 'lerm' ),
				),
				array(
					'id'          => 'url',
					'type'        => 'url',
					'label'       => __( 'Link URL', 'lerm' ),
					'default'     => '',
					'placeholder' => 'https://example.com/article',
				),
				array(
					'id'          => 'description',
					'type'        => 'textarea',
					'label'       => __( 'Caption text', 'lerm' ),
					'default'     => '',
					'rows'        => 3,
					'placeholder' => __( 'Short supporting text shown over the slide.', 'lerm' ),
				),
			),
		),
	),
);
