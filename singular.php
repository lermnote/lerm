<?php
/**
 * Singular template for posts and pages.
 *
 * @package Lerm
 */

get_header();

$template_options = lerm_get_template_options();
?>
<main role="main" class="container"><!--.container-->
	<?php get_template_part( 'template-parts/components/breadcrumb' ); ?>
	<div <?php lerm_row_class(); ?>><!--.row-->
		<div id="primary" <?php lerm_column_class(); ?>><!--.col-md-12 .col-lg-8-->
			<div class="site-main">
				<?php
				if ( have_posts() ) :
					while ( have_posts() ) :
						the_post();

						get_template_part( 'template-parts/post/content', get_post_type() );

						if ( is_singular( 'post' ) ) :
							$permalink       = get_permalink();
							$site_name       = get_bloginfo( 'name' );
							$permalink_title = sprintf(
								/* translators: %s: post title */
								__( 'Permalink to %s', 'lerm' ),
								get_the_title()
							);
							?>
							<ul class="card entry-copyright p-3 mb-2 list-unstyled">
								<li>
									<strong><?php esc_html_e( 'Copyright notice:', 'lerm' ); ?></strong>
									<span>
										<?php
										printf(
											/* translators: 1: opening link tag, 2: site name, 3: closing link tag */
											wp_kses_post( __( 'This article was published by %1$s%2$s%3$s. Please credit the original source when reposting.', 'lerm' ) ),
											'<a href="' . esc_url( $permalink ) . '" rel="bookmark" title="' . esc_attr( $permalink_title ) . '">',
											esc_html( $site_name ),
											'</a>'
										);
										?>
									</span>
								</li>
								<li>
									<strong><?php esc_html_e( 'Permalink:', 'lerm' ); ?></strong>
									<span>
										<a href="<?php echo esc_url( $permalink ); ?>" rel="bookmark" title="<?php echo esc_attr( $permalink_title ); ?>">
											<?php the_title(); ?> | <?php echo esc_html( $site_name ); ?>
										</a>
									</span>
								</li>
							</ul>

							<?php if ( ! empty( $template_options['related_posts'] ) ) : ?>
								<?php get_template_part( 'template-parts/components/related-posts' ); ?>
							<?php endif; ?>

							<?php get_template_part( 'template-parts/components/post-navigation' ); ?>
							<?php
						endif;

						if ( comments_open() || get_comments_number() ) {
							comments_template();
						}

					endwhile;
				endif;
				?>
			</div>
		</div>

		<?php get_sidebar(); ?>

	</div><!--.row-->
</main><!--.container-->
<?php
get_footer();
