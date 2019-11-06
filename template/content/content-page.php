<?php
/**
 * The template part for displaying content
 *
 * @package Lerm
 * @since Lerm 2.0
 */
?>
<article id="post-<?php the_ID(); ?>"  <?php post_class( 'entry p-3' ); ?>>
	<header class="entry-header text-center pb-2">
		<?php the_title( '<h1 class="entry-title p-3">', '</h1>' ); ?>
	</header>
	<div class="entry-content pt-2">
		<?php
			the_content(
				sprintf(
					__( 'Continue reading<span class="screen-reader-text">"%s"</span>', 'lerm' ),
					get_the_title()
				)
			);
			?>
		<?php
		wp_link_pages(
			array(
				'before'      => '<div class="page-links"><span class="page-links-title">' . __( 'Pages:', 'lerm' ) . '</span>',
				'after'       => '</div>',
				'link_before' => '<span>',
				'link_after'  => '</span>',
				'pagelink'    => '<span class="screen-reader-text">' . __( 'Page', 'lerm' ) . ' </span>%',
				'separator'   => '<span class="screen-reader-text">, </span>',
			)
		);
		?>
	</div>
	<footer class="donate">
	<!-- 捐助二维码 -->
	</footer>
</article>
