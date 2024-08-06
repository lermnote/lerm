<?php
/**
 * comments pagination template
 *
 * @package Lerm https://lerm.net
 */
if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) :
	?>
	<nav class="comment-nav mb-3">
		<div class="comment-pager d-flex justify-content-between">
			<div class="comment-prev prev btn btn-sm btn-custom">
				<i class="fa fa-chevron-left"></i>
				<?php previous_comments_link( esc_html__( 'Older Comments', 'lerm' ) ); ?>
			</div>
			<div class="comment-next btn btn-sm btn-custom">
				<?php next_comments_link( esc_html__( 'Newer Comments', 'lerm' ) ); ?>
				<i class=" fa fa-chevron-right"></i>
			</div>
		</div>
	</nav>
	<?php
endif;
