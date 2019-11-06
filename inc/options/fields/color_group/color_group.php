<?php if (! defined('ABSPATH')) {
    die;
} // Cannot access pages directly.
/**
 *
 * Field: color_group
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if (! class_exists('CSF_Field_color_group')) {
    class CSF_Field_color_group extends CSF_Fields
    {
        public function __construct($field, $value = '', $unique = '', $where = '', $parent = '')
        {
            parent::__construct($field, $value, $unique, $where, $parent);
        }

        public function render()
        {
            $options = (! empty($this->field['options'])) ? $this->field['options'] : array();

            echo $this->field_before();

            if (! empty($options)) {
                foreach ($options as $key => $option) {
                    $color_value  = (! empty($this->value[$key])) ? $this->value[$key] : '';
                    $default_attr = (! empty($this->field['default'][$key])) ? ' data-default-color="'. $this->field['default'][$key] .'"' : '';

                    echo '<div class="csf--left csf-field-color">';
                    echo '<div class="csf--title">'. $option .'</div>';
                    echo '<input type="text" name="'. $this->field_name('['. $key .']') .'" value="'. $color_value .'" class="csf-color"'. $default_attr . $this->field_attributes() .'/>';
                    echo '</div>';
                }
            }

            echo '<div class="clear"></div>';

            echo $this->field_after();
        }
        public function output()
        {
            $output    = '';
            $elements  = (is_array($this->field['output'])) ? $this->field['output'] : array_filter((array) $this->field['output']);
            $important = (! empty($this->field['output_important'])) ? '!important' : '';
            $mode      = (! empty($this->field['output_mode'])) ? $this->field['output_mode'] : 'color';

            if (! empty($elements) && isset($this->value) && $this->value !== '') {
                foreach ($elements as $key_property => $element) {
                    if (is_numeric($key_property)) {
                        $output = implode(',', $elements) .'{'. $mode .':'. $this->value . $important .';}';
                        break;
                    } else {
                        $output .= $element .'{'. $key_property .':'. $this->value . $important .'}';
                    }
                }
            }

            $this->parent->output_css .= $output;

            return $output;
        }
    }
}
