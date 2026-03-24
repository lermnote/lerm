<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Http\Rest\Repository;

use Lerm\Infrastructure\CacheRepository;
/**
 * 浏览数数据层
 *
 * post_meta 存储：_lerm_view_count => int
 * 防刷：IP + UA hash，30 分钟窗口内只计一次（transient）
 *
 * @package Lerm\Repository
 */
final class ViewsRepository {

	const META_COUNT   = 'pageviews';
	const DEDUP_WINDOW = 1800; // 30 分钟

	public static function get_count( int $post_id ): int {
		return (int) CacheRepository::remember(
			CacheRepository::GROUP_VIEWS,
			"count_{$post_id}",
			MINUTE_IN_SECONDS,
			fn() => (int) get_post_meta( $post_id, self::META_COUNT, true )
		);
	}

	/** @return array{ count: int, recorded: bool } */
	public static function record( int $post_id, string $visitor_key ): array {
		$dedup_key = 'lerm_vw_' . md5( $post_id . $visitor_key );

		if ( get_transient( $dedup_key ) ) {
			return array(
				'count'    => self::get_count( $post_id ),
				'recorded' => false,
			);
		}

		set_transient( $dedup_key, 1, self::DEDUP_WINDOW );
		$count = self::increment( $post_id );
		CacheRepository::delete( CacheRepository::GROUP_VIEWS, "count_{$post_id}" );

		return array(
			'count'    => $count,
			'recorded' => true,
		);
	}

	public static function increment( int $post_id ): int {
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
		return (int) get_post_meta( $post_id, self::META_COUNT, true );
	}
}
