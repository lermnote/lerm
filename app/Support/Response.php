<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Support;

/**
 * REST / AJAX 统一响应封装
 *
 * 提供语义化的静态工厂方法，消除 Controller 中散落的 WP_REST_Response 字面量。
 * 所有方法返回标准 WP_REST_Response，兼容 WP REST API 框架。
 *
 * 用法示例：
 *   return Response::ok( array( 'count' => 3, 'liked' => true ) );
 *   return Response::error( 'post_not_found', '文章不存在', 404 );
 *   return Response::no_content();
 *
 * @package Lerm\Support
 */
final class Response {

	// -------------------------------------------------------------------------
	// 成功响应
	// -------------------------------------------------------------------------

	/**
	 * 200 OK — 通用成功
	 *
	 * @param  mixed $data 响应主体，将被序列化为 JSON
	 * @return \WP_REST_Response
	 */
	public static function ok( mixed $data = null ): \WP_REST_Response {
		return new \WP_REST_Response( $data, 200 );
	}

	/**
	 * 201 Created — 资源创建成功
	 *
	 * @param  mixed  $data     新建资源的数据
	 * @param  string $location 可选，新资源的 URL（写入 Location 响应头）
	 * @return \WP_REST_Response
	 */
	public static function created( mixed $data = null, string $location = '' ): \WP_REST_Response {
		$response = new \WP_REST_Response( $data, 201 );
		if ( '' !== $location ) {
			$response->header( 'Location', $location );
		}
		return $response;
	}

	/**
	 * 204 No Content — 操作成功，无返回体（如删除）
	 *
	 * @return \WP_REST_Response
	 */
	public static function no_content(): \WP_REST_Response {
		return new \WP_REST_Response( null, 204 );
	}

	// -------------------------------------------------------------------------
	// 错误响应
	// -------------------------------------------------------------------------

	/**
	 * 通用错误响应
	 *
	 * @param  string $code    WP_Error 风格的错误码（snake_case）
	 * @param  string $message 面向用户的错误说明（已翻译）
	 * @param  int    $status  HTTP 状态码，默认 400
	 * @return \WP_Error
	 */
	public static function error( string $code, string $message, int $status = 400 ): \WP_Error {
		return new \WP_Error( $code, $message, array( 'status' => $status ) );
	}

	/**
	 * 400 Bad Request — 请求参数有误
	 *
	 * @param  string $message 错误说明
	 * @param  string $code    错误码，默认 'bad_request'
	 * @return \WP_Error
	 */
	public static function bad_request( string $message, string $code = 'bad_request' ): \WP_Error {
		return self::error( $code, $message, 400 );
	}

	/**
	 * 401 Unauthorized — 未登录或 token 失效
	 *
	 * @param  string $message 错误说明
	 * @return \WP_Error
	 */
	public static function unauthorized( string $message = '' ): \WP_Error {
		return self::error(
			'unauthorized',
			'' !== $message ? $message : __( '请先登录', 'lerm' ),
			401
		);
	}

	/**
	 * 403 Forbidden — 已登录但权限不足
	 *
	 * @param  string $message 错误说明
	 * @return \WP_Error
	 */
	public static function forbidden( string $message = '' ): \WP_Error {
		return self::error(
			'forbidden',
			'' !== $message ? $message : __( '权限不足', 'lerm' ),
			403
		);
	}

	/**
	 * 404 Not Found — 资源不存在
	 *
	 * @param  string $message 错误说明
	 * @param  string $code    错误码，默认 'not_found'
	 * @return \WP_Error
	 */
	public static function not_found( string $message = '', string $code = 'not_found' ): \WP_Error {
		return self::error(
			$code,
			'' !== $message ? $message : __( '资源不存在', 'lerm' ),
			404
		);
	}

	/**
	 * 422 Unprocessable Entity — 业务逻辑校验失败
	 *
	 * @param  string $message 错误说明
	 * @param  string $code    错误码
	 * @return \WP_Error
	 */
	public static function unprocessable( string $message, string $code = 'unprocessable_entity' ): \WP_Error {
		return self::error( $code, $message, 422 );
	}

	/**
	 * 429 Too Many Requests — 超出频率限制
	 *
	 * @param  string $message 错误说明
	 * @return \WP_Error
	 */
	public static function too_many_requests( string $message = '' ): \WP_Error {
		return self::error(
			'rate_limited',
			'' !== $message ? $message : __( '请求过于频繁，请稍后再试', 'lerm' ),
			429
		);
	}

	/**
	 * 500 Internal Server Error — 服务端异常
	 *
	 * @param  string $message 错误说明
	 * @param  string $code    错误码
	 * @return \WP_Error
	 */
	public static function server_error( string $message = '', string $code = 'internal_server_error' ): \WP_Error {
		return self::error(
			$code,
			'' !== $message ? $message : __( '服务器内部错误', 'lerm' ),
			500
		);
	}
}
