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
use function Lerm\Support\lerm_render_homepage_carousel;
?>
<?php $template_options = lerm_get_template_options(); ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="<?php echo esc_attr( $template_options['header_bg_color'] ); ?>">
	<?php wp_head(); ?>
		<?php if ( ! empty( $template_options['head_scripts'] ) ) : ?>
			<?php echo $template_options['head_scripts']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<?php endif; ?>
</head>
<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<div id="page" class="site">
		<header id="site-header" class="card site-header mb-3 <?php echo ! empty( $template_options['sticky_header'] ) ? ' site-header--sticky' : ''; ?><?php echo ! empty( $template_options['transparent_header'] ) ? ' site-header--transparent' : ''; ?>"
		data-shrink="<?php echo ( ! empty( $template_options['sticky_header'] ) && ! empty( $template_options['sticky_header_shrink'] ) ) ? 'true' : 'false'; ?>"
		itemscope="" itemtype="https://schema.org/WPHeader">
			<nav id="site-navigation" class="navbar navbar-expand-lg p-0">
				<div class="container">
					<!-- .navbar-brand  begin -->
					<?php get_template_part( 'template-parts/layout/site-brand' ); ?>
					<!-- .navbar-brand end -->
					<?php get_template_part( 'template-parts/layout/site-nav' ); ?>
				</div><!-- .container -->
			</nav>
		</header>
		<?php
		if ( 'full_width' === $template_options['slide_position'] ) {
			lerm_render_homepage_carousel( $template_options );
		}
		?>
		<main role="main" class="container" id="page-ajax"><!--.container-->
