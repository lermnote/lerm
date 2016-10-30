<?php
/**
 * archives list function
 * @authors lerm http://lerm.net
 * @date    2016-09-02
 * @since lerm 2.0
 */
 //文章归档
function lerm_archives_list() {
  if( !$output = get_option('lerm_db_cache_archives_list') ) {
    $output = '<div id="archives"><a class="label label-primary" id="al_expand_collapse" href="#">全部展开/收缩</a>';
    $args = array(
      'post_type' => 'post', //如果你有多个 post type，可以这样 array('post', 'product', 'news')
      'posts_per_page' => -1, //全部 posts
      'ignore_sticky_posts' => 1 //忽略 sticky posts
    );
    $the_query = new WP_Query( $args );
    $posts_rebuild = array();
    $year = $mon = 0;
    while ( $the_query->have_posts() ) : $the_query->the_post();
      $post_year = get_the_time('Y');
      $post_mon = get_the_time('m');
      $post_day = get_the_time('d');
      if ($year != $post_year) $year = $post_year;
      if ($mon != $post_mon) $mon = $post_mon;
      $posts_rebuild[$year][$mon][] = '<li>'. get_the_time('d日: ') .'<a href="'. get_permalink() .'">'. get_the_title() .'</a> <label class="label label-default">'. get_comments_number('', '1', '%') .'</label></li>';
    endwhile;
    wp_reset_postdata();
    foreach ($posts_rebuild as $key_y => $y) {
      $output .= '<h3 class="al_year">'. $key_y .' 年</h3><ul class="al_mon_list">'; //输出年份
      foreach ($y as $key_m => $m) {
        $posts = ''; $i = 0;
        foreach ($m as $p) {
          ++$i;
          $posts .= $p;
        }
        $output .= '<li><span class="al_mon">'. $key_m .' 月 <label class="label label-default">'. $i .' 篇</label></span><ul class="al_post_list">'; //输出月份
        $output .= $posts; //输出 posts
        $output .= '</ul></li>';
      }
      $output .= '</ul>';
    }

    $output .= '</div>';
    update_option('lerm_db_cache_archives_list', $output);
  }
  echo $output;
}
function clear_db_cache_archives_list() {
  update_option('lerm_db_cache_archives_list', ''); // 清空 lerm_archives_list
}
add_action('save_post', 'clear_db_cache_archives_list'); // 新发表文章/修改文章时
