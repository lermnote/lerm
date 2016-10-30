<?php if (post_password_required()) return;?>
<?php if (comments_open()): ?>
  <div id="comments" class="card card-block">
    <?php $args = array(
      'comment_notes_before'=>'<label for="Textarea">电子邮件地址不会被公开。 必填项已用 <i class="fa fa-star" aria-hidden="true"></i> 标注</label>',
      'comment_field'       =>'<fieldset class="form-group"><textarea class="form-control" rows="3" required="required" placeholder="留下评论，天下太平" name="comment"></textarea></fieldset>',
      'fields'              =>array(
        'author'=>'<div class="form-group input-group"><span class="input-group-addon"><i class="fa fa-user" aria-hidden="true"></i></span><input type="text" id="author" name="author" class="form-control" required="required" value="" placeholder="昵称"><span class="input-group-addon" style="background-color: inherit;"><i class="fa fa-star" aria-hidden="true"></i></span></div>',
        'email' =>'<div class="form-group input-group"><span class="input-group-addon"><i class="fa fa-envelope" aria-hidden="true"></i></span><input type="email" name="email" class="form-control" required="required" value="" placeholder="邮箱"><span class="input-group-addon" style="background-color: inherit;"><i class="fa fa-star" aria-hidden="true"></i></span></div>',
        'url'   =>'<div class="form-group input-group"><span class="input-group-addon"><i class="fa fa-home" aria-hidden="true"></i></span><input type="text" name="url" class="form-control" value="" placeholder="网址"></div>',
      ),
      'id_submit'         => 'submit',
      'class_submit'      => 'btn btn-primary',
      'label_submit'      => '发表评论',
      'title_reply'       => '欢迎评论',
      'title_reply_to'    => '回复评论 %s',
      'cancel_reply_link' => '取消回复',
    );
    comment_form($args);?>
  </div>
<?php endif; ?>
<?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) :?>
  <p class="no-comments card card-block"><?php _e( '评论已关闭', 'lerm' ); ?></p>
<?php endif; ?>
<?php if (have_comments()): ?>
    <h4 class="card card-header reply-title">
      <?php printf('《%1$s》| 有 %2$s 条评论',get_the_title(), number_format_i18n(get_comments_number()));?>
    </h4>
    <ol class="card-block comment-list">
      <?php wp_list_comments(array(
        'callback'   => 'lerm_comments',
        'type'       => 'comment',
        'style'      => 'ol',
        'short_ping' => true,
        'avatar_size'=> 40,
      ));?>
    </ol><!-- .comment-list -->
    <?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : ?>
      <nav class="comment-nav">
        <div class="pager"><?php paginate_comments_links('prev_text=上一页&next_text=下一页'); ?></div>
      </nav>
    <?php endif; // Check for comment navigation. ?>
<?php endif; // have comments?>
