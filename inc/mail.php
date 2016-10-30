<?php
/**
*
*wordpress邮件功能
*/
//使用smtp发送邮件，以163邮箱为例
if( of_get_option('mail') ):
  function lerm_mail_smtp( $phpmailer ) {
    $phpmailer->FromName = of_get_option('fromname','没有条目');
    $phpmailer->Host =of_get_option('host','没有条目');
    $phpmailer->Port = of_get_option('port','没有条目');
    $phpmailer->Username =of_get_option('username','没有条目');
    $phpmailer->Password =of_get_option('password','没有条目');
    $phpmailer->From =of_get_option('from','没有条目');
    $phpmailer->SMTPAuth = true;
    $phpmailer->SMTPSecure = '';
    $phpmailer->IsSMTP();
  }
add_action('phpmailer_init', 'lerm_mail_smtp');
endif;
//评论回复邮件
function lerm_comment_mail_notify($comment_id) {
  $comment = get_comment($comment_id);
  $parent_id = $comment->comment_parent ? $comment->comment_parent : '';
  $spam_confirmed = $comment->comment_approved;
  if (($parent_id != '') && ($spam_confirmed != 'spam')) {
    $wp_email = 'no-reply@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME']));//发件人e-mail地址
    $to = trim(get_comment($parent_id)->comment_author_email);
    $subject = '您在 [' . get_option("blogname") . '] 的留言有了回应';
    $message = '<div style="border-right:#666666 1px solid;border-radius:8px;color:#111;font-size:12px;width:702px;border-bottom:#666666 1px solid;font-family:微软雅黑,arial;margin:10px auto 0px;border-top:#666666 1px solid;border-left:#666666 1px solid"><div class="adM">
    </div><div style="width:100%;background:#666666;min-height:60px;color:white;border-radius:6px 6px 0 0"><span style="line-height:60px;min-height:60px;margin-left:30px;font-size:12px">您在<a style="color:#00bbff;font-weight:600;text-decoration:none" href="' . get_option('home') . '" target="_blank">' . get_option('blogname') . '</a> 上的留言有回复啦！</span> </div>
    <div style="margin:0px auto;width:90%"><p>' . trim(get_comment($parent_id)->comment_author) . ', 您好!</p>
    <p>您于' . trim(get_comment($parent_id)->comment_date) . ' 在文章《' . get_the_title($comment->comment_post_ID) . '》上发表评论: </p>
    <p style="border-bottom:#ddd 1px solid;border-left:#ddd 1px solid;padding-bottom:20px;background-color:#eee;margin:15px 0px;padding-left:20px;padding-right:20px;border-top:#ddd 1px solid;border-right:#ddd 1px solid;padding-top:20px">' . nl2br(get_comment($parent_id)->comment_content) . '</p>
    <p>' . trim($comment->comment_author) . ' 于' . trim($comment->comment_date) . ' 给您的回复如下: </p>
    <p style="border-bottom:#ddd 1px solid;border-left:#ddd 1px solid;padding-bottom:20px;background-color:#eee;margin:15px 0px;padding-left:20px;padding-right:20px;border-top:#ddd 1px solid;border-right:#ddd 1px solid;padding-top:20px">' . nl2br($comment->comment_content) . '</p>
    <p>您可以点击 <a style="color:#00bbff;text-decoration:none" href="' . htmlspecialchars(get_comment_link($parent_id)) . '" target="_blank">查看回复的完整內容</a></p>
    <p>感谢你对 <a style="color:#00bbff;text-decoration:none" href="' . get_option('home') . '" target="_blank">' . get_option('blogname') . '</a> 的关注，如您有任何疑问，欢迎在博客留言，我会一一解答</p><p>(此邮件由系统自动发出，请勿回复。)</p></div></div>';
    $from = "From: \"" . get_option('blogname') . "\" <$wp_email>";
    $headers = "$from\nContent-Type: text/html; charset=" . get_option('blog_charset') . "\n";
    wp_mail( $to, $subject, $message, $headers );
//echo 'mail to ', $to, '<br/> ' , $subject, $message; // for testing
  }
}
add_action('comment_post', 'lerm_comment_mail_notify');
