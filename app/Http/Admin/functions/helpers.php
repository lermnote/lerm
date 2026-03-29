<?php if ( ! defined( 'ABSPATH' ) ) {
	die; } // Cannot access directly.
/**
 *
 * Array search key & value
 *
 * @since 1.0.0
 * @version 1.0.0
 */
if ( ! function_exists( 'csf_array_search' ) ) {
	function csf_array_search( $data, $key, $value ) {

		$results = array();

		if ( is_array( $data ) ) {
			if ( isset( $data[ $key ] ) && $data[ $key ] === $value ) {
				$results[] = $data;
			}

			foreach ( $data as $sub_array ) {
				$results = array_merge( $results, csf_array_search( $sub_array, $key, $value ) );
			}
		}

		return $results;
	}
}
/**
 *
 * Getting POST Var
 *
 * @since 1.0.0
 * @version 1.0.0
 */
if ( ! function_exists( 'csf_get_var' ) ) {
	function csf_get_var( $variable, $default_value = '' ) {
		if ( isset( $_POST[ $variable ] ) ) {
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'csf_nonce' ) ) {
				return $default_value;
			}
			return $_POST[ $variable ];
		}

		if ( isset( $_GET[ $variable ] ) ) {
				return $_GET[ $variable ];
		}

			return $default_value;
	}
}

/**
*
* Getting POST Vars
*
* @since 1.0.0
* @version 1.0.0
*/
if ( ! function_exists( 'csf_get_vars' ) ) {
	function csf_get_vars( $variable, $depth, $default_value = '' ) {
		if ( isset( $_POST[ $variable ][ $depth ] ) ) {
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'csf_nonce' ) ) {
				return $default_value;
			}
			return $_POST[ $variable ][ $depth ];
		}

		if ( isset( $_GET[ $variable ][ $depth ] ) ) {
				return $_GET[ $variable ][ $depth ];
		}

			return $default_value;
	}
}
/**
 *
 * Between Microtime
 *
 * @since 1.0.0
 * @version 1.0.0
 */
if ( ! function_exists( 'csf_timeout' ) ) {
	function csf_timeout( $timenow, $starttime, $timeout = 30 ) {
		return ( ( $timenow - $starttime ) < $timeout ) ? true : false;
	}
}

/**
 *
 * Check for wp editor api
 *
 * @since 1.0.0
 * @version 1.0.0
 */
if ( ! function_exists( 'csf_wp_editor_api' ) ) {
	function csf_wp_editor_api() {
		global $wp_version;
		return version_compare( $wp_version, '4.8', '>=' );
	}
}
