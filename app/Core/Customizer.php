<?php // phpcs:disable WordPress.Files.FileName
/**
 * Custom class for misc functions.
 *
 * @package lerm http://lerm.net
 */
declare( strict_types = 1 );
namespace Lerm\Core;

use Lerm\Traits\Singleton;

class Customizer {
	use Singleton;

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
		// NOTE: site_width (content/sidebar column vars) is now handled by
		// CssVariables::output() at priority 22, which reads content_width /
		// sidebar_width directly from the full $options array.
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
		$large_logo     = self::$args['large_logo'];
		$mobile_logo    = self::$args['mobile_logo'];
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
	 * 输出布局相关的 CSS 变量（content / sidebar 列宽）。
	 *
	 * 替代原来直接写 width 属性的方式：只要 :root 里的变量改变，
	 * 所有引用 var(--lerm-content-width) 的规则自动跟着生效，
	 * 暗色模式或响应式覆盖时也不需要重复 !important 声明。
	 */
	public static function site_width() {
		$content = (float) self::$args['content_width'];
		$sidebar = (float) self::$args['sidebar_width'];

		if ( ! $content && ! $sidebar ) {
			return;
		}

		$vars = array();
		if ( $content ) {
			$vars[] = '--lerm-content-width:' . $content . '%';
		}
		if ( $sidebar ) {
			$vars[] = '--lerm-sidebar-width:' . $sidebar . '%';
		}

		wp_add_inline_style( 'main_style', ':root{' . implode( ';', $vars ) . '}' );
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
