<?php
declare( strict_types=1 );

namespace Lerm\Http\Ajax\Actions;

/**
 * 保存主题选项 Ajax 处理器
 *
 * action: lerm_save_options（仅管理员）
 *
 * 用于 Admin 面板局部保存（不整页刷新），通过白名单机制
 * 只允许保存已注册的选项键，防止任意写入。
 *
 * @package Lerm\Http\Ajax\Actions
 */
final class SaveOptionsAction {

	private const NONCE_ACTION  = 'lerm_save_options_nonce';
	private const OPTION_KEY    = 'lerm_theme_options';

	/**
	 * 允许通过此接口保存的选项键白名单
	 * 每个键对应一个 sanitize 回调
	 *
	 * @var array<string, callable>
	 */
	private static array $allowed_keys = [];

	public static function handle(): void {
		// 1. Nonce + 权限
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( '权限不足', 'lerm' ) ], 403 );
		}

		// 2. 初始化白名单（延迟到此处，确保 hooks 已加载）
		self::init_allowed_keys();

		// 3. 读取要保存的字段（POST 中的 options 字段，JSON 格式）
		$raw     = wp_unslash( $_POST['options'] ?? '' );
		$payload = json_decode( (string) $raw, true );

		if ( ! is_array( $payload ) ) {
			wp_send_json_error( [ 'message' => __( '无效的数据格式', 'lerm' ) ], 400 );
		}

		// 4. 取出当前选项，仅更新白名单内的键
		$current = get_option( self::OPTION_KEY, [] );
		if ( ! is_array( $current ) ) {
			$current = [];
		}

		$updated_keys = [];
		foreach ( $payload as $key => $value ) {
			$key = sanitize_key( (string) $key );

			if ( ! array_key_exists( $key, self::$allowed_keys ) ) {
				continue; // 静默跳过未在白名单的键
			}

			$sanitize        = self::$allowed_keys[ $key ];
			$current[ $key ] = $sanitize( $value );
			$updated_keys[]  = $key;
		}

		if ( empty( $updated_keys ) ) {
			wp_send_json_error( [ 'message' => __( '没有可更新的选项', 'lerm' ) ], 400 );
		}

		update_option( self::OPTION_KEY, $current );

		wp_send_json_success( [
			'message' => __( '选项已保存', 'lerm' ),
			'updated' => $updated_keys,
		] );
	}

	/**
	 * 初始化允许保存的选项键白名单
	 *
	 * 通过 filter `lerm_ajax_allowed_option_keys` 允许外部扩展。
	 */
	private static function init_allowed_keys(): void {
		$defaults = [
			// 常规设置
			'global_layout'    => 'sanitize_key',
			'layout_style'     => 'sanitize_key',
			'loading_animate'  => 'rest_sanitize_boolean',
			// SEO
			'separator'        => 'sanitize_text_field',
			'description'      => 'sanitize_textarea_field',
			'html_slug'        => 'rest_sanitize_boolean',
			// 性能
			'disable_pingback' => 'rest_sanitize_boolean',
			'gravatar_accel'   => 'sanitize_url',
			'google_replace'   => 'sanitize_key',
		];

		self::$allowed_keys = (array) apply_filters( 'lerm_ajax_allowed_option_keys', $defaults );
	}
}
