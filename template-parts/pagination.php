<?php if ( $wp_query->max_num_pages > 1 ) : ?>
	<div class="navigation mb-3">
		<?php if ( ( lerm_options( 'load_more' ) || wp_is_mobile() ) && ( $wp_query->max_num_pages > 1 ) ) : ?>
			<button class='btn btn-sm btn-custom container more-posts loading-animate fadeInUp' data-page="/"><?php esc_html_e( 'Load More', 'lerm' ); ?></button>
			<?php
		else :
			the_posts_pagination(
				array(
					'mid_size'           => 10,
					'prev_text'          => '<span class="screen-reader-text">' . __( 'Previous page', 'lerm' ) . '</span>',
					'next_text'          => '<span class="screen-reader-text">' . __( 'Next page', 'lerm' ),
					'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'The', 'lerm' ) . ' </span>',
					'after_page_number'  => '<span class="meta-nav screen-reader-text">' . __( ' Page', 'lerm' ) . ' </span>',
				)
			);
		endif;
		?>
	</div>
<?php endif; ?>
