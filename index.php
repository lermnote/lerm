<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme and one
 * of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query,
 * e.g., it puts together the home page when no home.php file exists.
 *
 *
 * @authors lerm http://lerm.net
 * @date    2016-09-19
 * @since   Lerm 2.0
 */

get_header(); ?>

<div class="col-sm-12 col-md-9">
  <?php if (have_posts()) :
    if( is_home() && ! is_paged() ):
      //slider
      lerm_slide();
      //get sticky post
      //get_template_part( 'inc/sticky' );
    endif;

    //$args = array(
    	//'post__not_in'        => get_option( 'sticky_posts' ),
      //'ignore_sticky_posts' => 1
    //);
    //$r = new WP_Query( $args );?>

    <?php
			// Start the loop.
			while ( have_posts() ) :the_post();
				get_template_part(  'template-parts/content', get_post_format() );
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
