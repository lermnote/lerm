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
