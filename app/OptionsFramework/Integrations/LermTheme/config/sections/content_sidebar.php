<?php // phpcs:disable WordPress.Files.FileName

declare( strict_types=1 );

use Lerm\OptionsFramework\Integrations\LermTheme\OptionsPageDefinition;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'title'       => __( 'Content / Sidebars', 'lerm' ),
	'description' => __( 'Custom sidebars, assignment targets, and widget-side tuning from the old CSF Sidebar tab.', 'lerm' ),
	'fields'      => array(
		array(
			'id'          => 'register_sidebars',
			'type'        => 'group',
			'label'       => __( 'Custom sidebars', 'lerm' ),
			'description' => __( 'Additional sidebars registered by the theme. Save once before assigning them elsewhere.', 'lerm' ),
			'group'       => __( 'Custom sidebars', 'lerm' ),
			'default'     => array(),
			'button_text' => __( 'Add sidebar', 'lerm' ),
			'fields'      => array(
				array(
					'id'          => 'sidebar_title',
					'type'        => 'text',
					'label'       => __( 'Sidebar name', 'lerm' ),
					'description' => __( 'Human-friendly name shown in Widgets and Customizer panels.', 'lerm' ),
					'default'     => '',
					'placeholder' => __( 'Example: Article sidebar', 'lerm' ),
				),
			),
		),
		array(
			'id'          => 'single_sidebar_select',
			'type'        => 'select',
			'label'       => __( 'Single post sidebar', 'lerm' ),
			'description' => __( 'Sidebar used on single post pages.', 'lerm' ),
			'group'       => __( 'Sidebar assignments', 'lerm' ),
			'default'     => 'home-sidebar',
			'choices'     => array( OptionsPageDefinition::class, 'sidebar_choices' ),
		),
		array(
			'id'          => 'blog_sidebar_select',
			'type'        => 'select',
			'label'       => __( 'Blog archive sidebar', 'lerm' ),
			'description' => __( 'Sidebar used on blog archives and home loops.', 'lerm' ),
			'group'       => __( 'Sidebar assignments', 'lerm' ),
			'default'     => 'home-sidebar',
			'choices'     => array( OptionsPageDefinition::class, 'sidebar_choices' ),
		),
		array(
			'id'          => 'front_page_sidebar',
			'type'        => 'select',
			'label'       => __( 'Front page sidebar', 'lerm' ),
			'description' => __( 'Sidebar used on the static front page template.', 'lerm' ),
			'group'       => __( 'Sidebar assignments', 'lerm' ),
			'default'     => 'home-sidebar',
			'choices'     => array( OptionsPageDefinition::class, 'sidebar_choices' ),
		),
		array(
			'id'          => 'page_sidebar',
			'type'        => 'select',
			'label'       => __( 'Static page sidebar', 'lerm' ),
			'description' => __( 'Sidebar used on standard WordPress pages.', 'lerm' ),
			'group'       => __( 'Sidebar assignments', 'lerm' ),
			'default'     => 'home-sidebar',
			'choices'     => array( OptionsPageDefinition::class, 'sidebar_choices' ),
		),
		array(
			'id'          => 'comment_excerpt_length',
			'type'        => 'number',
			'label'       => __( 'Latest comments excerpt length', 'lerm' ),
			'description' => __( 'Controls excerpt length inside the latest comments widget.', 'lerm' ),
			'group'       => __( 'Widget settings', 'lerm' ),
			'default'     => 95,
			'min'         => 0,
			'max'         => 300,
			'step'        => 5,
		),
	),
);
