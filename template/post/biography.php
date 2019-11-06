<?php
/**
 * The template part for displaying an Author biography
 *
 * @package Lerm
 * @since Lerm 2.0
 */
?>

<div class="author">
	<?php
	/**
	 * Filter the Lerm author bio avatar size.
	 *
	 * @since Lerm 2.0
	 *
	 * @param int $size The avatar height and width size in pixels.
	 */

	$author_bio_avatar_size = apply_filters( 'lerm_author_bio_avatar_size', 64 );
	?>
	<div class="bio-haeder pt-4">

			<?php echo get_avatar( get_the_author_meta( 'email' ), $author_bio_avatar_size ); ?>

		<h2 class="author-title">
		<span class="author-heading"><?php echo get_the_author(); ?></span> <label class="badge badge-info"><?php _e( ' Author', 'lerm' ); ?></label>
	</h2>
		<?php
		if ( '' !== get_the_author_meta( 'description' ) ) {
			?>
		<p class="author-bio text-muted p-3">
			<?php
			the_author_meta( 'description' );
			?>
		</p>
			<?php
		}
		?>
	<!-- .author-bio -->
	</div>

</div><!-- .author-avatar -->
<div class="author-contact pb-3">
	<?php
	wp_nav_menu(
		array(
			'theme_location' => 'social',
			'menu_class'     => 'social-links-menu d-flex justify-content-around list-unstyled',
			'link_before'    => '<span class="screen-reader-text">',
			'link_after'     => '</span>' . lerm_get_icon_svg( 'link' ),
			'depth'          => 1,
		)
	);
	?>
</div><!-- .author-description -->
