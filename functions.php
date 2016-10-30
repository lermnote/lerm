<?php
 /**
 * lerm  functions and definitions
 * @authors lerm http://lerm.net
 * @date    2016-08-28
 * @since   lerm 2.0
 */
define ( 'HOME', get_template_directory_uri() );
define ( 'DIR', get_template_directory() );

//载入后台框架
if ( !function_exists ( 'optionsframework_init' ) ) {
  define( 'OPTIONS_FRAMEWORK_DIRECTORY', HOME . '/options/' );
  require_once dirname( __FILE__ ) . '/options/options-framework.php';
  // Loads options.php from child or parent theme
  $optionsfile = locate_template( 'options.php' );
  load_template ( $optionsfile );
}

function lerm_theme_setup () {
  //站点标题
  add_theme_support( 'title-tag' );
  //站点logo
  add_theme_support('custom-logo', array(
    'height'      => 50,
    'flex-width'  => true,
    'header-text' => array( 'site-title', 'site-description' ),
  ) );
  //特色图像
  add_theme_support( 'post-thumbnails' );
  //
  add_theme_support( 'post-formats', array( 'aside', 'gallery' ) );
  //注册菜单
  register_nav_menus( array(
    'primary'          => '主菜单',
    'secondary'        => '顶部菜单',
  ) );
  /*
	 * Enable support for Post Formats.
	 *
	 */

  add_theme_support( 'post-formats', array('aside','image','video','quote','link',
  'gallery',
  'status',
  'audio',
  'chat',
) );

}
add_action( 'after_setup_theme', 'lerm_theme_setup' );

//将标题连接符改成 "|",默认为 "-"
if(of_get_option('title_separator') ):
  function lerm_title_separator () {
    return '|';
  }
  add_filter('document_title_separator', 'lerm_title_separator');
endif;
//载入CSS和JS
function lerm_enqueue_scripts () {
  //remove JS
  wp_deregister_script( 'jquery' );
  wp_deregister_script('wp-embed');

  if ( !is_admin() ) {
    //挂载样式表
    wp_enqueue_style( 'bootstrap_css',  HOME . '/css/bootstrap.min.css', array(), '4.0.0-alpha');

    wp_enqueue_style( 'font-awesome',  HOME . '/css/font-awesome.min.css', array(), '4.6.3');

    wp_enqueue_style( 'style', get_stylesheet_uri() );

    //enqueue JS
    wp_enqueue_script( 'jQuery', HOME . '/js/jquery.min.js', array() ,'3.1.0'  );
    wp_enqueue_script( 'bootstrap_js', HOME . '/js/bootstrap.min.js', array(), '4.0.0' , true);
    wp_enqueue_script( 'responsivelyLazy',  HOME . '/js/responsivelyLazy.min.js', array(), '1.2.1');
    wp_enqueue_script( 'lightbox', HOME . '/js/jquery.lightbox-0.5.min.js', array(jQuery), '0.5', true );
    wp_enqueue_script( 'affix', HOME . '/js/affix.js', array(), '3.5.7' );
    wp_enqueue_script( 'lerm_js', HOME . '/js/lerm.js', array(), '1.0' );
  }
  if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
    wp_enqueue_script( 'comment-reply' );
  }
  if ( is_singular() && wp_attachment_is_image() ) {

  }
}
add_action ( 'wp_enqueue_scripts', 'lerm_enqueue_scripts' );

//代码功能：实现js异步加载
//add_filter( 'clean_url', 'lerm_async_script',11,1);
//function lerm_async_script( $url ){
//    if(strpos($url, '.js') === false){
//        return $url;
//    }
//    return "$url' async='async";
//};
//面包屑导航
function lerm_breadcrumbs() {
  global $post;
  $home = '<i class="fa fa-home" aria-hidden="true"></i> 首页 '; // text for the 'Home' link
  $before = '<li class="active">'; // tag before the current crumb
  $after = '</li>'; // tag after the current crumb
  $homeLink = get_bloginfo('url');
  $crumb_notic_link=of_get_option("crumb-notice-link")? of_get_option("crumb-notice-link"): "";
  $crumb_notice_content=of_get_option("crumb-notice-content")? of_get_option("crumb-notice-content"): "";
  if (is_home() || is_front_page()) {
    if (of_get_option("crumb-notice")? of_get_option("crumb-notice"):""){
      if (of_get_option("notice-scroll")? of_get_option("notice-scroll"):"") {
        echo '<marquee id="crumbs" onmouseover="this.stop()" onmouseout="this.start()" scrollamount=5 direction="left"><a href="' . $crumb_notic_link .'"><i class="fa fa-bullhorn" aria-hidden="true"></i>最新公告：' . $crumb_notice_content . '</a></marquee>';
      } else {
        echo '<div id="crumbs"><a href="' . $crumb_notic_link .'"><i class="fa fa-bullhorn" aria-hidden="true"></i>最新公告：' . $crumb_notice_content . '</a></div>';
      }
    } else {
      echo '<ol class="breadcrumb"><li class="active">您的位置：' . $home . '</li></ol>';
    }
  } else {
    echo '<ol class="breadcrumb"><li><a href="' . $homeLink . '">' . $home . '</a></li>';
    if ( is_category()) {
      echo $before . single_cat_title('', false) . $after;
    } elseif ( is_single() && !is_attachment()) {
      $cat = get_the_category(); $cat = $cat[0];
      $cats = get_category_parents($cat, TRUE, '');
      echo '<li>' . $cats . '</li>' . $before . "正文" . $after;
    } elseif ( is_page()) {
      echo $before . get_the_title() . $after;
    } elseif ( is_search() ) {
      echo '<li>搜索</li>' . $before . get_search_query() . $after;
    } elseif ( is_tag() ) {
      echo '<li>标签</li>' . $before . single_tag_title('', false) . $after;
    } elseif ( is_404() ) {
      echo $before . '404' . $after;
    }
      echo '</ol>';
  }
}
 //pagination
 function pagination(){
   the_posts_pagination( array(
     'prev_text'          => '<span aria-hidden="true">上一页</span>',
     'next_text'          => '<span aria-hidden="true">下一页</span>',
     'before_page_number' => '<span class="screen-reader-text">' . __( '第 ', 'lerm' ) . '</span>',
     'after_page_number'  => '<span class="screen-reader-text">' . __( ' 页', 'lerm' ) . '</span>',
   ) );
 }

//关键字和描述
function lerm_keywords_and_description () {
  global $post;
  $blog_name = get_bloginfo('name');
  $keywords  = array();
	$description = '';
	if ( is_home() ) {
	  $keywords[]    = of_get_option( 'keywords' );
		$description   = trim(of_get_option( 'description' ) );
	} elseif ( is_singular() ) {
		if ( has_tag() ) { foreach( (get_the_tags()) as $tag ) { $keywords[] = $tag->name; }
		} else { $keywords[]= trim( wp_title('', false) ); }
    if( !empty( $post->post_excerpt ) ) {$text = $post->post_excerpt;
    } else { $text = $post->post_content;}
    $description = trim( str_replace( array( "\r\n", "\r", "\n", "　", " "), " ", str_replace( "\"", "'", strip_tags( $text ) ) ) );
    if ( !( $description ) ) $description = $blog_name . "-" . trim( wp_title('', false) );
	} elseif ( is_tag() ){
    $keywords[]  = single_tag_title('', false);
    $description = $blog_name . "-" . single_tag_title('', false);
	} elseif ( is_category() ) {
    $keywords[]  = single_term_title( "", false );
		$description = strip_tags( term_description() );
	} elseif ( is_search() ) {
    $keywords[]  = get_search_query();
  } else {
    $keywords[]  = trim( wp_title('', false) );
    $description = $blog_name . "-" . trim( wp_title('', false) );
  }
  $description   = mb_substr( $description, 0, 200, 'utf-8' );
	echo '<meta name="keywords" content="'.( implode(",", $keywords) ).'">
    <meta name="description" content="'.$description.'">';
}
//百度主动推送
if(!function_exists('Baidu_Submit') && of_get_option('sitemap_submit') ):
    function Baidu_Submit($post_ID) {
        $WEB_TOKEN  = of_get_option('sitemap_token');  //这里请换成你的网站的百度主动推送的token值
        $WEB_DOMAIN = get_option('home');
        //已成功推送的文章不再推送
        if(get_post_meta($post_ID,'Baidusubmit',true) == 1) return;
        $url = get_permalink($post_ID);
        $api = 'http://data.zz.baidu.com/urls?site='.$WEB_DOMAIN.'&token='.$WEB_TOKEN;
        $request = new WP_Http;
        $result = $request->request( $api , array( 'method' => 'POST', 'body' => $url , 'headers' => 'Content-Type: text/plain') );
        $result = json_decode($result['body'],true);
        //如果推送成功则在文章新增自定义栏目Baidusubmit，值为1
        if (array_key_exists('success',$result)) {
            add_post_meta($post_ID, 'Baidusubmit', 1, true);
        }
    }
    add_action('publish_post', 'Baidu_Submit', 0);
endif;
//文章阅读数量统计
function set_post_views($postID) {
  if (!current_user_can('level_10')) {
    $count_key = 'post_views_count';
    $count = get_post_meta($postID, $count_key, true);
    if ($count=='') {
      $count = 0;
      delete_post_meta($postID, $count_key);
      add_post_meta($postID, $count_key, '0');
    } else {
      $count++;
	    update_post_meta($postID, $count_key, $count);
    }
  }
}
function get_post_views($postID){
  $count_key = 'post_views_count';
  $count     = get_post_meta($postID, $count_key, true);
  if( $count =='' ) {
    delete_post_meta($postID, $count_key);
    add_post_meta($postID, $count_key, '0');
    return "0";
  }
  return $count;
}
//文章导航
if (of_get_option("post_navigation")):
  function lerm_post_navigation(){
    if ( is_single()):?>
      <div class="chevronavi hidden-xs-down">
        <?php if (get_previous_post()) {
          echo '<a class="chevron-left" title="'.get_previous_post()->post_title.' " href="'.get_permalink( get_previous_post()->ID ).'"><i class="fa fa-chevron-left fa-3x"></i></a>';}?>
        <?php if ( get_next_post() ) {
          echo '<a class="chevron-right" title="'.get_next_post()->post_title.' " href="'.get_permalink( get_next_post()->ID ).'"><i class="fa fa-chevron-right fa-3x"></i></a>';} ?>
      </div>
    <?php endif;
  }
  add_action('wp_footer', 'lerm_post_navigation');
endif;
//文章点赞
add_action('wp_ajax_nopriv_post_like', 'post_like');
add_action('wp_ajax_post_like', 'post_like');
function post_like(){
    global $wpdb,$post;
    $id = $_POST["um_id"];
    $action = $_POST["um_action"];
    if ( $action == 'ding'){
        $specs_raters = get_post_meta($id,'post_like',true);
        $expire = time() + 99999999;
        $domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? $_SERVER['HTTP_HOST'] : false; // make cookies work with localhost
        setcookie('post_like_'.$id,$id,$expire,'/',$domain,false);
        if (!$specs_raters || !is_numeric($specs_raters)) {
            update_post_meta($id, 'post_like', 1);
        }
        else {
            update_post_meta($id, 'post_like', ($specs_raters + 1));
        }
        echo get_post_meta($id,'post_like',true);
    }
    die;
}

//编辑器增加标签
function lerm_add_quicktags(){
    if(wp_script_is('quicktags')){
?>
    <script type="text/javascript">
        QTags.addButton('pre','pre','<pre>','</pre>');
        QTags.addButton('H1','h1','<h1>','</h1>');
        QTags.addButton('H2','h2','<h2>','</h2>');
        QTags.addButton('H3','h3','<h3>','</h3>');
        QTags.addButton('H4','h4','<h4>','</h4>');
        QTags.addButton('p','p','<p>','</p>');
        QTags.addButton('div','div','<div>','</div>');
        QTags.addButton('a','a','<a href="" title="">','</a>');
        QTags.addButton('downlod','下载按钮','<i class="fa fa-download fa-2x">','</i>');
        QTags.addButton('alert-success','成功背景','<div class="alert alert-success">','</div>');
        QTags.addButton('alert-info','信息背景','<div class="alert alert-info">','</div>');
        QTags.addButton('alert-warning','警告背景','<div class="alert alert-warning">','</div>');
        QTags.addButton('alert-danger','危险背景','<div class="alert alert-danger">','</div>');
        QTags.addButton('dismiss-success','可关闭成功','<div class="alert alert-success alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>','</div>');
        QTags.addButton('dismiss-info','可关闭信息','<div class="alert alert-info alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>','</div>');
        QTags.addButton('dismiss-warning','可关闭警告','<div class="alert alert-warning alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>','</div>');
        QTags.addButton('dismiss-danger','可关闭危险','<div class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>','</div>');
        QTags.addButton('a','a','<a href="" title="">','</a>');
    </script>
<?php
    }
}
add_action('admin_print_footer_scripts','lerm_add_quicktags');

// 编辑器增强
function enable_more_buttons($buttons) {
	$buttons[] = 'sub';//上标
	$buttons[] = 'sup';//下标
	$buttons[] = 'fontselect';//字体选择
	$buttons[] = 'fontsizeselect';//字号选择
	//$buttons[] = 'styleselect';//样式选择
	$buttons[] = 'wp_page';//分页符
	$buttons[] = 'backcolor';//背景颜色
	return $buttons;
}
add_filter( "mce_buttons_2", "enable_more_buttons" );

//链接功能
add_filter( 'pre_option_link_manager_enabled', '__return_true' );
//添加后台左下角文字
function lerm_admin_footer_text($text) {
  $text = '感谢使用<a target="_blank" href=http://www.lerm.net/ >Lerm主题</a>进行创作';
  return $text;
}
add_filter('admin_footer_text', 'lerm_admin_footer_text');


//Toolbar站点标题的菜单下，添加一个主题选项链接。
function minty_add_theme_options_to_admin_bar( $wp_admin_bar ) {
    $args = array(
        'id'    => 'theme-options',
        'title' => '主题选项',
        'href'  => admin_url( 'themes.php?page=options-framework' ),
        'parent'=> 'appearance'
    );
    $wp_admin_bar->add_node( $args );
}
add_action( 'admin_bar_menu', 'minty_add_theme_options_to_admin_bar', 999 );



//搜索结果排除所有页面
if (of_get_option("search_filter")):
  function search_filter_page($query) {
	  if ($query->is_search) {
		$query->set('post_type', 'post');
	}
	return $query;
  }
  add_filter('pre_get_posts','search_filter_page');
endif;
//分类目录后添加斜线“/”
function nice_trailingslashit($string, $type_of_url) {
  if ( $type_of_url != 'single' && $type_of_url != 'page'&& $type_of_url != 'paged'&& $type_of_url != 'single_paged' )
    $string = trailingslashit($string);
  return $string;
}
add_filter('user_trailingslashit', 'nice_trailingslashit', 10, 2);

if (of_get_option('html_page') ):
  add_action('init', 'html_page_permalink', -1);
  register_activation_hook(__FILE__, 'active');
  register_deactivation_hook(__FILE__, 'deactive');
  function html_page_permalink() {
    global $wp_rewrite;
    if (!strpos($wp_rewrite->get_page_permastruct() , '.html')) {
      $wp_rewrite->page_structure = $wp_rewrite->page_structure . '.html';
    }
  }
  add_filter('user_trailingslashit', 'no_page_slash', 66, 2);
  function no_page_slash($string, $type) {
    global $wp_rewrite;
    if ($wp_rewrite->using_permalinks() && $wp_rewrite->use_trailing_slashes == true && $type == 'page') {
      return untrailingslashit($string);
    } else {
      return $string;
    }
  }
  function active() {
    global $wp_rewrite;
    if (!strpos($wp_rewrite->get_page_permastruct() , '.html')) {
      $wp_rewrite->page_structure = $wp_rewrite->page_structure . '.html';
    }
    $wp_rewrite->flush_rules();
  }
  function deactive() {
    global $wp_rewrite;
    $wp_rewrite->page_structure = str_replace(".html", "", $wp_rewrite->page_structure);
    $wp_rewrite->flush_rules();
  }
endif;

// 显示全部设置
//function all_settings_link() {
//    add_options_page(__('All Settings'), __('All Settings'), 'administrator', 'options.php');
//}
//add_action('admin_menu', 'all_settings_link');
//保护后台登录
//add_action('login_enqueue_scripts','login_protection');
//function login_protection(){
//    if($_GET['body'] != 'papa')header('Location: http://www.lerm.net/');
//}
//隐藏用户名和密码错误提示
add_filter('login_errors', create_function('$a', "return 用户名或密码错误;"));
/**
 * WordPress 去除分类标志代码
 *
 */
add_action('load-themes.php',  'no_category_base_refresh_rules');
add_action('created_category', 'no_category_base_refresh_rules');
add_action('edited_category', 'no_category_base_refresh_rules');
add_action('delete_category', 'no_category_base_refresh_rules');
function no_category_base_refresh_rules() {
    global $wp_rewrite;
    $wp_rewrite -> flush_rules();
}
// register_deactivation_hook(__FILE__, 'no_category_base_deactivate');
// function no_category_base_deactivate() {
//  remove_filter('category_rewrite_rules', 'no_category_base_rewrite_rules');
//  // We don't want to insert our custom rules again
//  no_category_base_refresh_rules();
// }
// Remove category base
add_action('init', 'no_category_base_permastruct');
function no_category_base_permastruct() {
    global $wp_rewrite, $wp_version;
    if (version_compare($wp_version, '3.4', '<')) {
        // For pre-3.4 support
        $wp_rewrite -> extra_permastructs['category'][0] = '%category%';
    } else {
        $wp_rewrite -> extra_permastructs['category']['struct'] = '%category%';
    }
}
// Add our custom category rewrite rules
add_filter('category_rewrite_rules', 'no_category_base_rewrite_rules');
function no_category_base_rewrite_rules($category_rewrite) {
    //var_dump($category_rewrite); // For Debugging
    $category_rewrite = array();
    $categories = get_categories(array('hide_empty' => false));
    foreach ($categories as $category) {
        $category_nicename = $category -> slug;
        if ($category -> parent == $category -> cat_ID)// recursive recursion
            $category -> parent = 0;
        elseif ($category -> parent != 0)
            $category_nicename = get_category_parents($category -> parent, false, '/', true) . $category_nicename;
        $category_rewrite['(' . $category_nicename . ')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$'] = 'index.php?category_name=$matches[1]&feed=$matches[2]';
        $category_rewrite['(' . $category_nicename . ')/page/?([0-9]{1,})/?$'] = 'index.php?category_name=$matches[1]&paged=$matches[2]';
        $category_rewrite['(' . $category_nicename . ')/?$'] = 'index.php?category_name=$matches[1]';
    }
    // Redirect support from Old Category Base
    global $wp_rewrite;
    $old_category_base = get_option('category_base') ? get_option('category_base') : 'category';
    $old_category_base = trim($old_category_base, '/');
    $category_rewrite[$old_category_base . '/(.*)$'] = 'index.php?category_redirect=$matches[1]';
    //var_dump($category_rewrite); // For Debugging
    return $category_rewrite;
}
// Add 'category_redirect' query variable
add_filter('query_vars', 'no_category_base_query_vars');
function no_category_base_query_vars($public_query_vars) {
    $public_query_vars[] = 'category_redirect';
    return $public_query_vars;
}
// Redirect if 'category_redirect' is set
add_filter('request', 'no_category_base_request');
function no_category_base_request($query_vars) {
    //print_r($query_vars); // For Debugging
    if (isset($query_vars['category_redirect'])) {
        $catlink = trailingslashit(get_option('home')) . user_trailingslashit($query_vars['category_redirect'], 'category');
        status_header(301);
        header("Location: $catlink");
        exit();
    }
    return $query_vars;
}

 //替换Gavatar头像地址
function get_ssl_avatar($avatar) {
  if ( preg_match_all( '/(src|srcset)=["\']https?.*?\/avatar\/([^?]*)\?s=([\d]+)&([^"\']*)?["\']/i', $avatar, $matches ) > 0) {
    $url = 'https://secure.gravatar.com';
    $size = $matches[3][0];
    $vargs = array_pad(array(), count($matches[0]), array());
    for ($i = 1; $i < count($matches); $i++) {
      for ($j = 0; $j < count($matches[$i]); $j++) {
        $tmp = strtolower($matches[$i][$j]);
        $vargs[$j][] = $tmp;
        if ($tmp == 'src') {
          $size = $matches[3][$j];
        }
      }
    }
    $buffers = array();
    foreach ($vargs as $varg) {
      $buffers[] = vsprintf( '%s="%s/avatar/%s?s=%s&%s"',
        array($varg[0], $url, $varg[1], $varg[2], $varg[3])
      );
    }
    return sprintf( '<img alt="avatar" %s class="avatar avatar-%s" height="%s" width="%s" />', implode(' ', $buffers), $size, $size, $size );
  } else {
    return false;
  }
}
 add_filter('get_avatar', 'get_ssl_avatar');

//移除自动p标签
 //remove_filter (  'the_content' ,  'wpautop'  );//移除文章p自动标签
 remove_filter ( 'the_excerpt' , 'wpautop' );//移除摘要p自动标签
 remove_filter ( 'comment_text', 'wpautop', 30 );//取消评论自动<p></p>标签
//移除图片高度和宽度属性，
//add_filter( 'post_thumbnail_html', 'remove_width_attribute', 10 );
//add_filter( 'image_send_to_editor', 'remove_width_attribute', 10 );
//function remove_width_attribute( $html ) {
//  $html = preg_replace( '/( width|height ) = "\d*"\s/', "", $html );
//  return $html;
//}
//禁用emoji表情
function disable_emojis_tinymce( $plugins ) {
  if ( is_array( $plugins ) ) {
    return array_diff( $plugins, array( 'wpemoji' ) );
  } else {
    return array();
  }
}
function disable_emojis() {
  global $wp_version;
  if ( $wp_version >= 4.2 ) {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    add_filter( 'tiny_mce_plugins', 'disable_emojis_tinymce' );
  }
}
add_action ( 'init', 'disable_emojis' );

//移除头部多余信息
remove_action ( 'wp_head', 'wp_generator' );//版本信息
remove_action ( 'wp_head', 'rsd_link' );//离线编辑
remove_action ( 'wp_head', 'wlwmanifest_link' );//离线编辑
remove_action ( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );// 上下文章的url
remove_action ( 'wp_head', 'feed_links', 2 );// 文章和评论feed
remove_action ( 'wp_head', 'feed_links_extra', 3 );// 去除评论feed
remove_action ( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );// 短链接
remove_action ( "wp_head", "rel_canonical" );
remove_action ( 'wp_head', 'wp_resource_hints', 2 );//s.w.org
//移除wp-json链接
add_filter ('rest_enabled', '_return_false');
add_filter ('rest_jsonp_enabled', '_return_false');
remove_action ( 'wp_head', 'rest_output_link_wp_head', 10 );
remove_action ( 'wp_head', 'wp_oembed_add_discovery_links', 10 );

// Remove the REST API endpoint.
remove_action( 'rest_api_init', 'wp_oembed_register_route' );

// Turn off oEmbed auto discovery.
add_filter( 'embed_oembed_discover', '__return_false' );

// Don't filter oEmbed results.
remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );

// Remove oEmbed discovery links.
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );

// Remove oEmbed-specific JavaScript from the front-end and back-end.
remove_action( 'wp_head', 'wp_oembed_add_host_js' );

// Remove all embeds rewrite rules.
//add_filter( 'rewrite_rules_array', 'disable_embeds_rewrites' );
//禁用自动保存
function lerm_disable_autosave() {
  wp_deregister_script('autosave');
}
add_action( 'wp_print_scripts', 'lerm_disable_autosave' );


//禁用Pingback功能
function no_self_ping( &$links ) {
 $home = get_option( 'home' );
 foreach ( $links as $l => $link )
 if ( 0 === strpos( $link, $home ) )
 unset($links[$l]);
}
add_action( 'pre_ping', 'no_self_ping' );

//menu
require DIR . '/inc/menu.php';
//slider
require DIR . '/inc/slider.php';
//thumbnail
require DIR . '/inc/thumbnail.php';
//template-tags
require DIR . '/inc/template-tags.php';
//logo
require DIR . '/inc/logo.php';
//related-posts
require DIR . '/inc/related.php';
//comments
require DIR . '/inc/template-comment.php';
//archives
require DIR . '/inc/template-archives.php';
// mail
require DIR . '/inc/mail.php';
// widget
require DIR . '/inc/widgets.php';
// ad
require DIR . '/inc/ad.php';
// lgoin
require DIR . '/inc/login.php';
// lazyload
require DIR . '/inc/lazyload.php';
