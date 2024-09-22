<?php
/**
 * Sidebar containing the main widget area
 *
 * @package Lerm https://lerm.net
 * @since  1.0
 */

if ( wp_is_mobile() || in_array( lerm_site_layout(), array( 'layout-1c', 'layout-1c-narrow' ), true ) ) {
	return; // Do not display sidebar on mobile or one colunm layout
}?>
<div id="secondary" class="col-lg-4 d-none d-lg-block mb-3">
	<aside class="sidebar sidebar-affix">
		<?php if ( is_singular( 'post' ) ) : ?>

			<?php if ( lerm_options( 'author_bio' ) ) : ?>
				<section class="card author-info text-center mb-3">
					<?php get_template_part( 'template-parts/components/biography' ); ?>
				</section>
			<?php endif; ?>

			<?php
			$single_sidebar = lerm_options( 'single_sidebar_select' ) ? lerm_options( 'single_sidebar_select' ) : 'home-sidebar';
			dynamic_sidebar( $single_sidebar );
			?>
		<?php endif; ?>

		<?php
		if ( is_home() ) :
			$blog_sidebar = lerm_options( 'blog_sidebar_select' ) ? lerm_options( 'blog_sidebar_select' ) : 'home-sidebar';
			dynamic_sidebar( $blog_sidebar );
		elseif ( is_front_page() ) :
			$front_page_sidebar = lerm_options( 'front_page_sidebar' ) ? lerm_options( 'front_page_sidebar' ) : 'home-sidebar';
			dynamic_sidebar( $front_page_sidebar );
		endif;
		?>
		<?php
		if ( is_singular( 'page' ) ) :
			$page_sidebar = lerm_options( 'page_sidebar' ) ? lerm_options( 'page_sidebar' ) : 'home-sidebar';
			dynamic_sidebar( $page_sidebar );
		endif;
		?>
		<?php
		if ( is_search() || is_404() || is_archive() ) :
			dynamic_sidebar( 'home-sidebar' );
		endif;
		?>
	</aside>
</div>
