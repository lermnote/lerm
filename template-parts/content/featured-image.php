<?php
/**
 * Displays the featured image
 *
 * @package Lerm
 */

$featured_media_inner_classes = '';

// Make the featured media thinner on archive pages.
if ( ! is_singular() ) {
	$featured_media_inner_classes .= ' medium';
}
?>

<figure class="figure">

	<div class="featured-media-inner section-inner<?php echo $featured_media_inner_classes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static output ?>">

		<?php
		the_post_thumbnail();

		$caption = get_the_post_thumbnail_caption();

		if ( $caption ) {
			?>

			<figcaption class="figure-caption text-center"><?php echo wp_kses_post( $caption ); ?></figcaption>

			<?php
		}
		?>

	</div><!-- .featured-media-inner -->

</figure><!-- .featured -->