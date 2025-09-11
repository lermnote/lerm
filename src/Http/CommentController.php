<?php // phpcs:disable WordPress.Files.FileName
/**
 * Comment REST controller (继承 BaseRestController)
 *
 * @package Lerm
 */

declare(strict_types=1);

namespace Lerm\Http;

use Lerm\Traits\Singleton;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use RuntimeException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 评论 REST 控制器
 *
 * 处理评论提交的 REST API 端点
 *
 * @package Lerm\Inc\Rest
 */
final class CommentController extends BaseController {
	use Singleton;

	/**
	 * 路由（对应原来的 AJAX_ACTION）
	 */
	protected const ROUTE = 'ajax_comment';

	/**
	 * 允许未登录用户提交（与 check_ajax_referer 行为保持一致）
	 */
	protected const PUBLIC = true;
	/**
	 * 构造函数
	 *
	 * @throws RuntimeException 当父类构造失败时
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Permission check — keep legacy nonce 'like_nonce' for backward compatibility.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return true|WP_Error
	 */
	public function permission_check( $request ): bool|WP_Error {
		$header = $request->get_header( 'x-wp-nonce' );
		$nonce  = ! empty( $header ) ? $header : $request->get_param( self::NONCE_FIELD );

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'rest_forbidden', __( 'Invalid nonce.', 'lerm' ), array( 'status' => 403 ) );
		}

		return true;
	}
	/**
	 * 处理评论提交请求
	 *
	 * @param  WP_REST_Request $request REST 请求对象
	 * @return WP_REST_Response|WP_Error 响应对象或错误
	 */
	public function create_item( $request ) {
		// 确保参数是 WP_REST_Request 类型
		if ( ! $request instanceof WP_REST_Request ) {
			return $this->error( 'Invalid request object.', 400 );
		}

		// 支持 JSON body、form-data、urlencoded：先取 body params，如为空则取 params
		$postdata = (array) $request->get_params();
		if ( empty( $postdata ) ) {
			$postdata = (array) $request->get_params();
		}

		// 去转义（wp_handle_comment_submission 期望未转义数据）
		$postdata = wp_unslash( $postdata );

		// 调用 WP 的评论处理函数
		$comment = wp_handle_comment_submission( $postdata );

		if ( is_wp_error( $comment ) ) {
			// 返回错误结构（与原来兼容）
			$data = array(
				'code'    => $comment->get_error_code(),
				'message' => $comment->get_error_message(),
			);
			return $this->error( $data['message'], 400, 'comment_error' );
		}

		// 获取 comment ID / 对象
		$comment_id = 0;
		if ( is_object( $comment ) && isset( $comment->comment_ID ) ) {
			$comment_id = (int) $comment->comment_ID;
		} elseif ( is_int( $comment ) ) {
			$comment_id = $comment;
		}

		// 基本验证
		$comment_post_id = isset( $postdata['comment_post_ID'] ) ? (int) $postdata['comment_post_ID'] : 0;
		if ( 0 === $comment_post_id || 0 === $comment_id ) {
			return $this->error( __( 'Invalid post or comment ID.' ), 400, 'invalid_post' );
		}

		// 如果用户同意则设置评论 cookie
		if ( isset( $postdata['wp-comment-cookies-consent'] ) && 'yes' === $postdata['wp-comment-cookies-consent'] ) {
			do_action( 'set_comment_cookies', $comment, wp_get_current_user() );
		}

		// 获取完整评论对象
		$comment_obj = get_comment( $comment_id );

		// 头像逻辑（保留原有规则）
		$avatar_url     = get_avatar_url( $comment_obj );
		$comment_parent = isset( $postdata['comment_parent'] ) ? absint( $postdata['comment_parent'] ) : 0;
		$base_size      = wp_is_mobile() ? 32 : 48;
		$avatar_size    = $comment_parent ? intval( $base_size * 2 / 3 ) : $base_size;

		// Build comment payload matching frontend expectations
		$comment_payload = array(
			'comment_ID'           => (int) $comment_obj->comment_ID,
			'comment_post_ID'      => (int) $comment_obj->comment_post_ID,
			'comment_parent'       => (string) $comment_obj->comment_parent, // string '0' expected by JS
			'comment_type'         => $comment_obj->comment_type,
			'comment_author'       => $comment_obj->comment_author,
			'comment_author_email' => $comment_obj->comment_author_email,
			// sanitize comment content server-side; returns allowed HTML
			'comment_content'      => wp_kses_post( $comment_obj->comment_content ),
			'comment_date'         => $comment_obj->comment_date,
			'comment_date_gmt'     => $comment_obj->comment_date_gmt,
			'comment_approved'     => (string) $comment_obj->comment_approved, // '0' or '1'
		// helpful links / metadata expected by the JS
			'comment_post_link'    => get_comment_link( $comment_obj->comment_ID ),
		);
		// Avatar URL (pass email to get_avatar_url to get proper avatar for author)
		$avatar_url = get_avatar_url( $comment_obj->comment_author_email ?? $comment_obj );
		// Mirror avatar data into the comment payload (your JS reads comment.avatar_url)
		$comment_payload['avatar_url']  = $avatar_url;
		$comment_payload['avatar_size'] = $avatar_size;

		$response_data = array(
			'comment' => $comment_payload,
		);
		// 返回 201（已创建）
		return $this->success( $response_data, 201 );
	}

	/**
	 * Generate AJAX localization data.
	 *
	 * This function generates an array of localized data for use in AJAX requests.
	 *
	 * @param  array $l10n Existing localization data.
	 * @return array Localized data for AJAX requests.
	 */
	public static function rest_l10n_data( $l10n ) {
		$l10n = parent::rest_l10n_data( $l10n );
		$data = array(
			'comment_action' => self::ROUTE,
		);
		return wp_parse_args( $data, $l10n );
	}
}
