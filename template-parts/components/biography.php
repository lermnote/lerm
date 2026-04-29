<?php
/**
 * The template part for displaying an Author biography
 *
 * @package Lerm https://lerm.net
 * @since Lerm 2.0
 */
use Lerm\Core\Menu;
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
</div><!-- .author -->

<?php
if ( has_nav_menu( 'social' ) && $show_social_in_bio ) :
	wp_nav_menu(
		array(
			'theme_location' => 'social',
			'menu_class'     => 'navbar-nav flex-row flex-wrap ms-md-auto justify-content-around',
			'link_before'    => '<span class="screen-reader-text">',
			'link_after'     => '</span>',
			'items_wrap'     => '<ul class="%2$s">%3$s</ul>',
			'walker'         => new Menu(),
			'depth'          => 1,
		)
	);
	endif;
?>
