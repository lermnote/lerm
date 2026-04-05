<?php // phpcs:disable WordPress.Files.FileName

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'menu' => array(
		'parent_slug' => 'themes.php',
		'page_title'  => __( 'Lerm Settings', 'lerm' ),
		'menu_title'  => __( 'Lerm Settings', 'lerm' ),
		'capability'  => 'manage_options',
	),
	'view' => array(
		'eyebrow'      => __( 'Native admin', 'lerm' ),
		'title'        => __( 'Lerm Settings', 'lerm' ),
		'description'  => __( 'The first production page running on the new Lerm Options Framework MVP.', 'lerm' ),
		'legacy_panel' => array(
			'title'        => __( 'Legacy panel', 'lerm' ),
			'description'  => __( 'Any section that has not been migrated yet still lives in the old Codestar screen.', 'lerm' ),
			'button_label' => __( 'Open legacy panel', 'lerm' ),
			'url'          => admin_url( 'admin.php?page=lerm_options' ),
		),
	),
);
