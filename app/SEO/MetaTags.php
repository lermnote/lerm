<?php // phpcs:disable WordPress.Files.FileName
declare(strict_types=1);

namespace Lerm\SEO;

use Lerm\Traits\Singleton;

/**
 * SEO + Open Graph + Twitter Card
 */
class MetaTags {
	use Singleton;

	protected static array $args = array(
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
	}

	public static function output_meta_tags(): void {
		$keywords    = implode( ',', self::keywords() );
		$description = self::description();

		if ( $keywords ) {
			echo '<meta name="keywords" content="' . esc_attr( $keywords ) . '">' . PHP_EOL;
		}
		if ( $description ) {
			echo '<meta name="description" content="' . esc_attr( $description ) . '">' . PHP_EOL;
		}
	}


	public static function keywords( array $keywords = array() ): array {
		global $post;

		if ( ! empty( $keywords ) ) {
			$keywords = array_merge( $keywords, (array) self::$args['keywords'] );
		} elseif ( is_singular() && isset( $post->ID ) ) {
			$tags = get_the_tags( $post->ID );
			if ( $tags && ! is_wp_error( $tags ) ) {
				$keywords = wp_list_pluck( $tags, 'name' );
			} else {
				$cats = get_the_category( $post->ID );
				if ( $cats ) {
					$keywords = wp_list_pluck( $cats, 'name' );
				}
			}
		} elseif ( is_archive() ) {
			$term = single_term_title( '', false );
			if ( $term ) {
				$keywords[] = $term;
			}
		}

		$keywords[] = get_bloginfo( 'name' );

		return array_values( array_unique( array_filter( $keywords ) ) );
	}

	public static function description( string $description = '' ): string {
		global $post;

		if ( $description ) {
			return mb_substr( $description, 0, 200, 'UTF-8' );
		}

		if ( is_singular() && isset( $post->ID ) ) {
			if ( ! empty( $post->post_excerpt ) ) {
				$description = $post->post_excerpt;
			} else {
				$description = wp_strip_all_tags( strip_shortcodes( $post->post_content ?? '' ) );
			}
		} elseif ( is_archive() ) {
			$archive_desc = get_the_archive_description();
			$description  = $archive_desc ? wp_strip_all_tags( $archive_desc ) : get_bloginfo( 'name' ) . ' - ' . single_term_title( '', false );
		} else {
			$description = get_bloginfo( 'description' );
		}

		return mb_substr( trim( $description ), 0, 200, 'UTF-8' );
	}

	public static function trailingslashit( string $str, string $type_of_url ): string {
		$no_slash = array( 'category', 'page', 'home', 'single' );
		if ( in_array( $type_of_url, $no_slash, true ) ) {
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
	public static function get_args(): array {
		return self::$args;
	}
}
