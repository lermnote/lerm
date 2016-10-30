<?php
/**
 * style the comments list
 * @authors lerm http://lerm.net
 * @date    2016-09-02
 * @since lerm 2.0
 */
function lerm_comments($comment, $args, $depth) {
  if ( 'div' === $args['style'] ) {
    $tag       = 'div';
    $add_below = 'comment';
  } else {
    $tag       = 'li';
    $add_below = 'div-comment';
  }?>
  <<?php echo $tag ?> <?php comment_class( 'card card-block' ) ?> id="comment-<?php comment_ID() ?>">
  <?php if ( 'div' != $args['style'] ) : ?>
    <div id="div-comment-<?php comment_ID() ?>" class="comment-body">
  <?php endif; ?>
    <span class="comment-author vcard">
      <?php if ( $args['avatar_size'] != 0 ) echo get_avatar( $comment, $args['avatar_size'] ); ?>
      <?php printf( __( '<cite class="fn">%s</cite> <span class="says"> </span>' ), get_comment_author_link() ); ?>
    </span>
    <?php if ( $comment->comment_approved == '0' ) : ?>
      <em class="comment-awaiting-moderation"><?php _e( 'Your comment is awaiting moderation.' ); ?></em>
      <br />
    <?php endif; ?>
    <span class="comment-meta">
      <a href="<?php echo htmlspecialchars( get_comment_link( $comment->comment_ID ) ); ?>"></a>
      <?php printf( __('%1$s at %2$s'), get_comment_date(),  get_comment_time() ); ?>
    </span>
    <span class="reply pull-right">
      <?php edit_comment_link( __( 'Edit' ), '  ', '' ); ?>
      <?php comment_reply_link( array_merge( $args, array( 'add_below' => $add_below, 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>
    </span>
    <div class="comment-content">
      <?php comment_text(); ?>
    </div>
    <?php if ( 'div' != $args['style'] ) : ?>
    </div>
    <?php endif; ?>
    <?php
    }
//过滤外文评论
//if (git_get_option('git_spam_lang') && !is_user_logged_in()):
function refused_spam_comments($comment_data) {
    $pattern = '/[一-龥]/u';
    $jpattern = '/[ぁ-ん]+|[ァ-ヴ]+/u';
    if (!preg_match($pattern, $comment_data['comment_content'])) {
        err(__('写点汉字吧，博主外语很捉急！You should type some Chinese word!'));
    }
    if (preg_match($jpattern, $comment_data['comment_content'])) {
        err(__('日文滚粗！Japanese Get out！日本语出て行け！ You should type some Chinese word！'));
    }
    return ($comment_data);
}
    add_filter('preprocess_comment', 'refused_spam_comments');
//endif;
//屏蔽关键词，email，url，ip
//if (git_get_option('git_spam_keywords') && !is_user_logged_in()):
function lerm_keyword_spam($comment) {
    if (wp_blacklist_check($comment['comment_author'], $comment['comment_author_email'], $comment['comment_author_url'], $comment['comment_content'], $comment['comment_author_IP'], $comment['comment_agent'])) {
        header("Content-type: text/html; charset=utf-8");
        err(__('不好意思，您的评论违反本站评论规则'));
    } else {
        return $comment;
    }
}
add_filter('preprocess_comment', 'lerm_keyword_spam');
//endif;
//屏蔽长连接评论
//if (git_get_option('git_spam_long') && !is_user_logged_in()):
function lang_url_spamcheck($approved, $commentdata) {
    return (strlen($commentdata['comment_author_url']) > 50) ?
    'spam' : $approved;
}
add_filter('pre_comment_approved', 'lang_url_spamcheck', 99, 2);
//endif;
//屏蔽昵称，评论内容带链接的评论
/*/if (git_get_option('git_spam_url') && !is_user_logged_in()):
function Googlolink($comment_data) {
    $links = '/http:\/\/|https:\/\/|www\./u';
    if (preg_match($links, $comment_data['comment_author']) || preg_match($links, $comment_data['comment_content'])) {
        err(__('在昵称和评论里面是不准发链接滴.'));
//    }
//    return ($comment_data);
//}
///    add_filter('preprocess_comment', 'Googlolink');
//endif;
