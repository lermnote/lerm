<?php // phpcs:disable WordPress.Files.FileName
/**
 * Lazyload images via DOMDocument output buffering.
 *
 * 修复记录：
 *  - lazyload_match() 删除：该方法是新旧方案混合时留下的残骸，
 *    有效逻辑全部 dead code（在 return 之后），且 should_skip()
 *    被错误地嵌套定义在其花括号内，导致 Parse Error。
 *  - process_img_node() 新增：从 lazyload_match() 中提取有效逻辑，
 *    接收 DOMElement，直接修改节点，与 lazyload_content() 配合。
 *  - should_skip() 修复：移出嵌套，成为独立私有方法；
 *    foreach 和两个 if 补全缺失的结束花括号。
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
	 * @var array
	 */
	protected $default_args = array(
		// �?HTML 中包含以下字符串时跳�?lazyload（可按需添加�?
		'skip_list'    => array( 'skip_lazyload', 'custom-logo', 'slider', 'avatar', 'qrcode' ),
		// 是否在输出中附加 <noscript> 回退
		'add_noscript' => true,
	);

	private const PLACEHOLDER = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';

	private const ALLOWED_EXT = array( 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'avif' );
	/**
	 * 实例化并应用参数
	 *
	 * @param array $params Optional parameters for lazy loading.
	 */
	public function __construct( $params = array() ) {
		$this->default_args = apply_filters( 'lerm_lazyload_args', wp_parse_args( $params, $this->default_args ) );
		$this->hooks();
	}

	/**
	 * 绑定 hook
	 */
	public function hooks() {
		add_action( 'template_redirect', array( $this, 'start_buffer' ) );
	}

	/**
	 * 开始输出缓冲，回调 lazyload_content 处理 buffer
	 */
	public function start_buffer() {
		ob_start( array( $this, 'lazyload_content' ) );
	}

	/**
	 * 对整个页面内容进行替换：查找 <img> 标签并处�?   *
	 * @param string $content HTML 内容
	 * @return string 处理后的内容
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
	 * 处理匹配到的单个 <img> 标签
	 *
	 * @param array $matches preg 回调传入
	 * @return string 替换后的 HTML（通常�?lazyload �?+ 可�?<noscript> 回退�?  */
	/**
	 * 处理单个 <img> DOMElement 节点。
	 *
	 * 对需要 lazyload 的图片：
	 *   - 添加 loading="lazy" 属性
	 *   - 追加 lazy CSS class
	 *   - src  → data-src
	 *   - srcset → data-srcset
	 *   - src 替换为 1×1 透明占位图
	 *
	 * 若节点应跳过（在 skip_list 中、已有 loading="eager" 等），
	 * 则不做任何修改。
	 *
	 * @param \DOMElement $img
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

		// 只处理常见位图格式，SVG/未知格式跳过
		$path      = wp_parse_url( $src, PHP_URL_PATH );
		$extension = $path ? strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ) : '';
		if ( ! in_array( $extension, self::ALLOWED_EXT, true ) ) {
			return;
		}

		// 添加 loading="lazy"（已有时不覆盖）
		if ( ! $img->hasAttribute( 'loading' ) ) {
			$img->setAttribute( 'loading', 'lazy' );
		}

		// 追加 lazy class
		$existing = $img->getAttribute( 'class' );
		$classes  = array_filter( preg_split( '/\s+/', trim( $existing ) ) );
		if ( ! in_array( 'lazy', $classes, true ) ) {
			$classes[] = 'lazy';
		}
		$img->setAttribute( 'class', implode( ' ', $classes ) );

		// srcset → data-srcset
		if ( $img->hasAttribute( 'srcset' ) ) {
			$img->setAttribute( 'data-srcset', $img->getAttribute( 'srcset' ) );
			$img->removeAttribute( 'srcset' );
		}

		// src → data-src，占位图放 src
		$img->setAttribute( 'data-src', $src );
		$img->setAttribute( 'src', self::PLACEHOLDER );

		// <noscript> 回退（使用原始 img_html，保留原 src）
		if ( ! empty( $this->default_args['add_noscript'] ) ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
			$noscript = $img->ownerDocument->createElement( 'noscript' );
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
			$fragment = $img->ownerDocument->createDocumentFragment();
			// appendXML 要求有效 XML；img_html 已由 DOMDocument 生成，是合法的
			$fragment->appendXML( $img_html );
			$noscript->appendChild( $fragment );
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
			$img->parentNode->insertBefore( $noscript, $img->nextSibling );
		}
	}

		/**
		 * 判断单张 img 是否应跳�?lazyload
		 *
		 * @param string $img_html 图片标签 HTML
		 * @return bool
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
