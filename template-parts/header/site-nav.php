<?php
/**
 * Displays the site navigation.
 *
 * @package lerm
 * @since  3.5.0
 */
use Lerm\Inc\Core\NavWalker;
if ( wp_is_mobile() ) {
	$theme_location = 'mobile';
} else {
	$theme_location = 'primary';
}
?>

<?php if ( wp_is_mobile() ) : ?>
	<div class="d-flex align-items-center">
		<button type="button" class="navbar-search d-lg-none" data-bs-toggle="modal" data-bs-target="#searchModal"><i class="li li-search"></i></button>
		<button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu" aria-controls="offcanvasMenu">
			<span></span>
			<span></span>
			<span></span>
		</button>
	</div>
	<div class="offcanvas offcanvas-end d-lg-none" tabindex="-1" id="offcanvasMenu" aria-labelledby="offcanvasMenu">
		<div class="offcanvas-header py-0"></div>
		<div class="offcanvas-body px-0">
			<?php
			// primary menu begin
			if ( has_nav_menu( $theme_location ) ) :
				wp_nav_menu(
					array(
						'theme_location'  => $theme_location,
						'container'       => 'div',
						'container_class' => 'primary-nav',
						'container_id'    => 'navbar',
						'fallback_cb'     => 'NavWalker::fallback',
						'menu_class'      => 'navbar-nav',
						'items_wrap'      => '<ul class="%2$s">%3$s</ul>',
						'walker'          => new NavWalker(),
						'depth'           => 2,
					)
				);
			endif;
			?>
		</div>
	</div><!--.offcanvas-->

	<div class="modal fade" id="searchModal"  tabindex="-1" aria-labelledby="searchModalLabel" aria-hidden="true">
		<div class="modal-dialog mt-5">
			<div class="modal-content">
				<div class="modal-header">
					<h1 class="modal-title fs-5" id="ssearchModalLabel">Search whole site</h1>
				</div>
				<div class="modal-body">
				<?php get_search_form(); ?>
				</div>
				<div class="d-flex justify-content-center p-3">
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
			</div>
		</div>
	</div>
	<?php
else :
	if ( false === wp_cache_get( 'lerm_nav_menu' ) && has_nav_menu( $theme_location ) ) :
		$nav_menu = wp_nav_menu(
			array(
				'theme_location'  => $theme_location,
				'container'       => 'div',
				'container_class' => lerm_options( 'narbar_align' ) . ' primary-nav flex-grow-1 d-none d-lg-flex mx-2',
				'container_id'    => 'navbar',
				'menu_class'      => 'navbar-nav',
				'items_wrap'      => '<ul class="%2$s">%3$s</ul>',
				'walker'          => new NavWalker(),
				'depth'           => 2,
			)
		);
		wp_cache_set( 'lerm_nav_menu', $nav_menu, '', HOUR_IN_SECONDS );
	endif;

	if ( lerm_options( 'narbar_search' ) ) :
		?>
		<div class="d-none d-lg-block">
			<?php get_search_form(); ?>
		</div>
	<?php endif; ?>
<?php endif; ?>
