<?php
/**
 * Template Name: 归档模板
 * @authors lerm http://lerm.net
 * @date    2016-09-03
 * @since   lerm 2.0
 */
get_header(); ?>
<div class="col-sm-12 col-md-9">
  <?php if (have_posts()) : ?>
    <?php
			// Start the loop.
			while ( have_posts() ) : the_post();?>

      <article id="post-<?php the_ID(); ?>" class="card card-block">
      	<header class="card-header text-center">

      		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>

      			<small class="entry-meta">
              【注: 点击月份可以展开】
      			</small>
      	</header><!-- .entry-header -->

        <?php the_content(); ?>
        <?php lerm_archives_list(); ?>

      </article><!-- #post-## -->

    <?php // End the loop.
    endwhile; ?>

  <?php endif; ?>
</div>
<?php get_sidebar();?>
<?php get_footer();?>
