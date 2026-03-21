<?php
declare( strict_types=1 );

namespace Lerm\Http\Rest\Repository;

/**
 * 浏览数数据层
 *
 * post_meta 存储：_lerm_view_count => int
 * 防刷：IP + UA hash，30 分钟窗口内只计一次（transient）
 *
 * @package Lerm\Repository
 */
final class ViewsRepository {

	const META_COUNT   = '_lerm_view_count';
	const CACHE_GROUP  = 'lerm_views';
	const DEDUP_WINDOW = 1800; // 30 分钟

	public static function get_count( int $post_id ): int {
		$cached = wp_cache_get( "count_{$post_id}", self::CACHE_GROUP );
		if ( false !== $cached ) return (int) $cached;

		$count = (int) get_post_meta( $post_id, self::META_COUNT, true );
		wp_cache_set( "count_{$post_id}", $count, self::CACHE_GROUP, MINUTE_IN_SECONDS );
		return $count;
	}

	/** @return array{ count: int, recorded: bool } */
	public static function record( int $post_id, string $visitor_key ): array {
		$dedup_key = 'lerm_vw_' . md5( $post_id . $visitor_key );

		if ( get_transient( $dedup_key ) ) {
			return [ 'count' => self::get_count( $post_id ), 'recorded' => false ];
		}

		set_transient( $dedup_key, 1, self::DEDUP_WINDOW );
		$count = self::increment( $post_id );
		wp_cache_delete( "count_{$post_id}", self::CACHE_GROUP );

		return [ 'count' => $count, 'recorded' => true ];
	}

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
}
