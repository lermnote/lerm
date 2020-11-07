<?php
/*
 * @Author: your name
 * @Date: 2020-04-15 11:05:36
 * @LastEditTime: 2020-04-19 21:49:01
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: \lerm\template-parts\content-image.php
 */
/**
 * The template part for displaying content
 *
 * @package Lerm
 * @since Lerm 2.0
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'lerm-format-image' ); ?> >
	<div class="content-area">
		<header class="entry-header d-flex flex-column justify-content-between mb-md-2" style="background-image: url(<?php echo sunset_get_attachment(); ?>);">
			<?php
			if ( is_single() ) {
				the_title( '<h1 class="entry-title">', '</h1>' );
			} else {
				the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a><label class="sticky-label badge badge-danger">' . __( 'Sticky', 'lerm' ) . '</label></h2>' );
			}
			?>
			<small class="entry-meta text-muted">
				<?php lerm_post_meta( 'summary_bottom' ); ?>
			</small>
		</header>

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
</article>
