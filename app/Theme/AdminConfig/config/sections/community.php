<?php // phpcs:disable WordPress.Files.FileName

declare( strict_types=1 );

use Lerm\Theme\AdminConfig\ThemeOptionsDefinition;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'title'           => __( 'Community', 'lerm' ),
	'description'     => __( 'Share-platform controls, WeChat QR data, and donation media settings for community features.', 'lerm' ),
	'use_subsections' => true,
	'groups'          => array(
		array(
			'id'     => 'social',
			'label'  => __( 'Social', 'lerm' ),
			'fields' => array(
				array(
					'id'          => 'qrcode_image',
					'type'        => 'fieldset',
					'label'       => __( 'WeChat QR code', 'lerm' ),
					'description' => __( 'Optional image URL shown when visitors hover over the WeChat social link.', 'lerm' ),
					'group_heading' => __( 'Social assets', 'lerm' ),
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
					'group_heading' => __( 'Social assets', 'lerm' ),
					'default'     => array(),
				),
				array(
					'id'          => 'social_share',
					'type'        => 'checkbox_list',
					'label'       => __( 'Share buttons', 'lerm' ),
					'description' => __( 'Select which social platforms should appear in share UI on single posts.', 'lerm' ),
					'group_heading' => __( 'Share buttons', 'lerm' ),
					'default'     => array(),
					'choices'     => array( ThemeOptionsDefinition::class, 'social_share_choices' ),
				),
			),
		),
		array(
			'id'     => 'profile',
			'label'  => __( 'Profiles', 'lerm' ),
			'fields' => array(
				array(
					'id'          => 'social_weibo',
					'type'        => 'url',
					'label'       => 'Weibo',
					'group_heading' => __( 'Chinese platforms', 'lerm' ),
					'default'     => '',
					'placeholder' => 'https://weibo.com/yourname',
				),
				array(
					'id'          => 'social_wechat',
					'type'        => 'text',
					'label'       => 'WeChat',
					'group_heading' => __( 'Chinese platforms', 'lerm' ),
					'default'     => '',
					'placeholder' => 'your-public-account-id',
				),
				array(
					'id'          => 'social_qq',
					'type'        => 'text',
					'label'       => 'QQ',
					'group_heading' => __( 'Chinese platforms', 'lerm' ),
					'default'     => '',
					'placeholder' => 'https://wpa.qq.com/msgrd?v=3&uin=123456',
				),
				array(
					'id'          => 'social_bilibili',
					'type'        => 'url',
					'label'       => 'Bilibili',
					'group_heading' => __( 'Chinese platforms', 'lerm' ),
					'default'     => '',
					'placeholder' => 'https://space.bilibili.com/youruid',
				),
				array(
					'id'          => 'social_zhihu',
					'type'        => 'url',
					'label'       => 'Zhihu',
					'group_heading' => __( 'Chinese platforms', 'lerm' ),
					'default'     => '',
					'placeholder' => 'https://www.zhihu.com/people/yourname',
				),
				array(
					'id'          => 'social_douban',
					'type'        => 'url',
					'label'       => 'Douban',
					'group_heading' => __( 'Chinese platforms', 'lerm' ),
					'default'     => '',
					'placeholder' => 'https://www.douban.com/people/yourname',
				),
				array(
					'id'          => 'social_github',
					'type'        => 'url',
					'label'       => 'GitHub',
					'group_heading' => __( 'International platforms', 'lerm' ),
					'default'     => '',
					'placeholder' => 'https://github.com/yourname',
				),
				array(
					'id'          => 'social_twitter',
					'type'        => 'url',
					'label'       => 'X / Twitter',
					'group_heading' => __( 'International platforms', 'lerm' ),
					'default'     => '',
					'placeholder' => 'https://x.com/yourhandle',
				),
				array(
					'id'          => 'social_linkedin',
					'type'        => 'url',
					'label'       => 'LinkedIn',
					'group_heading' => __( 'International platforms', 'lerm' ),
					'default'     => '',
					'placeholder' => 'https://linkedin.com/in/yourname',
				),
				array(
					'id'          => 'social_instagram',
					'type'        => 'url',
					'label'       => 'Instagram',
					'group_heading' => __( 'International platforms', 'lerm' ),
					'default'     => '',
					'placeholder' => 'https://instagram.com/yourname',
				),
				array(
					'id'          => 'social_youtube',
					'type'        => 'url',
					'label'       => 'YouTube',
					'group_heading' => __( 'International platforms', 'lerm' ),
					'default'     => '',
					'placeholder' => 'https://youtube.com/@yourchannel',
				),
				array(
					'id'          => 'social_email',
					'type'        => 'text',
					'label'       => __( 'Email', 'lerm' ),
					'group_heading' => __( 'International platforms', 'lerm' ),
					'default'     => '',
					'placeholder' => 'hello@example.com',
				),
				array(
					'id'      => 'social_rss',
					'type'    => 'switcher',
					'label'   => __( 'Show RSS feed link', 'lerm' ),
					'group_heading' => __( 'Display settings', 'lerm' ),
					'default' => true,
				),
				array(
					'id'      => 'social_profiles_position',
					'type'    => 'checkbox_list',
					'label'   => __( 'Show social links in', 'lerm' ),
					'group_heading' => __( 'Display settings', 'lerm' ),
					'default' => array( 'footer', 'author_bio' ),
					'choices' => array(
						'header'     => __( 'Header', 'lerm' ),
						'footer'     => __( 'Footer', 'lerm' ),
						'author_bio' => __( 'Author bio box', 'lerm' ),
					),
				),
				array(
					'id'      => 'social_open_new_tab',
					'type'    => 'switcher',
					'label'   => __( 'Open links in a new tab', 'lerm' ),
					'group_heading' => __( 'Display settings', 'lerm' ),
					'default' => true,
				),
			),
		),
		array(
			'id'     => 'ad',
			'label'  => __( 'Advertisement', 'lerm' ),
			'fields' => array(
				array(
					'id'          => 'ad_switcher',
					'type'        => 'switcher',
					'label'       => __( 'Show advertisements', 'lerm' ),
					'description' => __( 'Reserved global switch for theme-owned ad slots.', 'lerm' ),
					'group_heading' => __( 'Homepage advertisement', 'lerm' ),
					'default'     => false,
				),
				array(
					'id'               => 'ad1',
					'type'             => 'wp_editor',
					'label'            => __( 'Homepage ad code', 'lerm' ),
					'description'      => __( 'Paste ad embed code for the homepage slot.', 'lerm' ),
					'group_heading' => __( 'Homepage advertisement', 'lerm' ),
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
		),
	),
);
