<?php
/**
 * The template part for displaying content
 *
 * @package Lerm
 * @since Lerm 2.0
 */
global $post;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'col' ); ?>>
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

        <div class="<?php echo esc_attr( $summary_col ); ?> d-flex">
            <div class="card-body d-flex flex-column justify-content-between p-3 p-sm-1 p-md-0">
                <h2 class=" card-title entry-title">
                    <?php
						the_title( '<a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a>' );
						?>
                    <?php if ( is_sticky() ) { ?>
                    <label class="sticky-label badge badge-danger"><?php echo esc_html__( 'Sticky', 'lerm' ); ?></label>
                    <?php } ?>
                </h2>
                <div class="d-sm-none d-md-block text-muted">
                    <?php
						if ( $post_title ) :
							the_excerpt();
						else :
							the_content();
						endif;
						?>
                </div>
                <?php
					if ( $post_title ) :
						lerm_post_meta( 'summary_bottom' );
					endif;
					?>
            </div>
        </div>

        <?php endif; ?>
        <footer class="entry-footer">
            <small class="entry-info">
                <div class="entry-tags pb-2">
                    <?php lerm_post_tag(); ?>
                </div><!-- .entry-tags -->
            </small>
        </footer>
    </div>
</article>