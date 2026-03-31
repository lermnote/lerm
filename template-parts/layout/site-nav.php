<?php
/**
 * Displays the site navigation.
 *
 * @package lerm
 * @since  3.5.0
 */

use Lerm\Core\Menu;

$template_options    = lerm_get_template_options();
$theme_location      = wp_is_mobile() ? 'mobile' : 'primary';
$show_navbar_search  = ! empty( $template_options['navbar_search'] );
$social_positions    = (array) ( $template_options['social_profiles_position'] ?? array( 'footer', 'author_bio' ) );
$show_header_social  = in_array( 'header', $social_positions, true );
$social_open_new_tab = ! isset( $template_options['social_open_new_tab'] ) || ! empty( $template_options['social_open_new_tab'] );
?>

<?php if ( wp_is_mobile() ) : ?>
	<div class="d-flex align-items-center">
		<?php if ( $show_navbar_search ) : ?>
			<button type="button" class="navbar-search d-lg-none" data-bs-toggle="modal" data-bs-target="#searchModal">
				<i class="fa fa-search"></i>
			</button>
		<?php endif; ?>
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
		</div>
	</div>

	<?php if ( $show_navbar_search ) : ?>
		<div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="searchModalLabel" aria-hidden="true">
			<div class="modal-dialog mt-5">
				<div class="modal-content">
					<div class="modal-header">
						<h1 class="modal-title fs-5" id="searchModalLabel"><?php esc_html_e( 'Search whole site', 'lerm' ); ?></h1>
					</div>
					<div class="modal-body">
						<?php get_search_form(); ?>
					</div>
					<div class="d-flex justify-content-center p-3">
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'lerm' ); ?>"></button>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>
<?php else : ?>
	<?php
	$found    = false;
	$nav_menu = wp_cache_get( 'lerm_nav_menu', 'lerm_nav', false, $found );

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
		wp_cache_set( 'lerm_nav_menu', (string) $nav_menu, 'lerm_nav', HOUR_IN_SECONDS );
	endif;

	if ( $nav_menu ) :
		echo $nav_menu; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	endif;

	if ( $show_header_social && function_exists( 'lerm_social_profile_links' ) ) :
		lerm_social_profile_links(
			$template_options,
			$social_open_new_tab,
			'lerm-social-links d-none d-lg-flex align-items-center gap-2 me-2 mb-0'
		);
	endif;

	if ( $show_navbar_search ) :
		?>
		<div class="d-none d-lg-block">
			<?php get_search_form(); ?>
		</div>
	<?php endif; ?>
<?php endif; ?>
