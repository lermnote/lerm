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
			'description' => __( 'Synced with the WordPress Site Identity logo.', 'lerm' ),
			'group_heading'    => __( 'Branding', 'lerm' ),
			'default'     => \Lerm\Theme\AdminConfig\SiteIdentitySync::media_value( absint( get_theme_mod( 'custom_logo', 0 ) ) ),
			'button_text' => __( 'Choose desktop logo', 'lerm' ),
		),
		array(
			'id'          => 'mobile_logo',
			'type'        => 'media',
			'label'       => __( 'Mobile logo', 'lerm' ),
			'description' => __( 'Shown on small screens. Falls back to the desktop logo if empty.', 'lerm' ),
			'group_heading'    => __( 'Branding', 'lerm' ),
			'default'     => array(),
			'button_text' => __( 'Choose mobile logo', 'lerm' ),
		),
		array(
			'id'          => 'blogname',
			'type'        => 'text',
			'label'       => __( 'Site title', 'lerm' ),
			'description' => __( 'Synced with Settings > General and the WordPress Customizer.', 'lerm' ),
			'group_heading'    => __( 'Branding', 'lerm' ),
			'default'     => get_bloginfo( 'name', 'display' ),
		),
		array(
			'id'          => 'tagline',
			'type'        => 'text',
			'label'       => __( 'Site tagline', 'lerm' ),
			'description' => __( 'Synced with Settings > General and the WordPress Customizer.', 'lerm' ),
			'group_heading'    => __( 'Branding', 'lerm' ),
			'default'     => get_bloginfo( 'description', 'display' ),
		),
		array(
			'id'            => 'display_header_text',
			'type'          => 'switcher',
			'label'         => __( 'Display site title and tagline', 'lerm' ),
			'description'   => __( 'Synced with the Site Identity display checkbox.', 'lerm' ),
			'group_heading' => __( 'Branding', 'lerm' ),
			'default'       => \Lerm\Theme\AdminConfig\SiteIdentitySync::display_header_text(),
		),
		array(
			'id'            => 'site_icon',
			'type'          => 'media',
			'label'         => __( 'Site icon', 'lerm' ),
			'description'   => __( 'Synced with the browser tab and app icon from WordPress Site Identity.', 'lerm' ),
			'group_heading' => __( 'Branding', 'lerm' ),
			'default'       => \Lerm\Theme\AdminConfig\SiteIdentitySync::media_value( absint( get_option( 'site_icon', 0 ) ) ),
			'button_text'   => __( 'Choose site icon', 'lerm' ),
		),
		array(
			'id'          => 'header_bg_color',
			'type'        => 'color',
			'label'       => __( 'Header background', 'lerm' ),
			'description' => __( 'Background color for the site header, dropdown menus, and off-canvas panel.', 'lerm' ),
			'group_heading'    => __( 'Navigation', 'lerm' ),
			'default'     => '#ffffff',
		),
		array(
			'id'          => 'navbar_align',
			'type'        => 'select',
			'label'       => __( 'Navigation alignment', 'lerm' ),
			'description' => __( 'Controls how the main navigation is aligned on desktop.', 'lerm' ),
			'group_heading'    => __( 'Navigation', 'lerm' ),
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
			'group_heading'    => __( 'Navigation', 'lerm' ),
			'default'     => false,
		),
		array(
			'id'          => 'sticky_header',
			'type'        => 'switcher',
			'label'       => __( 'Sticky header', 'lerm' ),
			'description' => __( 'Keep the header fixed at the top while the visitor scrolls.', 'lerm' ),
			'group_heading'    => __( 'Behaviour', 'lerm' ),
			'default'     => false,
		),
		array(
			'id'               => 'sticky_header_shrink',
			'type'             => 'switcher',
			'label'            => __( 'Shrink sticky header', 'lerm' ),
			'description'      => __( 'Reduce header height after the page starts scrolling.', 'lerm' ),
			'group_heading'         => __( 'Behaviour', 'lerm' ),
			'default'          => false,
			'dependency_field' => 'sticky_header',
			'dependency_value' => '1',
		),
		array(
			'id'          => 'transparent_header',
			'type'        => 'switcher',
			'label'       => __( 'Transparent header on hero', 'lerm' ),
			'description' => __( 'Allows the header to become transparent when a hero or slider sits directly below it.', 'lerm' ),
			'group_heading'    => __( 'Behaviour', 'lerm' ),
			'default'     => false,
		),
	),
);
