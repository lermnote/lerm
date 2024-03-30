<?php
/**
 * Template Name: Page Template
 *
 * @package Lerm https://lerm.net
 * 
 * @since  1.0
 */
get_header();
?>
<main role="main" class="container"><!--.container-->
	<?php get_template_part( 'template-parts/components/breadcrumb' ); ?>
	<div <?php lerm_row_class(); ?>><!--.row-->
		<div id="primary" <?php lerm_column_class(); ?>><!--.col-md-12 .col-lg-8-->
			<div class="site-main">
				<?php
				if ( have_posts() ) :
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/content/content', 'page' );

						// If comments are open or we have at least one comment, load up the comment template.
						if ( comments_open() || get_comments_number() ) {
							comments_template();
						}
					endwhile;
					?>
				<?php endif; ?>
			</div>
		</div>
		<?php get_sidebar(); ?>
	</div><!--.row-->
</main><!--.container-->
<?php
get_footer();
