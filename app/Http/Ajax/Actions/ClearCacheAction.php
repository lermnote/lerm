<?php
declare( strict_types=1 );

namespace Lerm\Http\Ajax\Actions;

/**
 * 清除缓存 Ajax 处理器
 *
 * action: lerm_clear_cache（仅登录用户）
 *
 * 支持清除的缓存类型（通过 type 参数指定）：
 *   - all      : 清除所有 lerm 缓存
 *   - views    : 浏览数缓存
 *   - likes    : 点赞缓存
 *   - search   : 搜索结果缓存
 *   - toc      : TOC 目录缓存
 *   - transient: 所有 lerm_ 前缀的 transient
 *
 * @package Lerm\Http\Ajax\Actions
 */
final class ClearCacheAction {

	private const NONCE_ACTION = 'lerm_clear_cache_nonce';

	public static function handle(): void {
		// 1. Nonce + 权限双重验证
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( '权限不足', 'lerm' ) ], 403 );
		}

		// 2. 清除类型
		$type = sanitize_key( $_POST['type'] ?? 'all' );

		$cleared = match ( $type ) {
			'views'     => self::clear_group( 'lerm_views' ),
			'likes'     => self::clear_group( 'lerm_likes' ),
			'search'    => self::clear_group( 'lerm_search' ),
			'toc'       => self::clear_group( 'lerm_toc' ),
			'transient' => self::clear_transients(),
			default     => self::clear_all(),
		};

		wp_send_json_success( [
			'message' => __( '缓存已清除', 'lerm' ),
			'cleared' => $cleared,
		] );
	}

	/** 清除指定 object cache group */
	private static function clear_group( string $group ): string {
		wp_cache_flush_group( $group );
		return $group;
	}

	/** 清除所有 lerm_ 前缀的 transient */
	private static function clear_transients(): string {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_lerm_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_lerm_' ) . '%'
			)
		);
		return 'transients';
	}

	/** 清除全部 lerm 缓存 */
	private static function clear_all(): string {
		foreach ( [ 'lerm_views', 'lerm_likes', 'lerm_search', 'lerm_toc' ] as $group ) {
			wp_cache_flush_group( $group );
		}
		self::clear_transients();
		return 'all';
	}
}
