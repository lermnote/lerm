<?php // phpcs:disable WordPress.Files.FileName

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'title'       => __( 'Appearance / Colors', 'lerm' ),
	'description' => __( 'Brand colours, backgrounds, borders, and button styling migrated from the old CSF Colors tab.', 'lerm' ),
	'fields'      => array(
		array(
			'id'          => 'primary_color',
			'type'        => 'fieldset',
			'label'       => __( 'Primary color', 'lerm' ),
			'description' => __( 'Used for buttons, badges, and interactive accents.', 'lerm' ),
			'group'       => __( 'Brand colors', 'lerm' ),
			'default'     => array(
				'color'  => '#0084ba',
				'hover'  => '#0063aa',
				'active' => '#0063aa',
				'focus'  => '#0063aa',
			),
			'fields'      => array(
				array( 'id' => 'color', 'type' => 'color', 'label' => __( 'Base', 'lerm' ), 'default' => '#0084ba' ),
				array( 'id' => 'hover', 'type' => 'color', 'label' => __( 'Hover', 'lerm' ), 'default' => '#0063aa' ),
				array( 'id' => 'active', 'type' => 'color', 'label' => __( 'Active', 'lerm' ), 'default' => '#0063aa' ),
				array( 'id' => 'focus', 'type' => 'color', 'label' => __( 'Focus', 'lerm' ), 'default' => '#0063aa' ),
			),
		),
		array(
			'id'          => 'link_color',
			'type'        => 'fieldset',
			'label'       => __( 'Link color', 'lerm' ),
			'description' => __( 'Colour of anchors in article and page content.', 'lerm' ),
			'group'       => __( 'Brand colors', 'lerm' ),
			'default'     => array(
				'color'  => '#0084ba',
				'hover'  => '#0063aa',
				'active' => '#0063aa',
				'focus'  => '#0063aa',
			),
			'fields'      => array(
				array( 'id' => 'color', 'type' => 'color', 'label' => __( 'Base', 'lerm' ), 'default' => '#0084ba' ),
				array( 'id' => 'hover', 'type' => 'color', 'label' => __( 'Hover', 'lerm' ), 'default' => '#0063aa' ),
				array( 'id' => 'active', 'type' => 'color', 'label' => __( 'Active', 'lerm' ), 'default' => '#0063aa' ),
				array( 'id' => 'focus', 'type' => 'color', 'label' => __( 'Focus', 'lerm' ), 'default' => '#0063aa' ),
			),
		),
		array(
			'id'          => 'body_background',
			'type'        => 'fieldset',
			'label'       => __( 'Body background', 'lerm' ),
			'description' => __( 'Page background colour used across the whole site.', 'lerm' ),
			'group'       => __( 'Page background', 'lerm' ),
			'default'     => array(
				'background-color' => '#ffffff',
			),
			'fields'      => array(
				array( 'id' => 'background-color', 'type' => 'color', 'label' => __( 'Background color', 'lerm' ), 'default' => '#ffffff' ),
			),
		),
		array(
			'id'          => 'content_background',
			'type'        => 'fieldset',
			'label'       => __( 'Card / content background', 'lerm' ),
			'description' => __( 'Background colour used by article cards and content surfaces.', 'lerm' ),
			'group'       => __( 'Page background', 'lerm' ),
			'default'     => array(
				'background-color' => '#ffffff',
			),
			'fields'      => array(
				array( 'id' => 'background-color', 'type' => 'color', 'label' => __( 'Background color', 'lerm' ), 'default' => '#ffffff' ),
			),
		),
		array(
			'id'          => 'site_header_border',
			'type'        => 'fieldset',
			'label'       => __( 'Header border', 'lerm' ),
			'description' => __( 'Border drawn around the site header. Most themes only use the bottom edge.', 'lerm' ),
			'group'       => __( 'Header & navigation', 'lerm' ),
			'default'     => array(
				'top'    => '0',
				'bottom' => '1',
				'left'   => '0',
				'right'  => '0',
				'style'  => 'solid',
				'color'  => '#82828244',
			),
			'fields'      => array(
				array( 'id' => 'top', 'type' => 'number', 'label' => __( 'Top (px)', 'lerm' ), 'default' => 0, 'min' => 0, 'max' => 20, 'step' => 1 ),
				array( 'id' => 'bottom', 'type' => 'number', 'label' => __( 'Bottom (px)', 'lerm' ), 'default' => 1, 'min' => 0, 'max' => 20, 'step' => 1 ),
				array( 'id' => 'left', 'type' => 'number', 'label' => __( 'Left (px)', 'lerm' ), 'default' => 0, 'min' => 0, 'max' => 20, 'step' => 1 ),
				array( 'id' => 'right', 'type' => 'number', 'label' => __( 'Right (px)', 'lerm' ), 'default' => 0, 'min' => 0, 'max' => 20, 'step' => 1 ),
				array(
					'id'      => 'style',
					'type'    => 'select',
					'label'   => __( 'Style', 'lerm' ),
					'default' => 'solid',
					'choices' => array(
						'solid'  => __( 'Solid', 'lerm' ),
						'dashed' => __( 'Dashed', 'lerm' ),
						'dotted' => __( 'Dotted', 'lerm' ),
						'double' => __( 'Double', 'lerm' ),
					),
				),
				array( 'id' => 'color', 'type' => 'color', 'label' => __( 'Color', 'lerm' ), 'default' => '#82828244' ),
			),
		),
		array(
			'id'          => 'navbar_link_color',
			'type'        => 'fieldset',
			'label'       => __( 'Nav link color', 'lerm' ),
			'description' => __( 'Colour of navigation links and dropdown items.', 'lerm' ),
			'group'       => __( 'Header & navigation', 'lerm' ),
			'default'     => array(
				'color' => '#828282',
				'hover' => '#0084ba',
			),
			'fields'      => array(
				array( 'id' => 'color', 'type' => 'color', 'label' => __( 'Base', 'lerm' ), 'default' => '#828282' ),
				array( 'id' => 'hover', 'type' => 'color', 'label' => __( 'Hover', 'lerm' ), 'default' => '#0084ba' ),
			),
		),
		array(
			'id'          => 'navbar_active_color',
			'type'        => 'fieldset',
			'label'       => __( 'Nav active item color', 'lerm' ),
			'description' => __( 'Text and background colours for the active navigation item.', 'lerm' ),
			'group'       => __( 'Header & navigation', 'lerm' ),
			'default'     => array(
				'color'            => '#0084ba',
				'background_color' => '#ffffff',
			),
			'fields'      => array(
				array( 'id' => 'color', 'type' => 'color', 'label' => __( 'Text color', 'lerm' ), 'default' => '#0084ba' ),
				array( 'id' => 'background_color', 'type' => 'color', 'label' => __( 'Background color', 'lerm' ), 'default' => '#ffffff' ),
			),
		),
		array(
			'id'          => 'navbar_item_padding',
			'type'        => 'fieldset',
			'label'       => __( 'Nav item padding', 'lerm' ),
			'description' => __( 'Vertical padding applied to each navigation link.', 'lerm' ),
			'group'       => __( 'Header & navigation', 'lerm' ),
			'default'     => array(
				'top'    => '1.5',
				'bottom' => '1.5',
				'unit'   => 'rem',
			),
			'fields'      => array(
				array( 'id' => 'top', 'type' => 'text', 'label' => __( 'Top', 'lerm' ), 'default' => '1.5' ),
				array( 'id' => 'bottom', 'type' => 'text', 'label' => __( 'Bottom', 'lerm' ), 'default' => '1.5' ),
				array( 'id' => 'left', 'type' => 'text', 'label' => __( 'Left', 'lerm' ), 'default' => '' ),
				array( 'id' => 'right', 'type' => 'text', 'label' => __( 'Right', 'lerm' ), 'default' => '' ),
				array(
					'id'      => 'unit',
					'type'    => 'select',
					'label'   => __( 'Unit', 'lerm' ),
					'default' => 'rem',
					'choices' => array(
						'rem' => 'rem',
						'em'  => 'em',
						'px'  => 'px',
					),
				),
			),
		),
		array(
			'id'          => 'widget_header_color',
			'type'        => 'fieldset',
			'label'       => __( 'Widget header color', 'lerm' ),
			'description' => __( 'Text, background, and border colours of widget and card headers.', 'lerm' ),
			'group'       => __( 'Widget headers', 'lerm' ),
			'default'     => array(
				'color'            => '',
				'background_color' => '',
				'border_color'     => '',
			),
			'fields'      => array(
				array( 'id' => 'color', 'type' => 'color', 'label' => __( 'Text color', 'lerm' ), 'default' => '' ),
				array( 'id' => 'background_color', 'type' => 'color', 'label' => __( 'Background color', 'lerm' ), 'default' => '' ),
				array( 'id' => 'border_color', 'type' => 'color', 'label' => __( 'Border color', 'lerm' ), 'default' => '' ),
			),
		),
		array(
			'id'          => 'footer_widget_color',
			'type'        => 'fieldset',
			'label'       => __( 'Footer widgets area', 'lerm' ),
			'description' => __( 'Text and background colours of the footer widgets zone.', 'lerm' ),
			'group'       => __( 'Footer', 'lerm' ),
			'default'     => array(
				'color'            => '#dddddd',
				'background_color' => '#333333',
			),
			'fields'      => array(
				array( 'id' => 'color', 'type' => 'color', 'label' => __( 'Text color', 'lerm' ), 'default' => '#dddddd' ),
				array( 'id' => 'background_color', 'type' => 'color', 'label' => __( 'Background color', 'lerm' ), 'default' => '#333333' ),
			),
		),
		array(
			'id'          => 'footer_bar_color',
			'type'        => 'fieldset',
			'label'       => __( 'Footer bar', 'lerm' ),
			'description' => __( 'Text and background colours of the copyright strip.', 'lerm' ),
			'group'       => __( 'Footer', 'lerm' ),
			'default'     => array(
				'color'            => '#dddddd',
				'background_color' => '#555555',
			),
			'fields'      => array(
				array( 'id' => 'color', 'type' => 'color', 'label' => __( 'Text color', 'lerm' ), 'default' => '#dddddd' ),
				array( 'id' => 'background_color', 'type' => 'color', 'label' => __( 'Background color', 'lerm' ), 'default' => '#555555' ),
			),
		),
		array(
			'id'          => 'btn_primary',
			'type'        => 'fieldset',
			'label'       => __( 'Primary button (default state)', 'lerm' ),
			'description' => __( 'Text, background, and border colours for primary buttons at rest.', 'lerm' ),
			'group'       => __( 'Buttons', 'lerm' ),
			'default'     => array(
				'color'            => '#0084ba',
				'background_color' => '',
				'border_color'     => '#0084ba',
			),
			'fields'      => array(
				array( 'id' => 'color', 'type' => 'color', 'label' => __( 'Text color', 'lerm' ), 'default' => '#0084ba' ),
				array( 'id' => 'background_color', 'type' => 'color', 'label' => __( 'Background color', 'lerm' ), 'default' => '' ),
				array( 'id' => 'border_color', 'type' => 'color', 'label' => __( 'Border color', 'lerm' ), 'default' => '#0084ba' ),
			),
		),
		array(
			'id'          => 'btn_primary_hover',
			'type'        => 'fieldset',
			'label'       => __( 'Primary button (hover state)', 'lerm' ),
			'description' => __( 'Text, background, and border colours for primary buttons on hover.', 'lerm' ),
			'group'       => __( 'Buttons', 'lerm' ),
			'default'     => array(
				'color'            => '#ffffff',
				'background_color' => '#0084ba',
				'border_color'     => '#0084ba',
			),
			'fields'      => array(
				array( 'id' => 'color', 'type' => 'color', 'label' => __( 'Text color', 'lerm' ), 'default' => '#ffffff' ),
				array( 'id' => 'background_color', 'type' => 'color', 'label' => __( 'Background color', 'lerm' ), 'default' => '#0084ba' ),
				array( 'id' => 'border_color', 'type' => 'color', 'label' => __( 'Border color', 'lerm' ), 'default' => '#0084ba' ),
			),
		),
	),
);
