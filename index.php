<?php
/**
 * This is the most generic template file in a WordPress theme and one
 * of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query,
 * e.g., it puts together the home page when no home.php file exists.
 *
 * @author lerm https://www.hanost.com
 * @package Lerm
 */

get_header();
$breadcrumb = new \Lerm\Inc\Breadcrumb();
$carousel   = new \Lerm\Inc\Carousel();
?>

<main role="main" class="container"><!--.container-->
	<?php $breadcrumb->trail(); ?>
	<div <?php lerm_row_class(); ?>><!--.row-->
		<div <?php lerm_column_class(); ?>><!--.col-md-12 .col-lg-8-->
			<?php
			if ( is_home() && ! is_paged() && lerm_options( 'slide_position' ) === 'above_entry_list' ) :
				$carousel->render();
			endif;
			?>
			<div id="main" class="site-main ajax-posts" data-page="<?php echo get_query_var( 'paged' ) ? esc_attr( get_query_var( 'paged' ) ) : 1; ?>" data-max="<?php echo esc_attr( $wp_query->max_num_pages ); ?>">
				<?php
				if ( have_posts() ) :
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/content/content', get_post_type() );
					endwhile;
				endif;
				?>
			</div><!--.site-main-->
			<?php get_template_part( 'template-parts/pagination' ); ?>
		</div>
		<?php get_sidebar(); ?>
	</div><!--.row-->
</main><!--.container-->
<?php
get_footer();
