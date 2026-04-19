<?php // phpcs:disable WordPress.Files.FileName

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'title'       => __( 'Tools / Backup', 'lerm' ),
	'description' => __( 'Export and import the full theme options snapshot from the current admin config runtime.', 'lerm' ),
	'fields'      => array(
		array(
			'id'          => 'backup_tools',
			'type'        => 'backup_tools',
			'label'       => __( 'Backup tools', 'lerm' ),
			'description' => __( 'Generate a JSON snapshot of the current options or paste one back in to restore it.', 'lerm' ),
			'group_heading' => __( 'Backup', 'lerm' ),
			'default'     => '',
			'save'        => false,
		),
	),
);
