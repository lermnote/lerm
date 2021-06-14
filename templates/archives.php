<?php
/**
 * Template Name: 归档模板
 *
 * @author lerm https://www.hanost.com
 * @package Lerm
 */
get_header();
$breadcrumb = new \Lerm\Inc\Breadcrumb();
$args       = array(
	'post_type'           => 'post',
	'posts_per_page'      => -1,
	'ignore_sticky_posts' => 1,
);

$the_query = new WP_Query( $args );
?>
<main role="main" class="container"><!--.container-->
	<?php
	if ( ( 'layout-1c-narrow' !== lerm_site_layout() ) ) {
		$breadcrumb->trail();
	}
	?>
	<div <?php lerm_row_class(); ?>><!--.row-->
		<div <?php lerm_column_class(); ?>><!--.col-md-12 .col-lg-8-->
			<?php
			if ( ( 'layout-1c-narrow' === lerm_site_layout() ) ) {
				$breadcrumb->trail();
			}
			?>
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
									【注: 点击月份可以展开】
								</small>
							</header>

							<?php the_content(); ?>
							<div id="archives" class="archives-page">
								<button type="button" class="btn btn-success" id="al_expand_collapse" style="margin-bottom:1rem">
									全部展开/收缩
								</button>
								<?php
								$posts_rebuild = array();
								$year          = 0;
								$mon           = 0;

								while ( $the_query->have_posts() ) :
									$the_query->the_post();

									$year  = get_the_date( _x( 'Y', 'yearly archives date format', 'lerm' ) );
									$month = get_the_date( _x( 'm', 'monthly archives date format', 'lerm' ) );
									$day   = get_the_date( _x( 'd', 'daily archives date format', 'lerm' ) );

									$posts_rebuild[ $year ][ $month ][ $day ] = sprintf(
										'<span class="entry-published">%s</span><a href="%s" >%s <span class="badge bg-primary">%s</span></a>',
										$day,
										get_permalink(),
										get_the_title(),
										get_comments_number( '0', '1', '%' )
									);
                                    var_dump(get_the_title());
								endwhile;
								wp_reset_postdata();
								foreach ( $posts_rebuild as $key_y => $y ) {
									?>
									<h2 class="year-list">
										<?php echo $key_y; ?>
									</h2>
									<ul class="list-unstyled month-list">
										<?php
										foreach ( $y as $key_m => $m ) {
											$posts = '';
											$i     = 0;
											foreach ( $m as $p ) {
												++$i;
												$posts .= '<li class="list-group-item d-flex justify-content-between align-items-center archives-post">' . $p . '</li>';
											}
											?>
											<li class="list-item">
												<span class="month-post-list">
												<?php echo $key_m; ?>
													<label class="badge bg-danger">
													<?php echo $i; ?>
													</label>
												</span>
												<ul class="list-group post-list">
												<?php echo $posts; ?>
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
							<?php //lerm_archives_list(); ?>

						</article><!-- #post-## -->
						<?php
						// If comments are open or we have at least one comment, load up the comment template.
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
</main><!--.container-->
<?php
get_footer();
