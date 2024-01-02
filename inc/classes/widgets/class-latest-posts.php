<?php
/**
 * Latest Posts Widget Block
 */
namespace Lerm\Inc\Widgets;

use WP_Query;
use WP_Widget_Block;

class Latest_Posts_Widget_Block extends WP_Widget_Block {

	public function __construct() {
		parent::__construct(
			'latest_posts_widget_block',
			__( 'Latest Posts', 'text-domain' ),
			array(
				'description' => __( 'Display latest posts in a block.', 'text-domain' ),
			)
		);
	}

	public function render_callback( $instance, $content ) {
		$title   = $instance['title'] ?? '';
		$number  = $instance['number'] ?? 5;
		$orderby = $instance['orderby'] ?? 'date';

		$query_args = array(
			'post_type'           => 'post',
			'posts_per_page'      => $number,
			'orderby'             => $orderby,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
		);

		$latest_posts = new WP_Query( $query_args );

		if ( $latest_posts->have_posts() ) :
			echo $content;
			echo '<ul>';
			while ( $latest_posts->have_posts() ) :
				$latest_posts->the_post();
				echo '<li class="widget-post d-flex"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></li>';
			endwhile;
			echo '</ul>';
		endif;

		wp_reset_postdata();
	}

}
