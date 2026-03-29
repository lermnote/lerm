<?php // phpcs:disable WordPress.Files.FileName
/**
 * Lazy-load images via DOMDocument output buffering.
 *
 * Notes:
 * - The old `lazyload_match()` implementation has been removed. It contained
 *   dead code after `return` statements and nested method definitions that
 *   could trigger parse errors.
 * - `process_img_node()` now contains the active DOM-based mutation logic.
 * - `should_skip()` is a dedicated private method with complete guard checks.
 *
 * @package Lerm\Runtime
 */

namespace Lerm\Runtime;

use Lerm\Traits\Singleton;

class Lazyload {
	use Singleton;

	/**
	 * Default arguments for lazy loading.
	 *
	 * @var array<string, mixed>
	 */
	protected $default_args = array(
		// Skip lazy loading when the rendered HTML contains any of these markers.
		'skip_list'    => array( 'skip_lazyload', 'custom-logo', 'slider', 'avatar', 'qrcode' ),
		// Append a <noscript> fallback copy of the original image.
		'add_noscript' => true,
	);

	private const PLACEHOLDER = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';

	private const ALLOWED_EXT = array( 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'avif' );

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $params Optional parameters for lazy loading.
	 */
	public function __construct( $params = array() ) {
		$this->default_args = apply_filters( 'lerm_lazyload_args', wp_parse_args( $params, $this->default_args ) );
		$this->hooks();
	}

	/**
	 * Register hooks.
	 */
	public function hooks() {
		add_action( 'template_redirect', array( $this, 'start_buffer' ) );
	}

	/**
	 * Start output buffering and process the response HTML.
	 */
	public function start_buffer() {
		if ( is_admin() || wp_doing_ajax() || is_feed() || is_embed() || is_preview() ) {
			return;
		}

		ob_start( array( $this, 'lazyload_content' ) );
	}

	/**
	 * Add lazy-loading attributes to matching `<img>` tags in the HTML buffer.
	 *
	 * @param string $content Buffered HTML content.
	 */
	public function lazyload_content( string $content ): string {
		if ( empty( $content ) ) {
			return $content;
		}

		$doc = new \DOMDocument();
		libxml_use_internal_errors( true );

		$doc->loadHTML(
			mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ),
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);

		libxml_clear_errors();

		foreach ( $doc->getElementsByTagName( 'img' ) as $img ) {
			$this->process_img_node( $img );
		}

		return $doc->saveHTML();
	}

	/**
	 * Process a single `<img>` DOM node.
	 *
	 * For images that should be lazy-loaded, this method:
	 * - adds `loading="lazy"` when it is missing
	 * - appends the `lazy` CSS class
	 * - moves `src` to `data-src`
	 * - moves `srcset` to `data-srcset`
	 * - replaces `src` with a transparent placeholder
	 * - optionally appends a `<noscript>` fallback
	 *
	 * @param \DOMElement $img Image node.
	 */
	private function process_img_node( \DOMElement $img ): void {
		$src = $img->getAttribute( 'src' );

		if ( ! $src || stripos( $src, 'data:' ) === 0 ) {
			return;
		}

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
		$img_html = $img->ownerDocument->saveHTML( $img );
		if ( $this->should_skip( $img_html ) ) {
			return;
		}

		// Only process common bitmap formats; skip SVG and unknown formats.
		$path      = wp_parse_url( $src, PHP_URL_PATH );
		$extension = $path ? strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ) : '';
		if ( ! in_array( $extension, self::ALLOWED_EXT, true ) ) {
			return;
		}

		// Add loading="lazy" when it is not already set.
		if ( ! $img->hasAttribute( 'loading' ) ) {
			$img->setAttribute( 'loading', 'lazy' );
		}

		// Append the lazy class once.
		$existing = $img->getAttribute( 'class' );
		$classes  = array_filter( preg_split( '/\s+/', trim( $existing ) ) );
		if ( ! in_array( 'lazy', $classes, true ) ) {
			$classes[] = 'lazy';
		}
		$img->setAttribute( 'class', implode( ' ', $classes ) );

		// Move srcset to data-srcset.
		if ( $img->hasAttribute( 'srcset' ) ) {
			$img->setAttribute( 'data-srcset', $img->getAttribute( 'srcset' ) );
			$img->removeAttribute( 'srcset' );
		}

		// Move src to data-src and replace src with the placeholder.
		$img->setAttribute( 'data-src', $src );
		$img->setAttribute( 'src', self::PLACEHOLDER );

		if ( ! empty( $this->default_args['add_noscript'] ) ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
			$noscript = $img->ownerDocument->createElement( 'noscript' );
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
			$fragment = $img->ownerDocument->createDocumentFragment();
			// appendXML requires valid XML, and $img_html comes from DOMDocument.
			$fragment->appendXML( $img_html );
			$noscript->appendChild( $fragment );
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
			$img->parentNode->insertBefore( $noscript, $img->nextSibling ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
		}
	}

	/**
	 * Determine whether a single image should skip lazy loading.
	 *
	 * @param string $img_html Image tag HTML.
	 */
	private function should_skip( $img_html ): bool {
		$skips = (array) $this->default_args['skip_list'];

		foreach ( $skips as $needle ) {
			if ( stripos( $img_html, $needle ) !== false ) {
				return true;
			}
		}

		if ( preg_match( '/\bloading\s*=\s*([\'"]?)eager\1/i', $img_html ) ) {
			return true;
		}
		if ( preg_match( '/\bdata-skip-lazy\b/i', $img_html ) ) {
			return true;
		}

		return false;
	}
}
