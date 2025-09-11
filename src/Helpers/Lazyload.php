<?php // phpcs:disable WordPress.Files.FileName
namespace Lerm\Inc\Misc;

use Lerm\Inc\Traits\Singleton;

class Lazyload {
	use Singleton;

	/**
	 * Default arguments for lazy loading.
	 *
	 * @var array
	 */
	protected $default_args = array(
		// 当 HTML 中包含以下字符串时跳过 lazyload（可按需添加）
		'skip_list'    => array( 'skip_lazyload', 'custom-logo', 'slider', 'avatar', 'qrcode' ),
		// 是否在输出中附加 <noscript> 回退
		'add_noscript' => true,
	);

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
	 * 对整个页面内容进行替换：查找 <img> 标签并处理
	 *
	 * @param string $content HTML 内容
	 * @return string 处理后的内容
	 */
	public function lazyload_content( $content ) {
		// 更通用、更稳健的匹配：逐个 img 标签处理
		return preg_replace_callback(
			'/<img\b[^>]*>/i',
			array( $this, 'lazyload_match' ),
			$content
		);
	}

	/**
	 * 处理匹配到的单个 <img> 标签
	 *
	 * @param array $matches preg 回调传入
	 * @return string 替换后的 HTML（通常为 lazyload 图 + 可选 <noscript> 回退）
	 */
	public function lazyload_match( $matches ) {
		$img_html = $matches[0];

		// 根据配置或属性决定是否跳过
		if ( $this->should_skip( $img_html ) ) {
			return $img_html;
		}

		// 使用 DOMDocument 做结构化修改（比复杂正则更可靠）
		libxml_use_internal_errors( true );

		$doc = new \DOMDocument();
		// 保证 UTF-8 正确解析
		$loaded = $doc->loadHTML( mb_convert_encoding( $img_html, 'HTML-ENTITIES', 'UTF-8' ), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		if ( ! $loaded ) {
			libxml_clear_errors();
			return $img_html; // 回退到原始
		}

		$img_node = $doc->getElementsByTagName( 'img' )->item( 0 );
		if ( ! $img_node ) {
			libxml_clear_errors();
			return $img_html;
		}

		$src = $img_node->getAttribute( 'src' );
		// 跳过 data: URI（已经是内嵌的），或没有 src 的
		if ( ! $src || stripos( $src, 'data:' ) === 0 ) {
			libxml_clear_errors();
			return $img_html;
		}

		// 根据 URL 的路径后缀判断是否为常见位图/图像格式；SVG 通常不需要 lazy
		$path        = wp_parse_url( $src, PHP_URL_PATH );
		$extension   = $path ? strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ) : '';
		$allowed_ext = array( 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'avif' );
		if ( ! in_array( $extension, $allowed_ext, true ) ) {
			libxml_clear_errors();
			return $img_html;
		}

		// 如果没有 loading 属性则添加
		if ( ! $img_node->hasAttribute( 'loading' ) ) {
			$img_node->setAttribute( 'loading', 'lazy' );
		}

		// 添加/追加 lazy class
		$existing_class = $img_node->getAttribute( 'class' );
		$classes        = preg_split( '/\s+/', trim( $existing_class ) );
		if ( ! in_array( 'lazy', $classes, true ) ) {
			$classes[] = 'lazy';
		}
		$img_node->setAttribute( 'class', trim( implode( ' ', array_filter( $classes ) ) ) );

		// 将 src -> data-src，将 srcset -> data-srcset（如果存在），并把真实 src 替换为 1x1 占位透明像素
		if ( $img_node->hasAttribute( 'srcset' ) ) {
			$img_node->setAttribute( 'data-srcset', $img_node->getAttribute( 'srcset' ) );
			$img_node->removeAttribute( 'srcset' );
		}

		$img_node->setAttribute( 'data-src', $src );

		// 小巧透明占位（1x1 gif）
		$placeholder = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';
		$img_node->setAttribute( 'src', $placeholder );

		// 输出修改后的 <img> 节点
		$modified_img = $doc->saveHTML( $img_node );

		// 可选地添加 <noscript> 回退，保留原始 HTML，保证无 JS 时能显示
		$final = $modified_img;
		if ( ! empty( $this->default_args['add_noscript'] ) ) {
			$final .= '<noscript>' . $img_html . '</noscript>';
		}

		libxml_clear_errors();
		return $final;
	}

	/**
	 * 判断单张 img 是否应跳过 lazyload
	 *
	 * @param string $img_html 图片标签 HTML
	 * @return bool
	 */
	private function should_skip( $img_html ) {
		$skips = (array) $this->default_args['skip_list'];

		// 跳过显式 data-skip 或类名/属性包含 skip_list 中的关键字
		foreach ( $skips as $needle ) {
			if ( stripos( $img_html, $needle ) !== false ) {
				return true;
			}
		}

		// 如果标签里声明了 loading="eager" 或 data-skip-lazy 属性，也跳过
		if ( preg_match( '/\bloading\s*=\s*([\'"]?)eager\1/i', $img_html ) ) {
			return true;
		}
		if ( preg_match( '/\bdata-skip-lazy\b/i', $img_html ) ) {
			return true;
		}

		return false;
	}
}
