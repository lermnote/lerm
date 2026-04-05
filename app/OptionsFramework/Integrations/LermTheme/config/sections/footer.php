<?php // phpcs:disable WordPress.Files.FileName

declare( strict_types=1 );

use Lerm\OptionsFramework\Integrations\LermTheme\OptionsPageDefinition;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'title'       => __( 'Footer', 'lerm' ),
	'description' => __( 'Footer content and menu settings.', 'lerm' ),
	'fields'      => array(
		array(
			'id'          => 'footer_menus',
			'type'        => 'select',
			'label'       => __( 'Footer menu', 'lerm' ),
			'description' => __( 'Navigation menu displayed in the footer area.', 'lerm' ),
			'group'       => __( 'Footer content', 'lerm' ),
			'default'     => 0,
			'choices'     => array( OptionsPageDefinition::class, 'menu_choices' ),
			'cast'        => 'int',
		),
		array(
			'id'          => 'icp_num',
			'type'        => 'text',
			'label'       => __( 'ICP registration number', 'lerm' ),
			'description' => __( 'Chinese ICP filing number shown in the footer.', 'lerm' ),
			'group'       => __( 'Footer content', 'lerm' ),
			'default'     => '',
			'placeholder' => (string) get_option( 'zh_cn_l10n_icp_num' ),
		),
		array(
			'id'          => 'copyright',
			'type'        => 'wp_editor',
			'label'       => __( 'Footer custom text', 'lerm' ),
			'description' => __( 'Additional content rendered in the footer. Basic HTML is allowed.', 'lerm' ),
			'group'       => __( 'Footer content', 'lerm' ),
			'default'     => '',
			'editor_args' => array(
				'teeny'         => true,
				'textarea_rows' => 6,
				'media_buttons' => false,
			),
		),
	),
);
