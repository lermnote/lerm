<?php
/**
 * Template Name: 无边栏
 * @authors lerm http://lerm.net
 * @date    2016-10-26
 * @since lerm 2.0
 */
get_header(); ?>
  <?php if (have_posts()) :
      // Start the loop.
      while ( have_posts() ) : the_post();
        get_template_part( 'template-parts/content', 'page' );
        comments_template();
      // End the loop.
      endwhile;
    endif;?>
<?php get_footer();?>
