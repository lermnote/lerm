<?php // phpcs:disable WordPress.Files.FileName
/**
 * Custom class for misc functions.
 *
 * @package lerm http://lerm.net
 */

namespace Lerm\Inc\Misc;

use Lerm\Inc\Traits\Singleton;

class Custom {

	use singleton;

	private static $args = array(
		'large_logo'    => '',
		'mobile_logo'   => '',
		'content_width' => 66.66666666666667,
		'sidebar_width' => 33.33333333333333,
		'custom_css'    => '',
	);

	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_custom_args', wp_parse_args( $params, self::$args ) );
		self::hooks();
	}

	/**
	 * Hooks
	 *
	 * Sets up the hooks for the SMTP configuration.
	 *
	 * @return void
	 */
	public static function hooks() {
		add_filter( 'get_custom_logo', array( __CLASS__, 'custom_logo' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'custom_css' ), 21 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'site_width' ), 21 );
	}

	/**
	 * Displays the optional custom logo.
	 *
	 * Does nothing if the custom logo is not available.
	 *
	 * @since Lerm 3.1
	 * @return string|null HTML for the custom logo or null if not available.
	 */
	public static function custom_logo() {
		$large_logo     = lerm_options( 'large_logo', 'id' );
		$mobile_logo    = lerm_options( 'mobile_logo', 'id' );
		$custom_logo_id = get_theme_mod( 'custom_logo' );

		if ( ! empty( $large_logo ) ) {
			$custom_logo_id = $large_logo;
		}

		if ( wp_is_mobile() && ! empty( $mobile_logo ) ) {
			$custom_logo_id = $mobile_logo;
		}

		if ( $custom_logo_id ) {
			$html = sprintf(
				'<a href="%1$s" class="custom-logo-link" rel="home" itemprop="url">%2$s</a>',
				esc_url( home_url( '/' ) ),
				wp_get_attachment_image(
					$custom_logo_id,
					'full',
					false,
					array(
						'class' => 'custom-logo me-1',
					)
				)
			);
			return $html;
		}
	}

	/**
	 * custom color
	 *
	 * @since  2.0
	 */
	public static function site_width() {
		$custom_width = '
		@media (min-width:992px) {
			#primary{
				width:%1$s%%
			}
			#secondary{
				width:%2$s%%
			}
		}
		';
		wp_add_inline_style( 'lerm_style', sprintf( $custom_width, self::$args['content_width'], self::$args['sidebar_width'] ) );
	}

	/**
	 * custom css
	 *
	 * @since Lerm 2.0
	 */
	public static function custom_css() {
		wp_add_inline_style(
			'main_style',
			sprintf( '%s', self::$args['custom_css'] )
		);
	}
}
