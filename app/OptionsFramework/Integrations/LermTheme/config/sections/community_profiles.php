<?php // phpcs:disable WordPress.Files.FileName

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'title'       => __( 'Community / Profiles', 'lerm' ),
	'description' => __( 'Author and site social profile links that were still only editable through the old CSF social profiles tab.', 'lerm' ),
	'fields'      => array(
		array( 'id' => 'social_weibo', 'type' => 'url', 'label' => 'Weibo', 'group' => __( 'Chinese platforms', 'lerm' ), 'default' => '', 'placeholder' => 'https://weibo.com/yourname' ),
		array( 'id' => 'social_wechat', 'type' => 'text', 'label' => 'WeChat', 'group' => __( 'Chinese platforms', 'lerm' ), 'default' => '', 'placeholder' => 'your-public-account-id' ),
		array( 'id' => 'social_qq', 'type' => 'text', 'label' => 'QQ', 'group' => __( 'Chinese platforms', 'lerm' ), 'default' => '', 'placeholder' => 'https://wpa.qq.com/msgrd?v=3&uin=123456' ),
		array( 'id' => 'social_bilibili', 'type' => 'url', 'label' => 'Bilibili', 'group' => __( 'Chinese platforms', 'lerm' ), 'default' => '', 'placeholder' => 'https://space.bilibili.com/youruid' ),
		array( 'id' => 'social_zhihu', 'type' => 'url', 'label' => 'Zhihu', 'group' => __( 'Chinese platforms', 'lerm' ), 'default' => '', 'placeholder' => 'https://www.zhihu.com/people/yourname' ),
		array( 'id' => 'social_douban', 'type' => 'url', 'label' => 'Douban', 'group' => __( 'Chinese platforms', 'lerm' ), 'default' => '', 'placeholder' => 'https://www.douban.com/people/yourname' ),
		array( 'id' => 'social_github', 'type' => 'url', 'label' => 'GitHub', 'group' => __( 'International platforms', 'lerm' ), 'default' => '', 'placeholder' => 'https://github.com/yourname' ),
		array( 'id' => 'social_twitter', 'type' => 'url', 'label' => 'X / Twitter', 'group' => __( 'International platforms', 'lerm' ), 'default' => '', 'placeholder' => 'https://x.com/yourhandle' ),
		array( 'id' => 'social_linkedin', 'type' => 'url', 'label' => 'LinkedIn', 'group' => __( 'International platforms', 'lerm' ), 'default' => '', 'placeholder' => 'https://linkedin.com/in/yourname' ),
		array( 'id' => 'social_instagram', 'type' => 'url', 'label' => 'Instagram', 'group' => __( 'International platforms', 'lerm' ), 'default' => '', 'placeholder' => 'https://instagram.com/yourname' ),
		array( 'id' => 'social_youtube', 'type' => 'url', 'label' => 'YouTube', 'group' => __( 'International platforms', 'lerm' ), 'default' => '', 'placeholder' => 'https://youtube.com/@yourchannel' ),
		array( 'id' => 'social_email', 'type' => 'text', 'label' => __( 'Email', 'lerm' ), 'group' => __( 'International platforms', 'lerm' ), 'default' => '', 'placeholder' => 'hello@example.com' ),
		array( 'id' => 'social_rss', 'type' => 'switcher', 'label' => __( 'Show RSS feed link', 'lerm' ), 'group' => __( 'Display settings', 'lerm' ), 'default' => true ),
		array(
			'id'      => 'social_profiles_position',
			'type'    => 'checkbox_list',
			'label'   => __( 'Show social links in', 'lerm' ),
			'group'   => __( 'Display settings', 'lerm' ),
			'default' => array( 'footer', 'author_bio' ),
			'choices' => array(
				'header'     => __( 'Header', 'lerm' ),
				'footer'     => __( 'Footer', 'lerm' ),
				'author_bio' => __( 'Author bio box', 'lerm' ),
			),
		),
		array( 'id' => 'social_open_new_tab', 'type' => 'switcher', 'label' => __( 'Open links in a new tab', 'lerm' ), 'group' => __( 'Display settings', 'lerm' ), 'default' => true ),
	),
);
