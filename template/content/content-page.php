<?php
/**
 * The template part for displaying content
 *
 * @package Lerm
 * @since Lerm 2.0
 */
?>
<article id="post-<?php the_ID(); ?>"  <?php post_class(); ?>>
	<header class="entry-header text-center pb-2">
		<?php the_title( '<h1 class="entry-title p-3">', '</h1>' ); ?>
	</header>
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
	<div class="py-3 clearfix">
		<?php lerm_link_pagination(); ?>
	</div>
	<footer class="donate">
	<!-- 捐助二维码 -->
	</footer>
</article>
