<?php
/**
 * Singular template for posts and pages
 *
 * Put this file in your theme root as singular.php.
 *
 * @package Lerm https://lerm.net
 * @since 1.0
 */
get_header();
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

						/*
						 * 加载不同 post type 的 content template：
						 * - 页面会加载 template-parts/content/content-page.php（如果存在）
						 * - 文章会加载 template-parts/content/content-post.php（如果存在）
						 * - 或者统一使用 content.php 作为兜底
						 */
						get_template_part( 'template-parts/post/content', get_post_type() );

						// 下面为仅在文章（post）显示的部分
						if ( is_singular( 'post' ) ) :
							?>
							<ul class="card entry-copyright p-3 mb-2 list-unstyled">
								<li><strong>版权声明：</strong>
									<span>本文由<a href="<?php the_permalink(); ?>" rel="bookmark" title="本文固定链接 <?php the_permalink(); ?>"> <?php bloginfo( 'name' ); ?> </a> 整理发表，转载请注明出处</span>
								</li>
								<li><strong>转载信息：</strong>
									<span><a href="<?php the_permalink(); ?>" rel="bookmark" title="本文固定链接 <?php the_permalink(); ?>"> <?php the_title(); ?> | <?php bloginfo( 'name' ); ?></a></span>
								</li>
							</ul>

							<?php if ( lerm_options( 'related_posts' ) ) : ?>
								<?php get_template_part( 'template-parts/components/related-posts' ); ?>
							<?php endif; ?>

							<?php get_template_part( 'template-parts/components/post-navigation' ); ?>

							<?php
						endif;

						// comments 对 page & post 通用（如果开启）
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
