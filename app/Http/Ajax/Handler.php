<?php
declare( strict_types=1 );

namespace Lerm\Http\Ajax;

use Lerm\Http\Ajax\Actions\ContactAction;
use Lerm\Http\Ajax\Actions\ClearCacheAction;
use Lerm\Http\Ajax\Actions\SaveOptionsAction;

/**
 * Ajax 动作注册中心
 *
 * 统一注册所有 wp_ajax_* 动作，禁止在 Action 类内部调用 add_action。
 *
 * 配置格式：
 *   'action_name' => [ ActionClass::class, $login_only ]
 *   $login_only = true  → 仅 wp_ajax_{action}（需登录）
 *   $login_only = false → 同时注册 wp_ajax_nopriv_{action}（访客也可访问）
 *
 * @package Lerm\Http\Ajax
 */
final class Handler {

	/**
	 * @var array<string, array{0: class-string, 1: bool}>
	 */
	private static array $actions = [
		'lerm_contact'      => [ ContactAction::class,     false ], // 访客可提交
		'lerm_clear_cache'  => [ ClearCacheAction::class,  true  ], // 仅管理员
		'lerm_save_options' => [ SaveOptionsAction::class, true  ], // 仅管理员
	];

	public static function register(): void {
		foreach ( self::$actions as $action => [ $class, $login_only ] ) {
			add_action( "wp_ajax_{$action}", [ $class, 'handle' ] );

			if ( ! $login_only ) {
				add_action( "wp_ajax_nopriv_{$action}", [ $class, 'handle' ] );
			}
		}
	}
}
