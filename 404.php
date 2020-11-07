<?php
/**
 * The template for displaying the 404 template in the Twenty Twenty theme.
 *
 * @date    2016-10-26
 * @since   2.0
 * @package https://www.hanost.com
 */

get_header();
?>
<main role="main" class="container"><!--.container-->
	<?php
	if ( ( 'layout-1c-narrow' !== lerm_site_layout() ) ) {
		breadcrumb_trail();
	}
	?>
	<div <?php lerm_row_class(); ?>><!--.row-->
		<div <?php lerm_column_class(); ?>><!--.col-md-12 .col-lg-8-->
		<?php
		if ( ( 'layout-1c-narrow' === lerm_site_layout() ) ) {
			breadcrumb_trail();
		}
		?>
			<div class="site-main card">
				<h1 class="entry-header text-center pb-2"><?php esc_html_e( 'Page Not Found', 'lerm' ); ?></h1>

				<?php get_template_part( 'template/content/content', 'none' ); ?>
			</div>
		</div>
		<?php get_sidebar(); ?>
	</div><!--.row-->
</main><!--.container-->
<?php
get_footer();
