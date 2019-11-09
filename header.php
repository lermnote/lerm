<?php
/**
 * The template for displaying the header
 *
 * Displays all of the head element and everything up until the "row" div.
 *
 * @package lerm
 * @since  1.0
 */
global $lerm;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
	<meta name="theme-color" content="<?php echo esc_attr( lerm_options( 'header_bg_color' ) ); ?>">
	<?php
	wp_head();
		echo wp_kses( $lerm['baidu_tongji'], array( 'script' => array() ) );
	?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
	<header id="site-header" class="site-header mb-md-3" itemscope="" itemtype="http://schema.org/WPHeader">
		<nav id="site-navigation" class="navbar navbar-expand-lg py-0">
			<div class="container">
				<!-- Container -->
				<div class="brand d-flex align-items-center">

					<?php
					the_custom_logo();
					?>
					<!-- .navbar-brand  begin -->
					<div>
						<?php
						$lerm_blogname = $lerm['blogname'] ? $lerm['blogname'] : get_bloginfo( 'name' );
						if ( is_front_page() || is_home() ) :
							?>
							<h1 class="site-title"><a href="<?php echo esc_url( home_url( '' ) ); ?>" rel="home"><?php echo esc_html( $lerm_blogname ); ?></a></h1>
						<?php else : ?>
							<p class="site-title"><a href="<?php echo esc_url( home_url( '' ) ); ?>" rel="home"><?php echo esc_html( $lerm_blogname ); ?></a></p>
							<?php
						endif;

						$description = $lerm['blogdesc'] ? $lerm['blogdesc'] : get_bloginfo( 'description' );
						if ( ! wp_is_mobile() && $description || is_customize_preview() ) :
							?>
							<span class="site-description small d-none d-md-block text-muted"><?php echo esc_html( $description ); ?></span>
						<?php endif; ?>
						<!-- .navbar-brand end -->
					</div>
				</div><!-- logo end -->
				<div class="d-lg-none d-flex justify-content-end">
						<button class="btn bg-inherit navbar-btn collapseds search-btn" type="button" data-toggle="collapse" data-target="#mobile-search" aria-expanded="false"  >
							<i class="fa fa-search"></i>
						</button>
						<button id="trigger" class="menu-toggle mr-2 btn bg-inherit navbar-btn" type="button"  aria-expanded="false" style="z-index:18">
							<span></span>
						</button>
					</div>
				<?php
				// primary menu begin
				if ( has_nav_menu( 'primary' ) ) :
					wp_nav_menu(
						array(
							'theme_location'  => 'primary',
							'container'       => 'div',
							'container_class' => $lerm['narbar_align'] . ' primary-nav navbar-collapse',
							'container_id'    => 'navbar',
							'menu_class'      => 'nav navbar-nav',
							'items_wrap'      => '<ul class="%2$s">%3$s</ul>',
							'walker'          => new Lerm_Walker_Nav_Menu(),
						)
					);
					if ( lerm_options( 'narbar_search' ) ) :
						?>
						<div class="d-none d-lg-block">
							<?php get_search_form(); ?>
						</div>
						<?php
				endif;
			endif;
				?>
			</div>
		</nav>
		<?php if ( wp_is_mobile() ) : ?>
			<div class="collapse" id="mobile-search">
				<?php get_search_form(); ?>
			</div>
		<?php endif; ?>

		<?php
		if ( is_home() && ! is_paged() && lerm_options( 'slide_position' ) === 'full_width' ) :
			lerm_carousel();
		endif;
		?>
	</header>
