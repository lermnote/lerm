<?php
/**
 * Template Name: 404
 * @authors lerm http://lerm.net
 * @date    2016-09-03
 * @since   lerm 2.0
 */
get_header(); ?>
  <div class="card entry-content">
      <main class="card-block">
        <header>
          <h1 class="card-block text-center">啊~哦~ 您要查看的页面不存在或已删除！</h1>
        </header>
        <img class="center-block img-responsive" src="<?php bloginfo('template_directory'); ?>/img/notfound.gif" height="320" width="520">
        <div class="text-center">

          <p class="card-block">请检查您输入的网址是否正确，或者点击链接继续浏览空间</p>

          <p class="card-block">您可以回到 <a  data-toggle="tooltip" data-placement="top" title="点击搜索" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">网站首页</a> 或到 <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">留言板</a> 留言反馈</p>
        </div>
        <form class="page-search card-block" role="search" method="get" action="<?php echo home_url('/'); ?>">
          <label>这儿似乎什么都没有，试试搜索？</label>
          <div class="input-group">
            <input type="text" class="form-control" name="s" require="require" value="<?php if (is_search()) { echo get_search_query(); } ?>" placeholder="<?php _e('搜索…', 'lerm');?>">
            <span class="input-group-btn">
              <button class="btn btn-primary" type="sumbit" onclick="document.forms['sbsearch'].submit(); return false;">站内搜索</button>
            </span>
          </div>
        </form>
      </main>
  </div>
<?php get_footer();?>
