<?php
/**
 * The template part for displaying content
 *
 * @package Lerm
 * @since Lerm 2.0
 */
global $post;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'card' ); ?>>

	<?php if ( is_singular() ) : ?>
		<div class="content-area">
			<?php get_template_part( 'template-parts/header/entry-header' ); ?>

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
		</div>
	<?php endif; ?>

</article>
