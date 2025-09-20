<?php // phpcs:disable WordPress.Files.FileName
/**
 * Enqueue theme styles and scripts here
 *
 * @package  Lerm\Inc
 */

namespace Lerm\Core;

use Lerm\Traits\Singleton;

class Enqueue {
	use singleton;

	// Version for assets
	private const ASSET_VERSION = '1.0.0';
	// Base URI for theme assets (常量在主题定义 LERM_URI)
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

	// 原先静态资源清单（回退用）
	private static $styles = array(
		// 'bootstrap'  => 'assets/css/bootstrap.min.css',
		'lerm_font'  => 'assets/css/lerm-icons.css',
		// 'animate'    => 'assets/css/animate.min.css',
		'solarized'  => 'assets/css/solarized-dark.min.css',
		'main_style' => 'assets/css/main.css',
	);

	public static $scripts = array(
		// 'bootstrap' => 'assets/js/bootstrap.bundle.min.js',
		'lazyload'  => 'assets/js/lazyload.min.js',
		'share'     => 'assets/js/social-share.min.js',
		'qrcode'    => 'assets/js/qrcode.min.js',
		'highlight' => 'assets/js/highlight.pack.js',
		// 'wow'       => 'assets/js/wow.min.js',
		// 'main-js'   => 'assets/dist/bundle.js',
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
	}

	/**
	 * 从 manifest.json 获取 dist 文件 URL（返回 URL 或 false）
	 *
	 * @param string $key manifest 中的 key，例如 'styles.css' 或 'bundle.js'
	 * @return false|string
	 */
	private static function get_dist_file_url( $key ) {
		$dist_dir      = get_template_directory() . '/assets/dist';
		$manifest_path = $dist_dir . '/manifest.json';

		if ( ! file_exists( $manifest_path ) ) {
			return false;
		}

		$manifest = json_decode( file_get_contents( $manifest_path ), true );
		if ( ! is_array( $manifest ) || ! isset( $manifest[ $key ] ) ) {
			return false;
		}

		return get_template_directory_uri() . '/assets/dist/' . $manifest[ $key ];
	}

	/**
	 * Styles enqueue.
	 *
	 * 优先使用 manifest（若存在），否则回退到静态样式数组。
	 *
	 * @return void
	 */
	public static function enqueue_styles() {
		// 如果存在 manifest，把打包后的 styles.css 作为 lerm-styles 入队
		$manifest_css = self::get_dist_file_url( 'styles.css' );

		if ( $manifest_css ) {
			// 使用 manifest 输出的带 hash 的文件名，便于缓存控制
			wp_enqueue_style( 'lerm-styles', $manifest_css, array(), null );
		} else {
			// 回退：按静态列表加载（开发或未构建时）
			foreach ( self::$styles as $handle => $relative_path ) {
				$src = self::LERM_URI . $relative_path;
				wp_enqueue_style( $handle, $src, array(), self::ASSET_VERSION );
			}

			// 条件加载示例（保留原有逻辑）
			if ( is_singular( 'post' ) && self::$args['enable_code_highlight'] ) {
				wp_enqueue_style( 'solarized', self::LERM_URI . self::$styles['solarized'], array(), self::ASSET_VERSION );
			}
		}
	}

	/**
	 * Scripts enqueue.
	 *
	 * 优先使用 manifest 输出的 main bundle（作为 main-js），否则回退到静态脚本数组。
	 *
	 * @return void
	 */
	public static function enqueue_scripts() {
		// Manifest 优先：把打包后的 bundle 映射为 main-js（和原先 localize 保持一致）
		$manifest_js = self::get_dist_file_url( 'bundle.js' ); // 注意 webpack manifest 中 key 是 'bundle.js'

		if ( $manifest_js ) {
			// 注册并入队 main-js（便于 wp_localize_script 使用）
			wp_register_script( 'main-js', $manifest_js, array(), null, true );
			wp_enqueue_script( 'main-js' );

			// 仍可按需注册其它脚本（例如在静态数组里有的），如果你希望同时加载静态脚本可以在此添加逻辑
		} else {
			// 回退：按静态列表注册并入队
			foreach ( self::$scripts as $handle => $relative_path ) {
				$src = self::LERM_URI . $relative_path;
				wp_register_script( $handle, $src, array(), self::ASSET_VERSION, true );
				wp_enqueue_script( $handle );

				// Apply defer or async for non-essential scripts
				if ( in_array( $handle, array( 'share', 'qrcode' ), true ) ) {
					wp_script_add_data( $handle, 'defer', true ); // Add defer for lazy loading
				}
			}
		}

		// CDN jQuery (如果配置了)
		if ( self::$args['cdn_jquery'] ) {
			wp_enqueue_script( 'jquery_cdn', self::$args['cdn_jquery'], array(), self::ASSET_VERSION, true );
		}

		// Highlight (保留条件注册/加载逻辑)
		if ( is_singular( 'post' ) ) {
			if ( self::$args['enable_code_highlight'] ) {
				// 如果使用 manifest 模式且 highlight 被包含在打包内，则这里可以省略；
				// 但为兼容原逻辑，尝试入队已注册的 highlight（如果存在）
				if ( wp_script_is( 'highlight', 'registered' ) ) {
					wp_enqueue_script( 'highlight' );
				} else {
					// 回退到静态文件（如果你希望把 highlight 始终单独加载）
					if ( isset( self::$scripts['highlight'] ) ) {
						$src = self::LERM_URI . self::$scripts['highlight'];
						wp_register_script( 'highlight', $src, array(), self::ASSET_VERSION, true );
						wp_enqueue_script( 'highlight' );
					}
				}
			}
		}

		// 保持原来 localize 的调用：针对 main-js（无论 manifest 还是回退都要存在 main-js）
		if ( wp_script_is( 'main-js', 'enqueued' ) || wp_script_is( 'main-js', 'registered' ) ) {
			wp_localize_script( 'main-js', 'lermData', apply_filters( 'lerm_l10n_datas', array() ) );
		} else {
			// 如果 main-js 不存在（例如完全回退至静态脚本集合且没有 main-js），你可以选择 localize 给某个已知 handle 或跳过
		}

		// 评论回复
		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}
}
