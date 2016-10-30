<?php if (is_home()&&!is_paged()):
  $category_id = of_get_option('link_category', '没有条目' ); ?>
  <?php if (!empty($category_id)): ?>
    <ul class="card card-block clearfix">
      <?php wp_list_bookmarks('orderby=date&title_li=&before=<li class="pull-left media-left">&categorize=0&category='.$category_id);?>
        <li class="pull-left media-left">
        <a href="<?php echo esc_url( home_url( '/links' ) ); ?>"><i class="fa fa-plus" aria-hidden="true"></i>友情链接</a>
        </li>
    </ul>
  <?php endif; ?>
<?php endif; 
