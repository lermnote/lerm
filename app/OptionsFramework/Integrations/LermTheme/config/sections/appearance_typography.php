<?php // phpcs:disable WordPress.Files.FileName

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'title'       => __( 'Appearance / Typography', 'lerm' ),
	'description' => __( 'Base text and navigation typography previously configured in the CSF Typography tab.', 'lerm' ),
	'fields'      => array(
		array(
			'id'          => 'body_typography',
			'type'        => 'fieldset',
			'label'       => __( 'Body text', 'lerm' ),
			'description' => __( 'Base typography applied to body copy and general content.', 'lerm' ),
			'group'       => __( 'Typography', 'lerm' ),
			'default'     => array(
				'font-weight' => '400',
				'color'       => '#5d6777',
				'font-size'   => '.875',
				'unit'        => 'rem',
			),
			'fields'      => array(
				array( 'id' => 'font-family', 'type' => 'text', 'label' => __( 'Font family', 'lerm' ), 'default' => '' ),
				array( 'id' => 'font-weight', 'type' => 'text', 'label' => __( 'Font weight', 'lerm' ), 'default' => '400' ),
				array( 'id' => 'color', 'type' => 'color', 'label' => __( 'Text color', 'lerm' ), 'default' => '#5d6777' ),
				array( 'id' => 'font-size', 'type' => 'text', 'label' => __( 'Font size', 'lerm' ), 'default' => '.875' ),
				array( 'id' => 'line-height', 'type' => 'text', 'label' => __( 'Line height', 'lerm' ), 'default' => '' ),
				array(
					'id'      => 'unit',
					'type'    => 'select',
					'label'   => __( 'Size unit', 'lerm' ),
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
			'id'          => 'menu_typography',
			'type'        => 'fieldset',
			'label'       => __( 'Navigation font', 'lerm' ),
			'description' => __( 'Typography applied to the top navigation bar.', 'lerm' ),
			'group'       => __( 'Typography', 'lerm' ),
			'default'     => array(
				'font-weight' => '400',
				'color'       => '#5d6777',
				'font-size'   => '.875',
				'unit'        => 'rem',
				'line-height' => '1.5',
			),
			'fields'      => array(
				array( 'id' => 'font-family', 'type' => 'text', 'label' => __( 'Font family', 'lerm' ), 'default' => '' ),
				array( 'id' => 'font-weight', 'type' => 'text', 'label' => __( 'Font weight', 'lerm' ), 'default' => '400' ),
				array( 'id' => 'color', 'type' => 'color', 'label' => __( 'Text color', 'lerm' ), 'default' => '#5d6777' ),
				array( 'id' => 'font-size', 'type' => 'text', 'label' => __( 'Font size', 'lerm' ), 'default' => '.875' ),
				array( 'id' => 'line-height', 'type' => 'text', 'label' => __( 'Line height', 'lerm' ), 'default' => '1.5' ),
				array(
					'id'      => 'unit',
					'type'    => 'select',
					'label'   => __( 'Size unit', 'lerm' ),
					'default' => 'rem',
					'choices' => array(
						'rem' => 'rem',
						'em'  => 'em',
						'px'  => 'px',
					),
				),
			),
		),
	),
);
