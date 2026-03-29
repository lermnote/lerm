<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Support;

/**
 * 安全相关工具集
 *
 * 集中管理主题内所有安全操作：输入过滤、输出转义、
 * Nonce 验证、内容策略等。避免在模板和 Controller 中散落同类代码。
 *
 * @package Lerm\Support
 */
final class Security {

	// -------------------------------------------------------------------------
	// Nonce
	// -------------------------------------------------------------------------

	/**
	 * 生成 REST API Nonce
	 *
	 * @return string
	 */
	public static function rest_nonce(): string {
		return wp_create_nonce( 'wp_rest' );
	}

	/**
	 * 验证 REST Nonce（从请求头或参数中读取）
	 *
	 * @param  \WP_REST_Request $request
	 * @param  string           $action  nonce action，默认 'wp_rest'
	 * @return bool
	 */
	public static function verify_rest_nonce( \WP_REST_Request $request, string $action = 'wp_rest' ): bool {
		$nonce = $request->get_header( 'X-WP-Nonce' )
			?? $request->get_param( '_wpnonce' )
			?? '';

		return (bool) wp_verify_nonce( (string) $nonce, $action );
	}

	// -------------------------------------------------------------------------
	// 输入过滤
	// -------------------------------------------------------------------------

	/**
	 * 过滤并验证电子邮件地址
	 *
	 * @param  mixed  $value 待过滤的值
	 * @return string 合法邮件地址，否则返回空字符串
	 */
	public static function sanitize_email( mixed $value ): string {
		$email = sanitize_email( (string) $value );
		return is_email( $email ) ? $email : '';
	}

	/**
	 * 过滤 URL（仅允许 http/https）
	 *
	 * @param  mixed  $value    待过滤的值
	 * @param  bool   $relative 是否允许相对路径，默认 false
	 * @return string 合法 URL，否则返回空字符串
	 */
	public static function sanitize_url( mixed $value, bool $relative = false ): string {
		$url = esc_url_raw( (string) $value, array( 'http', 'https' ) );
		if ( ! $relative && ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return '';
		}
		return $url;
	}

	/**
	 * 过滤整数（含边界检查）
	 *
	 * @param  mixed    $value 待过滤的值
	 * @param  int      $min   最小值，默认 PHP_INT_MIN
	 * @param  int      $max   最大值，默认 PHP_INT_MAX
	 * @return int|null 超出范围时返回 null
	 */
	public static function sanitize_int( mixed $value, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX ): ?int {
		if ( ! is_numeric( $value ) ) {
			return null;
		}
		$int = (int) $value;
		return ( $int >= $min && $int <= $max ) ? $int : null;
	}

	// -------------------------------------------------------------------------
	// 输出转义
	// -------------------------------------------------------------------------

	/**
	 * 转义用于 HTML 属性的字符串
	 *
	 * @param  mixed  $value
	 * @return string
	 */
	public static function attr( mixed $value ): string {
		return esc_attr( (string) $value );
	}

	/**
	 * 转义用于 HTML 内容区域的字符串
	 *
	 * @param  mixed  $value
	 * @return string
	 */
	public static function html( mixed $value ): string {
		return esc_html( (string) $value );
	}

	/**
	 * 转义用于 JS 上下文的字符串（输出到 <script> 块）
	 *
	 * @param  mixed  $value
	 * @return string 带引号的 JS 字符串字面量
	 */
	public static function js( mixed $value ): string {
		$encoded = wp_json_encode( (string) $value );
		return false !== $encoded ? $encoded : '""';
	}

	// -------------------------------------------------------------------------
	// SVG 过滤
	// -------------------------------------------------------------------------

	/**
	 * 对 SVG 字符串进行白名单过滤，防止注入恶意属性或脚本
	 *
	 * 只允许纯展示性标签与属性，排除 script、on* 事件、xlink:href 等危险属性。
	 *
	 * @param  string $svg 原始 SVG 字符串
	 * @return string 过滤后的安全 SVG
	 */
	public static function kses_svg( string $svg ): string {
		$allowed = array(
			'svg'      => array(
				'xmlns'       => true,
				'xmlns:xlink' => true,
				'width'       => true,
				'height'      => true,
				'viewbox'     => true,
				'version'     => true,
				'aria-hidden' => true,
				'role'        => true,
				'focusable'   => true,
			),
			'g'        => array(
				'transform' => true,
				'fill'      => true,
				'stroke'    => true,
			),
			'path'     => array(
				'd'            => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'fill-rule'    => true,
				'clip-rule'    => true,
			),
			'circle'   => array(
				'cx'     => true,
				'cy'     => true,
				'r'      => true,
				'fill'   => true,
				'stroke' => true,
			),
			'rect'     => array(
				'x'      => true,
				'y'      => true,
				'width'  => true,
				'height' => true,
				'rx'     => true,
				'ry'     => true,
				'fill'   => true,
			),
			'polyline' => array(
				'points'       => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
			),
			'polygon'  => array(
				'points' => true,
				'fill'   => true,
				'stroke' => true,
			),
			'line'     => array(
				'x1'           => true,
				'y1'           => true,
				'x2'           => true,
				'y2'           => true,
				'stroke'       => true,
				'stroke-width' => true,
			),
			'title'    => array(),
			'desc'     => array(),
		);

		return wp_kses( $svg, $allowed );
	}

	// -------------------------------------------------------------------------
	// 内容策略
	// -------------------------------------------------------------------------

	/**
	 * 检查字符串是否为合法的 WordPress 用户名（不含特殊字符）
	 *
	 * @param  string $username
	 * @return bool
	 */
	public static function is_valid_username( string $username ): bool {
		if ( '' === $username || strlen( $username ) > 60 ) {
			return false;
		}
		return sanitize_user( $username, true ) === $username;
	}

	/**
	 * 校验密码强度（至少 8 位，含字母与数字）
	 *
	 * @param  string $password
	 * @return bool
	 */
	public static function is_strong_password( string $password ): bool {
		return strlen( $password ) >= 8
			&& preg_match( '/[a-zA-Z]/', $password )
			&& preg_match( '/[0-9]/', $password );
	}
}
