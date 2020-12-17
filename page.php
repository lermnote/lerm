<?php
/**
 * Template Name: Page Template
 *
 * @author lerm https://www.hanost.com
 * @package Lerm
 */
get_header();
$breadcrumb = new \Lerm\Inc\Breadcrumb();
?>
<main role="main" class="container"><!--.container-->
	<?php
	if ( ( 'layout-1c-narrow' !== lerm_site_layout() ) ) {
		$breadcrumb->trail();
	}
	?>
	<div <?php lerm_row_class(); ?>><!--.row-->
		<div <?php lerm_column_class(); ?>><!--.col-md-12 .col-lg-8-->
		<?php
		if ( ( 'layout-1c-narrow' === lerm_site_layout() ) ) {
			$breadcrumb->trail();
		}
		?>
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
