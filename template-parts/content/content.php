<?php
/**
 * The template part for displaying content
 *
 * @package Lerm
 * @since Lerm 2.0
 */
global $post;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'col' ); ?> >
	<div class="content-area">

		<?php get_template_part( 'template-parts/header/entry-header' ); ?>
		<?php if ( is_singular() ): ?>
			<div class="entry-content clearfix pt-2">
				<?php
				the_content(
					sprintf(
						/* translators: %s = post title */
						__( 'Continue reading<span class="screen-reader-text">"%s"</span>', 'lerm' ),
						get_the_title()
					)
				);
				?>
			</div>
		<?php else : ?>

		<?php endif; ?>
		<footer class="entry-footer">
			<small class="entry-info">
				<div class="entry-tags pb-2">
					<?php lerm_post_tag(); ?>
				</div><!-- .entry-tags -->
			</small>
		</footer>
