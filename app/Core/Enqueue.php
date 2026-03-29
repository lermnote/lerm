<?php // phpcs:disable WordPress.Files.FileName
/**
 * Enqueue theme styles and scripts here
 *
 * @package Lerm
 */
declare( strict_types = 1 );
namespace Lerm\Core;

use Lerm\Traits\Singleton;

class Enqueue {
	use Singleton;

	// Version for assets
	private const ASSET_VERSION = LERM_VERSION;
	// Base URI for theme assets
	private const LERM_URI = LERM_URI;

	/**
	 * Default constants.
	 *
	 * @since 2.1.0
	 * @var array $args Default value.
	 */
	private static $args = array(
		'enable_code_highlight' => true,
		'cdn_jquery'            => '',
	);

	private static $styles = array(
		'main_style' => 'assets/dist/main.css',
		'solarized'  => 'assets/resources/css/solarized-dark.min.css',
	);

	private static array $scripts = array(
		'main-js' => 'assets/dist/bundle.js',
	);

	/**
	 * Constructor
	 *
	 * @param array $params Optional parameters.

	 * @return void
	 */
	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_assets_args', wp_parse_args( $params, self::$args ) );
		$this->hooks();
	}

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

		// 告诉浏览器 bundle.js 是 ES Module
		add_filter( 'script_loader_tag', array( __CLASS__, 'add_module_type' ), 10, 2 );
	}

	/**
	 * Styles enqueue.
	 *
	 * @return void
	 */
	public static function enqueue_styles() {
		foreach ( self::$styles as $handle => $relative_path ) {
			if ( 'solarized' === $handle && ! ( is_singular() && self::$args['enable_code_highlight'] ) ) {
				continue;
			}
			$src = self::LERM_URI . $relative_path;
			wp_enqueue_style( $handle, $src, array(), self::ASSET_VERSION );
		}
	}

	/**
	 * Scripts enqueue.
	 *
	 * @return void
	 */
	public static function enqueue_scripts() {
		$scripts = apply_filters( 'lerm_enqueue_scripts', self::$scripts );
		foreach ( $scripts as $handle => $relative_path ) {
			$src = self::LERM_URI . $relative_path;
			wp_register_script( $handle, $src, array(), self::ASSET_VERSION, true );
			wp_enqueue_script( $handle );
		}

		if ( self::$args['cdn_jquery'] ) {
			wp_enqueue_script( 'jquery_cdn', self::$args['cdn_jquery'], array(), self::ASSET_VERSION, true );
		}

		wp_localize_script(
			'main-js',
			'lermData',
			apply_filters(
				'lerm_l10n_data',
				array(
					// API 地址
					'rest_url'       => esc_url_raw( rest_url( 'lerm/v1/' ) ),
					'ajax_url'       => admin_url( 'admin-ajax.php' ),

					// Nonce
					'nonce'          => wp_create_nonce( 'wp_rest' ),
					'ajax_nonce'     => wp_create_nonce( 'lerm_admin_ajax' ),
					'profile_nonce'  => wp_create_nonce( 'lerm_profile' ),

					// 用户状态
					'loggedin'       => is_user_logged_in(),
					'post_id'        => is_singular() ? get_the_ID() : 0,

					'route_like'     => 'like',
					'route_views'    => 'views',
					'route_search'   => 'search',
					'route_loadmore' => 'posts',
					'route_comment'  => 'comment',
					'route_profile'  => 'profile',

					// 登录/更新后跳转地址
					'front_door'     => esc_url( home_url( '/' ) ),
					'redirect'       => esc_url(
						is_user_logged_in()
							? ( get_edit_profile_url() !== false ? get_edit_profile_url() : home_url( '/' ) )
							: home_url( '/' )
					),
				)
			)
		);

		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}
	/**
	 * Determine whether social share scripts should be enqueued.
	 *
	 * @return bool
	 */
	private static function should_enqueue_social_share(): bool {
		$should = is_singular( 'post' ) || is_page_template( 'templates/account.php' );
		return (bool) apply_filters( 'lerm_enqueue_social_share', $should );
	}
	public static function add_module_type( string $tag, string $handle ): string {
		if ( 'main-js' === $handle ) {
			return str_replace( '<script ', '<script type="module" ', $tag );
		}
		return $tag;
	}
}
