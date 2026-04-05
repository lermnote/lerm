<?php // phpcs:disable WordPress.Files.FileName

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'title'       => __( 'Content / 404', 'lerm' ),
	'description' => __( 'Customises the not-found page title, message, CTA, and illustration.', 'lerm' ),
	'fields'      => array(
		array(
			'id'          => '404_title',
			'type'        => 'text',
			'label'       => __( 'Page title', 'lerm' ),
			'group'       => __( '404 page', 'lerm' ),
			'default'     => __( '404 Not Found', 'lerm' ),
		),
		array(
			'id'          => '404_message',
			'type'        => 'textarea',
			'label'       => __( 'Error message', 'lerm' ),
			'group'       => __( '404 page', 'lerm' ),
			'default'     => __( 'Sorry, the page you are looking for could not be found.', 'lerm' ),
			'rows'        => 3,
		),
		array(
			'id'          => '404_button_text',
			'type'        => 'text',
			'label'       => __( 'Button label', 'lerm' ),
			'group'       => __( '404 page', 'lerm' ),
			'default'     => __( 'Back to home', 'lerm' ),
		),
		array(
			'id'          => '404_button_url',
			'type'        => 'url',
			'label'       => __( 'Button URL', 'lerm' ),
			'description' => __( 'Leave empty to send visitors back to the homepage.', 'lerm' ),
			'group'       => __( '404 page', 'lerm' ),
			'default'     => '',
			'placeholder' => home_url( '/' ),
		),
		array(
			'id'          => '404_image',
			'type'        => 'media',
			'label'       => __( 'Custom illustration', 'lerm' ),
			'description' => __( 'Optional image used instead of the default 404 illustration.', 'lerm' ),
			'group'       => __( '404 page', 'lerm' ),
			'default'     => array(),
		),
		array(
			'id'          => '404_show_search',
			'type'        => 'switcher',
			'label'       => __( 'Show search box', 'lerm' ),
			'group'       => __( '404 page', 'lerm' ),
			'default'     => true,
		),
	),
);
