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

	public static function get_count( int $id, string $type = 'post' ): int {
			return (int) CacheRepository::remember(
				CacheRepository::GROUP_LIKES,
				"{$type}_count_{$id}",
				5 * MINUTE_IN_SECONDS,
				static fn() => (int) self::get_meta( $id, self::META_COUNT, $type )
			);
	}

	public static function has_liked( int $id, string $user_id, string $type = 'post' ): bool {
		return in_array( $user_id, self::get_users( $id, $type ), true );
	}

	/** @return string[] */
	public static function get_users( int $id, string $type = 'post' ): array {
			$result = CacheRepository::remember(
				CacheRepository::GROUP_LIKES,
				"{$type}_users_{$id}",
				5 * MINUTE_IN_SECONDS,
				static function () use ( $id, $type ) {
					$raw = self::get_meta( $id, self::META_USERS, $type );
					return is_array( $raw ) ? $raw : array();
				}
			);
		return (array) $result;
	}

	// -------------------------------------------------------------------------
	// 写入
	// -------------------------------------------------------------------------

	/** @return array{ liked: bool, count: int } */
	public static function toggle( int $id, string $user_id, string $type = 'post' ): array {
		$users = self::get_users( $id, $type );
		$liked = in_array( $user_id, $users, true );

		if ( $liked ) {
			$users = array_values( array_filter( $users, static fn( $u ) => $u !== $user_id ) );
			$count = self::decrement( $id, $type );
		} else {
			$users[] = $user_id;
			$count   = self::increment( $id, $type );
		}

		// 最多保留 5000 条，防止 meta 无限膨胀
		self::update_meta( $id, self::META_USERS, array_slice( $users, -5000 ), $type );

		CacheRepository::delete( CacheRepository::GROUP_LIKES, "{$type}_count_{$id}" );
		CacheRepository::delete( CacheRepository::GROUP_LIKES, "{$type}_users_{$id}" );

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

	private static function increment( int $id, string $type ): int {
		global $wpdb;

		if ( 'comment' === $type ) {
			if ( '' === get_comment_meta( $id, self::META_COUNT, true ) ) {
				add_comment_meta( $id, self::META_COUNT, 0, true );
			}
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->commentmeta}
					 SET meta_value = GREATEST(0, CAST(meta_value AS SIGNED) + 1)
					 WHERE comment_id = %d AND meta_key = %s",
					$id,
					self::META_COUNT
				)
			);
			wp_cache_delete( $id, 'comment_meta' );
			return (int) get_comment_meta( $id, self::META_COUNT, true );
		}

		if ( '' === get_post_meta( $id, self::META_COUNT, true ) ) {
			add_post_meta( $id, self::META_COUNT, 0, true );
		}
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta}
				SET meta_value = GREATEST(0, CAST(meta_value AS SIGNED) + 1)
				WHERE post_id = %d AND meta_key = %s",
				$id,
				self::META_COUNT
			)
		);

		wp_cache_delete( $id, 'post_meta' );
		return (int) get_post_meta( $id, self::META_COUNT, true );
	}

	private static function decrement( int $id, string $type ): int {
		global $wpdb;

		if ( 'comment' === $type ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->commentmeta}
					 SET meta_value = GREATEST(0, CAST(meta_value AS SIGNED) - 1)
					 WHERE comment_id = %d AND meta_key = %s",
					$id,
					self::META_COUNT
				)
			);
			wp_cache_delete( $id, 'comment_meta' );
			return (int) get_comment_meta( $id, self::META_COUNT, true );
		}

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta}
			 SET meta_value = GREATEST(0, CAST(meta_value AS SIGNED) - 1)
			 WHERE post_id = %d AND meta_key = %s",
				$id,
				self::META_COUNT
			)
		);

		wp_cache_delete( $id, 'post_meta' );
		return (int) get_post_meta( $id, self::META_COUNT, true );
	}

	private static function get_meta( int $id, string $key, string $type ): mixed {
		return 'comment' === $type
		? get_comment_meta( $id, $key, true )
		: get_post_meta( $id, $key, true );
	}

	private static function update_meta( int $id, string $key, mixed $value, string $type ): void {
		if ( 'comment' === $type ) {
			update_comment_meta( $id, $key, $value );
		} else {
			update_post_meta( $id, $key, $value );
		}
	}
}
