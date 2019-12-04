<?php
/**
 * Template Name: 链接模板
 *
 * @author Lerm http://www.hanost.com
 * @date    2016-09-02
 * @since   lerm 2.0
 */
get_header(); ?>
	<main role="main" class="container">
	<?php $class = ( 'layout-1c-narrow' === lerm_page_layout() ) ? 'justify-content-md-center' : ''; ?>
		<div class="row <?php echo esc_attr( $class ); ?> ">
		<?php $class = wp_is_mobile() ? 'col-md-12' : 'col-lg-8'; ?>
			<div class="<?php echo esc_attr( $class ); ?> px-0">
				<div class="site-main">
					<?php if ( have_posts() ) : ?>
						<?php
						while ( have_posts() ) :
							the_post();
							?>
							<article id="post-<?php the_ID(); ?>"  <?php post_class( 'entry px-3' ); ?>>
								<div class="content-area">
									<header class="entry-header text-center pb-2">
										<?php the_title( '<h1 class="entry-title p-3">', '</h1>' ); ?>
									</header>
									<div class="entry-content pt-2">
										<?php
										the_content(
											sprintf(
												__( 'Continue reading', 'lerm' ) . '<span class="screen-reader-text">%s</span>',
												get_the_title()
											)
										);
										?>
										<?php
										wp_list_bookmarks(
											array(
												'before'  => '<li class="list-inline-item border p-2 blogroll-item">',
												'after'   => '</li>',
												'categorize' => 1,
												'orderby' => 'date',
												'order'   => 'ASC',
												'show_images' => true,
												'show_name' => true,
												'title_before' => '<h2>',
												'title_after' => '</h2>',
												'category_orderby' => 'name',
												'category_order' => 'ASC',
												'class'   => 'linkcat',
												'category_before' => '<div id=%id class=%class>',
												'category_after' => '</div>',
												'link_before' => '',
												'link_after' => '',
												'between' => '',
												'echo'    => 1,
											)
										);
										?>
										<?php
										wp_link_pages(
											array(
												'before'   => '<div class="page-links"><span class="page-links-title">' . __( 'Pages:', 'lerm' ) . '</span>',
												'after'    => '</div>',
												'link_before' => '<span>',
												'link_after' => '</span>',
												'pagelink' => '<span class="screen-reader-text">' . __( 'Page', 'lerm' ) . ' </span>%',
												'separator' => '<span class="screen-reader-text">, </span>',
											)
										);
										?>
									</div>
								</div>
							</article>
							<?php
							// If comments are open or we have at least one comment, load up the comment template.
							if ( comments_open() || get_comments_number() ) :
								comments_template();
							endif;

						endwhile;
						?>
					<div class="py-3">
						<?php lerm_pagination(); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php get_sidebar(); ?>
	</div>
</main>
<?php
get_footer();
