<?php
/**
 * Displays the site navigation.
 *
 * @package lerm
 * @since  3.5.0
 */

if ( wp_is_mobile() ) {
	$theme_location = 'mobile';
} else {
	$theme_location = 'primary';
}
?>
<button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu" aria-controls="offcanvasMenu">
	<span class="menu-icon">
		<span class="menu-icon-top"></span>
		<span class="menu-icon-middle"></span>
		<span class="menu-icon-bottom"></span>
	</span>
</button>
<?php
if ( false === wp_cache_get( 'lerm_nav_menu' ) && has_nav_menu( $theme_location ) ) :
	$nav_menu = wp_nav_menu(
		array(
			'theme_location'  => $theme_location,
			'container'       => 'div',
			'container_class' => lerm_options( 'narbar_align' ) . ' primary-nav flex-grow-1 d-none d-lg-flex mx-2',
			'container_id'    => 'navbar',
			'menu_class'      => 'navbar-nav',
			'items_wrap'      => '<ul class="%2$s">%3$s</ul>',
			'walker'          => new \Lerm\Inc\NavWalker(),
			'depth'           => 2,
		)
	);
	wp_cache_set( 'lerm_nav_menu', $nav_menu, '', HOUR_IN_SECONDS );
	endif;
?>
<div class="d-none d-lg-block">
	<?php
	if ( lerm_options( 'narbar_search' ) ) :
		get_search_form();
	endif;
	?>
</div>
<?php if ( wp_is_mobile() ) : ?>
	<div class="offcanvas offcanvas-end d-lg-none" tabindex="-1" id="offcanvasMenu" aria-labelledby="offcanvasMenu">
		<div class="offcanvas-header py-0">
			<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
		</div>
		<div class="offcanvas-body px-0">
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
						'container_class' => 'primary-nav',
						'container_id'    => 'navbar',
						'fallback_cb'     => '\Lerm\Inc\Nav_Walker::fallback',
						'menu_class'      => 'navbar-nav',
						'items_wrap'      => '<ul class="%2$s">%3$s</ul>',
						'walker'          => new \Lerm\Inc\Nav_Walker(),
						'depth'           => 2,
					)
				);
			endif;
			?>
		</div>
	</div><!--.offcanvas-->
<?php endif; ?>
