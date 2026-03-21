<?php
declare( strict_types=1 );

namespace Lerm\Http\Rest;

use Lerm\Http\Rest\Controllers\LikeController;
use Lerm\Http\Rest\Controllers\ViewsController;
use Lerm\Http\Rest\Controllers\SearchController;
use Lerm\Http\Rest\Controllers\PostsController;
use Lerm\Http\Rest\Controllers\TocController;

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
		add_action( 'rest_api_init', [ __CLASS__, 'routes' ] );
	}

	public static function routes(): void {
		$ns = 'lerm/v1';

		// ── 点赞 ──────────────────────────────────────────────
		register_rest_route( $ns, '/like/(?P<id>\d+)', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ LikeController::class, 'get' ],
				'permission_callback' => '__return_true',
				'args'                => self::post_id_arg(),
			],
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ LikeController::class, 'toggle' ],
				'permission_callback' => '__return_true',
				'args'                => self::post_id_arg(),
			],
		] );

		// ── 浏览数 ────────────────────────────────────────────
		register_rest_route( $ns, '/views/(?P<id>\d+)', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ ViewsController::class, 'get' ],
				'permission_callback' => '__return_true',
				'args'                => self::post_id_arg(),
			],
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ ViewsController::class, 'increment' ],
				'permission_callback' => '__return_true',
				'args'                => self::post_id_arg(),
			],
		] );

		// ── 实时搜索 ──────────────────────────────────────────
		register_rest_route( $ns, '/search', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ SearchController::class, 'handle' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'q'         => [
					'required'          => true,
					'type'              => 'string',
					'minLength'         => 1,
					'maxLength'         => 100,
					'sanitize_callback' => 'sanitize_text_field',
				],
				'post_type' => [
					'type'              => 'string',
					'default'           => 'post',
					'sanitize_callback' => 'sanitize_key',
				],
				'per_page'  => [
					'type'              => 'integer',
					'default'           => 5,
					'minimum'           => 1,
					'maximum'           => 20,
					'sanitize_callback' => 'absint',
				],
			],
		] );

		// ── 无限滚动 / 文章列表 ───────────────────────────────
		register_rest_route( $ns, '/posts', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ PostsController::class, 'handle' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'page'      => [ 'type' => 'integer', 'default' => 1,    'minimum' => 1,  'sanitize_callback' => 'absint' ],
				'per_page'  => [ 'type' => 'integer', 'default' => 10,   'minimum' => 1,  'maximum' => 50, 'sanitize_callback' => 'absint' ],
				'category'  => [ 'type' => 'integer', 'default' => 0,    'sanitize_callback' => 'absint' ],
				'tag'       => [ 'type' => 'integer', 'default' => 0,    'sanitize_callback' => 'absint' ],
				'post_type' => [ 'type' => 'string',  'default' => 'post','sanitize_callback' => 'sanitize_key' ],
			],
		] );

		// ── 文章目录 TOC ──────────────────────────────────────
		register_rest_route( $ns, '/toc/(?P<id>\d+)', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ TocController::class, 'handle' ],
			'permission_callback' => '__return_true',
			'args'                => self::post_id_arg(),
		] );
	}

	/** 通用文章 ID 参数定义（复用） */
	private static function post_id_arg(): array {
		return [
			'id' => [
				'required'          => true,
				'type'              => 'integer',
				'minimum'           => 1,
				'validate_callback' => static fn( $v ) => is_numeric( $v ) && (int) $v > 0,
				'sanitize_callback' => 'absint',
			],
		];
	}
}
