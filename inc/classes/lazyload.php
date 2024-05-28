<?php // phpcs:disable WordPress.Files.FileName
/**
 * Lazyload Class
 *
 * This class provides functionality for lazy loading images in WordPress.
 *
 * @package Lerm https://lerm.net
 *
 * @since lerm 3.0
 */
namespace Lerm\Inc;

use Lerm\Inc\Traits\Singleton;

class Lazyload {

	use singleton;

	/**
	 * Default arguments for lazy loading.
	 *
	 * @var array $default_args Default arguments for lazy loading.
	 */
	protected static $default_args = array(
		'skip_list' => array( 'skip_lazyload', 'custom-logo', 'slider', 'avatar', 'qrcode' ),
	);

	/**
	 * Constructor.
	 *
	 * Initializes the Lazyload class.
	 *
	 * @param array $params Optional parameters for lazy loading.
	 */
	public function __construct( $params = array() ) {
		self::$default_args = apply_filters( 'lerm_lazyload_', wp_parse_args( $params, self::$default_args ) );
		self::hooks();
	}

	/**
	 * Hook into WordPress.
	 */
	public static function hooks() {
		add_action( 'template_redirect', array( __CLASS__, 'lazyload' ) );
	}

	/**
	 * Lazyload images.
	 */
	public static function lazyload() {
		ob_start( array( __CLASS__, 'lazyload_content' ) );
	}

	/**
	 * Replace images with lazyloaded images.
	 *
	 * @param string $content The content to search for images.
	 * @return string Content with lazyloaded images.
	 */
	public static function lazyload_content( $content ) {
		$regexp = '/<img([^<>]*)\.(bmp|gif|jpeg|jpg|png)([^<>]*)>/i';

		return preg_replace_callback( $regexp, array( __CLASS__, 'lazyload_match' ), $content );
	}

	/**
	 * Callback function to match and lazyload images.
	 *
	 * @param array $matches Array of matched image tags.
	 * @return string Lazyloaded image tag.
	 */
	public static function lazyload_match( $matches ) {
		$image = $matches[0];

		if ( self::skip_lazyload( self::$default_args['skip_list'], $image ) ) {
			return $image;
		}

		$image = self::add_lazy_class( $image );
		$image = self::add_lazy_data_attributes( $image );

		return $image;
	}

	/**
	 * Check if image should be skipped from lazyloading.
	 *
	 * @param array  $skips Array of strings to skip.
	 * @param string $image Image tag to check.
	 * @return bool True if image should be skipped, false otherwise.
	 */
	private static function skip_lazyload( $skips, $image ) {
		foreach ( $skips as $value ) {
			if ( stripos( $image, $value ) !== false ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Add lazy class to image tag.
	 *
	 * @param string $image Image tag to add lazy class to.
	 * @return string Image tag with lazy class added.
	 */
	private static function add_lazy_class( $image ) {
		if ( stripos( $image, 'class=' ) === false ) {
			$image = preg_replace( '/<img(.*)>/i', '<img loading="lazy" class="lazy"$1>', $image );
		} else {
			$image = preg_replace( "/<img(.*)class=['\"]([\w\-\s]*)['\"](.*)>/i", '<img loading="lazy"$1class="$2 lazy"$3>', $image );
		}

		return $image;
	}

	/**
	 * Add lazy data attributes to image tag.
	 *
	 * @param string $image Image tag to add lazy data attributes to.
	 * @return string Image tag with lazy data attributes added.
	 */
	private static function add_lazy_data_attributes( $image ) {
		$image = preg_replace_callback(
			"/<img([^<>]*)src=['\"]([^<>'\"]*)\.(bmp|gif|jpeg|jpg|png)([^<>'\"]*)['\"]([^<>]*)>/i",
			function( $matches ) {
				$original_src = $matches[2] . '.' . $matches[3];
				$data_src     = 'data-src="' . $original_src . '"';
				$data_srcset  = '';

				if ( stripos( $matches[0], 'srcset=' ) !== false ) {
					$data_srcset = ' data-srcset="' . $original_src . '"';
					$matches[0]  = preg_replace( '/srcset=["\'][^"\']*["\']/', '', $matches[0] );
				}

				return '<img' . $matches[1] . 'src="data:image/gif;base64,R0lGODlhCgAKAJEDAMzMzP9mZv8AAP///yH/C05FVFNDQVBFMi4wAwEAAAAh+QQFAAADACwAAAAACgAKAAACF5wncgaAGgJzJ647cWua4sOBFEd62VEAACH5BAUAAAMALAEAAAAIAAMAAAIKnBM2IoMDAFMQFAAh+QQFAAADACwAAAAABgAGAAACDJwHMBGofKIRItJYAAAh+QQFAAADACwAAAEAAwAIAAACChxgOBPBvpYQYxYAIfkEBQAAAwAsAAAEAAYABgAAAgoEhmPJHOGgEGwWACH5BAUAAAMALAEABwAIAAMAAAIKBIYjYhOhRHqpAAAh+QQFAAADACwEAAQABgAGAAACDJwncqi7EQYAA0p6CgAh+QQJAAADACwHAAEAAwAIAAACCpRmoxoxvQAYchQAOw==" ' . $data_src . $data_srcset . $matches[4] . '>';
			},
			$image
		);

		return $image;
	}
}
