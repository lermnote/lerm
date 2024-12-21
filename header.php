<?php
/**
 * The template for displaying the header
 *
 * Displays all of the head element and everything up until the "row" div.
 *
 * @package Lerm https://lerm.net
 *
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
	<?php echo wp_kses( lerm_options( 'baidu_tongji' ), array( 'script' => array() ) ); ?>
</head>
<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<div id="page" class="site">
		<header id="site-header" class="card site-header" itemscope="" itemtype="http://schema.org/WPHeader">
			<nav id="site-navigation" class="navbar navbar-expand-lg p-0">
				<div class="container">
					<!-- .navbar-brand  begin -->
					<?php get_template_part( 'template-parts/header/site-brand' ); ?>
					<!-- .navbar-brand end -->
					<?php get_template_part( 'template-parts/header/site-nav' ); ?>
				</div><!-- .container -->
			</nav>
		</header>
		<?php
		if ( lerm_options( 'slide_position' ) === 'full_width' ) {
			get_template_part( 'template-parts/components/carousel' );
		}
