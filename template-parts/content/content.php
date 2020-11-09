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

		<?php get_template_part('template-parts/header/entry-header') ?>


		<div class="entry-content pt-2">
			<?php the_excerpt(); ?>
		</div>

		<footer class="entry-footer">
			<small class="entry-info">
				<div class="entry-tags pb-2">
					<?php lerm_post_tag(); ?>
				</div><!-- .entry-tags -->
			</small>
		</footer>
