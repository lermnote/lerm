<?php
/**
 * The template part for displaying content
 *
 * @package Lerm
 * @since Lerm 2.0
 */

use Lerm\Support\Image;
use Lerm\View\PostMeta;
use function Lerm\Support\link_pagination;

$current_post_id  = get_the_ID(); // 缓存 ID，避免多次调用
$card_classes     = 'card';
$card_classes     = 'card';
$template_options = lerm_get_template_options();
global $post;

		$show_thumbnail = ! isset( $template_options['show_thumbnail'] ) || ! empty( $template_options['show_thumbnail'] );
		$image          = null;
		$has_image      = false;
if ( $show_thumbnail ) {
	$image     = new Image(
		array(
			'post_id' => $current_post_id,
			'size'    => 'thumbnail',
			'lazy'    => 'lazy',
			'order'   => array( 'featured', 'block', 'scan', 'default' ),
			'default' => $template_options['thumbnail_gallery'],
		)
	);
	$has_image = ! empty( $image->attachment_id );
}
		$content_col_class = $has_image ? 'col-md-9' : 'col-md-12';
		$summary_mode      = (string) ( $template_options['summary_or_full'] ?? 'content_summary' );

?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'card' ); ?>>
		<?php
		$show_thumbnail = ! isset( $template_options['show_thumbnail'] ) || ! empty( $template_options['show_thumbnail'] );
		$image          = null;
		$has_image      = false;
		if ( $show_thumbnail ) {
			$image     = new Image(
				array(
					'post_id' => $current_post_id,
					'size'    => 'thumbnail',
					'lazy'    => 'lazy',
					'order'   => array( 'featured', 'block', 'scan', 'default' ),
					'default' => $template_options['thumbnail_gallery'],
				)
			);
			$has_image = ! empty( $image->attachment_id );
		}
		$content_col_class = $has_image ? 'col-md-9' : 'col-md-12';
		$summary_mode      = (string) ( $template_options['summary_or_full'] ?? 'content_summary' );
		?>
		<div class="row g-0 align-items-md-center">
			<?php if ( $has_image ) : ?>
				<div class="col-md-3">
					<?php get_template_part( 'template-parts/components/featured-image' ); ?>
				</div>
			<?php endif; ?>

			<div class="<?php echo esc_attr( $content_col_class ); ?>">
				<div class="card-body p-md-0">
					<h2 class="entry-title card-title">
						<?php
						the_title( '<a href="' . esc_url( get_permalink( $current_post_id ) ) . '" rel="bookmark">', '</a>' );
						if ( is_sticky() ) :
							?>
							<label class="sticky-label badge bg-danger m-0" aria-hidden="true"><?php echo esc_html__( 'Sticky', 'lerm' ); ?></label>
						<?php endif; ?>
					</h2>

					<?php if ( 'content_full' === $summary_mode ) : ?>
						<?php the_content(); ?>
					<?php else : ?>
						<?php the_excerpt(); ?>
					<?php endif; ?>
					<?php get_template_part( 'template-parts/layout/summary-footer' ); ?>
				</div>
			</div>
		</div>

</article>
