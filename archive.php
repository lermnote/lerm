<?php
/**
 * The template for displaying archive pages
 *
 * @date    2016-10-26
 * @since   2.0
 * @package https://www.hanost.com
 */

get_header();
$term = get_queried_object();
if ( $term ) {
	$meta = get_term_meta( $term->term_id, 'lerm_taxonomy_options', true );
}
	$bg_color       = isset( $meta['archive_color']['bg_color'] ) ? $meta['archive_color']['bg_color'] : '#fff';
	$font_color     = isset( $meta['archive_color']['font_color'] ) ? $meta['archive_color']['font_color'] : '#5d6777';
	$bg_image       = isset( $meta['archive_header_image'] ) ? 'url(' . $meta['archive_header_image']['url'] . ')' : '';
	$archive_header = sprintf( 'background: %s %s ; color: %s', $bg_image, $bg_color, $font_color );
?>
<main role="main" class="container">
	<?php $class = ( 'layout-1c-narrow' === lerm_page_layout() ) ? 'justify-content-md-center' : ''; ?>
	<div class="row <?php echo esc_attr( $class ); ?> ">

	<?php $class = ( wp_is_mobile() || 'layout-1c' === lerm_page_layout() ) ? 'col-md-12' : 'col-lg-8'; ?>
		<div class="<?php echo esc_attr( $class ); ?>  px-0" >
			<div id="main" class="site-main ajax-posts">
				<?php if ( have_posts() ) : ?>
						<header class="archive-header mb-2 p-3" style="<?php echo $archive_header; ?>">
						<?php
						the_archive_title( '<h1 class="page-title">', '</h1>' );
						the_archive_description( '<small class="taxonomy-description">', '</small>' );
						?>
					</header>
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template/content/content', 'excerpt' );
					endwhile;
				endif;
				?>
			</div>
			<div class="mt-3">
				<?php global $wp_query;
					if (  $wp_query->max_num_pages > 1 && (lerm_options( 'load_more', '' ) || wp_is_mobile()) ) : ?>
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
