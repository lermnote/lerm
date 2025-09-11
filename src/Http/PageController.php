<?php
// phpcs:disable WordPress.Files.FileName
/**
 * Page REST controller
 *
 * @package Lerm
 */
declare( strict_types=1 );

namespace Lerm\Http;

use Lerm\Traits\Singleton;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PageController extends BaseController {
	use Singleton;

	/**
	 * REST namespace is inherited from BaseController
	 */
	protected const ROUTE   = 'page';
	protected const METHODS = \WP_REST_Server::READABLE; // GET
	// 后端缓存 TTL（秒）
	protected const CACHE_TTL = 300; // 5 minutes

		/**
	 * 构造函数
	 *
	 * @throws RuntimeException 当父类构造失败时
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * 处理请求（BaseController 会把这个作为 callback 注册）
	 *
	 * @param WP_REST_Request $request REST request
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		// 获取并验证 url 参数
		$url = $request->get_param( 'url' );
		if ( empty( $url ) ) {
			return $this->error( 'Missing url parameter.', 400, 'missing_url' );
		}

		$url = esc_url_raw( wp_unslash( $url ) );
		if ( ! $url ) {
			return $this->error( 'Invalid url.', 400, 'invalid_url' );
		}

		// 只允许本站 host，防止 SSRF（如果支持子域，可扩展白名单）
		$site_host   = wp_parse_url( home_url(), PHP_URL_HOST );
		$target_host = wp_parse_url( $url, PHP_URL_HOST );
		if ( empty( $target_host ) || $site_host !== $target_host ) {
			return $this->error( 'Host not allowed.', 400, 'invalid_host' );
		}

		// cache key
		$cache_key = 'lerm_page_' . md5( $url );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) && ! empty( $cached['data'] ) && ! empty( $cached['etag'] ) ) {
			$if_none_match = $request->get_header( 'if-none-match' );
			if ( $if_none_match && $if_none_match === $cached['etag'] ) {
				$resp = new WP_REST_Response( null, 304 );
				$resp->header( 'ETag', $cached['etag'] );
				if ( ! empty( $cached['last_modified_header'] ) ) {
					$resp->header( 'Last-Modified', $cached['last_modified_header'] );
				}
				return $resp;
			}

			$resp = rest_ensure_response( $cached['data'] );
			$resp->set_status( 200 );
			$resp->header( 'ETag', $cached['etag'] );
			$resp->header( 'Cache-Control', 'private, max-age=' . self::CACHE_TTL );
			if ( ! empty( $cached['last_modified_header'] ) ) {
				$resp->header( 'Last-Modified', $cached['last_modified_header'] );
			}
			return $resp;
		}

		// 优先使用 WP 内部解析：url -> post_id（适用于单页、文章、页面）
		$post_id = url_to_postid( $url );

		// 如果 url_to_postid 找不到，尝试 path -> page_by_path 映射（已有），否则回退到远程抓取解析
		if ( empty( $post_id ) ) {
			$parsed = wp_parse_url( $url );
			$path   = isset( $parsed['path'] ) ? trim( $parsed['path'], '/' ) : '';
			if ( '' === $path ) {
				$front_page = get_option( 'page_on_front' );
				$post_id    = $front_page ? (int) $front_page : 0;
			} else {
				$page = get_page_by_path( $path );
				if ( $page ) {
					$post_id = (int) $page->ID;
				}
			}
		}

		$data          = null;
		$etag          = null;
		$last_modified = null;

		$remote = $this->_fetch_remote_page( $url );
		if ( is_wp_error( $remote ) ) {
			return $this->error( $remote->get_error_message(), 500, 'fetch_failed' );
		}
			// $remote 返回一个数组 ['title','content','meta_description','meta_keywords','last_modified']
			$data = $remote;

			// 生成一个 etag（基于内容哈希，方便条件请求）
			$etag          = '"' . md5( wp_json_encode( $data ) ) . '"';
			$last_modified = isset( $data['last_modified'] ) ? $data['last_modified'] : gmdate( 'c' );
			$data['etag']  = $etag;
			$data['url']   = $url;
		//}

		// 条件请求再次检查（If-None-Match）
		$if_none_match = $request->get_header( 'if-none-match' );
		if ( $if_none_match && $if_none_match === $etag ) {
			$resp = new WP_REST_Response( null, 304 );
			$resp->header( 'ETag', $etag );
			$resp->header( 'Cache-Control', 'private, max-age=' . self::CACHE_TTL );
			$resp->header( 'Last-Modified', gmdate( 'D, d M Y H:i:s', strtotime( $last_modified ) ) . ' GMT' );
			return $resp;
		}

		// 写入 transient（统一缓存结构）
		$cache_payload = array(
			'data'                 => $data,
			'etag'                 => $etag,
			'last_modified_header' => gmdate( 'D, d M Y H:i:s', strtotime( $last_modified ) ) . ' GMT',
		);
		set_transient( $cache_key, $cache_payload, self::CACHE_TTL );

		$resp = rest_ensure_response( $data );
		$resp->set_status( 200 );
		$resp->header( 'ETag', $etag );
		$resp->header( 'Cache-Control', 'private, max-age=' . self::CACHE_TTL );
		$resp->header( 'Last-Modified', gmdate( 'D, d M Y H:i:s', strtotime( $last_modified ) ) . ' GMT' );

		return $resp;
	}

	/**
	 * Fetch a remote page (same-site) and extract useful parts.
	 *
	 * @param string $url URL to fetch.
	 * @return array|WP_Error
	 */
	protected function _fetch_remote_page( string $url ) {
		// wp_remote_get with a short timeout
		$res = wp_remote_get(
			$url,
			array(
				'timeout'     => 10,
				'redirection' => 0, // 不自动跟随重定向，防止被重定向到外部
				'sslverify'   => true,
				'headers'     => array( 'Accept' => 'text/html,application/xhtml+xml' ),
			)
		);

		$code = wp_remote_retrieve_response_code( $res );
		if ( in_array( (int) $code, array( 301, 302, 307, 308 ), true ) ) {
			return new WP_Error( 'redirect_disallowed', 'Redirects are disallowed for security.', array( 'status' => $code ) );
		}

		// $res = wp_remote_get( $url, ['timeout'=>10,'redirection'=>5,'sslverify'=>true] );
		// after fetch:
		// $response_url = $res['http_response']->get_response_object()->response ? null : null; // WP hides final URL; instead check headers Location chain or use cURL wrapper
		// simpler: parse wp_remote_retrieve_headers and check 'x-final-url' if set by proxy, OR avoid redirects by setting 'redirection' => 0 and manually following only same-host redirects.
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$code = wp_remote_retrieve_response_code( $res );
		$body = wp_remote_retrieve_body( $res );
		if ( 200 !== (int) $code || empty( $body ) ) {
			return new WP_Error( 'fetch_failed', 'Failed to fetch remote content', array( 'status' => $code ) );
		}

		// parse DOM
		libxml_use_internal_errors( true );
		$doc = new \DOMDocument();
		// ensure HTML5-ish handling: prepend UTF-8 meta if absent to avoid mangled chars
		if ( false === stripos( $body, '<meta' ) ) {
			$body = '<?xml encoding="utf-8" ?>' . $body;
		}
		$doc->loadHTML( $body );
		libxml_clear_errors();

		// title
		$title = '';
		$te    = $doc->getElementsByTagName( 'title' );
		if ( $te->length ) {
			$title = $te->item( 0 )->textContent;
		}

		// metas
		$meta_description = '';
		$meta_keywords    = '';
		$meta_elements    = $doc->getElementsByTagName( 'meta' );
		foreach ( $meta_elements as $meta ) {
			$name = strtolower( $meta->getAttribute( 'name' ) );
			if ( 'description' === $name && ! $meta_description ) {
				$meta_description = $meta->getAttribute( 'content' );
			}
			if ( 'keywords' === $name && ! $meta_keywords ) {
				$meta_keywords = $meta->getAttribute( 'content' );
			}
		}

		// content: try #page-ajax, fallback to #content, .entry-content, body
		$content    = '';
		$candidates = array( 'page-ajax', 'content', 'main', 'primary' );
		foreach ( $candidates as $id ) {
			$el = $doc->getElementById( $id );
			if ( $el ) {
				$content = $doc->saveHTML( $el );
				break;
			}
		}
		if ( ! $content ) {
			// try .entry-content or first <main>
			$xpath = new \DOMXPath( $doc );
			$node  = $xpath->query( "//*[contains(concat(' ', normalize-space(@class), ' '), ' entry-content ')]" )->item( 0 );
			if ( $node ) {
				$content = $doc->saveHTML( $node );
			} else {
				$mains = $doc->getElementsByTagName( 'main' );
				if ( $mains->length ) {
					$content = $doc->saveHTML( $mains->item( 0 ) );
				}
			}
		}

		// last_modified guess (use server Date header or current time)
		$last_modified = gmdate( 'c' );
		$headers       = wp_remote_retrieve_headers( $res );
		// if ( $headers ) {
		//  $lm = $headers->get( 'last-modified' ) ?: $headers->get( 'Last-Modified' );
		//  if ( $lm ) {
		//      $last_modified = gmdate( 'c', strtotime( $lm ) );
		//  }
		// }
		$content = wp_kses_post( $content );
		return array(
			'title'            => $title,
			'meta_description' => $meta_description,
			'meta_keywords'    => $meta_keywords,
			'content'          => $content,
			'last_modified'    => $last_modified,
		);
	}
	/**
	 * Generate AJAX localization data.
	 *
	 * This function generates an array of localized data for use in AJAX requests.
	 *
	 * @param  array $l10n Existing localization data.
	 * @return array Localized data for AJAX requests.
	 */
	public static function rest_l10n_data( $l10n ) {
		$l10n = parent::rest_l10n_data( $l10n );
		$data = array(
			'page_route' => self::ROUTE,
		);
		return wp_parse_args( $data, $l10n );
	}
}
