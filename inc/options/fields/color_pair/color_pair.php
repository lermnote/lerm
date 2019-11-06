<?php if ( ! defined( 'ABSPATH' ) ) {
	die;
} // Cannot access pages directly.
/**
 *
 * Field: color
 *
 * @since 1.0.0
 * @version 1.0.0
 */
if ( ! class_exists( 'CSF_Field_color_pair' ) ) {
	class CSF_Field_color_pair extends CSF_Fields {

		public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
			parent::__construct( $field, $value, $unique, $where, $parent );
		}

		public function render() {
			$args = wp_parse_args(
				$this->field,
				array(
					'color'            => true,
					'background_color' => true,
					'border_color'     => false,
				)
			);

					$default_values = array(
						'color'            => '',
						'background_color' => '',
						'border_color'     => '',
					);
					$value          = wp_parse_args( $this->value, $default_values );
					echo $this->field_before();
					foreach ( array( 'color', 'background_color', 'border_color' ) as $props ) {
						if ( ! empty( $args[ $props ] ) ) {
							$default_attr = ( ! empty( $this->field['default'][ $props ] ) ) ? ' data-default-color="' . $this->field['default'][ $props ] . '"' : '';
							echo '<div class="csf--left csf-field-color">';
							echo '<div class="csf--title">' . ucfirst( $props ) . '</div>';
							echo '<input type="text" name="' . $this->field_name( '[' . $props . ']' ) . '" value="' . $value[ $props ] . '" class="csf-color"' . $default_attr . $this->field_attributes() . '/>';
							echo '</div>';
						}
					}
					echo $this->field_after();
		}

		public function output() {
			$output    = '';
			$element   = ( is_array( $this->field['output'] ) ) ? join( ',', $this->field['output'] ) : $this->field['output'];
			$important = ( ! empty( $this->field['output_important'] ) ) ? '!important' : '';

			$color            = ( isset( $this->value['color'] ) && $this->value['color'] !== '' ) ? 'color:' . $this->value['color'] . $important . ';' : '';
			$background_color = ( isset( $this->value['background_color'] ) && $this->value['background_color'] !== '' ) ? 'background-color:' . $this->value['background_color'] . $important . ';' : '';
			$border_color     = ( isset( $this->value['border_color'] ) && $this->value['border_color'] !== '' ) ? 'border-color:' . $this->value['border_color'] . $important . ';' : '';
			if ( $color !== '' || $background_color !== '' ) {
				$output = $element . '{' . $color . $background_color . $border_color . '}';
			}
			$this->parent->output_css .= $output;

			return $output;
		}
	}
}
