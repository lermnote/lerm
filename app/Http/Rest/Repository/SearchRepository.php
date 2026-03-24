<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Http\Rest\Repository;

use Lerm\Infrastructure\CacheRepository;
final class SearchRepository {
	const HOT_OPTION_KEY = 'lerm_search_hot_words';
	const HOT_LIMIT      = 10;

	public static function search( string $keyword, string $post_type = 'post', int $per_page = 6 ): array {
		if ( ! post_type_exists( $post_type ) ) {
			$post_type = 'post';
		}
		$query = new \WP_Query(
			array(
				's'              => $keyword,
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => $per_page,
				'no_found_rows'  => false,
				'fields'         => 'ids',
			)
		);
		$items = array_filter( array_map( array( __CLASS__, 'format_post' ), $query->posts ) );
		return array(
			'items' => array_values( $items ),
			'total' => $query->found_posts,
		);
	}

	private static function format_post( int $post_id ): ?array {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return null;
		}
		$excerpt   = $post->post_excerpt ? $post->post_excerpt : mb_substr( wp_strip_all_tags( $post->post_content ), 0, 100, 'UTF-8' ) . '...';
		$thumbnail = has_post_thumbnail( $post_id ) ? (string) get_the_post_thumbnail_url( $post_id, 'thumbnail' ) : '';
		$cats      = get_the_category( $post_id );
		return array(
			'id'        => $post_id,
			'title'     => get_the_title( $post_id ),
			'url'       => get_permalink( $post_id ),
			'excerpt'   => esc_html( $excerpt ),
			'thumbnail' => esc_url( $thumbnail ),
			'date'      => get_the_date( 'Y-m-d', $post_id ),
			'category'  => $cats ? esc_html( $cats[0]->name ) : '',
		);
	}

	public static function record_keyword( string $keyword ): void {
		if ( mb_strlen( $keyword ) < 2 || mb_strlen( $keyword ) > 50 ) {
			return;
		}
		$hot             = self::get_raw_hot_words();
		$hot[ $keyword ] = ( $hot[ $keyword ] ?? 0 ) + 1;
		arsort( $hot );
		update_option( self::HOT_OPTION_KEY, array_slice( $hot, 0, 200, true ), false );
		CacheRepository::delete( CacheRepository::GROUP_SEARCH, 'hot_words' );
	}

	public static function get_hot_words( int $limit = self::HOT_LIMIT ): array {
		$result = CacheRepository::remember(
			CacheRepository::GROUP_SEARCH,
			'hot_words',
			10 * MINUTE_IN_SECONDS,
			static function () use ( $limit ) {
				$hot = SearchRepository::get_raw_hot_words();
				arsort( $hot );
				return array_slice( array_keys( $hot ), 0, $limit );
			}
		);
		return (array) $result;
	}

	private static function get_raw_hot_words(): array {
		$raw = get_option( self::HOT_OPTION_KEY, array() );
		return is_array( $raw ) ? $raw : array();
	}
}
