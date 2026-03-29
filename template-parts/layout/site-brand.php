<?php
/**
 * Displays the site brand.
 *
 * @package lerm
 * @since  3.5.0
 */
?>
<?php $template_options = lerm_get_template_options(); ?>
<div class="navbar-brand d-flex align-items-center">
	<?php the_custom_logo(); ?>

	<div class="masthead">
		<?php
		$lerm_blogname = $template_options['blogname'] ? $template_options['blogname'] : get_bloginfo( 'name' );
		if ( is_front_page() || is_home() ) :
			?>
			<h1 class="site-title"><a href="<?php echo esc_url( home_url( '' ) ); ?>" rel="home"><?php echo esc_html( $lerm_blogname ); ?></a></h1>
		<?php else : ?>
			<p class="site-title h1"><a href="<?php echo esc_url( home_url( '' ) ); ?>" rel="home"><?php echo esc_html( $lerm_blogname ); ?></a></p>
			<?php
		endif;

		$description = $template_options['blogdesc'] ? $template_options['blogdesc'] : get_bloginfo( 'description' );
		if ( ( ! wp_is_mobile() && $description ) || is_customize_preview() ) :
			?>
			<span class="site-description small d-none d-md-block text-muted"><?php echo esc_html( $description ); ?></span>
		<?php endif; ?>
	</div><!-- .masthead -->
</div>
