<?php
/**
 * The template for displaying archive pages
 *
 * @author lerm https://www.hanost.com
 * @package Lerm
 */

get_header();
?>
<main role="main" class="container"><!--.container-->
	<div <?php lerm_row_class(); ?>><!--.row-->
		<div <?php lerm_column_class(); ?>><!--.col-md-12 .col-lg-8-->
			<div id="main" class="site-main ajax-posts" data-page="<?php echo get_query_var( 'paged' ) ? esc_attr( get_query_var( 'paged' ) ) : 1; ?>" data-max="<?php echo esc_attr( $wp_query->max_num_pages ); ?>">
				<header class="archive-header card mb-2 p-3">
					<?php if ( have_posts() ) : ?>
						<h1 class="page-title p-3 bg-white "><?php printf( esc_html_e( 'Search results for: ', 'lerm' ) . ' "%1$s" ', '<span>' . get_search_query() . '</span>' ); ?></h1>
					<?php else : ?>
						<h1 class="page-title p-3 bg-white "><?php esc_html_e( 'Nothing Found', 'lerm' ); ?></h1>
					<?php endif; ?>
				</header>
				<?php
				if ( have_posts() ) :
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/content/content', get_post_format() );
					endwhile;
				else :
					?>
					<section class="bg-white p-3">
						<p><?php esc_html_e( 'Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'lerm' ); ?></p>
						<?php get_search_form(); ?>
					</section>
				<?php endif; ?>
			</div>

			<?php get_template_part( 'template-parts/components/pagination' ); ?>
		</div>
		<?php get_sidebar(); ?>
		</div><!--.row-->
</main><!--.container-->
<?php
get_footer();
