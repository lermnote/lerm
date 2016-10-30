<?php
/**
 * The template for displaying the header
 *
 * Displays all of the head element and everything up until the "row" div.
 *
 * @package lerm
 * @date 2016-10-26
 * @since lerm 1.0
 */
?><!DOCTYPE html>
<html lang="zh-CN">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
  <!-- 上述3个meta标签*必须*放在最前面，任何其他内容都*必须*跟随其后！ -->
  <?php lerm_keywords_and_description(); ?>
  <!-- Bootstrap -->
  <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
    <script src="//cdn.bootcss.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="//cdn.bootcss.com/respond.js/1.4.2/respond.min.js"></script>
  <![endif]-->
  <?php wp_head(); ?>
</head>
<body>
  <nav class="navbar navbar-light">
    <button class="navbar-toggler hidden-sm-up pull-right" type="button" data-toggle="collapse" data-target="#navbar">
      &#9776;
    </button>
    <div class="container">
      <?php if ( of_get_option("blink") ) :?>
      <div class="site-logo">
        <?php else: ?>
          <div class="logo">
        <?php endif; ?>

        <?php lerm_the_custom_logo(); ?>

        <?php if ( is_front_page() && is_home() ): ?>
          <h1 class="navbar-brand site-title"><a  href="<?php echo esc_url( home_url( '' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
        <?php else : ?>
          <p class="h1 navbar-brand site-title"><a href="<?php echo esc_url( home_url( '' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
        <?php endif;

        $description = get_bloginfo( 'description', 'display' );
        if ( $description || is_customize_preview() ):?>
          <p class="navbar-brand site-description hidden-sm-down"><small class="text-muted"> | <?php echo $description; ?></small></p>
        <?php endif; ?><!-- .navbar-brand -->

      </div>
      <?php
        wp_nav_menu(array(
          'theme_location' => 'primary', //指定显示的导航名，如果没有设置，则显示第一个
          'container' => 'div', //最外层容器标签名
          'container_class' => 'collapse navbar-toggleable-xs pull-right', //最外层容器class名
          'container_id' => 'navbar', //最外层容器id值
          'menu_class' => 'nav navbar-nav', //ul标签class
          'menu_id' => '', //ul标签id
          'before' => '', //显示在导航a标签之前
          'after' => '', //显示在导航a标签之后
          'link_before' => '', //显示在导航链接名之后
          'link_after' => '', //显示在导航链接名之前
          'items_wrap' => '<ul class="%2$s">%3$s</ul>',
          'walker' => new lerm_walker_nav_menu()));
      ?>
    </div>
  </nav>
  <div class="container">
    <div class="collapse" id="search">
      <div class="searchform">
        <?php get_search_form(); ?>
      </div>
    </div>
    <?php if (function_exists('lerm_breadcrumbs')) lerm_breadcrumbs(); ?>
    <div class="row">
