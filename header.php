<?php
/**
 * The template for displaying the header
 *
 * Displays all of the head element and everything up until the "row" div.
 *
 * @package lerm
 * @since  1.0
 */
if ( wp_is_mobile() ) {
	$theme_location = 'mobile';
} else {
	$theme_location = 'primary';
}
$carousel   = new \Lerm\Inc\Carousel();
$breadcrumb = new \Lerm\Inc\Breadcrumb();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
	<meta name="theme-color" content="<?php echo esc_attr( lerm_options( 'header_bg_color' ) ); ?>">
	<?php wp_head(); ?>
	<script>
		<?php echo wp_kses( lerm_options( 'baidu_tongji' ), array( 'script' => array() ) ); ?>
	</script>
</head>
<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<header id="site-header" class="site-header <?php echo ( false === $breadcrumb->args['show_on_front'] && is_home() ) ? 'mb-3' : ' '; ?>" itemscope="" itemtype="http://schema.org/WPHeader">
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
					</div><!-- .navbar-brand end -->
				</div><!-- logo end -->

				<button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu" aria-controls="offcanvasMenu">
					<span class="menu-icon">
						<span class="menu-icon-top"></span>
						<span class="menu-icon-middle"></span>
						<span class="menu-icon-bottom"></span>
					</span>
				</button>
				<?php
				if ( has_nav_menu( $theme_location ) ) :
					wp_nav_menu(
						array(
							'theme_location'  => $theme_location,
							'container'       => 'div',
							'container_class' => lerm_options( 'narbar_align' ) . ' primary-nav flex-grow-1 d-none d-lg-flex mx-2',
							'container_id'    => 'navbar',
							'fallback_cb'     => 'return _false_',
							'menu_class'      => 'nav navbar-nav',
							'items_wrap'      => '<ul class="%2$s">%3$s</ul>',
							'walker'          => new \Lerm\Inc\Nav_Walker(),
						)
					);
					endif;
				?>
				<div class="d-none d-lg-block">
					<?php
					if ( lerm_options( 'narbar_search' ) ) :
						get_search_form();
					endif;
					?>
				</div>

				<div class="offcanvas offcanvas-end d-lg-none" tabindex="-1" id="offcanvasMenu" aria-labelledby="offcanvasMenu">
					<div class="offcanvas-header py-0">
						<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
					</div>
					<div class="offcanvas-body">
						<?php
						if ( lerm_options( 'narbar_search' ) ) :
							get_search_form();
						endif;
						// primary menu begin
						if ( has_nav_menu( $theme_location ) ) :
							wp_nav_menu(
								array(
									'theme_location'  => $theme_location,
									'container'       => 'div',
									'container_class' => 'primary-nav mx-1',
									'container_id'    => 'navbar',
									'fallback_cb'     => 'return _false_',
									'menu_class'      => 'nav navbar-nav',
									'items_wrap'      => '<ul class="%2$s">%3$s</ul>',
									'walker'          => new \Lerm\Inc\Nav_Walker(),
								)
							);
						endif;
						?>
					</div>
				</div><!--.offcanvas-->
			</div><!-- .container -->
		</nav>

	</header>
	<?php
	if ( is_home() && ! is_paged() ) {
		switch ( lerm_options( 'slide_position' ) ) {
			case 'full_width':
				$carousel->render();
				break;
			case 'under_navbar':
				?>
				<div class="container">
					<?php $carousel->render(); ?>
				</div>
				<?php
				break;
		}
	}
