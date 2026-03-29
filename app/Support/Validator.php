<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Support;

/**
 * 输入验证器
 *
 * 提供可组合的验证规则，供 Controller / REST 路由使用。
 * 每个方法返回 true 表示通过，返回字符串表示失败原因。
 *
 * 用法示例：
 *   $err = Validator::required( $value )
 *       ?? Validator::min_length( $value, 3 )
 *       ?? Validator::max_length( $value, 100 );
 *   if ( $err ) return Response::bad_request( $err );
 *
 * @package Lerm\Support
 */
final class Validator {

	// -------------------------------------------------------------------------
	// 存在性
	// -------------------------------------------------------------------------

	/**
	 * 值不能为空（null、''、[]）
	 *
	 * @param  mixed  $value
	 * @param  string $field 字段名，用于错误提示
	 * @return string|null   失败原因，通过时返回 null
	 */
	public static function required( mixed $value, string $field = '' ): ?string {
		if ( null === $value || '' === $value || array() === $value ) {
			$label = $field ? "「{$field}」" : '该字段';
			/* translators: %s: field name */
			return sprintf( __( '%s 不能为空', 'lerm' ), $label );
		}
		return null;
	}

	// -------------------------------------------------------------------------
	// 字符串
	// -------------------------------------------------------------------------

	/**
	 * 最小长度（字符数，非字节数）
	 *
	 * @param  string $value
	 * @param  int    $min
	 * @return string|null
	 */
	public static function min_length( string $value, int $min ): ?string {
		if ( mb_strlen( $value ) < $min ) {
			/* translators: %d: minimum length */
			return sprintf( __( '长度不能少于 %d 个字符', 'lerm' ), $min );
		}
		return null;
	}

	/**
	 * 最大长度（字符数，非字节数）
	 *
	 * @param  string $value
	 * @param  int    $max
	 * @return string|null
	 */
	public static function max_length( string $value, int $max ): ?string {
		if ( mb_strlen( $value ) > $max ) {
			/* translators: %d: maximum length */
			return sprintf( __( '长度不能超过 %d 个字符', 'lerm' ), $max );
		}
		return null;
	}

	/**
	 * 正则匹配
	 *
	 * @param  string $value
	 * @param  string $pattern  PCRE 正则表达式（含定界符）
	 * @param  string $message  自定义错误提示
	 * @return string|null
	 */
	public static function regex( string $value, string $pattern, string $message = '' ): ?string {
		if ( ! preg_match( $pattern, $value ) ) {
			return $message ? $message : __( '格式不正确', 'lerm' );
		}
		return null;
	}

	// -------------------------------------------------------------------------
	// 数字
	// -------------------------------------------------------------------------

	/**
	 * 最小值（数字）
	 *
	 * @param  int|float $value
	 * @param  int|float $min
	 * @return string|null
	 */
	public static function min( int|float $value, int|float $min ): ?string {
		if ( $value < $min ) {
			/* translators: %s: minimum value */
			return sprintf( __( '不能小于 %s', 'lerm' ), $min );
		}
		return null;
	}

	/**
	 * 最大值（数字）
	 *
	 * @param  int|float $value
	 * @param  int|float $max
	 * @return string|null
	 */
	public static function max( int|float $value, int|float $max ): ?string {
		if ( $value > $max ) {
			/* translators: %s: maximum value */
			return sprintf( __( '不能大于 %s', 'lerm' ), $max );
		}
		return null;
	}

	/**
	 * 是否为正整数（> 0）
	 *
	 * @param  mixed $value
	 * @return string|null
	 */
	public static function positive_int( mixed $value ): ?string {
		if ( ! is_numeric( $value ) || (int) $value <= 0 ) {
			return __( '必须是正整数', 'lerm' );
		}
		return null;
	}

	// -------------------------------------------------------------------------
	// 格式
	// -------------------------------------------------------------------------

	/**
	 * 有效的电子邮件地址
	 *
	 * @param  string $value
	 * @return string|null
	 */
	public static function email( string $value ): ?string {
		if ( ! is_email( $value ) ) {
			return __( '请输入有效的电子邮件地址', 'lerm' );
		}
		return null;
	}

	/**
	 * 有效的 URL（http/https）
	 *
	 * @param  string $value
	 * @return string|null
	 */
	public static function url( string $value ): ?string {
		$cleaned = esc_url_raw( $value, array( 'http', 'https' ) );
		if ( '' === $cleaned || ! filter_var( $cleaned, FILTER_VALIDATE_URL ) ) {
			return __( '请输入有效的 URL', 'lerm' );
		}
		return null;
	}

	// -------------------------------------------------------------------------
	// 枚举
	// -------------------------------------------------------------------------

	/**
	 * 值必须在允许的枚举列表中
	 *
	 * @param  mixed    $value
	 * @param  array    $allowed 允许值列表
	 * @param  bool     $strict  是否严格类型比较，默认 true
	 * @return string|null
	 */
	public static function in( mixed $value, array $allowed, bool $strict = true ): ?string {
	//phpcs:disable WordPress.PHP.StrictInArray
		if ( ! in_array( $value, $allowed, $strict ) ) {
			$list = implode( '、', array_map( 'strval', $allowed ) );
			/* translators: %s: allowed values */
			return sprintf( __( '必须是以下值之一：%s', 'lerm' ), $list );
		}
		return null;
	}

	// -------------------------------------------------------------------------
	// 组合器
	// -------------------------------------------------------------------------

	/**
	 * 依次执行多个规则，返回第一个错误；全部通过则返回 null
	 *
	 * 用法：Validator::chain( fn() => Validator::required($v), fn() => Validator::email($v) )
	 *
	 * @param  callable ...$rules 每个 callable 返回 string|null
	 * @return string|null
	 */
	public static function chain( callable ...$rules ): ?string {
		foreach ( $rules as $rule ) {
			$result = $rule();
			if ( null !== $result ) {
				return $result;
			}
		}
		return null;
	}
}
