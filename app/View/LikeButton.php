<?php
declare( strict_types=1 );

namespace Lerm\View;

use Lerm\Http\Rest\Repository\LikeRepository;
use Lerm\Support\Utilities;

/**
 * 点赞按钮视图助手
 *
 * 从 PostLikeRestController 中提取的纯渲染逻辑。
 * 数据读取委托给 LikeRepository，不含任何 REST 路由注册。
 *
 * 使用方式：
 *   // 文章点赞按钮
 *   LikeButton::render( get_the_ID() );
 *
 *   // 评论点赞按钮
 *   LikeButton::render( $comment->comment_ID, true );
 *
 * @package Lerm\View
 */
final class LikeButton {

	// meta key 与 LikeRepository 保持一致
	private const META_COUNT = '_lerm_like_count';

	// -------------------------------------------------------------------------
	// 主入口
	// -------------------------------------------------------------------------

	/**
	 * 渲染点赞按钮
	 *
	 * @param int   $id         文章 ID 或评论 ID
	 * @param bool  $is_comment 是否为评论点赞
	 * @param array $args {
	 *   @type string $class 额外 CSS class
	 *   @type bool   $echo  是否直接输出，默认 true
	 * }
	 * @return string|null echo 模式下返回 null，否则返回 HTML
	 */
	public static function render( int $id, bool $is_comment = false, array $args = array() ): ?string {
		$args = wp_parse_args(
			$args,
			array(
				'class' => '',
				'echo'  => true,
			)
		);

		$user_id    = Utilities::get_like_user_id();
		$liked      = LikeRepository::has_liked( $id, $user_id );
		$like_count = LikeRepository::get_count( $id );
		$type       = $is_comment ? 'comment' : 'post';

		$classes = array_filter( array(
			'like-button',
			"like-{$type}-{$id}",   // 供 JS 批量更新同一文章的多个按钮
			$args['class'],
			$liked ? 'btn-outline-danger' : 'btn-outline-secondary',
		) );

		$label  = $liked ? __( 'Unlike', 'lerm' ) : __( 'Like', 'lerm' );
		$nonce  = wp_create_nonce( 'wp_rest' );

		$button = sprintf(
			'<button type="button" class="%1$s" aria-pressed="%2$s" aria-label="%3$s" title="%3$s" data-id="%4$d" data-type="%5$s" data-nonce="%6$s">'
			. '<i class="li li-heart" aria-hidden="true"></i>'
			. '<span class="count-wrap">%7$s</span>'
			. '</button>',
			esc_attr( implode( ' ', $classes ) ),
			$liked ? 'true' : 'false',
			esc_attr( $label ),
			$id,
			esc_attr( $type ),
			esc_attr( $nonce ),
			esc_html( self::format_count( $like_count ) )
		);

		if ( false === $args['echo'] ) {
			return $button;
		}

		echo $button; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above
		return null;
	}

	// -------------------------------------------------------------------------
	// 工具方法
	// -------------------------------------------------------------------------

	/**
	 * 格式化计数（1200 → 1.2K，1500000 → 1.5M）
	 */
	public static function format_count( int $number ): string {
		if ( $number >= 1_000_000 ) {
			$formatted = rtrim( rtrim( number_format( $number / 1_000_000, 2 ), '0' ), '.' ) . 'M';
		} elseif ( $number >= 1_000 ) {
			$formatted = rtrim( rtrim( number_format( $number / 1_000, 2 ), '0' ), '.' ) . 'K';
		} else {
			$formatted = (string) $number;
		}
		return $formatted;
	}
}
