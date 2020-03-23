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
$row_class    = ( 'layout-1c-narrow' === lerm_page_layout() ) ? 'justify-content-md-center' : '';
$colunm_class = ( wp_is_mobile() || 'layout-1c' === lerm_page_layout() ) ? 'col-md-12' : 'col-lg-8';
?>
<main role="main" class="container mt-md-3"><!--.container-->

	<div class="row <?php echo esc_attr( $row_class ); ?> "><!--.row-->

		<div class="<?php echo esc_attr( $colunm_class ); ?>  px-0" ><!--.col-md-12 .col-lg-8-->
			<?php if ( is_home() && ! is_paged() && lerm_options( 'slide_position' ) === 'above_entry_list' ) : ?>
				<div class="mb-2">
					<?php lerm_carousel( array() ); ?>
				</div>
			<?php endif; ?>

			<div id="main" class="site-main ajax-posts">

				<?php
				if ( have_posts() ) :
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template/content/content', 'excerpt' );
					endwhile;
				endif;
				?>

			</div><!--.col-md-12 .col-lg-8-->
			<div class="px-3 px-md-0">
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
	</div><!--.row-->
</main><!--.container-->
<?php
get_footer();
