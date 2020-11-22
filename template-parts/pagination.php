<div class="navigation">
	<?php if ( ( lerm_options( 'load_more' ) || wp_is_mobile() ) ) : ?>
		<button class='btn btn-sm btn-custom btn-block more-posts loading-animate fadeInUp' data-page="/"><?php esc_html_e( 'Load More', 'lerm' ); ?></button>
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
