<?php // phpcs:disable WordPress.Files.FileName

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'title'       => __( 'Content / Pages', 'lerm' ),
	'description' => __( 'Static-page content rules that used to live in the CSF Page tab.', 'lerm' ),
	'fields'      => array(
		array(
			'id'          => 'search_filter',
			'type'        => 'switcher',
			'label'       => __( 'Exclude pages from search results', 'lerm' ),
			'description' => __( 'When enabled, WordPress search only returns posts instead of mixing in static pages.', 'lerm' ),
			'group'       => __( 'Search behaviour', 'lerm' ),
			'default'     => true,
		),
	),
);
