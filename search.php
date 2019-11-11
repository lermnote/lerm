<?php
/**
 * This is the most generic template file in a WordPress theme and one
 * of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query,
 * e.g., it puts together the home page when no home.php file exists.
 *
 * @author lerm http://lerm.net
 * @since  1.0
 */

get_header();
?>
<main role="main" class="container">

	<?php $class = ( 'layout-1c-narrow' === lerm_page_layout() ) ? 'justify-content-md-center' : ''; ?>
	<div class="row <?php echo esc_attr( $class ); ?> ">

		<?php $class = ( wp_is_mobile() || 'layout-1c' === lerm_page_layout() ) ? 'col-md-12' : 'col-lg-8'; ?>
		<div class="<?php echo esc_attr( $class ); ?>  px-0" >

			<div id="main" class="site-main ajax-posts">
				<header>
					<?php if ( have_posts() ) : ?>
						<h1 class="page-title p-3 bg-white "><?php printf( esc_attr_e( 'Search results for: ', 'lerm' ) . ' "%1s" ', '<span>' . get_search_query() . '</span>' ); ?></h1>
					<?php else : ?>
						<h1 class="page-title p-3 bg-white "><?php esc_attr_e( 'Nothing Found', 'lerm' ); ?></h1>
					<?php endif; ?>
				</header>
				<?php
				if ( have_posts() ) :
					?>
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template/content/content', 'excerpt' );
					endwhile;
					?>
				<?php else : ?>
					<section class="bg-white p-3">
						<p><?php esc_attr_e( 'Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'twentyseventeen' ); ?></p>
						<?php get_search_form(); ?>
					</section>
				<?php endif; ?>

			</div>
			<div class="mt-3">
				<?php
				global $wp_query;
				if ( $wp_query->max_num_pages > 1 && ( lerm_options( 'load_more' ) || wp_is_mobile() ) ) :
					?>
					<button class='btn btn-custom btn-block more-posts' data-page="1"><?php esc_html_e( 'Load More', 'lerm' ); ?></button>
					<?php
				else :
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
