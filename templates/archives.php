<?php
/**
 * Template Name: Archives
 *
 * @package Lerm
 */

use function Lerm\Support\lerm_breadcrumb;

/**
 * 归档数据结构：
 * [
 *   'Y' => [
 *     'm' => [
 *       ['title'=>string, 'url'=>string, 'day'=>string, 'comments'=>int],
 *       …
 *     ],
 *   ],
 * ]
 *
 * @return array<string, array<string, list<array{title:string, url:string, day:string, comments:int}>>>
 */
function lerm_build_archive_data(): array {
	$cache_key = 'lerm_archives_v1';
	$cached    = get_transient( $cache_key );

	if ( is_array( $cached ) && ! empty( $cached ) ) {
		return $cached;
	}

	$query = new WP_Query(
		array(
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'ignore_sticky_posts'    => true,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,  // 跳过 SQL COUNT(*)，不需要分页
			'update_post_meta_cache' => false, // 不读取 post meta 缓存
			'update_post_term_cache' => false, // 不读取 term 缓存
		)
	);

	$data = array();

	foreach ( $query->posts as $post ) {
		$year  = get_the_date( 'Y', $post );
		$month = get_the_date( 'm', $post );

		// 仅存储原始数据，HTML 在渲染时生成，保持数据与视图分离
		$data[ $year ][ $month ][] = array(
			'title'    => get_the_title( $post ),
			'url'      => get_permalink( $post ),
			'day'      => get_the_date( 'd', $post ),
			'comments' => (int) get_comments_number( $post ),
		);
	}

	set_transient( $cache_key, $data, 12 * HOUR_IN_SECONDS );

	return $data;
}

$archive_data = lerm_build_archive_data();

get_header();
lerm_breadcrumb();
?>

<div <?php lerm_row_class(); ?>>
	<div id="primary" <?php lerm_column_class(); ?>>
		<div class="site-main">

			<?php
			if ( have_posts() ) :
				the_post();
				?>

				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

					<header class="entry-header d-flex flex-column text-center mb-md-2">
						<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
						<small class="entry-meta text-muted">
							<?php esc_html_e( 'Tip: click a month to expand it.', 'lerm' ); ?>
						</small>
					</header>

					<?php the_content(); ?>

					<div id="archives" class="archives-page">

						<?php if ( empty( $archive_data ) ) : ?>

							<p class="text-muted"><?php esc_html_e( 'No posts found.', 'lerm' ); ?></p>

						<?php else : ?>

							<?php
							foreach ( $archive_data as $year => $months ) :
								$year_count = array_sum( array_map( 'count', $months ) );
								?>

								<section class="archives-year card mb-3">

									<h2 class="card-header mb-0 d-flex justify-content-between align-items-center fs-5">
										<span><?php echo esc_html( (string) $year ); ?></span>
										<span class="badge text-bg-secondary">
											<?php
											echo esc_html(
												sprintf(
													/* translators: %d: post count in a year. */
													_n( '%d post', '%d posts', $year_count, 'lerm' ),
													$year_count
												)
											);
											?>
										</span>
									</h2>

									<ul class="list-unstyled card-body py-2 mb-0">

										<?php
										foreach ( $months as $month_num => $posts ) :
											$list_id    = sprintf(
												'archives-%s-%s',
												sanitize_html_class( (string) $year ),
												sanitize_html_class( (string) $month_num )
											);
											$month_name = date_i18n( 'F', mktime( 0, 0, 0, (int) $month_num, 1 ) );
											$post_count = count( $posts );
											?>

											<li class="mb-1">

												<button
													type="button"
													class="archives-month-btn btn btn-link p-0 w-100 text-start text-decoration-none d-flex align-items-center"
													data-archives-toggle
													data-bs-toggle="collapse"
													data-bs-target="#<?php echo esc_attr( $list_id ); ?>"
													aria-expanded="false"
													aria-controls="<?php echo esc_attr( $list_id ); ?>">
													<span class="month-name"><?php echo esc_html( $month_name ); ?></span>
													<span class="archives-toggle-icon" aria-hidden="true"></span>
													<span class="badge rounded-pill text-bg-danger ms-auto">
														<?php
														echo esc_html(
															sprintf(
																/* translators: %d: post count in a month. */
																_n( '%d post', '%d posts', $post_count, 'lerm' ),
																$post_count
															)
														);
														?>
													</span>
												</button>

												<ul class="post-list collapse mt-1 ps-3" id="<?php echo esc_attr( $list_id ); ?>">
													<?php foreach ( $posts as $post_item ) : ?>
														<li class="archives-post d-flex align-items-center">
															<span class="entry-published me-2 text-muted small">
																<?php echo esc_html( $post_item['day'] ); ?>
															</span>
															<a href="<?php echo esc_url( $post_item['url'] ); ?>" class="flex-grow-1">
																<?php echo esc_html( $post_item['title'] ); ?>
															</a>
															<?php if ( $post_item['comments'] > 0 ) : ?>
																<span class="badge rounded-pill text-bg-primary ms-1">
																	<?php echo esc_html( (string) $post_item['comments'] ); ?>
																</span>
															<?php endif; ?>
														</li>
													<?php endforeach; ?>
												</ul>

											</li>

										<?php endforeach; ?>

									</ul>

								</section>

							<?php endforeach; ?>

						<?php endif; ?>

					</div><!-- #archives -->

				</article>

				<?php
				if ( comments_open() || get_comments_number() ) :
					comments_template();
				endif;

			endif;
			?>

		</div>
	</div>
	<?php get_sidebar(); ?>
</div>

<?php
get_footer();