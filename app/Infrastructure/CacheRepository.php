<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Infrastructure;

/**
 * 对象缓存统一入口
 *
 * 封装 WordPress Object Cache（wp_cache_*），让上层 Repository
 * 不直接依赖全局 WP 函数。好处：
 *
 *   1. 所有缓存调用集中在一处，group/key 命名规范统一可查
 *   2. 切换缓存驱动、加前缀、开调试日志只改这一个文件
 *   3. 单元测试时可以用 WP_Mock 在这里 mock 一次，覆盖所有 Repository
 *
 * 设计原则：
 *   - 只包装 object cache（内存级，请求内有效或依赖持久化驱动如 Redis）
 *   - transient（持久化 DB 缓存）不在此管理，语义不同，各自在业务层处理
 *   - 所有方法静态，与现有 Repository 风格一致，不引入实例生命周期
 *
 * @package Lerm\Infrastructure
 */
final class CacheRepository {

	/** 各业务模块的缓存组名（集中声明，避免各文件各自硬编码字符串） */
	public const GROUP_LIKES  = 'lerm_likes';
	public const GROUP_VIEWS  = 'lerm_views';
	public const GROUP_SEARCH = 'lerm_search';
	public const GROUP_TOC    = 'lerm_toc';

	// -------------------------------------------------------------------------
	// 基础操作
	// -------------------------------------------------------------------------

	/**
	 * 读取缓存。命中返回缓存值，未命中返回 false。
	 */
	public static function get( string $group, string $key ): mixed {
		return wp_cache_get( $key, $group );
	}

	/**
	 * 写入缓存。
	 *
	 * @param int $ttl 过期时间（秒），0 = 不过期（驱动决定）
	 */
	public static function set( string $group, string $key, mixed $value, int $ttl = 300 ): void {
		wp_cache_set( $key, $value, $group, $ttl );
	}

	/**
	 * 删除单条缓存。
	 */
	public static function delete( string $group, string $key ): void {
		wp_cache_delete( $key, $group );
	}

	/**
	 * 清空整个缓存组。
	 *
	 * 依赖持久化缓存驱动（Redis/Memcached）的 group flush 支持。
	 * 默认 WP object cache 调用此方法为静默 no-op，不会报错。
	 */
	public static function flush( string $group ): void {
		wp_cache_flush_group( $group );
	}

	// -------------------------------------------------------------------------
	// Read-through 模式
	// -------------------------------------------------------------------------

	/**
	 * 读取缓存，未命中时执行回调并自动写入。
	 *
	 * 用法：
	 *   $count = CacheRepository::remember(
	 *       self::GROUP_LIKES,
	 *       "count_{$post_id}",
	 *       5 * MINUTE_IN_SECONDS,
	 *       fn() => (int) get_post_meta( $post_id, '_lerm_like_count', true )
	 *   );
	 */
	public static function remember( string $group, string $key, int $ttl, callable $callback ): mixed {
		$cached = self::get( $group, $key );
		if ( false !== $cached ) {
			return $cached;
		}
		$value = $callback();
		self::set( $group, $key, $value, $ttl );
		return $value;
	}

	// -------------------------------------------------------------------------
	// 批量清理
	// -------------------------------------------------------------------------

	/**
	 * 清空所有 lerm 业务缓存组。
	 */
	public static function flush_all(): void {
		foreach ( self::all_groups() as $group ) {
			self::flush( $group );
		}
	}

	/**
	 * 返回所有受管理的缓存组列表。
	 * 新增缓存组时在此追加，ClearCacheAction 自动覆盖，无需手动同步。
	 *
	 * @return string[]
	 */
	public static function all_groups(): array {
		return (array) apply_filters(
			'lerm_cache_groups',
			array(
				self::GROUP_LIKES,
				self::GROUP_VIEWS,
				self::GROUP_SEARCH,
				self::GROUP_TOC,
			)
		);
	}
}
