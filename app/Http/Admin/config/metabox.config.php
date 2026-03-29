<?php if ( ! defined( 'ABSPATH' ) ) {
	die;
} // Cannot access pages directly.
// ===============================================================================================
// -----------------------------------------------------------------------------------------------
// METABOX OPTIONS
// -----------------------------------------------------------------------------------------------
// ===============================================================================================
$theme_options = (array) get_option( 'lerm_theme_options', array() );
$global_layout = (string) ( $theme_options['global_layout'] ?? 'layout-2c-r' );
$imagepath     = LERM_URI . 'assets/img/';
$layout        = array(
	'layout-1c'        => $imagepath . '1c.png',
	'layout-1c-narrow' => $imagepath . '1c-narrow.png',
	'layout-2c-l'      => $imagepath . '2c-l.png',
	'layout-2c-r'      => $imagepath . '2c-r.png',
);
// Control core classes for avoid errors
if ( class_exists( 'CSF' ) ) {

	// Set a unique slug-like ID
	$prefix_meta_opts = '_lerm_metabox_options';

	// Create a post option metabox
	CSF::createMetabox(
		$prefix_meta_opts,
		array(
			'title'     => __( 'Post Options', 'lerm' ),
			'post_type' => array( 'post', 'page' ),
			'context'   => 'side',
		)
	);

	// Create a section
	CSF::createSection(
		$prefix_meta_opts,
		array(
			'fields' => array(
				array(
					'title'   => __( 'Layout', 'lerm' ),
					'id'      => 'page_layout',
					'type'    => 'image_select',
					'options' => $layout,
					'default' => $global_layout,
				),
				array(
					'id'          => 'sidebar_select',
					'type'        => 'select',
					'title'       => __( 'Select a sidebar', 'lerm' ),
					'placeholder' => __( 'Select a sidebar', 'lerm' ),
					'options'     => 'sidebars',
				),
			),
		)
	);
	$prefix_post_opts = '_lerm_post_options';
}
