<?php // phpcs:disable WordPress.Files.FileName

declare( strict_types=1 );

use Lerm\OptionsFramework\Integrations\LermTheme\OptionsPageDefinition;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'title'       => __( 'System', 'lerm' ),
	'description' => __( 'Runtime optimization, delivery mirrors, and custom head/footer code.', 'lerm' ),
	'fields'      => array(
		array(
			'id'          => 'super_admin',
			'type'        => 'switcher',
			'label'       => __( 'Backend asset acceleration', 'lerm' ),
			'description' => __( 'Load WordPress dashboard static assets from a public mirror for faster admin performance in China.', 'lerm' ),
			'group'       => __( 'Asset mirrors', 'lerm' ),
			'default'     => false,
		),
		array(
			'id'          => 'super_gravatar',
			'type'        => 'select',
			'label'       => __( 'Gravatar mirror', 'lerm' ),
			'description' => __( 'Replace the default avatar CDN with a faster mirror.', 'lerm' ),
			'group'       => __( 'Asset mirrors', 'lerm' ),
			'default'     => 'disable',
			'choices'     => array( OptionsPageDefinition::class, 'gravatar_choices' ),
		),
		array(
			'id'          => 'super_googleapis',
			'type'        => 'select',
			'label'       => __( 'Google Fonts mirror', 'lerm' ),
			'description' => __( 'Only needed when the theme is loading Google-hosted font assets.', 'lerm' ),
			'group'       => __( 'Asset mirrors', 'lerm' ),
			'default'     => 'disable',
			'choices'     => array( OptionsPageDefinition::class, 'google_mirror_choices' ),
		),
		array(
			'id'          => 'lazyload',
			'type'        => 'switcher',
			'label'       => __( 'Lazy-load images', 'lerm' ),
			'description' => __( 'Defer off-screen image loading until the visitor scrolls near them.', 'lerm' ),
			'group'       => __( 'Frontend delivery', 'lerm' ),
			'default'     => false,
		),
		array(
			'id'          => 'super_optimize',
			'type'        => 'checkbox_list',
			'label'       => __( 'WordPress head cleanup', 'lerm' ),
			'description' => __( 'Remove optional tags and features from wp_head and related outputs.', 'lerm' ),
			'group'       => __( 'Frontend delivery', 'lerm' ),
			'default'     => array(),
			'choices'     => array( OptionsPageDefinition::class, 'optimize_flags_choices' ),
		),
		array(
			'id'          => 'head_scripts',
			'type'        => 'code_editor',
			'label'       => __( 'Before </head>', 'lerm' ),
			'description' => __( 'Code injected into the head of every page: analytics, verification tags, custom fonts, and similar snippets.', 'lerm' ),
			'group'       => __( 'Custom injection', 'lerm' ),
			'default'     => '',
			'rows'        => 10,
		),
		array(
			'id'          => 'footer_scripts',
			'type'        => 'code_editor',
			'label'       => __( 'Before </body>', 'lerm' ),
			'description' => __( 'Code injected just before the closing body tag: chat widgets, deferred scripts, and similar snippets.', 'lerm' ),
			'group'       => __( 'Custom injection', 'lerm' ),
			'default'     => '',
			'rows'        => 10,
		),
	),
);
