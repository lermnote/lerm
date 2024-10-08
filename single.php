<?php
/**
 * The template for displaying all single posts and attachments
 *
 * @package Lerm https://lerm.net
 * @since  1.0
 */
get_header();
?>
<main role="main" class="container"><!--.container-->
	<?php get_template_part( 'template-parts/components/breadcrumb' ); ?>
	<div <?php lerm_row_class(); ?> ><!--.row-->
		<div id="primary" <?php lerm_column_class(); ?> ><!--.col-md-12 .col-lg-8-->
			<div class="site-main">
				<?php
				if ( have_posts() ) :
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/content/content', get_post_type() );
						?>
						<ul class="card entry-copyright p-3 mb-2 list-unstyled">
							<li><strong>版权声明：</strong> <span>本文由<a href="<?php the_permalink(); ?>" rel="bookmark" title="本文固定链接 <?php the_permalink(); ?>"> <?php bloginfo( 'name' ); ?> </a> 整理发表，转载请注明出处</span> </li>
							<li><strong>转载信息：</strong> <span><a href="<?php the_permalink(); ?>" rel="bookmark" title="本文固定链接 <?php the_permalink(); ?>"> <?php the_title(); ?> | <?php bloginfo( 'name' ); ?></a></span> </li>
						</ul>
						<?php if ( lerm_options( 'related_posts' ) ) : ?>
							<!-- <section id="related" class="card mb-2"> -->
								<?php get_template_part( 'template-parts/related-posts' ); ?>
							<!-- </section> -->
							<?php
						endif;
						get_template_part( 'template-parts/components/navigation' );
						// If comments are open or we have at least one comment, load up the comment template.
						if ( comments_open() || get_comments_number() ) :
							comments_template();
						endif;
					endwhile;
				endif;
				?>
			</div>
		</div>
	<?php get_sidebar(); ?>
	</div><!--.row-->
</main><!--.container-->
<?php
get_footer();
