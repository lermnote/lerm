<?php
/**
 * Front page template (CMS homepage).
 *
 * Sections:
 * 1. Carousel / Hero slider
 * 2. Featured posts (sticky + recent)
 * 3. Category-based post grid
 * 4. Main post loop with sidebar
 *
 * @package Lerm
 * @since   5.0.0
 */

use Lerm\Support\Image;
use Lerm\View\PostMeta;

get_header();

$template_options = lerm_get_template_options();
$layout           = lerm_site_layout();
$show_thumbnail   = ! isset( $template_options['show_thumbnail'] ) || ! empty( $template_options['show_thumbnail'] );
$thumbnail_gallery = (array) ( $template_options['thumbnail_gallery'] ?? array() );
$excerpt_length   = (int) ( $template_options['excerpt_length'] ?? 95 );
$cat_exclude      = (array) ( $template_options['cat_exclude'] ?? array() );

// ─── Section 1: Carousel ────────────────────────────────────────────────────
if ( ! empty( $template_options['slide_enable'] ) ) :
	get_template_part( 'template-parts/components/carousel' );
endif;
?>

<main id="main" class="<?php echo esc_attr( implode( ' ', lerm_get_row_class( 'site-main' ) ) ); ?>">

<?php
// ─── Section 2: Featured / Sticky Posts ─────────────────────────────────────
$sticky_ids = get_option( 'sticky_posts', array() );

$featured_args = array(
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => 6,
	'ignore_sticky_posts' => false,
	'no_found_rows'       => true,
	'tax_query'           => array(
		array(
			'taxonomy' => 'post_format',
			'terms'    => array( 'post-format-quote', 'post-format-aside' ),
			'field'    => 'slug',
			'operator' => 'NOT IN',
		),
	),
);

// If we have sticky posts, show them as the featured section.
if ( ! empty( $sticky_ids ) && is_array( $sticky_ids ) ) {
	$featured_args['post__in']            = $sticky_ids;
	$featured_args['ignore_sticky_posts'] = true;
	$featured_args['posts_per_page']      = 6;
} else {
	// No sticky posts → use the latest posts.
	$featured_args['ignore_sticky_posts'] = true;
	$featured_args['posts_per_page']      = 6;

	// Exclude categories if configured.
	if ( ! empty( $cat_exclude ) ) {
		$featured_args['category__not_in'] = array_map( 'absint', $cat_exclude );
	}
}

$featured_query = new WP_Query( $featured_args );

if ( $featured_query->have_posts() ) :
	?>
	<section class="featured-posts mb-4">
		<?php if ( ! empty( $sticky_ids ) ) : ?>
			<h2 class="section-title h5 mb-3">
				<i class="fa fa-thumb-tack me-1" aria-hidden="true"></i>
				<?php esc_html_e( 'Featured', 'lerm' ); ?>
			</h2>
		<?php else : ?>
			<h2 class="section-title h5 mb-3">
				<i class="fa fa-newspaper-o me-1" aria-hidden="true"></i>
				<?php esc_html_e( 'Latest Posts', 'lerm' ); ?>
			</h2>
		<?php endif; ?>

		<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
			<?php
			while ( $featured_query->have_posts() ) :
				$featured_query->the_post();
				$current_post_id = get_the_ID();
				$image           = null;
				$has_image       = false;

				if ( $show_thumbnail ) {
					$image     = new Image(
						array(
							'post_id' => $current_post_id,
							'size'    => 'home-thumb',
							'lazy'    => 'lazy',
							'order'   => array( 'featured', 'block', 'scan', 'default' ),
							'default' => $thumbnail_gallery,
						)
					);
					$has_image = ! empty( $image->attachment_id );
				}
				?>
				<div class="col">
					<article id="post-<?php echo esc_attr( $current_post_id ); ?>" <?php post_class( 'card h-100' ); ?>>
						<?php if ( $has_image ) : ?>
							<a href="<?php echo esc_url( get_permalink( $current_post_id ) ); ?>" class="card-img-link" aria-hidden="true" tabindex="-1">
								<?php get_template_part( 'template-parts/components/featured-image' ); ?>
							</a>
						<?php endif; ?>
						<div class="card-body d-flex flex-column">
							<h3 class="card-title entry-title h6 mb-1">
								<?php the_title( '<a href="' . esc_url( get_permalink( $current_post_id ) ) . '" rel="bookmark">', '</a>' ); ?>
								<?php if ( is_sticky() ) : ?>
									<label class="sticky-label badge bg-danger ms-1" aria-hidden="true"><?php esc_html_e( 'Sticky', 'lerm' ); ?></label>
								<?php endif; ?>
							</h3>
							<div class="card-text text-muted small mt-auto">
								<?php echo wp_trim_words( get_the_excerpt(), $excerpt_length, '&hellip;' ); ?>
							</div>
						</div>
						<?php get_template_part( 'template-parts/layout/summary-footer' ); ?>
					</article>
				</div>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</section>
	<?php
endif;

// ─── Section 3: Category Sections ───────────────────────────────────────────
$featured_cat_ids = get_categories(
	array(
		'orderby'    => 'count',
		'order'      => 'DESC',
		'number'     => 3,
		'hide_empty' => true,
		'exclude'    => array_map( 'absint', $cat_exclude ),
	)
);

if ( ! empty( $featured_cat_ids ) && ! is_wp_error( $featured_cat_ids ) ) :
	foreach ( $featured_cat_ids as $category ) :
		$cat_query = new WP_Query(
			array(
				'cat'            => $category->term_id,
				'posts_per_page' => 4,
				'post_status'    => 'publish',
				'no_found_rows'  => true,
			)
		);

		if ( $cat_query->have_posts() ) :
			?>
			<section class="category-section mb-4">
				<h2 class="section-title h5 mb-3">
					<a href="<?php echo esc_url( get_category_link( $category ) ); ?>" class="text-decoration-none">
						<i class="fa fa-folder-o me-1" aria-hidden="true"></i>
						<?php echo esc_html( $category->name ); ?>
					</a>
					<small class="text-muted ms-2">(<?php echo absint( $category->count ); ?>)</small>
				</h2>

				<div class="row g-3">
					<?php
					// First post as a large featured card.
					$cat_query->the_post();
					$first_id    = get_the_ID();
					$first_image = null;
					$first_has   = false;

					if ( $show_thumbnail ) {
						$first_image = new Image(
							array(
								'post_id' => $first_id,
								'size'    => 'featured-thumb',
								'lazy'    => 'lazy',
								'order'   => array( 'featured', 'block', 'scan', 'default' ),
								'default' => $thumbnail_gallery,
							)
						);
						$first_has = ! empty( $first_image->attachment_id );
					}
					?>
					<div class="col-md-6">
						<article id="post-<?php echo esc_attr( $first_id ); ?>" <?php post_class( 'card h-100' ); ?>>
							<?php if ( $first_has ) : ?>
								<a href="<?php echo esc_url( get_permalink( $first_id ) ); ?>" class="card-img-link" aria-hidden="true" tabindex="-1">
									<?php get_template_part( 'template-parts/components/featured-image' ); ?>
								</a>
							<?php endif; ?>
							<div class="card-body">
								<h3 class="card-title entry-title h5">
									<?php the_title( '<a href="' . esc_url( get_permalink( $first_id ) ) . '" rel="bookmark">', '</a>' ); ?>
								</h3>
								<p class="card-text text-muted">
									<?php echo wp_trim_words( get_the_excerpt(), $excerpt_length, '&hellip;' ); ?>
								</p>
							</div>
							<?php get_template_part( 'template-parts/layout/summary-footer' ); ?>
						</article>
					</div>

					<div class="col-md-6">
						<div class="row row-cols-1 g-3">
							<?php
							while ( $cat_query->have_posts() ) :
								$cat_query->the_post();
								$post_id   = get_the_ID();
								$thumb     = null;
								$has_thumb = false;

								if ( $show_thumbnail ) {
									$thumb = new Image(
										array(
											'post_id' => $post_id,
											'size'    => 'home-thumb',
											'lazy'    => 'lazy',
											'order'   => array( 'featured', 'block', 'scan', 'default' ),
											'default' => $thumbnail_gallery,
										)
									);
									$has_thumb = ! empty( $thumb->attachment_id );
								}
								?>
								<div class="col">
									<article id="post-<?php echo esc_attr( $post_id ); ?>" <?php post_class( 'card' ); ?>>
										<div class="row g-0 align-items-center">
											<?php if ( $has_thumb ) : ?>
												<div class="col-4 col-sm-3">
													<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" aria-hidden="true" tabindex="-1">
														<?php get_template_part( 'template-parts/components/featured-image' ); ?>
													</a>
												</div>
											<?php endif; ?>
											<div class="<?php echo $has_thumb ? 'col-8 col-sm-9' : 'col-12'; ?>">
												<div class="card-body py-2">
													<h4 class="card-title entry-title h6 mb-1">
														<?php the_title( '<a href="' . esc_url( get_permalink( $post_id ) ) . '" rel="bookmark">', '</a>' ); ?>
													</h4>
													<?php get_template_part( 'template-parts/layout/summary-footer' ); ?>
												</div>
											</div>
										</div>
									</article>
								</div>
							<?php endwhile; ?>
						</div>
					</div>
				</div>
			</section>
			<?php
		endif;
		wp_reset_postdata();
	endforeach;
endif;

// ─── Section 4: Main Post Loop ──────────────────────────────────────────────
$main_args = array(
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'paged'               => get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : 1,
	'ignore_sticky_posts' => true,
	'tax_query'           => array(
		array(
			'taxonomy' => 'post_format',
			'terms'    => array( 'post-format-quote', 'post-format-aside' ),
			'field'    => 'slug',
			'operator' => 'NOT IN',
		),
	),
);

if ( ! empty( $cat_exclude ) ) {
	$main_args['category__not_in'] = array_map( 'absint', $cat_exclude );
}

$main_query = new WP_Query( $main_args );

if ( $main_query->have_posts() ) :
	?>
	<section class="main-loop mb-4">
		<h2 class="section-title h5 mb-3">
			<i class="fa fa-list me-1" aria-hidden="true"></i>
			<?php esc_html_e( 'Recent', 'lerm' ); ?>
		</h2>

		<div <?php lerm_column_class( 'site-content' ); ?>>
			<?php
			while ( $main_query->have_posts() ) :
				$main_query->the_post();
				$summary_mode = (string) ( $template_options['summary_or_full'] ?? 'content_summary' );

				if ( 'content_full' === $summary_mode ) {
					get_template_part( 'template-parts/post/content', '' );
				} else {
					get_template_part( 'template-parts/post/content', 'excerpt' );
				}
			endwhile;
			?>
		</div>

		<?php
		// Restore global query for pagination.
		wp_reset_postdata();

		// Temporarily swap $wp_query for pagination rendering.
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$original_query = $wp_query;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query = $main_query;

		get_template_part( 'template-parts/components/pagination' );

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query = $original_query;
		?>
	</section>
	<?php
else :
	?>
	<div <?php lerm_column_class( 'site-content' ); ?>>
		<p class="text-muted text-center py-5"><?php esc_html_e( 'No posts found.', 'lerm' ); ?></p>
	</div>
	<?php
endif;
?>

</main>

<?php
get_sidebar();
get_footer();
