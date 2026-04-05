<?php // phpcs:disable WordPress.Files.FileName

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'title'       => __( 'Community / Ads', 'lerm' ),
	'description' => __( 'Basic homepage advertisement controls migrated from the old CSF advertisement tab.', 'lerm' ),
	'fields'      => array(
		array(
			'id'          => 'ad_switcher',
			'type'        => 'switcher',
			'label'       => __( 'Show advertisements', 'lerm' ),
			'description' => __( 'Reserved global switch for theme-owned ad slots.', 'lerm' ),
			'group'       => __( 'Homepage advertisement', 'lerm' ),
			'default'     => false,
		),
		array(
			'id'               => 'ad1',
			'type'             => 'wp_editor',
			'label'            => __( 'Homepage ad code', 'lerm' ),
			'description'      => __( 'Paste ad embed code for the homepage slot.', 'lerm' ),
			'group'            => __( 'Homepage advertisement', 'lerm' ),
			'default'          => '',
			'dependency_field' => 'ad_switcher',
			'dependency_value' => '1',
			'editor_args'      => array(
				'textarea_rows' => 6,
				'media_buttons' => false,
				'quicktags'     => false,
				'tinymce'       => false,
			),
		),
	),
);
