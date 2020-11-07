<?php
/**
 * Template Name: Links
 *
 * @author Lerm http://www.hanost.com
 * @date    2016-09-02
 * @since   lerm 2.0
 */
get_header();
$link_categories = get_terms(
	array(
		'taxonmomy' => 'link_category',
	)
);
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
			<div class="site-main">
				<?php
				if ( have_posts() ) :
					while ( have_posts() ) :
						the_post();
						?>
						<article id="post-<?php the_ID(); ?>"  <?php post_class( 'entry px-3' ); ?>>
							<div class="content-area">
								<header class="entry-header text-center pb-2">
									<?php the_title( '<h1 class="entry-title p-3">', '</h1>' ); ?>
								</header>
								<div class="entry-content pt-2">
									<?php
									the_content(
										sprintf(
											__( 'Continue reading', 'lerm' ) . '<span class="screen-reader-text">%s</span>',
											get_the_title()
										)
									);
									?>
									<?php
									foreach ( $link_categories as $link_category ) {
										$link_card = '';
										?>
										<h2 class="link-title text-center">
											<?php echo esc_html( $link_category->name ); ?>
										</h2>
										<?php
										$link_terms = get_bookmarks(
											array(
												'orderby'  => 'name',
												'category' => $link_category->term_id,
											)
										);
										foreach ( $link_terms as $link_term ) {
											$grap_favicon     = grap_favicon(
												array(
													'URL'  => $link_term->link_url,
													'SAVE' => true,
													'DIR'  => './',
													'TRY'  => true,
													'DEV'  => null,
												)
											);
											$link_name        = sprintf( '<h5 class="card-title m-0">%s</h5>', esc_html( $link_term->link_name ) );
											$link_description = sprintf( '<div class="card-body"><p class="card-text">%s</p></div>', esc_html( $link_term->link_description ) );
											$link_image       = sprintf( '<div class="card-header text-center d-flex justify-content-center align-items-center"><img src="%s" class="mr-1" alt="%s" style="height:1.5rem;width:1.5rem;overflow: hidden;">%s</div>', $grap_favicon, esc_html( $link_term->link_name ), $link_name );
											$link_card       .= sprintf( '<div class="col mb-3"><div class="card h-100 link-card"><a class="text-dark h-100" href="%s" target="%s">%s%s</a></div></div>', esc_html( $link_term->link_url ), esc_html( $link_term->link_target ), $link_image, $link_description );
										}
										echo sprintf( '<div class="row row-cols-1 row-cols-md-4">%s</div>', $link_card ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									}
									?>
									<div class="py-3 clearfix">
										<?php lerm_link_pagination(); ?>
									</div>
								</div>
							</div>
						</article>
					<?php endwhile; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php get_sidebar(); ?>
	</div><!--.row-->
</main><!--.container-->
<?php
get_footer();
