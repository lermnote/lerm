<?php
/**
 * Template Name: 页面模板
 * @authors lerm http://lerm.net
 * @date    2016-10-26
 * @since lerm 2.0
 */
get_header(); ?>
<main role="main" class="container">
	<?php $class = ( 'layout-1c-narrow' === lerm_page_layout() ) ? 'justify-content-md-center' : ''; ?>
	<div class="row <?php echo esc_attr( $class ); ?> ">
		<?php $class = wp_is_mobile() ? 'col-md-12' : 'col-lg-8'; ?>
		<div class="<?php echo esc_attr( $class ); ?> px-0">
			<div class="site-main">
				<?php if ( have_posts() ) : ?>
						<?php
						while ( have_posts() ) :
							the_post();
							get_template_part( 'template/content/content', 'page' );
							// If comments are open or we have at least one comment, load up the comment template.
							if ( comments_open() || get_comments_number() ) :
								comments_template();
							endif;
						endwhile;
						?>
					<div class="py-3">
						<?php lerm_pagination(); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php get_sidebar(); ?>
	</div>
</main>
<?php
get_footer();


add_action( 'admin_print_footer_scripts', 'lerm_add_quicktags' );
