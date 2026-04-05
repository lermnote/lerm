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
?>
<article id="post-<?php echo esc_attr( $current_post_id ); ?>" <?php post_class( $card_classes ); ?>>

	<?php if ( is_singular() ) : ?>
		<div class="content-area">
			<?php get_template_part( 'template-parts/layout/entry-header' ); ?>

			<?php if ( is_singular( 'post' ) && in_array( (string) ( $template_options['share_position'] ?? 'bottom' ), array( 'top', 'both' ), true ) ) : ?>
				<div class="mt-3">
					<?php \Lerm\View\lerm_social_icons( (array) $template_options['social_share'] ); ?>
				</div>
			<?php endif; ?>

			<div class="entry-content clearfix mb-3">
				<?php
				$continue_text = sprintf(
					/* translators: %s: the post title, shown inside a screen-reader-only span. */
					__( 'Continue reading<span class="screen-reader-text">"%s"</span>', 'lerm' ),
					get_the_title()
				);
				$continue_text = wp_kses(
					$continue_text,
					array(
						'span' => array( 'class' => array() ),
					)
				);
				the_content( $continue_text );


				link_pagination();
				?>
			</div>

			<?php
			if ( is_singular( 'post' ) ) {
				PostMeta::post_meta( array_keys( (array) ( $template_options['single_bottom']['enabled'] ?? array() ) ), 'justify-content-between mb-1' );
			}

			$tag_list = get_the_tag_list(
				'<ul class="list-unstyled m-0 small text-muted"><li class="d-inline "><i class="fa fa-tags"> </i>#',
				'</li><li class="d-inline ms-2">#',
				'</li></ul>'
			);
			if ( $tag_list ) {
				echo wp_kses(
					$tag_list,
					array(
						'ul' => array( 'class' => array() ),
						'li' => array( 'class' => array() ),
						'i'  => array( 'class' => array() ),
						'a'  => array(
							'href'  => array(),
							'rel'   => array(),
							'class' => array(),
						),
					)
				);
			}

			get_template_part( 'template-parts/layout/entry-footer' );
			?>
		</div>

	<?php else : ?>
		<?php
		$show_thumbnail    = ! isset( $template_options['show_thumbnail'] ) || ! empty( $template_options['show_thumbnail'] );
		$image             = null;
		$has_image         = false;
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
	<?php endif; ?>
</article>
