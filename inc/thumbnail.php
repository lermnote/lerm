<?php
if ( ! function_exists( 'lerm_post_thumbnail' ) ) :
  /**
    * @authors lerm http://lerm.net
    * @date    2016-08-27
    * @since   lerm 2.0
    *
    *文章列表页缩略图显示
    *显示特色图像，或者文章内第一张图片，或者随机显示一张图片
  **/
  function lerm_thumbnail() {
    global $post;
    if ( post_password_required() || is_attachment()) {
  		return;
  	}
    if ( has_post_thumbnail() ) {
      // 判断该文章是否已经设置了“特色图像”，如果有则直接显示该特色图像的缩略图
      echo '<figure class="thumbnail pull-left"><a href="' . get_permalink() . '" title="' . get_the_title() . '">';
      the_post_thumbnail();
      echo '</a></figure>';
    } else {
      //如果文章没有设置特色图像，则查找文章内是否包含图片
      $content = $post->post_content;
      preg_match_all('/<img [^>]*src=["|\']([^"|\']+)/i', $content, $strResult, PREG_PATTERN_ORDER);
      $n = count($strResult[1]);
      if ($n > 0) {
        // 如果文章内包含有图片，则取第一张图片的缩略图；
        echo '<figure class="thumbnail pull-left"><a href="'. get_permalink() .'" rel="bookmark" title="' . get_the_title() . '"><img src="' . $strResult[1][0] . '" alt="' . get_the_title() . '"></a></figure>';
      }else{
        //如果文章内没有图片，则随机显示根目录下images/random文件夹下的一张图片。
        echo '<figure class="thumbnail pull-left"><a href="'.get_permalink().'" rel="bookmark" title="' . get_the_title() . '"><img src="'.get_template_directory_uri().'/img/random/'.rand(1,10).'.jpg" alt="' . get_the_title() . '"></a></figure>';
      }
    }
  }
endif;
