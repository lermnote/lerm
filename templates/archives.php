<?php
/**
 * Template Name: Archives
 *
 * @package Lerm
 */

get_header();
use function Lerm\Support\lerm_breadcrumb;
$args  = array(
	'post_type'           => 'post',
	'posts_per_page'      => -1,
	'ignore_sticky_posts' => 1,
	'orderby'             => 'date',
	'order'               => 'DESC',
);
$query = new WP_Query( $args );
?>
<?php lerm_breadcrumb(); ?>
<div <?php lerm_row_class(); ?>>
	<div id="primary" <?php lerm_column_class(); ?>>
		<div class="site-main">
			<?php
			if ( have_posts() ) :
				while ( have_posts() ) :
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
					<?php
							$posts_rebuild = array();

					while ( $query->have_posts() ) :
						$query->the_post();

						$year  = get_the_date( 'Y' );
						$month = get_the_date( 'm' );

						$posts_rebuild[ $year ][ $month ][] = sprintf(
							'<span class="entry-published me-2 text-muted small">%s</span>'
							. '<a href="%s" class="flex-grow-1">%s</a>'
							. '%s',
							esc_html( get_the_date( 'd' ) ),
							esc_url( get_permalink() ),
							esc_html( get_the_title() ),
							get_comments_number() > 0
								? sprintf(
									'<span class="badge rounded-pill text-bg-primary ms-1">%s</span>',
									esc_html( (string) get_comments_number() )
								)
								: ''
						);
											endwhile;
							wp_reset_postdata();

					foreach ( $posts_rebuild as $key_y => $months ) :
						$year_count = array_sum( array_map( 'count', $months ) );
						?>
					<section class="archives-year card mb-3">
						<h2 class="card-header mb-0 d-flex justify-content-between align-items-center fs-5">
							<span><?php echo esc_html( $key_y ); ?></span>
							<span class="badge text-bg-secondary">
								<?php echo esc_html( (string) $year_count ); ?>
								<?php esc_html_e( '篇', 'lerm' ); ?>
							</span>
						</h2>
						<ul class="list-unstyled card-body py-2 mb-0">
							<?php
							foreach ( $months as $key_m => $posts ) :
									$list_id    = sprintf(
										'archives-%s-%s',
										sanitize_html_class( $key_y ),
										sanitize_html_class( $key_m )
									);
									$month_name = date_i18n( 'F', mktime( 0, 0, 0, (int) $key_m, 1 ) );
									$post_count = count( $posts );
								?>
							<li class="mb-1">

								<button type="button"
									class="archives-month-btn btn btn-link p-0 w-100 text-start text-decoration-none d-flex align-items-center"
									data-archives-toggle data-bs-toggle="collapse"
									data-bs-target="#<?php echo esc_attr( $list_id ); ?>" aria-expanded="false"
									aria-controls="<?php echo esc_attr( $list_id ); ?>">
									<span class="month-name"><?php echo esc_html( $month_name ); ?></span>
									<span class="archives-toggle-icon"></span>
									<span class="badge rounded-pill text-bg-danger ms-auto">
										<?php echo esc_html( (string) $post_count ); ?>
										<?php esc_html_e( '篇', 'lerm' ); ?>
									</span>
								</button>
								<ul class=" post-list collapse mt-1 ps-3" id="<?php echo esc_attr( $list_id ); ?>">
									<?php foreach ( $posts as $post_item ) : ?>
									<li class="archives-post d-flex align-items-center">
										<?php echo wp_kses_post( $post_item ); ?>
									</li>
									<?php endforeach; ?>
								</ul>
							</li>
							<?php endforeach; ?>
						</ul>
					</section>
					<?php endforeach; ?>

				</div><!-- #archives -->
			</article>

					<?php
					if ( comments_open() || get_comments_number() ) :
						comments_template();
				endif;
					?>

					<?php
				endwhile;
		endif;
			?>
		</div>
	</div>
	<?php get_sidebar(); ?>
</div>

<style>
/* 用伪元素画 + / - */
.archives-toggle-icon {
	position: relative;
	display: inline-block;
	width: 14px;
	height: 14px;
	flex-shrink: 0;
}

/* 横线（始终存在） */
.archives-toggle-icon::before,
.archives-toggle-icon::after {
	content: "";
	position: absolute;
	background-color: var(--bs-primary, #0d6efd);
	border-radius: 2px;
	transition: transform 0.25s ease, opacity 0.25s ease;
}

/* 横线 */
.archives-toggle-icon::before {
	top: 50%;
	left: 0;
	width: 100%;
	height: 2px;
	transform: translateY(-50%);
}

/* 竖线 */
.archives-toggle-icon::after {
	top: 0;
	left: 50%;
	width: 2px;
	height: 100%;
	transform: translateX(-50%);
	/* 展开时旋转 90deg 并淡出 */
}

/* 展开状态：竖线旋转 90° 变成横线（视觉上消失，呈现 - ） */
[data-archives-toggle][aria-expanded="true"] .archives-toggle-icon::after {
	transform: translateX(-50%) rotate(90deg);
	opacity: 0;
}
</style>

<?php
get_footer();
