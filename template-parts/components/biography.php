<?php
/**
 * The template part for displaying an Author biography
 *
 * @package Lerm https://lerm.net
 * @since Lerm 2.0
 */

$template_options   = lerm_get_template_options();
$social_positions   = (array) ( $template_options['social_profiles_position'] ?? array( 'footer', 'author_bio' ) );
$social_new_tab     = (bool) ( $template_options['social_open_new_tab'] ?? true );
$show_social_in_bio = in_array( 'author_bio', $social_positions, true );
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
			<span class="author-heading"><?php echo get_the_author(); ?></span>
			<label class="badge bg-info"><?php esc_html_e( 'Author', 'lerm' ); ?></label>
		</h2>
		<?php if ( '' !== get_the_author_meta( 'description' ) ) : ?>
		<p class="author-bio text-muted p-3"><?php the_author_meta( 'description' ); ?></p>
		<?php endif; ?>
	</div>
</div><!-- .author-avatar -->

<div class="author-contact pb-3">
	<?php if ( $show_social_in_bio && function_exists( 'lerm_social_profile_links' ) ) : ?>
		<?php lerm_social_profile_links( $template_options, $social_new_tab ); ?>
	<?php elseif ( has_nav_menu( 'social' ) ) : ?>
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
		endif;
		?>
</div><!-- .author-description -->