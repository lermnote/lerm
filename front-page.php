<?php
/**
 * Front page template (CMS homepage).
 *
 * Sections:
 * 1. Carousel / Hero slider
 * 2. Featured posts
 * 3. Category-based post grid
 * 4. Main post loop with sidebar
 *
 * @package Lerm
 * @since   5.0.0
 */

use Lerm\Support\Image;

get_header();

$template_options  = lerm_get_template_options();
$show_thumbnail    = ! isset( $template_options['show_thumbnail'] ) || ! empty( $template_options['show_thumbnail'] );
$thumbnail_gallery = (array) ( $template_options['thumbnail_gallery'] ?? array() );
$excerpt_length    = (int) ( $template_options['excerpt_length'] ?? 95 );
$cat_exclude       = (array) ( $template_options['cat_exclude'] ?? array() );

// ─── Section 1: Carousel ────────────────────────────────────────────────
if ( ! empty( $template_options['slide_enable'] ) ) :
	get_template_part( 'template-parts/components/carousel' );
endif;
?>

<div class="row front-page">
<main id="main" class="col-lg-8 pe-lg-0">

<?php
// ─── Section 2: Featured Posts ───────────────────────────────────────────
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

if ( ! empty( $sticky_ids ) && is_array( $sticky_ids ) ) {
	$featured_args['post__in']            = $sticky_ids;
	$featured_args['ignore_sticky_posts'] = true;
	$featured_args['posts_per_page']      = 6;
} else {
	$featured_args['ignore_sticky_posts'] = true;
	$featured_args['posts_per_page']      = 6;
	if ( ! empty( $cat_exclude ) ) {
		$featured_args['category__not_in'] = array_map( 'absint', $cat_exclude );
	}
}

$featured_query = new WP_Query( $featured_args );

if ( $featured_query->have_posts() ) :
	$has_sticky = ! empty( $sticky_ids ) && is_array( $sticky_ids );
	?>
	<section class="featured-grid py-4">
		<h2 class="h5 mb-3">
			<?php if ( $has_sticky ) : ?>
				<i class="fa fa-thumb-tack me-1" aria-hidden="true"></i><?php esc_html_e( 'Featured', 'lerm' ); ?>
			<?php else : ?>
				<i class="fa fa-clock-o me-1" aria-hidden="true"></i><?php esc_html_e( 'Latest Posts', 'lerm' ); ?>
			<?php endif; ?>
		</h2>
		<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3">
			<?php
			while ( $featured_query->have_posts() ) :
				$featured_query->the_post();
				?>
				<div class="col">
					<article <?php post_class( 'card h-100' ); ?>>
						<?php if ( $show_thumbnail ) : ?>
							<a href="<?php the_permalink(); ?>" class="card-img-top overflow-hidden" aria-hidden="true" tabindex="-1">
								<?php
								get_template_part( 'template-parts/components/featured-image' );
								?>
							</a>
						<?php endif; ?>
						<div class="card-body d-flex flex-column">
							<h3 class="card-title h6 mb-2"><?php the_title( '<a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a>' ); ?></h3>
							<p class="card-text text-muted small mt-auto"><?php echo esc_html( wp_trim_words( get_the_excerpt(), $excerpt_length ) ); ?></p>
						</div>
						<div class="card-footer bg-transparent border-top-0 pt-0">
							<small class="text-muted"><i class="fa fa-calendar-o me-1" aria-hidden="true"></i><?php echo esc_html( get_the_time( 'M d, Y' ) ); ?></small>
						</div>
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
		if ( ! $cat_query->have_posts() ) {
			continue;
		}
		$cat_color_meta = get_term_meta( $category->term_id, 'cc_color', true );
		$cat_color      = ! empty( $cat_color_meta ) ? $cat_color_meta : 'var(--lerm-color-primary)';
		?>
		<section class="category-section py-4">
			<h2 class="h5 mb-3">
				<a href="<?php echo esc_url( get_category_link( $category ) ); ?>" class="text-decoration-none">
					<i class="fa fa-folder-o me-1" aria-hidden="true"></i><?php echo esc_html( $category->name ); ?>
				</a>
				<small class="text-muted ms-2">(<?php echo absint( $category->count ); ?>)</small>
			</h2>
			<div class="row g-3">
				<?php
				$cat_query->the_post();
				?>
				<div class="col-md-6">
					<article <?php post_class( 'card h-100' ); ?> style="border-left:3px solid <?php echo esc_attr( $cat_color ); ?>">
						<?php if ( $show_thumbnail ) : ?>
							<a href="<?php the_permalink(); ?>" class="card-img-top overflow-hidden" aria-hidden="true" tabindex="-1">
								<?php get_template_part( 'template-parts/components/featured-image' ); ?>
							</a>
						<?php endif; ?>
						<div class="card-body">
							<h3 class="card-title h5"><?php the_title( '<a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a>' ); ?></h3>
							<p class="card-text text-muted small"><?php echo esc_html( wp_trim_words( get_the_excerpt(), $excerpt_length ) ); ?></p>
						</div>
					</article>
				</div>
				<div class="col-md-6">
					<div class="row row-cols-1 g-3">
						<?php
						while ( $cat_query->have_posts() ) :
							$cat_query->the_post();
							?>
							<div class="col">
								<article <?php post_class( 'card' ); ?>>
									<div class="row g-0 align-items-center">
										<?php if ( $show_thumbnail ) : ?>
											<div class="col-4 col-sm-3">
												<a href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
													<?php get_template_part( 'template-parts/components/featured-image' ); ?>
												</a>
											</div>
										<?php endif; ?>
										<div class="<?php echo $show_thumbnail ? 'col-8 col-sm-9' : 'col-12'; ?>">
											<div class="card-body py-2">
												<h4 class="card-title h6 mb-0"><?php the_title( '<a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a>' ); ?></h4>
												<small class="text-muted"><i class="fa fa-calendar-o me-1" aria-hidden="true"></i><?php echo esc_html( get_the_time( 'M d, Y' ) ); ?></small>
											</div>
										</div>
									</div>
								</article>
							</div>
							<?php
						endwhile;
						?>
					</div>
				</div>
			</div>
		</section>
		<?php
		wp_reset_postdata();
	endforeach;
endif;

// ─── Section 4: Main Post Loop ──────────────────────────────────────────
$main_args = array(
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'paged'               => get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : 1,
	'ignore_sticky_posts' => true,
);

if ( ! empty( $cat_exclude ) ) {
	$main_args['category__not_in'] = array_map( 'absint', $cat_exclude );
}

$main_query = new WP_Query( $main_args );

if ( $main_query->have_posts() ) :
	?>
	<section class="main-loop py-4">
		<h2 class="h5 mb-3"><?php esc_html_e( 'Recent Articles', 'lerm' ); ?></h2>
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
		wp_reset_postdata();

		$original_query = $wp_query;
		$wp_query       = $main_query; // phpcs:ignore
		get_template_part( 'template-parts/components/pagination' );
		$wp_query       = $original_query; // phpcs:ignore
		?>
	</section>
	<?php
else :
	?>
	<p class="text-muted text-center py-5"><?php esc_html_e( 'No posts found.', 'lerm' ); ?></p>
	<?php
endif;
?>

</main>
<?php get_sidebar(); ?>
</div>

<?php get_footer();
