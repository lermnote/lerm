<?php
/**
 * Template Name: Full Width
 * Template Post Type: post, page
 *
* @package Lerm https://lerm.net
 * @date   2016-10-26
 * @since lerm 3.0
 */
get_header();?>
<main role="main" class="container-fluid"><!--.container-->
	<?php get_template_part( 'template-parts/breadcrumb' ); ?>
	<div class="row justify-content-md-center"><!--.row-->
		<div class="col-md-12 px-1 px-md-0" ><!--.col-md-12 .col-lg-8-->
			<div class="site-main">
				<?php
				if ( have_posts() ) :
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/content/content', 'page' );
						// If comments are open or we have at least one comment, load up the comment template.
						if ( comments_open() || get_comments_number() ) :
							comments_template();
						endif;
					endwhile;
					?>
				<?php endif; ?>
			</div>
		</div>
	</div><!--.row-->
</main><!--.container-->
<?php
get_footer();
