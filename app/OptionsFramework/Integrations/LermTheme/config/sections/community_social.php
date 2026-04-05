<?php // phpcs:disable WordPress.Files.FileName

declare( strict_types=1 );

use Lerm\OptionsFramework\Integrations\LermTheme\OptionsPageDefinition;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'title'       => __( 'Community / Social', 'lerm' ),
	'description' => __( 'Share-platform controls, WeChat QR data, and donation media moved out of the legacy CSF social tab.', 'lerm' ),
	'fields'      => array(
		array(
			'id'          => 'qrcode_image',
			'type'        => 'fieldset',
			'label'       => __( 'WeChat QR code', 'lerm' ),
			'description' => __( 'Optional image URL shown when visitors hover over the WeChat social link.', 'lerm' ),
			'group'       => __( 'Social assets', 'lerm' ),
			'default'     => array(
				'background-image' => '',
			),
			'fields'      => array(
				array(
					'id'          => 'background-image',
					'type'        => 'url',
					'label'       => __( 'Image URL', 'lerm' ),
					'default'     => '',
					'placeholder' => 'https://example.com/wechat-qr.png',
				),
			),
		),
		array(
			'id'          => 'donate_qrcode',
			'type'        => 'media',
			'label'       => __( 'Donation QR code', 'lerm' ),
			'description' => __( 'Payment QR code displayed by donation-related UI blocks.', 'lerm' ),
			'group'       => __( 'Social assets', 'lerm' ),
			'default'     => array(),
		),
		array(
			'id'          => 'social_share',
			'type'        => 'checkbox_list',
			'label'       => __( 'Share buttons', 'lerm' ),
			'description' => __( 'Select which social platforms should appear in share UI on single posts.', 'lerm' ),
			'group'       => __( 'Share buttons', 'lerm' ),
			'default'     => array(),
			'choices'     => array( OptionsPageDefinition::class, 'social_share_choices' ),
		),
	),
);
