<?php
/**
 * Template Name: Archives
 *
 * @package Lerm
 */

get_header();

$args  = array(
	'post_type'           => 'post',
	'posts_per_page'      => -1,
	'ignore_sticky_posts' => 1,
);
$query = new WP_Query( $args );
?>
	<?php get_template_part( 'template-parts/components/breadcrumb' ); ?>
	<div <?php lerm_row_class(); ?>><!--.row-->
		<div id="primary" <?php lerm_column_class(); ?>><!--.col-md-12 .col-lg-8-->
			<div class="site-main">
				<?php
				if ( have_posts() ) :
					while ( have_posts() ) :
						the_post();
						?>
						<article id="post-<?php the_ID(); ?>" <?php post_class( 'card' ); ?>>
							<header class="entry-header d-flex flex-column text-center mb-md-2">
								<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
								<small class="entry-meta">
									<?php esc_html_e( 'Tip: click a month to expand it.', 'lerm' ); ?>
								</small>
							</header>

							<?php the_content(); ?>
							<div id="archives" class="archives-page">
								<button type="button" class="btn btn-success" id="al_expand_collapse" style="margin-bottom:1rem">
									<?php esc_html_e( 'Expand/Collapse All', 'lerm' ); ?>
								</button>
								<?php
								$posts_rebuild = array();

								while ( $query->have_posts() ) :
									$query->the_post();

									$year  = get_the_date( _x( 'Y', 'yearly archives date format', 'lerm' ) );
									$month = get_the_date( _x( 'm', 'monthly archives date format', 'lerm' ) );
									$day   = get_the_date( _x( 'd', 'daily archives date format', 'lerm' ) );

									$posts_rebuild[ $year ][ $month ][ $day ] = sprintf(
										'<span class="entry-published">%s</span><a href="%s">%s <span class="badge bg-primary">%s</span></a>',
										$day,
										get_permalink(),
										get_the_title(),
										get_comments_number( '0', '1', '%' )
									);
								endwhile;
								wp_reset_postdata();
								foreach ( $posts_rebuild as $key_y => $y ) {
									?>
									<h2 class="year-list">
										<?php echo esc_html( $key_y ); ?>
									</h2>
									<ul class="list-unstyled month-list">
										<?php
										foreach ( $y as $key_m => $m ) {
											$items = '';
											$i     = 0;
											foreach ( $m as $p ) {
												++$i;
												$items .= '<li class="list-group-item d-flex justify-content-between align-items-center archives-post">' . $p . '</li>';
											}
											?>
											<li class="list-item">
												<span class="month-post-list">
													<?php echo esc_html( $key_m ); ?>
													<label class="badge bg-danger">
														<?php echo esc_html( (string) $i ); ?>
													</label>
												</span>
												<ul class="list-group post-list">
													<?php echo wp_kses_post( $items ); ?>
												</ul>
											</li>
											<?php
										}
										?>
									</ul>
									<?php
								}
								?>
							</div>

						</article><!-- #post-## -->
						<?php
						if ( comments_open() || get_comments_number() ) {
							comments_template();
						}

					endwhile;
					?>
				<?php endif; ?>
			</div>
		</div>
		<?php get_sidebar(); ?>
	</div><!--.row-->
<?php
get_footer();
