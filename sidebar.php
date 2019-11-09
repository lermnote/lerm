<?php
/**
 * Sidebar containing the main widget area
 *
 * @author Lerm https://www.hanost.com
 * @since  1.0
 */

if ( wp_is_mobile() || 'layout-1c' === lerm_page_layout() ) :
	// Do not display sidebar on mobile or one colunm layout
	return;
elseif ( in_array( lerm_page_layout(), array( 'layout-2c-l', 'layout-2c-r' ), true ) ) :
	$class = ( lerm_page_layout() === 'layout-2c-l' ) ? 'order-md-first' : ''; ?>
	<div class="col-lg-4 <?php echo esc_attr( $class ); ?>">
		<aside class="sidebar sidebar-affix">
			<?php if ( is_single() ) : ?>

				<?php if ( lerm_options( 'author_bio' ) ) : ?>
					<section class="author-info text-center mb-3">
						<?php get_template_part( 'template/post/biography' ); ?>
					</section>
				<?php endif; ?>

				<?php
				$single_sidebar = lerm_options( 'single_sidebar_select'  ) ? lerm_options( 'single_sidebar_select' ) : 'home-sidebar';
				dynamic_sidebar( $single_sidebar );
				?>
			<?php endif; ?>

			<?php
			if ( is_home() ) :
				$blog_sidebar = lerm_options( 'blog_sidebar_select'  ) ? lerm_options( 'blog_sidebar_select' ) : 'home-sidebar';
				dynamic_sidebar( $blog_sidebar );
			elseif ( is_front_page() ) :
				$front_page_sidebar = lerm_options( 'front_page_sidebar' ) ? lerm_options( 'front_page_sidebar' ) : 'home-sidebar';
				dynamic_sidebar( $front_page_sidebar );
			endif;
			?>
			<?php
			if ( is_singular( 'page' ) ) :
				$page_sidebar = lerm_options( 'page_sidebar' ) ? lerm_options( 'page_sidebar'  ) : 'home-sidebar';
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
	<?php
endif;
