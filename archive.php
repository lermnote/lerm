<?php
/**
 * The template for displaying archive pages
 *
 * @author lerm https://www.hanost.com
 * @package Lerm
 */

get_header();
// $lerm_term = get_queried_object();
// if ( $lerm_term ) {
// $meta = get_term_meta( $lerm_term->term_id, 'lerm_taxonomy_options', true );
// }
// $bg_color       = isset( $meta['archive_color']['bg_color'] ) ? $meta['archive_color']['bg_color'] : '#fff';
// $font_color     = isset( $meta['archive_color']['font_color'] ) ? $meta['archive_color']['font_color'] : '#5d6777';
// $bg_image       = isset( $meta['archive_header_image'] ) ? 'url(' . $meta['archive_header_image']['url'] . ')' : '';
// $archive_header = sprintf( 'background: %s %s ; color: %s', $bg_image, $bg_color, $font_color );
?>
<main role="main" class="container"><!--.container-->
<?php get_template_part( 'template-parts/breadcrumb' ); ?>
	<div <?php lerm_row_class(); ?>><!--.row-->
		<div <?php lerm_column_class(); ?>><!--.col-md-12 .col-lg-8-->
			<div id="main" class="site-main ajax-posts" data-page="<?php echo get_query_var( 'paged' ) ? esc_attr( get_query_var( 'paged' ) ) : 1; ?>" data-max="<?php echo esc_attr( $wp_query->max_num_pages ); ?>">
				<?php if ( have_posts() ) : ?>
					<header class="archive-header card mb-2 p-3" style="<?php // echo esc_attr( $archive_header ); ?>">
						<?php
						the_archive_title( '<h1 class="page-title">', '</h1>' );
						the_archive_description( '<div class="taxonomy-description small">', '</div>' );
						?>
					</header>
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/content/content', get_post_format() );
					endwhile;
				endif;
				?>
			</div>

			<?php get_template_part( 'template-parts/pagination' ); ?>
		</div>
		<?php get_sidebar(); ?>
		</div><!--.row-->
</main><!--.container-->
<?php
get_footer();
