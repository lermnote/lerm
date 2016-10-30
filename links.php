<?php
/**
 * Template Name: 链接模板
 * @authors lerm http://lerm.net
 * @date    2016-09-02
 * @since   lerm 2.0
 */
get_header();?>

  <article class="card">

    <?php if (have_posts()) : the_post(); update_post_caches($posts); ?>

      <header class="card-header text-center">
        <h2><?php the_title(); ?></h2>
          <small> 人生得一知已足矣，斯世当以同怀视之 </small>
      </header>

      <main class="card-block">
        <?php the_content(); ?>

        <?php $bookmarks = get_bookmarks();
        if ( !empty($bookmarks ) ) {
          echo '<ul class="link-content card-block clearfix">';
          foreach ( $bookmarks as $bookmark ) {
            echo '<li class="pull-left media-left text-center media-heading"><a href="' . $bookmark->link_url . '" title="' . $bookmark->link_description . '" target="_blank"><img src="https://f.ydr.me/'.$bookmark->link_url.'" onerror="javascript:this.src=\' ', $default_ico , '\'" class="img-circle avatar"><span>'. $bookmark->link_name .'</span></a></li>';
          }
          echo '</ul>';
        }?>
      </main>

    <?php endif; ?>

  </article>

<?php comments_template(); ?>
<?php get_footer();?>
