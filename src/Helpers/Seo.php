<?php // phpcs:disable WordPress.Files.FileName
declare(strict_types=1);

namespace Lerm\Helpers;

use Lerm\Traits\Singleton;

/**
 * SEO + Open Graph + Twitter Card
 */
class Seo {
	use Singleton;

	protected static array $args = array(
		'baidu_submit'     => false,
		'submit_url'       => '',
		'submit_token'     => '',
		'separator'        => '|',
		'keywords'         => array(),
		'description'      => '',
		'html_slug'        => false,
		'default_og_image' => 'https://s0.wp.com/i/blank.jpg',
		'og_image_width'   => 1200,
		'og_image_height'  => 630,
	);

	public function __construct( array $params = array() ) {
		self::$args = apply_filters( 'lerm_seo_args', wp_parse_args( $params, self::$args ) );
		$this->hooks();
	}

	public function hooks(): void {
		add_filter( 'document_title_separator', array( __CLASS__, 'title_separator' ), 15, 1 );
		add_action( 'wp_head', array( __CLASS__, 'output_meta_tags' ), 1 );

		if ( self::$args['html_slug'] ) {
			add_filter( 'user_trailingslashit', array( __CLASS__, 'trailingslashit' ), 10, 2 );
			add_action( 'init', array( __CLASS__, 'html_page_permalink' ), -1 );
		}
		if ( self::$args['baidu_submit'] ) {
			add_action( 'publish_post', array( __CLASS__, 'post_submit' ) );
		}
	}

	public static function trailingslashit( string $str, string $type_of_url ): string {
		if ( in_array( $type_of_url, array( 'category' ), true ) ) {
			return untrailingslashit( $str );
		}
		if ( in_array( $type_of_url, array( 'page', 'home', 'single' ), true ) ) {
			return untrailingslashit( $str );
		}
		return trailingslashit( $str );
	}

	public static function html_page_permalink(): void {
		global $wp_rewrite;
		if ( ! isset( $wp_rewrite ) ) {
			return;
		}
		if ( ! str_contains( $wp_rewrite->get_page_permastruct(), '.html' ) ) {
			$wp_rewrite->page_structure .= '.html';
		}
	}

	public static function title_separator( string $sep ): string {
		return self::$args['separator'] ? self::$args['separator'] : $sep;
	}

	/** -------- SEO Meta -------- */

	/** @return array<int,string> */
	public static function keywords( array $keywords = array() ): array {
		global $post;
		if ( ! empty( $keywords ) ) {
			$keywords = array_merge( $keywords, (array) self::$args['keywords'] );
		} elseif ( is_singular() ) {
			$tags = get_the_tags( $post->ID ?? 0 );
			if ( $tags ) {
				$keywords = wp_list_pluck( $tags, 'name' );
			} else {
				$keywords = wp_list_pluck( get_the_category( $post->ID ?? 0 ), 'name' );
			}
		} elseif ( is_archive() ) {
			$keywords[] = single_term_title( '', false );
		}
		$keywords[] = get_bloginfo( 'name' );
		return array_unique( array_filter( $keywords ) );
	}

	public static function description( string $description = '' ): string {
		global $post;
		if ( $description ) {
			return mb_substr( $description, 0, 200, 'UTF-8' );
		}
		if ( is_singular() ) {
			if ( ! empty( $post->post_excerpt ) ) {
				$description = $post->post_excerpt;
			} else {
				$description = wp_strip_all_tags( strip_shortcodes( $post->post_content ?? '' ) );
			}
		} elseif ( is_archive() ) {
			$archive_desc = get_the_archive_description();
			if ( ! $archive_desc ) {
				$archive_desc = get_bloginfo( 'name' ) . ' - ' . single_term_title( '', false );
			}
			$description = wp_strip_all_tags( $archive_desc );
		} else {
			$description = get_bloginfo( 'description' );
		}
		return mb_substr( $description, 0, 200, 'UTF-8' );
	}

	public static function output_meta_tags(): void {
		$keywords    = implode( ',', self::keywords() );
		$description = self::description();

		// SEO
		echo '<meta name="keywords" content="' . esc_attr( $keywords ) . '">' . PHP_EOL;
		echo '<meta name="description" content="' . esc_attr( $description ) . '">' . PHP_EOL;

		// Open Graph
		$og = self::generate_open_graph_tags( $description );
		foreach ( $og as $property => $content ) {
			echo '<meta property="' . esc_attr( $property ) . '" content="' . esc_attr( (string) $content ) . '">' . PHP_EOL;
		}

		// Twitter Card
		$twitter = self::generate_twitter_tags( $og, $description );
		foreach ( $twitter as $name => $content ) {
			echo '<meta name="' . esc_attr( $name ) . '" content="' . esc_attr( (string) $content ) . '">' . PHP_EOL;
		}
	}

	/** -------- Open Graph -------- */

	/** -------- Open Graph -------- */

	/** @return array<string,string|int> */
	private static function generate_open_graph_tags( string $description ): array {
		global $post;

		$tags = array(
			'og:site_name'   => get_bloginfo( 'name' ),
			'og:title'       => is_singular() ? get_the_title( $post->ID ?? 0 ) : get_bloginfo( 'name' ),
			'og:description' => $description,
			'og:url'         => is_singular() ? get_permalink( $post->ID ?? 0 ) : home_url( '/' ),
			'og:type'        => is_singular() ? 'article' : 'website',
		);

		// image
		$image                   = self::get_open_graph_image( $post->ID ?? 0 );
		$tags['og:image']        = $image['src'];
		$tags['og:image:width']  = $image['width'];
		$tags['og:image:height'] = $image['height'];

		// 如果是文章，增加作者、标签、发布时间和修改时间
		if ( is_singular( 'post' ) && isset( $post->ID ) ) {
			$author_id              = (int) $post->post_author;
			$tags['article:author'] = get_author_posts_url( $author_id );

			$terms = get_the_tags( $post->ID );
			if ( $terms && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$tags[ "article:tag:{$term->term_id}" ] = $term->name;
				}
			}

			// 发布时间和修改时间
			$tags['article:published_time'] = gmdate( 'c', strtotime( $post->post_date_gmt ) );
			$tags['article:modified_time']  = gmdate( 'c', strtotime( $post->post_modified_gmt ) );
		}

		return $tags;
	}

	/** @return array{src:string,width:int,height:int} */
	private static function get_open_graph_image( int $post_id ): array {
		$thumbnail = get_the_post_thumbnail_url( $post_id, 'full' );
		$src       = $thumbnail ? $thumbnail : self::$args['default_og_image'];
		return array(
			'src'    => $src,
			'width'  => (int) self::$args['og_image_width'],
			'height' => (int) self::$args['og_image_height'],
		);
	}

	/** -------- Twitter Card -------- */

	/** @param array<string,string|int> $og */
	private static function generate_twitter_tags( array $og, string $description ): array {
		return array(
			'twitter:card'        => 'summary_large_image',
			'twitter:title'       => (string) ( $og['og:title'] ?? get_bloginfo( 'name' ) ),
			'twitter:description' => $description,
			'twitter:image'       => (string) ( $og['og:image'] ?? self::$args['default_og_image'] ),
		);
	}

	/** -------- Baidu 提交 -------- */

	public static function post_submit( int $post_ID ): void {
		if ( ! $post_ID ) {
			return;
		}
		$post_url = get_permalink( $post_ID );
		if ( $post_url ) {
			self::baidu_submit( $post_url, self::$args['submit_url'], self::$args['submit_token'] );
		}
	}

	/** @return array<string,mixed>|\WP_Error|null */
	public static function baidu_submit( string $url, string $site = '', string $token = '' ): array|\WP_Error|null {
		if ( ! $site || ! $token || ! $url ) {
			return null;
		}
		$api      = "https://data.zz.baidu.com/urls?site={$site}&token={$token}";
		$response = wp_remote_post(
			$api,
			array(
				'headers' => array( 'Content-Type' => 'text/plain' ),
				'body'    => $url,
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$body = wp_remote_retrieve_body( $response );
		return json_decode( (string) $body, true );
	}
}
