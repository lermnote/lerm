<?php
/**
 * Template Name: 页面模板
 * @authors lerm http://lerm.net
 * @date    2016-10-26
 * @since lerm 2.0
 */
get_header(); ?>
<div class="col-sm-12 col-md-9">
  <?php if (have_posts()) : ?>
    <?php
      // Start the loop.
      while ( have_posts() ) : the_post();
        get_template_part( 'template-parts/content', 'page' );
         //If comments are open or we have at least one comment, load up the comment template
				  comments_template();
      // End the loop.
      endwhile;
    endif;?>
</div><!-- .col-xs-12 .col-sm-9 -->
<?php get_sidebar();?>
<?php get_footer();?>
