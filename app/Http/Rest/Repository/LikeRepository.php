<?php  // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Http\Rest\Repository;

use Lerm\Infrastructure\CacheRepository;
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

	const META_COUNT = '_lerm_like_count';
	const META_USERS = '_lerm_like_users';

	// -------------------------------------------------------------------------
	// 查询
	// -------------------------------------------------------------------------

	public static function get_count( int $post_id ): int {
			return (int) CacheRepository::remember(
				CacheRepository::GROUP_LIKES,
				"count_{$post_id}",
				5 * MINUTE_IN_SECONDS,
				fn() => (int) get_post_meta( $post_id, self::META_COUNT, true )
			);
	}

	public static function has_liked( int $post_id, string $user_id ): bool {
		return in_array( $user_id, self::get_users( $post_id ), true );
	}

	/** @return string[] */
	public static function get_users( int $post_id ): array {
			$result = CacheRepository::remember(
				CacheRepository::GROUP_LIKES,
				"users_{$post_id}",
				5 * MINUTE_IN_SECONDS,
				static function () use ( $post_id ) {
					$raw = get_post_meta( $post_id, LikeRepository::META_USERS, true );
					return is_array( $raw ) ? $raw : array();
				}
			);
		return (array) $result;
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

		CacheRepository::delete( CacheRepository::GROUP_LIKES, "count_{$post_id}" );
		CacheRepository::delete( CacheRepository::GROUP_LIKES, "users_{$post_id}" );

		$new_liked = ! $liked;
		return array(
			'liked'  => $new_liked,
			'count'  => max( 0, $count ),
			'status' => $new_liked ? 'liked' : 'unliked',
		);
	}

	// -------------------------------------------------------------------------
	// 原子计数
	// -------------------------------------------------------------------------

	private static function increment( int $post_id ): int {
		global $wpdb;
		if ( '' === get_post_meta( $post_id, self::META_COUNT, true ) ) {
			add_post_meta( $post_id, self::META_COUNT, 0, true );
		}
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta}
			 SET meta_value = GREATEST(0, CAST(meta_value AS SIGNED) + 1)
			 WHERE post_id = %d AND meta_key = %s",
				$post_id,
				self::META_COUNT
			)
		);

		wp_cache_delete( $post_id, 'post_meta' );
		return (int) get_post_meta( $post_id, self::META_COUNT, true );
	}

	private static function decrement( int $post_id ): int {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta}
			 SET meta_value = GREATEST(0, CAST(meta_value AS SIGNED) - 1)
			 WHERE post_id = %d AND meta_key = %s",
				$post_id,
				self::META_COUNT
			)
		);

		wp_cache_delete( $post_id, 'post_meta' );
		return (int) get_post_meta( $post_id, self::META_COUNT, true );
	}
}
