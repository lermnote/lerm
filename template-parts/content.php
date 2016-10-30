<?php
/**
 * The template part for displaying content
 *
 * @package Lerm
 * @since Lerm 2.0
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('card card-block summary'); ?>>
	<?php lerm_thumbnail(); ?>
	<?php the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>

	<?php lerm_excerpt(); ?>

	<small class="summary-meta">

		<?php lerm_entry_meta() ?>

	</small><!-- .summary-meta -->
</article><!-- #post-## -->
