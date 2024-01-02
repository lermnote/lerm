<?php if ( ! defined( 'ABSPATH' ) ) {
	die; } // Cannot access directly.
/**
 *
 * @package   Codestar Framework - WordPress Options Framework
 * @author    Codestar <info@codestarthemes.com>
 * @link      http://codestarframework.com
 * @copyright 2015-2020 Codestar
 *
 *
 * Plugin Name: Codestar Framework
 * Plugin URI: http://codestarframework.com/
 * Author: Codestar
 * Author URI: http://codestarthemes.com/
 * Version: 2.1.7.1
 * Description: A Simple and Lightweight WordPress Option Framework for Themes and Plugins
 * Text Domain: lerm
 * Domain Path: /languages
 *
 */
require_once plugin_dir_path( __FILE__ ) . 'classes/setup.class.php';
require_once plugin_dir_path( __FILE__ ) . 'config/options.config.php';
require_once plugin_dir_path( __FILE__ ) . 'config/metabox.config.php';
require_once plugin_dir_path( __FILE__ ) . 'config/taxonomy.options.php';

/**
 * Theme options functions.
 *
 * @param string $id option id.
 * @param string $tag tag id.
 * @param string $value default option.
 * @return string $options options of theme.
 */
function lerm_options( string $id, string $tag = '', $value = '' ) {
	$options = (array) get_option( 'lerm_theme_options', array() );

	if ( array_key_exists( $id, $options ) ) {
		if ( is_array( $options[ $id ] ) ) {
			if ( ! empty( $tag ) && array_key_exists( $tag, $options[ $id ] ) ) {
				$value = $options[ $id ][ $tag ];
			} else {
				$value = $options[ $id ];
			}
		} else {
			$value = $options[ $id ];
		}
	}
	return $value;
}
