<?php
/**
 * Site navigation — primary menu, search, dark mode, user controls.
 *
 * @package Lerm
 * @since   3.5.0
 */

use Lerm\Core\Menu;

$theme_location        = 'primary';
$template_options      = lerm_get_template_options();
$navbar_align          = trim( (string) ( $template_options['navbar_align'] ?? 'justify-content-md-end' ) );
$show_navbar_search    = ! empty( $template_options['navbar_search'] );
$show_social_in_header = in_array(
	'header',
	(array) ( $template_options['social_profiles_position'] ?? array( 'footer', 'author_bio' ) ),
	true
);
$show_darkmode_navbar  =
	! empty( $template_options['dark_mode_enable'] )
	&& ( $template_options['dark_mode_toggle_position'] ?? 'navbar' ) === 'navbar';

$walker_args = array(
	'theme_location' => $theme_location,
	'fallback_cb'    => false,
	'menu_class'     => 'navbar-nav',
	'items_wrap'     => '<ul class="%2$s">%3$s</ul>',
	'walker'         => new Menu(),
	'depth'          => 4,
);

add_action(
	'wp_update_nav_menu',
	static function () use ( $theme_location ): void {
		wp_cache_delete( 'lerm_nav_menu_' . $theme_location, 'lerm_nav' );
	}
);

$site_name           = $template_options['blogname'] ?: get_bloginfo( 'name' );
$current_user_object = wp_get_current_user();
?>

<?php if ( wp_is_mobile() ) : ?>
	<button
		class="navbar-toggler"
		type="button"
		data-bs-toggle="offcanvas"
		data-bs-target="#offcanvasMenu"
		aria-controls="offcanvasMenu"
		aria-label="<?php esc_attr_e( 'Open navigation', 'lerm' ); ?>"
	>
		<span></span>
		<span></span>
		<span></span>
	</button>

	<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasMenu" aria-labelledby="offcanvasMenuLabel">
		<div class="offcanvas-header border-bottom d-flex align-items-center">
			<div class="d-flex align-items-center gap-2">
				<?php the_custom_logo(); ?>
				<span class="site-title h5 mb-0" id="offcanvasMenuLabel">
					<a href="<?php echo esc_url( home_url( '' ) ); ?>" rel="home">
						<?php echo esc_html( $site_name ); ?>
					</a>
				</span>
			</div>
		</div>

		<div class="offcanvas-body px-0 d-flex flex-column">
			<?php
			if ( has_nav_menu( $theme_location ) ) :
				wp_nav_menu(
					array_merge(
						$walker_args,
						array(
							'container'       => 'div',
							'container_class' => 'primary-nav',
							'container_id'    => 'navbar',
						)
					)
				);
			endif;
			?>

			<?php if ( $show_darkmode_navbar || is_user_logged_in() ) : ?>
				<ul class="navbar-nav border-top mt-2 pt-2">

					<?php if ( $show_darkmode_navbar ) : ?>
						<li class="nav-item">
							<button
								type="button"
								class="nav-link dark-mode-toggle w-100 text-start d-flex align-items-center gap-2"
								id="dark-mode-btn-mobile"
								aria-label="<?php esc_attr_e( 'Toggle colour scheme', 'lerm' ); ?>"
							>
								<i class="fa fa-moon" aria-hidden="true"></i>
								<span><?php esc_html_e( 'Dark mode', 'lerm' ); ?></span>
							</button>
						</li>
					<?php endif; ?>

					<li class="nav-item menu-item-login">
						<?php
						if ( is_user_logged_in() ) :
							$current_user_object = wp_get_current_user();
							?>
							<a class="nav-link d-flex align-items-center gap-2" href="<?php echo esc_url( home_url( '/' ) ); ?>">
								<?php echo get_avatar( $current_user_object->ID, 20, '', '', array( 'class' => 'rounded-circle' ) ); ?>
								<?php echo esc_html( $current_user_object->display_name ); ?>
							</a>
						<?php else : ?>
							<a class="nav-link" href="<?php echo esc_url( wp_login_url() ); ?>">
								<i class="fa fa-sign-in" aria-hidden="true"></i>
								<?php esc_html_e( 'Login', 'lerm' ); ?>
							</a>
						<?php endif; ?>
					</li>

				</ul>
			<?php endif; ?>

			<?php if ( $show_navbar_search ) : ?>
				<div class="border-top px-3 py-3 mt-auto">
					<?php get_search_form(); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>

<?php else : ?>
	<?php
	$cache_key = 'lerm_nav_menu_' . $theme_location;
	$nav_html  = wp_cache_get( $cache_key, 'lerm_nav' );

	if ( false === $nav_html && has_nav_menu( $theme_location ) ) {
		$nav_html = (string) wp_nav_menu(
			array_merge(
				$walker_args,
				array(
					'container'       => 'div',
					'container_class' => $navbar_align . ' primary-nav flex-grow-1 d-none d-lg-flex mx-2',
					'container_id'    => 'navbar',
					'echo'            => false,
				)
			)
		);
		wp_cache_set( $cache_key, $nav_html, 'lerm_nav', HOUR_IN_SECONDS );
	}

	if ( $nav_html ) :
		echo $nav_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	endif;
	?>

	<?php if ( $show_navbar_search ) : ?>
		<div class="navbar-search-wrapper d-none d-lg-flex">
			<?php get_search_form(); ?>
		</div>
	<?php endif; ?>

	<?php if ( has_nav_menu( 'secondary' ) && $show_social_in_header ) : ?>
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'secondary',
				'fallback_cb'    => false,
				'menu_class'     => 'top-social-menus navbar-nav flex-row flex-wrap ms-md-auto justify-content-around',
				'link_before'    => '<span class="screen-reader-text">',
				'link_after'     => '</span>',
				'items_wrap'     => '<ul class="%2$s">%3$s</ul>',
				'walker'         => new Menu(),
				'depth'          => 1,
			)
		);
		?>
	<?php endif; ?>

	<?php if ( $show_darkmode_navbar || is_user_logged_in() ) : ?>
		<div class="navbar-utility d-none d-lg-flex align-items-center">

			<?php if ( $show_darkmode_navbar ) : ?>
				<button
					type="button"
					class="btn btn-sm btn-custom dark-mode-toggle nav-item"
					id="dark-mode-btn-desktop"
					aria-label="<?php esc_attr_e( 'Toggle colour scheme', 'lerm' ); ?>"
				>
					<i class="fa fa-moon" aria-hidden="true"></i>
				</button>
			<?php endif; ?>

			<?php if ( is_user_logged_in() ) : ?>
				<?php $current_user_object = wp_get_current_user(); ?>
				<div class="nav-item dropdown menu-item-login">
					<a class="nav-link dropdown-toggle d-flex align-items-center gap-1" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" >
						<?php echo get_avatar( $current_user_object->ID, 20, '', '', array( 'class' => 'rounded-circle' ) ); ?>
						<span class="d-none d-xl-inline"><?php echo esc_html( $current_user_object->user_login ); ?></span>
					</a>
					<ul class="dropdown-menu dropdown-menu-end">
						<li class="text-center">
							<h6 class="dropdown-header"><?php echo get_avatar( $current_user_object->ID, 64 ); ?></h6>
							<span class="text-info"><?php echo esc_html( $current_user_object->display_name ); ?></span>
						</li>
						<li>
							<a class="dropdown-item" href="<?php echo esc_url( home_url( '/' ) ); ?>">
								<?php esc_html_e( 'Account', 'lerm' ); ?>
							</a>
						</li>
						<li><hr class="dropdown-divider"></li>
						<li>
							<a class="dropdown-item" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">
								<?php esc_html_e( 'Log out', 'lerm' ); ?>
							</a>
						</li>
					</ul>
				</div>
			<?php else : ?>
				<div class="nav-item menu-item-login">
					<a class="nav-link" href="<?php echo esc_url( wp_login_url() ); ?>">
						<i class="fa fa-sign-in" aria-hidden="true"></i>
						<?php esc_html_e( 'Login', 'lerm' ); ?>
					</a>
				</div>
			<?php endif; ?>

		</div>
	<?php endif; ?>

<?php endif; ?>