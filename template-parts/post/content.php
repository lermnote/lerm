<?php
/**
 * Template part: post card / article body.
 *
 * @package Lerm
 */

use Lerm\Support\Image;
use Lerm\View\PostMeta;

use function Lerm\Support\link_pagination;
$current_post_id  = get_the_ID();
$template_options = lerm_get_template_options();
$card_classes     = 'card';
?>
<article id="post-<?php echo esc_attr( $current_post_id ); ?>" <?php post_class( $card_classes ); ?> >

	<?php if ( is_singular() ) : ?>
		<div class="content-area">
			<?php
			get_template_part( 'template-parts/layout/entry-header' );
			?>

			<div class="entry-content clearfix mb-3">
				<?php
				$continue_text = wp_kses(
					sprintf(
						/* translators: %s: post title inside screen-reader span */
						__( 'Continue reading<span class="screen-reader-text">"%s"</span>', 'lerm' ),
						get_the_title()
					),
					array( 'span' => array( 'class' => array() ) )
				);
				the_content( $continue_text );
				link_pagination();
				?>
			</div>

			<?php
			if ( is_singular( 'post' ) ) :
				PostMeta::post_meta( array_keys( (array) ( $template_options['single_bottom']['enabled'] ?? array() ) ), 'justify-content-between mb-1' );

				$tag_list = get_the_tag_list(
					'<ul class="list-unstyled m-0 small text-muted"><li class="d-inline"><i class="fa fa-tags"> </i>#',
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
			endif;
			get_template_part( 'template-parts/layout/entry-footer' );
			?>
		</div>

	<?php else : ?>
		<?php
		$show_thumbnail = ! isset( $template_options['show_thumbnail'] ) || ! empty( $template_options['show_thumbnail'] );
		$image          = null;
		if ( $show_thumbnail ) {
			$image = new Image(
				array(
					'post_id' => $current_post_id,
					'size'    => 'thumbnail',
					'lazy'    => 'lazy',
					'order'   => array( 'featured', 'block', 'scan', 'default' ),
					'default' => $template_options['thumbnail_gallery'] ?? array(),
				)
			);
		}
		$has_image         = $image && $image->found();
		$content_col_class = $has_image ? 'col-md-9' : 'col-md-12';
		$summary_mode      = (string) ( $template_options['summary_or_full'] ?? 'content_summary' );
		?>

		<div class="row align-items-md-center">

			<?php if ( $has_image ) : ?>
				<div class="col-md-3">
					<figure class="figure w-100 m-0" style="max-height:115px;overflow:hidden">
						<?php
						echo $image->generate_image_html(); // phpcs:ignore WordPress.Security.EscapeOutput
						?>
					</figure>
				</div>
			<?php endif; ?>

			<div class="<?php echo esc_attr( $content_col_class ); ?>">
				<div class="card-body p-md-0">

					<h2 class="entry-title card-title">
						<a href="<?php echo esc_url( get_permalink( $current_post_id ) ); ?>" rel="bookmark">
							<?php the_title(); ?>
						</a>
						<?php if ( is_sticky() ) : ?>
							<label class="sticky-label badge bg-danger m-0" aria-hidden="true">
								<?php esc_html_e( 'Sticky', 'lerm' ); ?>
							</label>
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