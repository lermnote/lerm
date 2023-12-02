<?php
/**
 * Autoloader PHP files use this function.
 *
 * @package LERM
 */
namespace Lerm\Inc;

function themeloader( $file = '' ) {
	$file_path      = false;
	$namespace_root = 'Lerm\\';
	$file           = trim( $file, '\\' );

	if ( empty( $file ) || strpos( $file, '\\' ) === false || strpos( $file, $namespace_root ) !== 0 ) {
		// Not our namespace, bail out.
		return;
	}

	// Remove our root namespace.
	$file = str_replace( $namespace_root, '', $file );

	$path = explode(
		'\\',
		str_replace( '_', '-', strtolower( $file ) )
	);

	/**
	 * Time to determine which type of resource path it is,
	 * so that we can deduce the correct file path for it.
	 */
	if ( empty( $path[0] ) || empty( $path[1] ) ) {
		return;
	}

	$directory = '';
	$file_name = '';

	if ( 'inc' === $path[0] ) {

		switch ( $path[1] ) {
			case 'traits':
				$directory = 'traits';
				$file_name = sprintf( 'trait-%s', trim( strtolower( $path[2] ) ) );
				break;

			case 'widgets':
			case 'blocks': // phpcs:ignore PSR2.ControlStructures.SwitchDeclaration.TerminatingComment
				/**
				 * If there is class name provided for specific directory then load that.
				 * otherwise find in inc/ directory.
				 */
				if ( ! empty( $path[2] ) ) {
					$directory = sprintf( 'classes/%s', $path[1] );
					$file_name = sprintf( 'class-%s', trim( strtolower( $path[2] ) ) );
					break;
				}
			default:
				$directory = 'classes';
				$file_name = sprintf( 'class-%s', trim( strtolower( $path[1] ) ) );
				break;
		}

		$file_path = sprintf( '%s/inc/%s/%s.php', untrailingslashit( LERM_DIR ), $directory, $file_name );

	}

	/**
	 * If $is_valid_file has 0 means valid path or 2 means the file path contains a Windows drive path.
	 */
	$is_valid_file = validate_file( $file_path );

	if ( ! empty( $file_path ) && file_exists( $file_path ) && ( 0 === $is_valid_file || 2 === $is_valid_file ) ) {
		// We already making sure that file is exists and valid.
		require_once( $file_path ); // phpcs:ignore
	}
}
spl_autoload_register( '\Lerm\Inc\themeloader' );



require_once LERM_DIR . 'inc/options/codestar-framework.php';
// loader function files
require_once LERM_DIR . 'inc/functions/functions-opengraph.php';

require_once LERM_DIR . 'inc/functions/function-login.php';
require_once LERM_DIR . 'inc/functions/functions-icon.php';
require_once LERM_DIR . 'inc/functions/functions-layout.php';

/**
 * Custom template tags for this theme.
 */
require LERM_DIR . 'inc/template-tags.php';
require LERM_DIR . 'inc/customizer.php';
