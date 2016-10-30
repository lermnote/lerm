<?php
/**
 * The template for displaying all single posts and attachments
 *
 * @package Lerm
 * @date 2016-10-26
 * @since Lerm 2.0
 */
get_header(); ?>
<div class="col-sm-12 col-md-9">
  <?php if (have_posts()): ?>
    <?php set_post_views(get_the_ID());
    // Start the loop.
    while ( have_posts() ) : the_post();
     /*
      * Include the Post-Format-specific template for the content.
      * If you want to override this in a child theme, then include a file
      * called content-___.php (where ___ is the Post Format name) and that will be used instead.
      */
        get_template_part( 'template-parts/content', 'single' );

    // End the loop.

    endwhile; ?>
  <?php else:
    get_template_part( 'content', 'none' );?>
  <?php endif; ?>
  <div class="card card-block">
      <b>转载请注明出处: </b> <a href="<?php the_permalink() ?>" title="<?php the_title(); ?>"><?php the_title(); ?> | <?php bloginfo('name'); ?></a>
  </div>
  <?php related_posts(); ?>
    <ul class="pager">
      <?php if (get_previous_post()) {
        echo '<li class="pager-prev"><a title="'.get_previous_post()->post_title.' " href="'.get_permalink( get_previous_post()->ID ).'">上一篇</a></li>';
      } else { echo '<li class="pager-prev disabled"><a href="javascript:">已是最后文章</a></li>';} ?>
      <li class="current hidden-xs-down"> <a href="#">再次浏览</a></li>
      <?php if ( get_next_post() ) {
        echo '<li class="pager-next"><a title="'.get_next_post()->post_title.' " href="'.get_permalink( get_next_post()->ID ).'">下一篇</a></li>';
      } else { echo '<li class="pager-next disabled"><a href="javascript:">已是最新文章</a></li>'; } ?>
    </ul>
  <?php comments_template(); ?>
</div><!--.col-xs-12 .col-sm-9-->
<?php get_sidebar();?>
<?php get_footer();?>
