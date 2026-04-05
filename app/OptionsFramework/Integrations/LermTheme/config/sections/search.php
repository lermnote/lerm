<?php // phpcs:disable WordPress.Files.FileName

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'title'       => __( 'Search', 'lerm' ),
	'description' => __( 'Instant search dropdown behaviour and search box copy.', 'lerm' ),
	'fields'      => array(
		array(
			'id'          => 'search_results_per_page',
			'type'        => 'number',
			'label'       => __( 'Instant search results count', 'lerm' ),
			'description' => __( 'Maximum number of items shown in the instant search dropdown.', 'lerm' ),
			'group'       => __( 'Instant search', 'lerm' ),
			'default'     => 5,
			'min'         => 1,
			'max'         => 20,
			'step'        => 1,
		),
		array(
			'id'          => 'search_placeholder',
			'type'        => 'text',
			'label'       => __( 'Search box placeholder', 'lerm' ),
			'description' => __( 'Placeholder shown in the search form and instant search field.', 'lerm' ),
			'group'       => __( 'Instant search', 'lerm' ),
			'default'     => '',
			'placeholder' => __( 'Search...', 'lerm' ),
		),
	),
);
