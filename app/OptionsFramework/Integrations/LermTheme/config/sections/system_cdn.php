<?php // phpcs:disable WordPress.Files.FileName

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'title'       => __( 'System / CDN', 'lerm' ),
	'description' => __( 'Static asset CDN rewriting settings migrated from the CSF CDN tab.', 'lerm' ),
	'fields'      => array(
		array(
			'id'          => 'enable_cdn',
			'type'        => 'switcher',
			'label'       => __( 'Enable CDN URL rewriting', 'lerm' ),
			'description' => __( 'Rewrites supported static asset URLs so they point at your CDN origin.', 'lerm' ),
			'group'       => __( 'CDN rewriting', 'lerm' ),
			'default'     => false,
		),
		array(
			'id'               => 'new_url',
			'type'             => 'url',
			'label'            => __( 'CDN URL', 'lerm' ),
			'description'      => __( 'CDN origin without a trailing slash. Example: https://cdn.example.com', 'lerm' ),
			'group'            => __( 'CDN rewriting', 'lerm' ),
			'default'          => get_bloginfo( 'url', 'display' ),
			'dependency_field' => 'enable_cdn',
			'dependency_value' => '1',
			'placeholder'      => get_bloginfo( 'url', 'display' ),
		),
		array(
			'id'               => 'off_new_url',
			'type'             => 'url',
			'label'            => __( 'Relative URL prefix', 'lerm' ),
			'description'      => __( 'Prefix used when rewriting relative URLs such as /wp-content/.', 'lerm' ),
			'group'            => __( 'CDN rewriting', 'lerm' ),
			'default'          => get_bloginfo( 'url', 'display' ),
			'dependency_field' => 'enable_cdn',
			'dependency_value' => '1',
			'placeholder'      => get_bloginfo( 'url', 'display' ),
		),
		array(
			'id'               => 'include_dir',
			'type'             => 'text',
			'label'            => __( 'Included directories', 'lerm' ),
			'description'      => __( 'Comma-separated directory prefixes that should be rewritten.', 'lerm' ),
			'group'            => __( 'CDN rewriting', 'lerm' ),
			'default'          => 'wp-content, wp-includes',
			'dependency_field' => 'enable_cdn',
			'dependency_value' => '1',
		),
		array(
			'id'               => 'exclude_if_substring',
			'type'             => 'text',
			'label'            => __( 'Excluded substrings', 'lerm' ),
			'description'      => __( 'Skip rewriting whenever a URL contains one of these substrings.', 'lerm' ),
			'group'            => __( 'CDN rewriting', 'lerm' ),
			'default'          => '.php',
			'dependency_field' => 'enable_cdn',
			'dependency_value' => '1',
		),
	),
);
