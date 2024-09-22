<?php
/**
 * Get the client's IP address.
 */
function lerm_client_ip() {
	$ip_keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
	foreach ( $ip_keys as $key ) {
		if ( ! empty( $_SERVER[ $key ] ) && filter_var( $_SERVER[ $key ], FILTER_VALIDATE_IP ) ) {
			return $_SERVER[ $key ];
		}
	}
	return '';
}

function float_form_input( $args ) {
	$defaults = array(
		'container_class' => 'form-floating mb-3',
		'class'           => 'form-control',
		'type'            => 'text',
		'name'            => 'username',
		'id'              => 'username',
		'placeholder'     => 'name@example.com',
		'label_text'      => __( 'Username' ),
		'required'        => 'required',
		'input_attrs'     => '',
	);

	$args = wp_parse_args( $args, apply_filters( 'lerm_form_args', $defaults ) );

	$additional_attrs = '';
	if ( ! empty( $args['input_attrs'] ) && is_array( $args['input_attrs'] ) ) {
		foreach ( $args['input_attrs'] as $attr_name => $attr_value ) {
			$additional_attrs .= sprintf( ' %s="%s"', esc_attr( $attr_name ), esc_attr( $attr_value ) );
		}
	}
	ob_start();
	echo sprintf(
		'<div class="%1$s">
		<input type="%2$s" name="%3$s" id="%4$s"  class="%5$s" placeholder="%6$s" %7$s %8$s>
		<label for="%5$s">%9$s</label>
	</div>',
		esc_attr( $args['container_class'] ),
		esc_attr( $args['type'] ),
		esc_attr( $args['name'] ),
		esc_attr( $args['id'] ),
		esc_attr( $args['class'] ),
		esc_attr( $args['placeholder'] ),
		esc_attr( $args['required'] ),
		$additional_attrs,
		esc_html( $args['label_text'] )
	);
	$input = ob_get_clean();
	return $input;
}
