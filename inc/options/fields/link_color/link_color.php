<?php if ( ! defined( 'ABSPATH' ) ) {
	die;
} // Cannot access pages directly.
/**
 *
 * Field: link_color
 *
 * @since 1.0.0
 * @version 1.0.0
 */
if ( ! class_exists( 'CSF_Field_link_color' ) ) {
	class CSF_Field_link_color extends CSF_Fields {

		public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
			parent::__construct( $field, $value, $unique, $where, $parent );
		}

		public function render() {
			$args = wp_parse_args(
				$this->field,
				array(
					'color'    => true,
					'bg_color' => false,
					'hover'    => true,
					'bg_hover' => false,
					'active'   => false,
					'visited'  => false,
					'focus'    => false,
				)
			);

			$default_values = array(
				'color'    => '',
				'bg_color' => '',
				'hover'    => '',
				'bg_hover' => '',
				'active'   => '',
				'visited'  => '',
				'focus'    => '',
			);

			$value = wp_parse_args( $this->value, $default_values );

			echo $this->field_before();

			foreach ( array( 'color', 'hover', 'bg_color', 'bg_hover', 'active', 'visited', 'focus' ) as $prop ) {
				if ( ! empty( $args[ $prop ] ) ) {
					$default_attr = ( ! empty( $this->field['default'][ $prop ] ) ) ? ' data-default-color="' . $this->field['default'][ $prop ] . '"' : '';

					echo '<div class="csf--left csf-field-color">';
					echo '<div class="csf--title">' . ucfirst( $prop ) . '</div>';
					echo '<input type="text" name="' . $this->field_name( '[' . $prop . ']' ) . '" value="' . $value[ $prop ] . '" class="csf-color"' . $default_attr . $this->field_attributes() . '/>';
					echo '</div>';
				}
			}

			echo $this->field_after();
		}

		public function output() {
			$output    = '';
			$element   = ( is_array( $this->field['output'] ) ) ? join( ',', $this->field['output'] ) : $this->field['output'];
			$important = ( ! empty( $this->field['output_important'] ) ) ? '!important' : '';

			if ( isset( $this->value['color'] ) && $this->value['color'] !== '' && isset( $this->value['bg_color'] ) && $this->value['bg_color'] !== '' ) {
				$output .= $element . '{color:' . $this->value['color'] . $important . ';background-color:' . $this->value['bg_color'] . $important . ';}';
			} else {
				$output .= $element . '{color:' . $this->value['color'] . $important . '}';
			}
			if ( isset( $this->value['hover'] ) && $this->value['hover'] !== '' && isset( $this->value['bg_hover'] ) && $this->value['bg_hover'] !== '' ) {
				$element = ( is_array( $this->field['output'] ) ) ? join( ':hover,', $this->field['output'] ) : $this->field['output'] . ':hover';
				$output .= $element . ':hover{color:' . $this->value['hover'] . $important . ';background-color:' . $this->value['bg_hover'] . $important . ';}';
			} else {
				$element = ( is_array( $this->field['output'] ) ) ? join( ':hover,', $this->field['output'] ) : $this->field['output'] . ':hover';
				$output .= $element . ':hover{color:' . $this->value['hover'] . $important . ';}';
			}
			if ( isset( $this->value['active'] ) && $this->value['active'] !== '' ) {
				$output .= $element . ':active{color:' . $this->value['active'] . $important . ';}';
			}
			if ( isset( $this->value['visited'] ) && $this->value['visited'] !== '' ) {
				$output .= $element . ':visited{color:' . $this->value['visited'] . $important . ';}';
			}
			if ( isset( $this->value['focus'] ) && $this->value['focus'] !== '' ) {
				$output .= $element . ':focus{color:' . $this->value['focus'] . $important . ';}';
			}

			$this->parent->output_css .= $output;

			return $output;
		}
	}
}
