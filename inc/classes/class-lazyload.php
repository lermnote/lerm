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

	protected static $args = array(
		'skip_list' => array(),
	);

	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_optimize_', wp_parse_args( $params, self::$args ) );
		self::hooks();
	}

	// instance
	public static function instance( $params = array() ) {
		return new self( $params );
	}

	public static function hooks() {
		add_action( 'template_redirect', array( __CLASS__, 'lazyload' ) );
	}

	public static function lazyload() {
		ob_start( array( __CLASS__, 'lazyload_content' ) );
	}

	public static function lazyload_content( $content ) {
		$regexp = '/<img([^<>]*)\.(bmp|gif|jpeg|jpg|png)([^<>]*)>/i';
		return preg_replace_callback( $regexp, array( __CLASS__, 'lazyload_match' ), $content );
	}

	public static function lazyload_match( $matches ) {
		$image = $matches[0];

		if ( self::skip_lazyload( $image ) ) {
			return $image;
		}

		$image = self::add_lazy_class( $image );
		$image = self::add_lazy_data_attributes( $image );

		return $image;
	}

	private static function skip_lazyload( $image ) {
		if (
		stripos( $image, 'skip_lazyload' ) !== false ||
		stripos( $image, 'custom-logo' ) !== false ||
		stripos( $image, 'slider' ) !== false ||
		stripos( $image, 'avatar' ) !== false ||
		stripos( $image, 'qrcode' ) !== false
		) {
			return true;
		}
		return false;
	}

	private static function add_lazy_class( $image ) {
		if ( stripos( $image, 'class=' ) === false ) {
			$image = preg_replace( '/<img(.*)>/i', '<img class="lazy"$1>', $image );
		} else {
			$image = preg_replace( "/<img(.*)class=['\"]([\w\-\s]*)['\"](.*)>/i", '<img$1class="$2 lazy"$3>', $image );
		}

		return $image;
	}

	private static function add_lazy_data_attributes( $image ) {
		if ( stripos( $image, 'srcset=' ) ) {
			if ( ! stripos( $image, 'data-srcset=' ) ) {
				$regexp  = "/<img([^<>]*)srcset=['\"]([^<>'\"]*)['\"]([^<>]*)>/i";
				$replace = '<img$1srcset="data:image/gif;base64,R0lGODlhDwAPAKECAAAAzMzM/ wAAACwAAAAADwAPAAACIISPeQHsrZ5ModrLlN48CXF8m2iQ3YmmKqVlRtW4ML wWACH+H09wdGltaXplZCBieSBVbGVhZCBTbWFydFNhdmVyIQAAOw==" data-src="$2" data-srcset="$2"$3>';
			}
		} else {
			$regexp  = "/<img([^<>]*)src=['\"]([^<>'\"]*)\.(bmp|gif|jpeg|jpg|png)([^<>'\"]*)['\"]([^<>]*)>/i";
			$replace = '<img$1src="data:image/gif;base64,R0lGODlhDwAPAKECAAAAzMzM/ wAAACwAAAAADwAPAAACIISPeQHsrZ5ModrLlN48CXF8m2iQ3YmmKqVlRtW4ML wWACH+H09wdGltaXplZCBieSBVbGVhZCBTbWFydFNhdmVyIQAAOw==" data-src="$2.$3$4"$5>';
		}
		$image = preg_replace( $regexp, $replace, $image );
		return $image;
	}

	private function replace( $regexp, $replace ) {
		ob_start(
			function( $buffer ) use ( $regexp, $replace ) {
				return preg_replace( $regexp, $replace, $buffer );
			}
		);
	}
}
