<?php // phpcs:disable WordPress.Files.FileName
/**
 * Base REST controller (继承 WP_REST_Controller)
 *
 * @package Lerm
 */
declare(strict_types=1);

namespace Lerm\Http;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use RuntimeException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 基础 REST 控制器抽象类
 * 采用现代 PHP 语法和 PSR 标准
 *
 * @package Lerm\Inc\Rest
 */
abstract class BaseController extends WP_REST_Controller {

	/**
	 * REST API 命名空间
	 */
	protected const NAMESPACE = 'lerm/v1';

	/**
	 * REST API 路由基址
	 */
	protected const ROUTE = '';

	/**
	 * 支持的 HTTP 方法
	 */
	protected const METHODS = WP_REST_Server::CREATABLE;

	/**
	 * Nonce 字段名称
	 */
	protected const NONCE_FIELD = 'security';

	/**
	 * 是否公开访问
	 */
	protected const PUBLIC = true;

	/**
	 * 构造函数
	 *
	 * @throws RuntimeException 当子类未定义 ROUTE 常量时
	 */
	public function __construct() {
		if ( empty( static::ROUTE ) ) {
			throw new RuntimeException(
				sprintf( '%s 必须定义 ROUTE 常量。', static::class )
			);
		}

		$this->namespace = static::NAMESPACE;
		$this->rest_base = static::ROUTE;

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

		add_filter( 'lerm_l10n_datas', array( static::class, 'rest_l10n_data' ) );
	}

	/**
	 * 注册 REST API 路由
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => static::METHODS,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);
	}

	/**
	 * 权限检查
	 *
	 * 优先读取 X-WP-Nonce header，再读取请求 param 的 NONCE_FIELD。
	 * 验证 nonce（使用 static::ROUTE 作为 action），若 PUBLIC 为 false 则要求登录。
	 *
	 * @param  WP_REST_Request $request REST 请求对象
	 * @return bool|WP_Error 权限验证结果
	 */
	public function permission_check( WP_REST_Request $request ): bool|WP_Error {
		$nonce = $request->get_header( 'x-wp-nonce' )
			?? $request->get_param( static::NONCE_FIELD );

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Invalid nonce.' ),
				array( 'status' => 403 )
			);
		}

		if ( ! static::PUBLIC && ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You must be logged in.' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * 处理创建项目的请求
	 *
	 * 子类必须实现此方法以处理 POST/创建逻辑
	 *
	 * @param  WP_REST_Request $request REST 请求对象
	 * @return WP_REST_Response|WP_Error 响应对象或错误
	 */
	public function create_item( $request ) {
		// By default we return a helpful error. Subclasses MUST override this method.
		return new \WP_Error(
			'not_implemented',
			__( 'create_item() not implemented for this controller.', 'lerm' ),
			array( 'status' => 501 )
		);
	}
	/**
	 * 创建成功响应
	 *
	 * @param  mixed $data   响应数据
	 * @param  int   $status HTTP
	 *                       状态码
	 * @return WP_REST_Response REST 响应对象
	 */
	protected function success( mixed $data = null, int $status = 200 ): WP_REST_Response {
		return new WP_REST_Response( $data, $status );
	}

	/**
	 * 创建错误响应
	 *
	 * @param  string|array $message 错误消息
	 * @param  int          $status  HTTP
	 *                               状态码
	 * @param  string       $code    错误代码
	 * @return WP_Error 错误对象
	 */
	protected function error(
		string|array $message = 'error',
		int $status = 400,
		string $code = 'rest_error'
	): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}

	/**
	 * 生成本地化数据
	 *
	 * 通过 lerm_l10n_data 过滤器注入前端
	 * 字段：rest_url, ajax_nonce, loggedin
	 *
	 * @param  array $l10n 现有的本地化数组
	 * @return array 更新后的本地化数组
	 */
	public static function rest_l10n_data( $l10n ) {
		$data = array(
			'rest_url' => rest_url( static::NAMESPACE ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'loggedin' => is_user_logged_in(),
		);

			$data = wp_parse_args( $data, $l10n );
		return $data;
	}
}
