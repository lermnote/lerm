<?php
declare( strict_types=1 );

namespace Lerm\Http\Rest\Repository;

/**
 * 点赞数据层
 *
 * post_meta 存储方案：
 *   _lerm_like_count  => int       总点赞数
 *   _lerm_like_users  => string[]  已点赞用户标识列表（最多 5000 条）
 *
 * 写入使用 GREATEST(0, CAST AS SIGNED ± 1) 原子 SQL，避免并发竞态。
 *
 * @package Lerm\Repository
 */
final class LikeRepository {

	const META_COUNT  = '_lerm_like_count';
	const META_USERS  = '_lerm_like_users';
	const CACHE_GROUP = 'lerm_likes';

	// -------------------------------------------------------------------------
	// 查询
	// -------------------------------------------------------------------------

	public static function get_count( int $post_id ): int {
		$cached = wp_cache_get( "count_{$post_id}", self::CACHE_GROUP );
		if ( false !== $cached ) return (int) $cached;

		$count = (int) get_post_meta( $post_id, self::META_COUNT, true );
		wp_cache_set( "count_{$post_id}", $count, self::CACHE_GROUP, 5 * MINUTE_IN_SECONDS );
		return $count;
	}

	public static function has_liked( int $post_id, string $user_id ): bool {
		return in_array( $user_id, self::get_users( $post_id ), true );
	}

	/** @return string[] */
	public static function get_users( int $post_id ): array {
		$cached = wp_cache_get( "users_{$post_id}", self::CACHE_GROUP );
		if ( false !== $cached ) return (array) $cached;

		$raw   = get_post_meta( $post_id, self::META_USERS, true );
		$users = is_array( $raw ) ? $raw : [];
		wp_cache_set( "users_{$post_id}", $users, self::CACHE_GROUP, 5 * MINUTE_IN_SECONDS );
		return $users;
	}

	// -------------------------------------------------------------------------
	// 写入
	// -------------------------------------------------------------------------

	/** @return array{ liked: bool, count: int } */
	public static function toggle( int $post_id, string $user_id ): array {
		$users = self::get_users( $post_id );
		$liked = in_array( $user_id, $users, true );

		if ( $liked ) {
			$users = array_values( array_filter( $users, static fn( $u ) => $u !== $user_id ) );
			$count = self::decrement( $post_id );
		} else {
			$users[] = $user_id;
			$count   = self::increment( $post_id );
		}

		// 最多保留 5000 条，防止 meta 无限膨胀
		update_post_meta( $post_id, self::META_USERS, array_slice( $users, -5000 ) );

		wp_cache_delete( "count_{$post_id}", self::CACHE_GROUP );
		wp_cache_delete( "users_{$post_id}", self::CACHE_GROUP );

		return [ 'liked' => ! $liked, 'count' => max( 0, $count ) ];
	}

	// -------------------------------------------------------------------------
	// 原子计数
	// -------------------------------------------------------------------------

	private static function increment( int $post_id ): int {
		global $wpdb;
		if ( '' === get_post_meta( $post_id, self::META_COUNT, true ) ) {
			add_post_meta( $post_id, self::META_COUNT, 0, true );
		}
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->postmeta}
			 SET meta_value = GREATEST(0, CAST(meta_value AS SIGNED) + 1)
			 WHERE post_id = %d AND meta_key = %s",
			$post_id, self::META_COUNT
		) );
		return (int) get_post_meta( $post_id, self::META_COUNT, true );
	}

	private static function decrement( int $post_id ): int {
		global $wpdb;
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->postmeta}
			 SET meta_value = GREATEST(0, CAST(meta_value AS SIGNED) - 1)
			 WHERE post_id = %d AND meta_key = %s",
			$post_id, self::META_COUNT
		) );
		return (int) get_post_meta( $post_id, self::META_COUNT, true );
	}
}
