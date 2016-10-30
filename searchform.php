<?php
 /**
	* @authors lerm http://lerm.net
	* @date    2016-07-14
	* @since version lerm 1.0
	*
	* WordPress search form
	*/
?>
<form role="search" method="get" action="<?php echo home_url('/'); ?>">
  <div class="input-group">
    <input type="text" class="form-control" name="s" value="<?php if (is_search()) { echo get_search_query(); } ?>" placeholder="<?php _e('搜索…', 'lerm');?>">
    <span class="input-group-btn">
      <button class="btn btn-primary" onclick="document.forms['sbsearch'].submit(); return false;"><i class="fa fa-search" aria-hidden="true"></i></button>
    </span>
  </div>
</form>
