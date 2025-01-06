<?php if ( ! defined( 'ABSPATH' ) ) {
	die; } // Cannot access directly.
/**
 *
 * @package   Codestar Framework - WordPress Options Framework
 * @author    Codestar <info@codestarthemes.com>
 * @link      http://codestarframework.com
 * @copyright 2015-2022 Codestar
 *
 *
 * Plugin Name: Codestar Framework
 * Plugin URI: http://codestarframework.com/
 * Author: Codestar
 * Author URI: http://codestarthemes.com/
 * Version: 2.3.1
 * Description: A Simple and Lightweight WordPress Option Framework for Themes and Plugins
 * Text Domain: csf
 * Domain Path: /languages
 */
require_once plugin_dir_path( __FILE__ ) . 'classes/Setup.php';
require_once plugin_dir_path( __FILE__ ) . 'config/options.config.php';
require_once plugin_dir_path( __FILE__ ) . 'config/metabox.config.php';
require_once plugin_dir_path( __FILE__ ) . 'config/taxonomy.options.php';

/**
 * Retrieve theme options.
 *
 * @param string $id    Option ID.
 * @param string $tag   Optional. Tag ID. Default is an empty string.
 * @param mixed  $default Optional. Default value if the option is not found. Default is an empty string.
 * @return mixed The theme option value.
 */
function lerm_options( string $id, string $tag = '', $default = '' ) {
	// Fetch the theme options array from the database
	$options = (array) get_option( 'lerm_theme_options', array() );

	// Check if the main option ID exists in the options array
	if ( ! array_key_exists( $id, $options ) ) {
		return $default;
	}

	$option_value = $options[ $id ];

	// If the option value is an array and a tag is specified, return the tagged value or default
	if ( is_array( $option_value ) && '' !== $tag ) {
		return $options[ $id ][ $tag ] ?? $default;
	}

	// Return the option value (either array or single value)
	return $option_value;
}
