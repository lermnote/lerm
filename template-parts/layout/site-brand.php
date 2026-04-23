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

	<?php $show_header_text = is_customize_preview() ? display_header_text() : ! empty( $template_options['display_header_text'] ); ?>
	<?php if ( $show_header_text ) : ?>
	<div class="masthead">
		<?php
		$lerm_blogname = get_bloginfo( 'name' );
		if ( is_front_page() || is_home() ) :
			?>
			<h1 class="site-title"><a href="<?php echo esc_url( home_url( '' ) ); ?>" rel="home"><?php echo esc_html( $lerm_blogname ); ?></a></h1>
		<?php else : ?>
			<p class="site-title h1"><a href="<?php echo esc_url( home_url( '' ) ); ?>" rel="home"><?php echo esc_html( $lerm_blogname ); ?></a></p>
			<?php
		endif;

		$description = get_bloginfo( 'description' );
		if ( ( ! wp_is_mobile() && $description ) || is_customize_preview() ) :
			?>
			<span class="site-description small d-none d-md-block text-muted"><?php echo esc_html( $description ); ?></span>
		<?php endif; ?>
	</div><!-- .masthead -->
	<?php endif; ?>
</div>
