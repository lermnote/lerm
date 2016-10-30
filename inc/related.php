<?php
/**
 * related post show on single
 */
function related_posts(){
  global $post, $wpdb;
  $post_tags = wp_get_post_tags($post->ID);
  if ($post_tags) {
    $tag_list = '';
    foreach ($post_tags as $tag) {
      // 获取标签列表
      $tag_list .= $tag->term_id.',';
    }
    $tag_list = substr($tag_list, 0, strlen($tag_list)-1);
    $related_posts = $wpdb->get_results("
      SELECT DISTINCT ID, post_title
      FROM {$wpdb->prefix}posts, {$wpdb->prefix}term_relationships, {$wpdb->prefix}term_taxonomy
      WHERE {$wpdb->prefix}term_taxonomy.term_taxonomy_id = {$wpdb->prefix}term_relationships.term_taxonomy_id
      AND ID = object_id
      AND taxonomy = 'post_tag'
      AND post_status = 'publish'
      AND post_type = 'post'
      AND term_id IN (" . $tag_list . ")
      AND ID != '" . $post->ID . "'
      ORDER BY RAND()
      LIMIT 4");
      // 以上代码中的  为限制只获取篇相关文章
      // 通过修改数字 ，可修改你想要的文章数量
    if ( $related_posts ) {
      echo '<div class="card">';
      foreach ($related_posts as $related_post) {
        $content = $related_post->post_title;
        $content = mb_strimwidth(strip_tags($content), 0, '21', '...', 'UTF-8');
      echo '<div class="card-block related-post">
              <a href="'.get_permalink($related_post->ID).'" rel="bookmark" title="'.$related_post->post_title.'">
                <img src="'.get_template_directory_uri() .'/img/random/'.rand(1,10).'.jpg" alt="'.$related_post->post_title.'">
                <p class="card-text">'. $content.'</p>
              </a>
            </div>';
	  }
$num=count($related_posts);
for ($i=$num; $i < 4 ; $i++) {
  $content = get_the_title();
  $content = mb_strimwidth(strip_tags($content), 0, '21', '...', 'UTF-8');
  echo '<div class="card-block related-post">
          <a href="'.get_permalink().'" rel="bookmark" title="'. get_the_title().'">
            <img src="'.get_template_directory_uri() .'/img/random/'.rand(1,10).'.jpg'.'" alt="'. get_the_title().'">
            <p class="card-text">'.  $content.'</p>
          </a>
        </div>';

      }
      echo '</div>';
    } else { echo '<div class="card card-block">暂无相关文章</div>';}
  } else { echo '<div class="card card-block">暂无相关文章</div>';}
}
