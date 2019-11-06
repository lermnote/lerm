<?php
/**
 * related post show on single
 *
 * @since 2.0
 */

function related_posts() {
	global $post, $wpdb;

	$post_tags = wp_get_post_tags( $post->ID );

	if ( $post_tags ) {
		$tag_list = '';

		foreach ( $post_tags as $tag ) {

			// 获取标签列表

			$tag_list .= $tag->term_id . ',';
		}

		$tag_list = substr( $tag_list, 0, strlen( $tag_list ) - 1 );

		$related_posts = $wpdb->get_results(
			"

      SELECT DISTINCT ID, post_title

      FROM {$wpdb->prefix}posts, {$wpdb->prefix}term_relationships, {$wpdb->prefix}term_taxonomy

      WHERE {$wpdb->prefix}term_taxonomy.term_taxonomy_id = {$wpdb->prefix}term_relationships.term_taxonomy_id

      AND ID = object_id

      AND taxonomy = 'post_tag'

      AND post_status = 'publish'

      AND post_type = 'post'

      AND term_id IN (" . $tag_list . ")

      AND ID != '" . $post->ID . "'

      ORDER BY RAND()

      LIMIT 4"
		);

		if ( $related_posts ) {
			echo '<ul class="list-unstyled p-3">';
			foreach ( $related_posts as $related_post ) {
				echo sprintf( '<li class="related-title mb-1" ><i class="fa fa-caret-right"></i><a href="%1$s" rel="bookmark" title="%2$s">%2$s</a></li>', get_permalink( $related_post->ID ), $related_post->post_title );
			}
			echo '</ul>';
		} else {
			echo '<p class="p-3">暂无相关文章</p>';
		}
	} else {
		echo '<p class="p-3">暂无相关文章</p>';
	}
}
