<?php
/**
 * The template for displaying the header
 *
 * Displays all of the head element and everything up until the "row" div.
 *
 * @package lerm
 * @since  1.0
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
	<meta name="theme-color" content="<?php echo esc_attr( lerm_options( 'header_bg_color' ) ); ?>">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<div id="page" class="site">
		<header id="site-header" class="site-header <?php // echo ( false === $breadcrumb->args['show_on_front'] && is_home() ) ? 'mb-3' : ' '; ?>" itemscope="" itemtype="http://schema.org/WPHeader">
			<nav id="site-navigation" class="navbar navbar-expand-lg p-0">
				<div class="container">
					<div class="navbar-brand d-flex"><!-- .navbar-brand  begin -->

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
					</div><!-- navbar-brand end -->
					<?php get_template_part( 'template-parts/header/site-nav' ); ?>
				</div><!-- .container -->
			</nav>
		</header>
<?php get_template_part( 'template-parts/carousel' ); ?>
