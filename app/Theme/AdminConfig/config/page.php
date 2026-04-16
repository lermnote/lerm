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
		'description'  => __( 'Schema-driven theme configuration running on the extracted admin config runtime.', 'lerm' ),
	),
);
