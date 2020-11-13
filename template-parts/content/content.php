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
    <div class="content-area">

        
		<?php if ( is_singular() ): ?>
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
		<?php else : ?>
			<div class="row no-gutters">
				<div class="col-md-4">
				<?php
				lerm_thumb_nail(
					array(
						'classes' => 'card-img post-thumbnail',
						'height'  => '110',
						'width'   => '180',
					)
				);
				?>
				</div>
				<div class="col-md-8">
				<div class="card-body">
					<h5 class="card-title">Card title</h5>
					<p class="card-text">This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.</p>
					<p class="card-text"><small class="text-muted">Last updated 3 mins ago</small></p>
				</div>
				</div>
			</div>

        <?php endif; ?>
    </div>
</article>