<?php // phpcs:disable WordPress.Files.FileName

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'title'       => __( 'System / Mailing', 'lerm' ),
	'description' => __( 'SMTP and comment-notification settings previously left in the legacy CSF mailing tab.', 'lerm' ),
	'fields'      => array(
		array(
			'id'          => 'email_notice',
			'type'        => 'switcher',
			'label'       => __( 'Comment notification emails', 'lerm' ),
			'description' => __( 'Send emails when new comments are posted and route them through the SMTP settings below.', 'lerm' ),
			'group'       => __( 'Mail delivery', 'lerm' ),
			'default'     => false,
		),
		array(
			'id'               => 'smtp_options',
			'type'             => 'fieldset',
			'label'            => __( 'SMTP settings', 'lerm' ),
			'description'      => __( 'Sender identity and SMTP server credentials used for outgoing mail.', 'lerm' ),
			'group'            => __( 'Mail delivery', 'lerm' ),
			'default'          => array(
				'from_email' => '',
				'from_name'  => '',
				'smtp_host'  => '',
				'smtp_port'  => 0,
				'ssl_enable' => 'tls',
				'smtp_auth'  => '0',
				'username'   => '',
				'pswd'       => '',
			),
			'dependency_field' => 'email_notice',
			'dependency_value' => '1',
			'fields'           => array(
				array( 'id' => 'from_email', 'type' => 'text', 'label' => __( 'From address', 'lerm' ), 'default' => '', 'placeholder' => 'hello@example.com' ),
				array( 'id' => 'from_name', 'type' => 'text', 'label' => __( 'From name', 'lerm' ), 'default' => '', 'placeholder' => get_bloginfo( 'name' ) ),
				array( 'id' => 'smtp_host', 'type' => 'text', 'label' => __( 'SMTP host', 'lerm' ), 'default' => '', 'placeholder' => 'smtp.example.com' ),
				array( 'id' => 'smtp_port', 'type' => 'number', 'label' => __( 'SMTP port', 'lerm' ), 'default' => 587, 'min' => 1, 'max' => 65535, 'step' => 1 ),
				array(
					'id'      => 'ssl_enable',
					'type'    => 'button_set',
					'label'   => __( 'Encryption', 'lerm' ),
					'default' => 'tls',
					'choices' => array(
						'none' => __( 'None', 'lerm' ),
						'tls'  => 'TLS',
						'ssl'  => 'SSL',
					),
				),
				array(
					'id'      => 'smtp_auth',
					'type'    => 'radio',
					'label'   => __( 'SMTP authentication', 'lerm' ),
					'default' => '0',
					'choices' => array(
						'1' => __( 'Use username and password', 'lerm' ),
						'0' => __( 'No authentication', 'lerm' ),
					),
				),
				array( 'id' => 'username', 'type' => 'text', 'label' => __( 'SMTP username', 'lerm' ), 'default' => '' ),
				array( 'id' => 'pswd', 'type' => 'text', 'input_type' => 'password', 'label' => __( 'SMTP password', 'lerm' ), 'default' => '' ),
			),
		),
	),
);
