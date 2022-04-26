<?php
/**
 * Comments walker
 *
 * @package Lerm https://www.hanost.com
 *
 * @since lerm 3.0
 */
namespace Lerm\Inc;

class Lazyload {

	public static $args = array(
		// 'gravatar_accel' => 'disable',
		// 'admin_accel'    => false,
		// 'google_replace' => 'disable',
		// 'super_optimize' => array(),
	);

	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_optimize_', wp_parse_args( $params, self::$args ) );
		// self::hooks();
	}

	// instance
	public static function instance( $params = array() ) {
		return new self( $params );
	}

	public static function hooks() {
		add_action( 'template_redirect', array( __NAMESPACE__ . '\Lazyload', 'lazyload' ) );
	}

	public static function lazyoad( $subject ) {
		$pattern = '/<img([^<>]*)\.(bmp|gif|jpeg|jpg|png)([^<>]*)>/i';
		$subject = preg_replace( $pattern, $replacement, $subject );
		return $subject;
	}






	// public function lazyload() {
	// ob_start( 'lazyload_content' );
	// }

	public function lazyload_content( $subject ) {
		$regexp = '/<img([^<>]*)\.(bmp|gif|jpeg|jpg|png)([^<>]*)>/i';

		return preg_replace_callback( $regexp, 'match', $subject );
	}

	public function match( $matches ) {
		$lazyimg = $matches[0];
		// if (stripos($lazyimg, 'src') === false ) {
		// return $lazyimg;
		// }

		if ( ( stripos( $lazyimg, 'skip_lazyload' ) !== false ) || ( stripos( $lazyimg, 'custom-logo' ) !== false ) || ( stripos( $lazyimg, 'slider' ) !== false ) || ( stripos( $lazyimg, 'avatar' ) !== false ) || ( stripos( $lazyimg, 'qrcode' ) !== false ) ) {
			return $lazyimg;
		}

		if ( stripos( $lazyimg, 'slider' ) !== false ) {
			return $lazyimg;
		}

		if ( stripos( $lazyimg, 'class=' ) === false ) {
			$lazyimg = preg_replace(
				'/<img(.*)>/i',
				'<img class="lazy"$1>',
				$lazyimg
			);
		} else {
			$lazyimg = preg_replace(
				"/<img(.*)class=['\"]([\w\-\s]*)['\"](.*)>/i",
				'<img$1class="$2 lazy"$3>',
				$lazyimg
			);
		}

		if ( stripos( $lazyimg, 'srcset=' ) ) {
			if ( ! stripos( $lazyimg, 'data-srcset=' ) ) {
				$regexp  = "/<img([^<>]*)srcset=['\"]([^<>'\"]*)['\"]([^<>]*)>/i";
				$replace = '<img$1srcset="data:image/gif;base64,R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="$2" data-srcset="$2"$3>';
				$lazyimg = preg_replace( $regexp, $replace, $lazyimg );
			}
		} else {
			$regexp  = "/<img([^<>]*)src=['\"]([^<>'\"]*)\.(bmp|gif|jpeg|jpg|png)([^<>'\"]*)['\"]([^<>]*)>/i";
			$replace = '<img$1src="data:image/gif;base64,R0lGODdhAQABAPAAAMPDwwAAACwAAAAAAQABAAACAkQBADs=" data-src="$2.$3$4"$5>';
			$lazyimg = preg_replace( $regexp, $replace, $lazyimg );
		}
		return $lazyimg;
	}

	public function replace( $regexp, $replace ) {
		ob_start(
			function( $buffer ) use ( $regexp, $replace ) {
				return preg_replace( $regexp, $replace, $buffer );
			}
		);
	}
}
