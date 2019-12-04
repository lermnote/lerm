<?php
/**
 * The template for displaying all single posts and attachments
 *
 * @author Lerm https://www.hanost.com
 * @since  1.0
 */
get_header();
?>
<main role="main" class="container">
	<?php $class = ( 'layout-1c-narrow' === lerm_page_layout() ) ? 'justify-content-md-center' : ''; ?>
	<div class="row <?php echo esc_attr( $class ); ?>">

		<?php $class = ( wp_is_mobile() || 'layout-1c' === lerm_page_layout() ) ? 'col-md-12' : 'col-lg-8'; ?>
		<div class="px-0 <?php echo esc_attr( $class ); ?>">
			<div class="site-main">
				<?php
				if ( have_posts() ) :
					breadcrumb_trail();

					while ( have_posts() ) :
						the_post();
						get_template_part( 'template/content/content', 'single' );
						?>
							<?php lerm_entry_tag(); ?>
							<div class="entry-copyright content-bg p-3">
								<div><i class="fa fa-exclamation-triangle pr-2 "></i> <strong>版权声明：</strong> <span>本文由<a href="<?php the_permalink(); ?>" rel="bookmark" title="本文固定链接 <?php the_permalink(); ?>"> <?php bloginfo( 'name' ); ?> </a> 整理发表，转载请注明出处</span> </div>
								<div><i class="fa fa-bullseye pr-2 "></i> <strong>转载信息：</strong> <span><a href="<?php the_permalink(); ?>" rel="bookmark" title="本文固定链接 <?php the_permalink(); ?>"> <?php the_title(); ?> | <?php bloginfo( 'name' ); ?></a></span> </div>
							</div>
							<?php if ( lerm_options( 'related_posts' ) ) : ?>
								<section id="related" class="p-2 mt-2 mb-2 border-radus-none">
									<?php related_posts(); ?>
								</section>
								<?php
							endif;
							if ( lerm_options( 'post_navigation' ) ) {
								lerm_post_navigation();
							}

								// If comments are open or we have at least one comment, load up the comment template.
							if ( comments_open() || get_comments_number() ) :
								comments_template();
							endif;
						endwhile;
					lerm_pagination();
				endif;
				?>
			</div>
		</div>
		<?php get_sidebar(); ?>
	</div>
</main>
<?php
get_footer();
