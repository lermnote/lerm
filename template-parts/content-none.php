<?php
/**
 * The template part for displaying a message that posts cannot be found
 *
 * @auther Lerm
 * @date 2016-10-26
 * @since Lerm 2.0
 */
?>

<section class="card card-block">
	<header class="page-header card card-block card-inverse card-primary text-center search-title">
		<h1 class="page-title card-blockquote"><?php _e( '未找到相关内容', 'lerm' ); ?></h1>
	</header><!-- .page-header -->

	<div class="page-content">
		<?php if ( is_home() && current_user_can( 'publish_posts' ) ) : ?>

			<p><?php printf( __( '准备好发表您的第一篇文章吗? <a href="%1$s">Get started here</a>.', 'lerm' ), esc_url( admin_url( 'post-new.php' ) ) ); ?></p>

		<?php elseif ( is_search() ) : ?>

			<p><?php _e( '抱歉！未找到相关搜索结果，请尝试搜索其他关键词.', 'lerm' ); ?></p>

			<?php get_search_form(); ?>


		<?php else : ?>

			<p><?php _e( '我们无法完成您的要求，请尝试搜索？', 'lerm' ); ?></p>
			<?php get_search_form(); ?>

		<?php endif; ?>
	</div><!-- .page-content -->
	<canvas id="canvas" width="300" height="300"></canvas>
</section><!-- .no-results -->
