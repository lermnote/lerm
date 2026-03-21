<?php
declare( strict_types=1 );

namespace Lerm\Http\Ajax\Actions;

use Lerm\Support\Utilities;

/**
 * 联系表单 Ajax 处理器
 *
 * action: lerm_contact（登录/访客均可）
 *
 * 前端请求示例：
 *   fetch(lermData.ajaxUrl, {
 *     method: 'POST',
 *     body: new URLSearchParams({
 *       action: 'lerm_contact',
 *       nonce:  lermData.ajaxNonce,
 *       name:   '张三',
 *       email:  'zhangsan@example.com',
 *       message:'你好...'
 *     })
 *   })
 *
 * @package Lerm\Http\Ajax\Actions
 */
final class ContactAction {

	private const NONCE_ACTION   = 'lerm_contact_nonce';
	private const RATE_LIMIT_TTL = 5 * MINUTE_IN_SECONDS; // 5 分钟内不能重复提交

	public static function handle(): void {
		// 1. Nonce 验证
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		// 2. 频率限制（基于 IP）
		$ip      = Utilities::client_ip();
		$rate_key = 'lerm_contact_rl_' . md5( $ip );

		if ( get_transient( $rate_key ) ) {
			wp_send_json_error(
				[ 'message' => __( '提交过于频繁，请 5 分钟后再试', 'lerm' ) ],
				429
			);
		}

		// 3. 参数清洗
		$name    = sanitize_text_field( wp_unslash( $_POST['name']    ?? '' ) );
		$email   = sanitize_email(      wp_unslash( $_POST['email']   ?? '' ) );
		$subject = sanitize_text_field( wp_unslash( $_POST['subject'] ?? __( '来自网站的留言', 'lerm' ) ) );
		$message = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );

		// 4. 参数验证
		$errors = [];
		if ( empty( $name ) ) {
			$errors[] = __( '请填写姓名', 'lerm' );
		}
		if ( ! is_email( $email ) ) {
			$errors[] = __( '请填写有效的邮箱地址', 'lerm' );
		}
		if ( mb_strlen( $message ) < 10 ) {
			$errors[] = __( '留言内容至少需要 10 个字符', 'lerm' );
		}

		if ( ! empty( $errors ) ) {
			wp_send_json_error( [ 'message' => implode( '；', $errors ) ], 400 );
		}

		// 5. 写频率限制（在发送前写，防止重复点击）
		set_transient( $rate_key, 1, self::RATE_LIMIT_TTL );

		// 6. 发送邮件
		$admin_email = get_option( 'admin_email' );
		$mail_subject = sprintf(
			/* translators: 1: site name, 2: user subject */
			'[%1$s] %2$s',
			get_bloginfo( 'name' ),
			$subject
		);
		$mail_body = sprintf(
			"姓名：%s\n邮箱：%s\n\n%s",
			$name,
			$email,
			$message
		);
		$headers = [
			'Content-Type: text/plain; charset=UTF-8',
			sprintf( 'Reply-To: %s <%s>', $name, $email ),
		];

		$sent = wp_mail( $admin_email, $mail_subject, $mail_body, $headers );

		if ( $sent ) {
			wp_send_json_success( [ 'message' => __( '留言已发送，我们会尽快回复您', 'lerm' ) ] );
		} else {
			// 发送失败时删除频率限制，允许重试
			delete_transient( $rate_key );
			wp_send_json_error( [ 'message' => __( '发送失败，请稍后重试', 'lerm' ) ], 500 );
		}
	}
}
