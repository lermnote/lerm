<?php // phpcs:disable WordPress.Files.FileName0
declare(strict_types=1);

namespace Lerm\Support;

final class Image {

	/**
	 * 获取文章图片（特色图、Gutenberg 块图、content 扫描、meta_key、默认图等多种来源，支持优先级和回退）
	 * @param array $params
	 * @return array ['id' => int|null, 'src' => string|null]
	 */
	public static function get_image( array $params = array() ): array {
		$args = wp_parse_args(
			$params,
			array(
				'post_id'           => 0,
				'size'              => 'home-thumb',
				'lazy'              => 'lazy',
				'order'             => array( 'meta_key', 'featured', 'block', 'scan', 'default' ),
				'meta_key'          => 'thumbnail',
				'featured'          => true,
				'block'             => true,
				'scan'              => true,
				'default'           => array(),
				'save_as_thumbnail' => false,
				'save_as_meta_key'  => false,
				'cache'             => false,
			)
		);

		$post_id = (int) $args['post_id'];
		if ( $post_id <= 0 ) {
			return array(
				'id'  => null,
				'src' => null,
			);
		}

		$attachment_id = null;
		$image_src     = null;

		$valid_orders = array( 'meta_key', 'featured', 'block', 'scan', 'default' );
		$order        = array_intersect( (array) $args['order'], $valid_orders );
		if ( empty( $order ) ) {
			$order = $valid_orders;
		}

		if ( $args['cache'] ) {
			$cached = wp_cache_get( 'lerm_image_' . $post_id, 'post_thumbnail' );
			if ( $cached ) {
				return array(
					'id'  => (int) $cached,
					'src' => null,
				);
			}
		}

		foreach ( $order as $order_item ) {
			if ( $attachment_id ) {
				break;
			}
			switch ( $order_item ) {
				case 'meta_key':
					if ( $args['meta_key'] ) {
						[$attachment_id, $image_src] = self::get_meta_key_image( $post_id, (array) $args['meta_key'] );
					}
					break;
				case 'featured':
					if ( $args['featured'] ) {
						$attachment_id = self::get_featured_image( $post_id );
					}
					break;
				case 'block':
					if ( $args['block'] ) {
						[$attachment_id, $image_src] = self::get_blocks_image( $post_id );
					}
					break;
				case 'scan':
					if ( $args['scan'] ) {
						[$attachment_id, $image_src] = self::get_scan_image( $post_id );
					}
					break;
				case 'default':
					if ( ! empty( $args['default'] ) ) {
						$attachment_id = self::set_default_image( $args['default'] );
					}
					break;
			}
		}

		if ( $attachment_id ) {
			if ( $args['save_as_meta_key'] && $args['meta_key'] ) {
				self::meta_key_save( $post_id, (array) $args['meta_key'], $attachment_id );
			}
			if ( $args['save_as_thumbnail'] ) {
				self::set_image_as_thumbnail( $post_id, $attachment_id );
			}
			if ( $args['cache'] ) {
				wp_cache_set( 'lerm_image_' . $post_id, $attachment_id, 'post_thumbnail', HOUR_IN_SECONDS );
			}
		}

		return array(
			'id'  => null !== $attachment_id ? (int) $attachment_id : null,
			'src' => null !== $image_src ? (string) $image_src : null,
		);
	}

	public static function get_featured_image( int $post_id ): ?int {
		$id = get_post_thumbnail_id( $post_id );
		return $id ? (int) $id : null;
	}

	public static function get_blocks_image( int $post_id ): array {
		$content = (string) get_post_field( 'post_content', $post_id );
		if ( empty( $content ) ) {
			return array( null, null );
		}
		if ( strpos( $content, 'wp:image' ) === false ) {
			return array( null, null );
		}
		$blocks = parse_blocks( $content );
		foreach ( $blocks as $block ) {
			if ( ! empty( $block['blockName'] ) && 'core/image' === $block['blockName'] ) {
				$attrs = $block['attrs'] ?? array();
				if ( ! empty( $attrs['id'] ) ) {
					return array( (int) $attrs['id'], null );
				}
				if ( ! empty( $attrs['url'] ) ) {
					$id = attachment_url_to_postid( $attrs['url'] );
					return array( $id ? (int) $id : null, $id ? null : $attrs['url'] );
				}
			}
		}
		return array( null, null );
	}


	public static function get_scan_image( int $post_id ): array {
		$content = (string) get_post_field( 'post_content', $post_id );
		if ( strpos( $content, 'wp-image-' ) === false && stripos( $content, '<img' ) === false ) {
			return array( null, null );
		}
		if ( preg_match( '/wp-image-(\d+)/i', $content, $m ) ) {
			return array( (int) $m[1], null );
		}
		if ( preg_match( '|<img[^>]+src=[\'"]([^\'"]+)[\'"]|i', $content, $m2 ) ) {
			$src = $m2[1] ?? '';
			$id  = attachment_url_to_postid( $src );
			return array( $id ? (int) $id : null, $id ? null : $src );
		}
		return array( null, null );
	}


	private static function get_meta_key_image( int $post_id, array $keys ): array {
		foreach ( $keys as $key ) {
			$image = get_post_meta( $post_id, (string) $key, true );
			if ( empty( $image ) ) {
				continue;
			}
			if ( is_numeric( $image ) ) {
				return array( absint( $image ), null );
			}
			if ( is_string( $image ) && filter_var( $image, FILTER_VALIDATE_URL ) ) {
				$id = attachment_url_to_postid( $image );
				return array( $id ? (int) $id : null, $id ? null : $image );
			}
			// 其它非标准格式，保存原始字符串做 src 回退
			return array( null, (string) $image );
		}
		return array( null, null );
	}

	/** 保存 meta_key（支持多个） */
	private static function meta_key_save( int $post_id, array $keys, $value ): void {
		foreach ( $keys as $key ) {
			$old = get_post_meta( $post_id, (string) $key, true );
			if ( empty( $old ) ) {
				add_post_meta( $post_id, (string) $key, $value );
			} elseif ( (string) $old !== (string) $value ) {
				update_post_meta( $post_id, (string) $key, $value, $old );
			}
		}
	}


	public static function set_default_image( $list_items ): ?int {
		$ids = is_array( $list_items ) ? $list_items : array_map( 'trim', explode( ',', (string) $list_items ) );
		$ids = array_filter( array_map( 'intval', $ids ), fn( $id ) => $id > 0 );
		if ( empty( $ids ) ) {
			return null;
		}
		return $ids[ array_rand( $ids ) ];
	}


	private static function set_image_as_thumbnail( int $post_id, int $id ): void {
		if ( $post_id > 0 && $id > 0 && ! has_post_thumbnail( $post_id ) ) {
			set_post_thumbnail( $post_id, $id );
		}
	}


	public static function get_image_url( array $result, string $size = 'full' ): ?string {
		if ( ! empty( $result['id'] ) && is_numeric( $result['id'] ) ) {
			$url = wp_get_attachment_image_url( (int) $result['id'], $size );
			if ( ! empty( $url ) ) {
				return $url;
			}
		}
		return ! empty( $result['src'] ) ? (string) $result['src'] : null;
	}


	public static function generate_image_html( array $result, array $args = array(), array $extra_attr = array() ): string {
		$size = sanitize_key( $args['size'] ?? 'home-thumb' );
		$lazy = $args['lazy'] ?? 'lazy';

		$default_classes = array( 'attachment-' . $size, 'w-100', 'h-100', 'rounded' );

		$user_classes = $args['class'] ?? $args['classes'] ?? '';
		if ( is_array( $user_classes ) ) {
			$merged_classes = array_merge( $default_classes, $user_classes );
		} elseif ( is_string( $user_classes ) && '' !== $user_classes ) {
			$merged_classes = array_merge( $default_classes, array_filter( array_map( 'trim', explode( ' ', $user_classes ) ) ) );
		} else {
			$merged_classes = $default_classes;
		}
		$classes = trim( implode( ' ', array_unique( $merged_classes ) ) );

		$base_attr = array(
			'class'   => $classes,
			'loading' => $lazy,
		);

		// 如果附件存在，优先用 WP 的渲染函数（它会处理 srcset/sizes 等）
		if ( ! empty( $result['id'] ) && is_numeric( $result['id'] ) ) {
			$id = (int) $result['id'];

			// alt 优先：args['alt'] -> attachment alt meta -> attachment title
			$alt = $args['alt'] ?? get_post_meta( $id, '_wp_attachment_image_alt', true );
			if ( empty( $alt ) ) {
				$alt = get_the_title( $id );
			}

			$attr = array_merge(
				$base_attr,
				array(
					'alt'   => esc_attr( (string) $alt ),
					'title' => esc_attr( (string) $alt ),
				),
				$extra_attr
			);

			return wp_get_attachment_image( $id, $size, false, $attr );
		}

		if ( ! empty( $result['src'] ) && filter_var( (string) $result['src'], FILTER_VALIDATE_URL ) ) {
			$alt  = $args['alt'] ?? '';
			$attr = array_merge(
				$base_attr,
				array(
					'src'   => esc_url( (string) $result['src'] ),
					'alt'   => esc_attr( (string) $alt ),
					'title' => esc_attr( (string) $alt ),
				),
				$extra_attr
			);

			// 构建属性字符串（安全）
			$attr_pairs = array();
			foreach ( $attr as $k => $v ) {
				$attr_pairs[] = esc_attr( (string) $k ) . '="' . esc_attr( (string) $v ) . '"';
			}
			return '<img ' . implode( ' ', $attr_pairs ) . ' />';
		}

		return '';
	}
}
