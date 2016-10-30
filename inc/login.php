<?php
  function lerm_login_logo() {
    echo '<style type="text/css">
    body{background: url('.get_bloginfo('template_directory').'/inc/bing.php); background-size:cover}
    h1 a { background-image:url('.get_bloginfo('template_directory').'/favicon.ico) !important;}
    #nav, #nav a, #backtoblog a{color:#fff!important;font-size:112%}
    #loginform{opacity:0.6}
    </style>';
  }
  add_action('login_head', 'lerm_login_logo');
  //logo链接为博客首页
  add_filter('login_headerurl', create_function(false,"return get_bloginfo('url');"));
  //自定义登录页面的LOGO提示为网站名称
  add_filter('login_headertitle', create_function(false,"return get_bloginfo('name');"));
