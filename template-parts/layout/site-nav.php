<?php
/**
 * Displays the site navigation.
 *
 * @package lerm
 * @since  3.5.0
 */

use Lerm\Core\Menu;

$template_options      = lerm_get_template_options();
$theme_location        = wp_is_mobile() ? 'mobile' : 'primary';
$show_navbar_search    = ! empty( $template_options['navbar_search'] );
$show_social_in_header = in_array( 'header', (array) ( $template_options['social_profiles_position'] ?? array( 'footer', 'author_bio' ) ), true );
?>

<?php if ( wp_is_mobile() ) : ?>
	<button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu" aria-controls="offcanvasMenu">
		<span></span>
		<span></span>
		<span></span>
	</button>

	<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasMenu" aria-labelledby="offcanvasMenu">
		<div class="offcanvas-header border-bottom d-flex align-items-center">
			<div class="d-flex align-items-center gap-2">
				<?php the_custom_logo(); ?>
				<span class="site-title h5 mb-0">
					<a href="<?php echo esc_url( home_url( '' ) ); ?>" rel="home">
						<?php echo esc_html( $template_options['blogname'] ? $template_options['blogname'] : get_bloginfo( 'name' ) ); ?>
					</a>
				</span>
			</div>
		</div>
		<div class="offcanvas-body px-0 d-flex flex-column">
			<?php
			if ( has_nav_menu( $theme_location ) ) :
				wp_nav_menu(
					array(
						'theme_location'  => $theme_location,
						'container'       => 'div',
						'container_class' => 'primary-nav',
						'container_id'    => 'navbar',
						'fallback_cb'     => 'Menu::fallback',
						'menu_class'      => 'navbar-nav',
						'items_wrap'      => '<ul class="%2$s">%3$s</ul>',
						'walker'          => new Menu(),
						'depth'           => 4,
					)
				);
			endif;
			?>
			<?php if ( $show_navbar_search ) : ?>
				<div class="border-top px-3 py-3 mt-auto">
					<?php get_search_form(); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
<?php else : ?>
	<?php
	$found    = false;
	$nav_menu = wp_cache_get( 'lerm_nav_menu_' . $theme_location, 'lerm_nav', false, $found );

	if ( ! $found && has_nav_menu( $theme_location ) ) :
		$nav_menu = wp_nav_menu(
			array(
				'theme_location'  => $theme_location,
				'container'       => 'div',
				'container_class' => trim( (string) ( $template_options['navbar_align'] ?? 'justify-content-md-end' ) . ' primary-nav flex-grow-1 d-none d-lg-flex mx-2' ),
				'container_id'    => 'navbar',
				'fallback_cb'     => 'Menu::fallback',
				'menu_class'      => 'navbar-nav',
				'items_wrap'      => '<ul class="%2$s">%3$s</ul>',
				'walker'          => new Menu(),
				'depth'           => 4,
				'echo'            => false,
			)
		);
		wp_cache_set( 'lerm_nav_menu_' . $theme_location, (string) $nav_menu, 'lerm_nav', HOUR_IN_SECONDS );
	endif;

	if ( $nav_menu ) :
		echo $nav_menu; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	endif;
	?>

	<?php if ( $show_navbar_search ) : ?>
		<div class="navbar-search-wrapper d-none d-lg-flex">
			<?php get_search_form(); ?>
		</div>
	<?php endif; ?>

	<?php
	if ( has_nav_menu( 'secondary' ) && $show_social_in_header ) :
		wp_nav_menu(
			array(
				'theme_location' => 'secondary',
				'menu_class'     => 'top-social-menus navbar-nav flex-row flex-wrap ms-md-auto justify-content-around',
				'link_before'    => '<span class="screen-reader-text">',
				'link_after'     => '</span>',
				'items_wrap'     => '<ul class="%2$s">%3$s</ul>',
				'walker'         => new Menu(),
				'depth'          => 1,
			)
		);
	endif;
	?>
<?php endif; ?>
