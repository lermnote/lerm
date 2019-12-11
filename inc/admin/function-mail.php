<?php
/**
 * WordPress STMP mail functions.
 *
 * @package    http://www.hanost.com/
 * @author     Lerm
 */
if ( lerm_options( 'email_notice' ) ) :
	function lerm_mail_smtp( $phpmailer ) {
		$phpmailer->From     = lerm_options( 'mail_options', 'from_email' );
		$phpmailer->FromName = lerm_options( 'mail_options', 'from_name' );

		$phpmailer->Host       = lerm_options( 'smtp_options', 'smtp_host' );
		$phpmailer->Port       = lerm_options( 'smtp_options', 'smtp_port' );
		$phpmailer->SMTPSecure = lerm_options( 'smtp_options', 'ssl_switcher' ) ? 'ssl' : '';

		$phpmailer->SMTPAuth = lerm_options( 'smtp_options', 'smtp_auth' );
		$phpmailer->Username = lerm_options( 'smtp_options', 'username' );
		$phpmailer->Password = lerm_options( 'smtp_options', 'pswd' );
		$phpmailer->IsSMTP();
	}
	add_action( 'phpmailer_init', 'lerm_mail_smtp' );
endif;

// Reply comment notice
function lerm_comment_mail_notify( $comment_id ) {
	$comment        = get_comment( $comment_id );
	$parent_id      = $comment->comment_parent ? $comment->comment_parent : '';
	$spam_confirmed = $comment->comment_approved;
	if ( ( $parent_id != '' ) && ( $spam_confirmed != 'spam' ) ) {
		$wp_email = 'no-reply@' . preg_replace( '#^www\.#', '', strtolower( $_SERVER['SERVER_NAME'] ) );//发件人e-mail地址
		$to       = trim( get_comment( $parent_id )->comment_author_email );
		$subject  = '您在 [' . get_option( 'blogname' ) . '] 的留言有了回应';
		$message  = '<div style="border:#666 1px solid;border-radius:8px;color:#333;font-size:12px;width:702px;font-family:微软雅黑,arial;margin:10px auto 0px;">';
		$message .= sprintf( '<div style="width:100%;background:#666;min-height:60px;color:#fff;border-radius:6px 6px 0 0"><span style="line-height:60px;min-height:60px;margin-left:30px;font-size:12px">您在<a style="color:#00bbff;font-weight:600;text-decoration:none" href="%s" target="_blank">%s</a> 上的留言有回复啦！</span></div>', home_url(), get_option( 'blogname' ) );
		$message .= '<div style="margin:0px auto;width:90%">';
		$message .= sprintf( '<p>%s, 您好!</p><p>您于%s在文章《%s》上发表评论:</p><p style="border-bottom:#ddd 1px solid;border-left:#ddd 1px solid;padding-bottom:20px;background-color:#eee;margin:15px 0px;padding-left:20px;padding-right:20px;border-top:#ddd 1px solid;border-right:#ddd 1px solid;padding-top:20px">%s</p><p>%s于%s给您的回复如下:</p><p style="border-bottom:#ddd 1px solid;border-left:#ddd 1px solid;padding-bottom:20px;background-color:#eee;margin:15px 0px;padding-left:20px;padding-right:20px;border-top:#ddd 1px solid;border-right:#ddd 1px solid;padding-top:20px"></p><p>您可以点击<a style="color:#00bbff;text-decoration:none" href="%s" target="_blank">查看回复的完整內容</a></p><p>感谢你对<a style="color:#00bbff;text-decoration:none" href="%s" target="_blank">%s</a> 的关注，如您有任何疑问，欢迎在博客留言，我会一一解答</p>', trim( get_comment( $parent_id )->comment_author ), trim( get_comment( $parent_id )->comment_date ), get_the_title( $comment->comment_post_ID ), nl2br( get_comment( $parent_id )->comment_content ), trim( $comment->comment_author ), trim( $comment->comment_date ), nl2br( $comment->comment_content ), htmlspecialchars( get_comment_link( $parent_id ) ), home_url(), get_option( 'blogname' ) );
		$message .= '</div></div>';
		$from     = 'From: "' . get_option( 'blogname' ) . "\" <$wp_email>";
		$headers  = "$from\nContent-Type: text/html; charset=" . get_option( 'blog_charset' ) . "\n";
		wp_mail( $to, $subject, $message, $headers );
		//echo 'mail to ', $to, '<br/> ' , $subject, $message; // for testing
	}
}
add_action( 'comment_post', 'lerm_comment_mail_notify' );
