<?php if ( ! defined( 'ABSPATH' ) ) {
	die;
} // Cannot access pages directly.
// ===============================================================================================
// -----------------------------------------------------------------------------------------------
// METABOX OPTIONS
// -----------------------------------------------------------------------------------------------
// ===============================================================================================
// If using image radio buttons, define a directory path
global $lerm;
//global layout
$global_layout = $lerm['global_layout'];
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
				),
				array(
					'id'          => 'sidebar_select',
					'type'        => 'select',
					'title'       => 'Selec a sidebar',
					'placeholder' => 'Select a sidebar',
					'options'     => 'sidebars',
				),
			),
		)
	);

	$prefix_post_opts = '_lerm_post_options';

	//
	// Create a metabox
	//
	CSF::createMetabox(
		$prefix_post_opts,
		array(
			'title'        => 'Custom Post Options',
			'post_type'    => 'post',
			'show_restore' => true,
		)
	);

	//
	// Create a section
	//
	CSF::createSection(
		$prefix_post_opts,
		array(
			'fields' => array(
				array(
					'title'   => __( 'Original', 'lerm' ),
					'id'      => 'original_switcher',
					'type'    => 'switcher',
					'default' => true,
				),

				//
				// A text field
				//
				array(
					'id'    => 'opt-text',
					'type'  => 'text',
					'title' => 'Text',
				),

				array(
					'id'    => 'opt-textarea',
					'type'  => 'textarea',
					'title' => 'Textarea',
					'help'  => 'The help text of the field.',
				),

				array(
					'id'    => 'opt-upload',
					'type'  => 'upload',
					'title' => 'Upload',
				),

				array(
					'id'    => 'opt-switcher',
					'type'  => 'switcher',
					'title' => 'Switcher',
					'label' => 'The label text of the switcher.',
				),

				array(
					'id'    => 'opt-color',
					'type'  => 'color',
					'title' => 'Color',
				),

				array(
					'id'    => 'opt-checkbox',
					'type'  => 'checkbox',
					'title' => 'Checkbox',
					'label' => 'The label text of the checkbox.',
				),

				array(
					'id'      => 'opt-radio',
					'type'    => 'radio',
					'title'   => 'Radio',
					'options' => array(
						'yes' => 'Yes, Please.',
						'no'  => 'No, Thank you.',
					),
					'default' => 'yes',
				),

				array(
					'id'          => 'opt-select',
					'type'        => 'select',
					'title'       => 'Select',
					'placeholder' => 'Select an option',
					'options'     => array(
						'opt-1' => 'Option 1',
						'opt-2' => 'Option 2',
						'opt-3' => 'Option 3',
					),
				),

			),
		)
	);
}
