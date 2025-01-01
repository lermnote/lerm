<?php
/**
 * Template Name: Links
 *
* @package Lerm https://lerm.net
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
	<?php get_template_part( 'template-parts/breadcrumb' ); ?>
	<div <?php lerm_row_class(); ?>><!--.row-->
		<div <?php lerm_column_class(); ?>><!--.col-md-12 .col-lg-8-->
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
										<div class="card mb-4">

											<h2 class="card-header">
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
												// $grap_favicon = grap_favicon(
												// array(
												// 'URL'  => $link_term->link_url,
												// 'SAVE' => false,
												// 'DIR'  => './',
												// 'TRY'  => true,
												// )
												// );

												$link_name        = sprintf( '<h5 class="card-title m-0">%s</h5>', esc_html( $link_term->link_name ) );
												$link_description = sprintf( '<div class="card-body"><p class="card-text">%s</p></div>', esc_html( $link_term->link_description ) );
												$link_image       = sprintf( '<img src="%s" class="me-auto p-2 border rounded-3 " alt="%s" style="height:3rem;width:3rem;overflow: hidden;">%s', '$grap_favicon', esc_html( $link_term->link_name ), $link_name );
												$link_card       .= sprintf( '<li class="col-3 col-sm-3 col-md-3 col-lg-auto text-center my-2 py-2"><a class="text-dark h-100" href="%s" target="%s">%s</a></li>', esc_html( $link_term->link_url ), esc_html( $link_term->link_target ), $link_image, $link_description );
											}
											echo sprintf( '<ul class="row list-unstyled card-body">%s</ul>', $link_card ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											?>
											</div>
										<?php
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
<?php
get_footer();
