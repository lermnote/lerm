<?php
/**
 * Displays the site navigation.
 *
 * @package lerm
 * @since  3.5.0
 */
?>
<div class="navbar-brand d-flex">
	<?php the_custom_logo(); ?>

	<div class="masthead">
		<?php
		$lerm_blogname = lerm_options( 'blogname' ) ? lerm_options( 'blogname' ) : get_bloginfo( 'name' );
		if ( is_front_page() || is_home() ) :
			?>
			<h1 class="site-title"><a href="<?php echo esc_url( home_url( '' ) ); ?>" rel="home"><?php echo esc_html( $lerm_blogname ); ?></a></h1>
		<?php else : ?>
			<p class="site-title h1"><a href="<?php echo esc_url( home_url( '' ) ); ?>" rel="home"><?php echo esc_html( $lerm_blogname ); ?></a></p>
			<?php
		endif;

		$description = lerm_options( 'blogdesc' ) ? lerm_options( 'blogdesc' ) : get_bloginfo( 'description' );
		if ( ! wp_is_mobile() && $description || is_customize_preview() ) :
			?>
			<span class="site-description small d-none d-md-block text-muted"><?php echo esc_html( $description ); ?></span>
		<?php endif; ?>
	</div><!-- .masthead -->
</div>
