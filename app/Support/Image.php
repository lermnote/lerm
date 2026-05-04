<?php // phpcs:disable WordPress.Files.FileName0
declare( strict_types=1 );

namespace Lerm\Support;

/**
 * Image helper — resolves a post's representative image from multiple sources
 * and renders responsive <img> HTML.
 *
 * Usage (instance):
 *   $image = new Image(['post_id' => 42, 'size' => 'home-thumb']);
 *   if ($image->found()) echo $image->generate_image_html();
 *
 * Usage (static):
 *   $result = Image::get_image(['post_id' => 42]);
 *   echo Image::build_image_html($result, ['size' => 'home-thumb']);
 */
final class Image {

	// ── Instance state ────────────────────────────────────────────────────────

	public readonly ?int $attachment_id;
	public readonly ?string $src;
	private readonly array $args;

	// ── Constants ─────────────────────────────────────────────────────────────

	/**
	 * Valid image-source strategies in priority order.
	 */
	private const VALID_ORDERS = array( 'meta_key', 'featured', 'block', 'scan', 'default' );

	/**
	 * Default size when none is supplied.
	 */
	private const DEFAULT_SIZE = 'home-thumb';

	// ── Constructor ───────────────────────────────────────────────────────────

	/**
	 * @param array{
	 *   post_id?: int,
	 *   size?: string,
	 *   lazy?: string,
	 *   order?: string[],
	 *   default?: int|int[]|string,
	 *   class?: string|string[],
	 *   alt?: string,
	 * } $params
	 */
	public function __construct( array $params = array() ) {
		$this->args = wp_parse_args(
			$params,
			array(
				'post_id' => 0,
				'size'    => self::DEFAULT_SIZE,
				'lazy'    => 'lazy',
				'order'   => array( 'featured', 'block', 'scan', 'default' ),
				'default' => array(),
			)
		);

		$result              = self::get_image( $this->args );
		$this->attachment_id = $result['id'];
		$this->src           = $result['src'];
	}

	// ── Instance helpers ──────────────────────────────────────────────────────

	/**
	 * Returns true when either an attachment ID or a raw src URL was resolved.
	 * Use this instead of checking attachment_id directly to cover src-only results.
	 */
	public function found(): bool {
		return null !== $this->attachment_id || null !== $this->src;
	}

	/**
	 * Render <img> HTML using the resolved image and the constructor's args.
	 *
	 * @param array $extra_attr Extra HTML attributes merged (and overriding) the defaults.
	 */
	public function generate_image_html( array $extra_attr = array() ): string {
		return self::build_image_html(
			array(
				'id'  => $this->attachment_id,
				'src' => $this->src,
			),
			$this->args,
			$extra_attr
		);
	}

	/**
	 * Resolve the URL for the instance image (convenience wrapper).
	 */
	public function get_url( string $size = '' ): ?string {
		return self::get_image_url(
			array(
				'id'  => $this->attachment_id,
				'src' => $this->src,
			),
			$size ?: ( $this->args['size'] ?? self::DEFAULT_SIZE )
		);
	}

	// ── Static API ────────────────────────────────────────────────────────────

	/**
	 * Resolve an image for a post from multiple sources in priority order.
	 *
	 * Returns ['id' => int|null, 'src' => string|null].
	 * Exactly one of id/src will be non-null when an image is found; both null when not.
	 *
	 * @param array{
	 *   post_id?: int,
	 *   order?: string[],
	 *   meta_key?: string|string[],
	 *   featured?: bool,
	 *   block?: bool,
	 *   scan?: bool,
	 *   default?: int|int[]|string,
	 *   save_as_thumbnail?: bool,
	 *   save_as_meta_key?: bool,
	 *   cache?: bool,
	 * } $params
	 * @return array{id: int|null, src: string|null}
	 */
	public static function get_image( array $params = array() ): array {
		$args = wp_parse_args(
			$params,
			array(
				'post_id'           => 0,
				'size'              => self::DEFAULT_SIZE,
				'lazy'              => 'lazy',
				'order'             => self::VALID_ORDERS,
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
			return self::empty_result();
		}

		if ( $args['cache'] ) {
			$cache_key = 'lerm_image_' . $post_id;
			$cached    = wp_cache_get( $cache_key, 'post_thumbnail' );
			if ( false !== $cached && is_int( $cached ) ) {
				return array(
					'id'  => $cached,
					'src' => null,
				);
			}
		}

		$order = array_values(                         // re-index after intersect
			array_intersect( (array) $args['order'], self::VALID_ORDERS )
		);
		if ( empty( $order ) ) {
			$order = self::VALID_ORDERS;
		}

		$attachment_id = null;
		$image_src     = null;

		foreach ( $order as $strategy ) {
			if ( null !== $attachment_id || null !== $image_src ) {
				break;
			}

			switch ( $strategy ) {
				case 'meta_key':
					if ( $args['meta_key'] ) {
						[ $attachment_id, $image_src ] = self::get_meta_key_image( $post_id, (array) $args['meta_key'] );
					}
					break;

				case 'featured':
					if ( $args['featured'] ) {
						$attachment_id = self::get_featured_image( $post_id );
					}
					break;

				case 'block':
					if ( $args['block'] ) {
						[ $attachment_id, $image_src ] = self::get_blocks_image( $post_id );
					}
					break;

				case 'scan':
					if ( $args['scan'] ) {
						[ $attachment_id, $image_src ] = self::get_scan_image( $post_id );
					}
					break;

				case 'default':
					if ( ! empty( $args['default'] ) ) {
						$attachment_id = self::set_default_image( $args['default'] );
					}
					break;
			}
		}

		// Side-effects: persist the resolved image if requested.
		if ( null !== $attachment_id ) {
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

	/**
	 * Build responsive <img> HTML from a resolved result array.
	 *
	 * @param array{id?: int|null, src?: string|null} $result  From get_image().
	 * @param array                                   $args    size, lazy, class/classes, alt.
	 * @param array                                   $extra_attr  Merged last — overrides defaults.
	 */
	public static function build_image_html( array $result, array $args = array(), array $extra_attr = array() ): string {
		$size = sanitize_key( $args['size'] ?? self::DEFAULT_SIZE );
		$lazy = (string) ( $args['lazy'] ?? 'lazy' );

		$classes = self::resolve_classes( $size, $args );

		$base_attr = array(
			'class'   => $classes,
			'loading' => $lazy,
		);

		// ── Attachment path (preferred — WP handles srcset/sizes) ─────────
		$id = isset( $result['id'] ) && is_numeric( $result['id'] ) ? (int) $result['id'] : null;
		if ( null !== $id && $id > 0 ) {
			$alt  = self::resolve_alt( $id, $args );
			$attr = array_merge(
				$base_attr,
				array(
					'alt'   => esc_attr( $alt ),
					'title' => esc_attr( $alt ),
				),
				$extra_attr
			);
			return wp_get_attachment_image( $id, $size, false, $attr );
		}

		// ── Raw-URL fallback ───────────────────────────────────────────────
		$raw_src = isset( $result['src'] ) ? (string) $result['src'] : '';
		if ( '' !== $raw_src ) {
			// Accept both absolute URLs and root-relative paths (e.g. /wp-content/…).
			$is_valid = filter_var( $raw_src, FILTER_VALIDATE_URL )
				|| ( str_starts_with( $raw_src, '/' ) && ! str_starts_with( $raw_src, '//' ) );

			if ( $is_valid ) {
				$alt  = esc_attr( (string) ( $args['alt'] ?? '' ) );
				$attr = array_merge(
					$base_attr,
					array(
						'src'   => esc_url( $raw_src ),
						'alt'   => $alt,
						'title' => $alt,
					),
					$extra_attr
				);

				$attr_pairs = array();
				foreach ( $attr as $k => $v ) {
					$attr_pairs[] = esc_attr( (string) $k ) . '="' . esc_attr( (string) $v ) . '"';
				}
				return '<img ' . implode( ' ', $attr_pairs ) . '>';
			}
		}

		return '';
	}

	/**
	 * Resolve a URL from a result array, preferring a registered WP size.
	 *
	 * @param array{id?: int|null, src?: string|null} $result
	 */
	public static function get_image_url( array $result, string $size = 'full' ): ?string {
		$id = isset( $result['id'] ) && is_numeric( $result['id'] ) ? (int) $result['id'] : null;
		if ( null !== $id ) {
			$url = wp_get_attachment_image_url( $id, $size );
			if ( $url ) {
				return $url;
			}
		}
		return isset( $result['src'] ) && '' !== $result['src'] ? (string) $result['src'] : null;
	}

	// ── Source resolvers ──────────────────────────────────────────────────────

	public static function get_featured_image( int $post_id ): ?int {
		$id = get_post_thumbnail_id( $post_id );
		return $id ? (int) $id : null;
	}

	/**
	 * Find the first core/image block in Gutenberg post content.
	 * Uses a per-request static cache to avoid parsing the same post twice.
	 *
	 * @return array{0: int|null, 1: string|null}
	 */
	public static function get_blocks_image( int $post_id ): array {
		static $cache = array();

		if ( isset( $cache[ $post_id ] ) ) {
			return $cache[ $post_id ];
		}

		$content = (string) get_post_field( 'post_content', $post_id );

		// Cheap string check before the expensive parse_blocks call.
		if ( '' === $content || false === strpos( $content, 'wp:image' ) ) {
			return $cache[ $post_id ] = array( null, null );
		}

		foreach ( parse_blocks( $content ) as $block ) {
			if ( ( $block['blockName'] ?? '' ) !== 'core/image' ) {
				continue;
			}
			$attrs = $block['attrs'] ?? array();

			if ( ! empty( $attrs['id'] ) ) {
				return $cache[ $post_id ] = array( (int) $attrs['id'], null );
			}

			if ( ! empty( $attrs['url'] ) ) {
				$id                       = attachment_url_to_postid( $attrs['url'] );
				return $cache[ $post_id ] = $id
					? array( (int) $id, null )
					: array( null, (string) $attrs['url'] );
			}
		}

		return $cache[ $post_id ] = array( null, null );
	}

	/**
	 * Scan raw post HTML for any <img> tag.
	 *
	 * @return array{0: int|null, 1: string|null}
	 */
	public static function get_scan_image( int $post_id ): array {
		$content = (string) get_post_field( 'post_content', $post_id );

		if ( '' === $content ) {
			return array( null, null );
		}

		// Fast path: WP class-based ID.
		if ( preg_match( '/wp-image-(\d+)/i', $content, $m ) ) {
			return array( (int) $m[1], null );
		}

		// Fallback: first <img src="…">.
		if ( false !== stripos( $content, '<img' )
			&& preg_match( '/<img[^>]+src=[\'"]([^\'"]+)[\'"]/i', $content, $m2 )
		) {
			$src = $m2[1];
			$id  = attachment_url_to_postid( $src );
			return $id ? array( (int) $id, null ) : array( null, $src );
		}

		return array( null, null );
	}

	// ── Private helpers ───────────────────────────────────────────────────────

	/** @return array{id: null, src: null} */
	private static function empty_result(): array {
		return array(
			'id'  => null,
			'src' => null,
		);
	}

	/**
	 * @return array{0: int|null, 1: string|null}
	 */
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
				return $id ? array( (int) $id, null ) : array( null, $image );
			}
			return array( null, (string) $image );
		}
		return array( null, null );
	}

	private static function meta_key_save( int $post_id, array $keys, int $value ): void {
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
		$ids = is_array( $list_items )
			? $list_items
			: array_map( 'trim', explode( ',', (string) $list_items ) );

		// intval + filter in one pass; re-index so array_rand is predictable.
		$ids = array_values(
			array_filter( array_map( 'intval', $ids ), static fn( int $id ) => $id > 0 )
		);

		return empty( $ids ) ? null : $ids[ array_rand( $ids ) ];
	}

	private static function set_image_as_thumbnail( int $post_id, int $id ): void {
		if ( $post_id > 0 && $id > 0 && ! has_post_thumbnail( $post_id ) ) {
			set_post_thumbnail( $post_id, $id );
		}
	}

	/**
	 * Build the CSS class string for the <img> element.
	 */
	private static function resolve_classes( string $size, array $args ): string {
		$default = array( 'attachment-' . $size, 'w-100', 'h-100', 'rounded' );

		$user = $args['class'] ?? $args['classes'] ?? '';
		if ( is_array( $user ) ) {
			$merged = array_merge( $default, $user );
		} elseif ( is_string( $user ) && '' !== $user ) {
			$merged = array_merge( $default, array_filter( array_map( 'trim', explode( ' ', $user ) ) ) );
		} else {
			$merged = $default;
		}

		return trim( implode( ' ', array_unique( $merged ) ) );
	}

	/**
	 * Resolve alt text: explicit arg → attachment alt meta → attachment title.
	 */
	private static function resolve_alt( int $id, array $args ): string {
		if ( isset( $args['alt'] ) && '' !== $args['alt'] ) {
			return (string) $args['alt'];
		}
		$meta = get_post_meta( $id, '_wp_attachment_image_alt', true );
		return $meta ? (string) $meta : (string) get_the_title( $id );
	}
}
