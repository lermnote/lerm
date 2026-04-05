<?php // phpcs:disable WordPress.Files.FileName

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'title'       => __( 'Header', 'lerm' ),
	'description' => __( 'Branding, navigation, and header behaviour settings.', 'lerm' ),
	'fields'      => array(
		array(
			'id'          => 'large_logo',
			'type'        => 'media',
			'label'       => __( 'Desktop logo', 'lerm' ),
			'description' => __( 'Shown in the desktop header.', 'lerm' ),
			'group'       => __( 'Branding', 'lerm' ),
			'default'     => array(),
			'button_text' => __( 'Choose desktop logo', 'lerm' ),
		),
		array(
			'id'          => 'mobile_logo',
			'type'        => 'media',
			'label'       => __( 'Mobile logo', 'lerm' ),
			'description' => __( 'Shown on small screens. Falls back to the desktop logo if empty.', 'lerm' ),
			'group'       => __( 'Branding', 'lerm' ),
			'default'     => array(),
			'button_text' => __( 'Choose mobile logo', 'lerm' ),
		),
		array(
			'id'          => 'blogname',
			'type'        => 'text',
			'label'       => __( 'Site title override', 'lerm' ),
			'description' => __( 'Overrides the WordPress site title inside the theme.', 'lerm' ),
			'group'       => __( 'Branding', 'lerm' ),
			'default'     => '',
			'placeholder' => get_bloginfo( 'name', 'display' ),
		),
		array(
			'id'          => 'blogdesc',
			'type'        => 'text',
			'label'       => __( 'Site tagline override', 'lerm' ),
			'description' => __( 'Overrides the WordPress tagline inside the theme.', 'lerm' ),
			'group'       => __( 'Branding', 'lerm' ),
			'default'     => '',
			'placeholder' => get_bloginfo( 'description', 'display' ),
		),
		array(
			'id'          => 'header_bg_color',
			'type'        => 'color',
			'label'       => __( 'Header background', 'lerm' ),
			'description' => __( 'Background color for the site header, dropdown menus, and off-canvas panel.', 'lerm' ),
			'group'       => __( 'Navigation', 'lerm' ),
			'default'     => '#ffffff',
		),
		array(
			'id'          => 'navbar_align',
			'type'        => 'select',
			'label'       => __( 'Navigation alignment', 'lerm' ),
			'description' => __( 'Controls how the main navigation is aligned on desktop.', 'lerm' ),
			'group'       => __( 'Navigation', 'lerm' ),
			'default'     => 'justify-content-md-end',
			'choices'     => array(
				'justify-content-md-start'  => __( 'Left', 'lerm' ),
				'justify-content-md-center' => __( 'Center', 'lerm' ),
				'justify-content-md-end'    => __( 'Right', 'lerm' ),
			),
		),
		array(
			'id'          => 'navbar_search',
			'type'        => 'switcher',
			'label'       => __( 'Show search in navbar', 'lerm' ),
			'description' => __( 'Displays a search trigger in the desktop and mobile navigation.', 'lerm' ),
			'group'       => __( 'Navigation', 'lerm' ),
			'default'     => false,
		),
		array(
			'id'          => 'sticky_header',
			'type'        => 'switcher',
			'label'       => __( 'Sticky header', 'lerm' ),
			'description' => __( 'Keep the header fixed at the top while the visitor scrolls.', 'lerm' ),
			'group'       => __( 'Behaviour', 'lerm' ),
			'default'     => false,
		),
		array(
			'id'               => 'sticky_header_shrink',
			'type'             => 'switcher',
			'label'            => __( 'Shrink sticky header', 'lerm' ),
			'description'      => __( 'Reduce header height after the page starts scrolling.', 'lerm' ),
			'group'            => __( 'Behaviour', 'lerm' ),
			'default'          => false,
			'dependency_field' => 'sticky_header',
			'dependency_value' => '1',
		),
		array(
			'id'          => 'transparent_header',
			'type'        => 'switcher',
			'label'       => __( 'Transparent header on hero', 'lerm' ),
			'description' => __( 'Allows the header to become transparent when a hero or slider sits directly below it.', 'lerm' ),
			'group'       => __( 'Behaviour', 'lerm' ),
			'default'     => false,
		),
	),
);
