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
    <header class="card card-block text-center">
      <h2><?php wp_title('');?></h2>
      <small>
        <?php $term_description = term_description();// Show an optional term description.
    		if ( ! empty( $term_description ) ) :
    			printf( '<p class="taxonomy-description">%s</p>', $term_description );
    		endif;?>
      </small>
    </header>
    <?php
      // Start the loop.
      while ( have_posts() ) : the_post();
        get_template_part( 'template-parts/content', get_post_format() );
      // End the loop.
      endwhile;
    //pagination
    pagination();
  else:
    get_template_part( 'template-parts/content', 'none' );
  endif; ?>
</div><!-- .col-xs-12 .col-sm-9 -->
<?php get_sidebar();?>
<?php get_footer();?>
