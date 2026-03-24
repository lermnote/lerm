<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Http\Rest;

use Lerm\Http\Rest\Controllers\LikeController;
use Lerm\Http\Rest\Controllers\ViewsController;
use Lerm\Http\Rest\Controllers\SearchController;
use Lerm\Http\Rest\Controllers\PostsController;
use Lerm\Http\Rest\Controllers\TocController;
use Lerm\Http\Rest\Controllers\LoginController;
use Lerm\Http\Rest\Controllers\ProfileController;

/**
 * REST API 路由注册中心
 *
 * 所有路由统一在此声明，禁止在 Controller 内部调用 register_rest_route()。
 * 命名空间：lerm/v1
 *
 * @package Lerm\Http\Rest
 */
final class Router {

	public static function register(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes(): void {
		$ns = 'lerm/v1';

		// ── 点赞 ──────────────────────────────────────────────
		register_rest_route(
			$ns,
			'/like/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( LikeController::class, 'get' ),
					'permission_callback' => '__return_true',
					'args'                => self::post_id_arg(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( LikeController::class, 'toggle' ),
					'permission_callback' => '__return_true',
					'args'                => self::post_id_arg(),
				),
			)
		);

		// ── 浏览数 ────────────────────────────────────────────
		register_rest_route(
			$ns,
			'/views/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( ViewsController::class, 'get' ),
					'permission_callback' => '__return_true',
					'args'                => self::post_id_arg(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( ViewsController::class, 'increment' ),
					'permission_callback' => '__return_true',
					'args'                => self::post_id_arg(),
				),
			)
		);

		// ── 实时搜索 ──────────────────────────────────────────
		register_rest_route(
			$ns,
			'/search',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( SearchController::class, 'handle' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'q'         => array(
						'required'          => true,
						'type'              => 'string',
						'minLength'         => 1,
						'maxLength'         => 100,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'post_type' => array(
						'type'              => 'string',
						'default'           => 'post',
						'sanitize_callback' => 'sanitize_key',
					),
					'per_page'  => array(
						'type'              => 'integer',
						'default'           => 5,
						'minimum'           => 1,
						'maximum'           => 20,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// ── 无限滚动 / 文章列表 ───────────────────────────────
		register_rest_route(
			$ns,
			'/posts',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( PostsController::class, 'handle' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'page'      => array(
						'type'              => 'integer',
						'default'           => 1,
						'minimum'           => 1,
						'sanitize_callback' => 'absint',
					),
					'per_page'  => array(
						'type'              => 'integer',
						'default'           => 10,
						'minimum'           => 1,
						'maximum'           => 50,
						'sanitize_callback' => 'absint',
					),
					'category'  => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
					'tag'       => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
					'post_type' => array(
						'type'              => 'string',
						'default'           => 'post',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);

		// ── 文章目录 TOC ──────────────────────────────────────
		register_rest_route(
			$ns,
			'/toc/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( TocController::class, 'handle' ),
				'permission_callback' => '__return_true',
				'args'                => self::post_id_arg(),
			)
		);

		// ── 前台登录 ──────────────────────────────────────────
		register_rest_route(
			$ns,
			'/auth/login',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( LoginController::class, 'handle' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'username' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'password' => array(
						'required' => true,
						'type'     => 'string',
					),
					'remember' => array(
						'required' => false,
						'type'     => 'boolean',
						'default'  => false,
					),
				),
			)
		);

		// ── 个人资料 ──────────────────────────────────────────
		register_rest_route(
			$ns,
			'/profile',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( ProfileController::class, 'get' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( ProfileController::class, 'update' ),
					'permission_callback' => '__return_true',
				),
			)
		);
	}

	/** 通用文章 ID 参数定义（复用） */
	private static function post_id_arg(): array {
		return array(
			'id' => array(
				'required'          => true,
				'type'              => 'integer',
				'minimum'           => 1,
				'validate_callback' => static fn( $v ) => is_numeric( $v ) && (int) $v > 0,
				'sanitize_callback' => 'absint',
			),
		);
	}
}
