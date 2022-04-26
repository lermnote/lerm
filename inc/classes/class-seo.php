<?php

namespace Lerm\Inc;

/**
 * todo
 * 1.site-sep
 * 2.social
 * 3.验证码 百度 bing 谷歌
 * 4.面包屑导航
 * 5.seo标题
 */
class SEO {

	public static $args = array(
		'baidu_submit' => false,
		'submit_url'   => '',
		'submit_token' => '',
		'post_urls'    => array(),
		'separator'    => '',
		'keywords'     => array(),
		'description'  => '',
	);

	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_seo_', wp_parse_args( $params, self::$args ) );
		self::hooks();
	}

	// instance
	public static function instance( $params = array() ) {
		return new self( $params );
	}

	public static function hooks() {
		add_filter( 'document_title_separator', array( __NAMESPACE__ . '\SEO', 'title_separator' ), 15, 1 );

		add_action( 'wp_head', array( __NAMESPACE__ . '\SEO', 'html' ), 1 );
		if ( self::$args['baidu_submit'] ) {
			add_action( 'publish_post', array( __NAMESPACE__ . '\SEO', 'baidu_submit' ) );
		}
		if ( self::$args['html_slug'] ) {
			add_filter( 'user_trailingslashit', array( __NAMESPACE__ . '\SEO', 'trailingslashit' ), 10, 2 );
			add_action( 'init', array( __NAMESPACE__ . '\SEO', 'html_page_permalink' ), -1 );
		}
	}

	/**
	 * Fix end of archive url with slash.
	 *
	 * @param  array  $args Arguments to pass to Breadcrumb_Trail.
	 * @param string $string      URL with or without a trailing slash.
	 * @param string $type_of_url The type of URL being considered. Accepts 'single', 'single_trackback',
	 *                            'single_feed', 'single_paged', 'commentpaged', 'paged', 'home', 'feed',
	 *                            'category', 'page', 'year', 'month', 'day', 'post_type_archive'.
	 */
	public static function trailingslashit( $string, $type_of_url ) {
		if ( 'category' !== $type_of_url ) {
			$string = untrailingslashit( $string );
		}
		if ( 'page' !== $type_of_url && 'home' !== $type_of_url && 'single' !== $type_of_url ) {
			$string = trailingslashit( $string );
		}
		return $string;
	}

	// add html slug
	public static function html_page_permalink() {
		global $wp_rewrite;
		if ( ! strpos( $wp_rewrite->get_page_permastruct(), '.html' ) ) {
			$wp_rewrite->page_structure = $wp_rewrite->page_structure . '.html';
		}
	}

	/**
	 * Set title separatpr of site.
	 *
	 * @param string $sep
	 * @return string The site title separator.
	 */
	public static function title_separator( $sep ) {
		return self::$args['separator'] ? str_replace( '-', self::$args['separator'], $sep ) : $sep;
	}

	/**
	 * Keywords
	 *
	 * @since  1.0.0
	 * @return string $keywords
	 */
	public static function keywords( $keywords = array() ) {
		global $post;
		if ( isset( $keywords ) && ! empty( $keywords ) ) {
			$keywords[] = $keywords;
		} elseif ( is_singular() ) {
			if ( has_tag() ) {
				foreach ( get_the_tags() as $tag ) {
					$keywords[] = $tag->name;
				}
			} else {
				foreach ( get_the_category( $post->ID ) as $cat ) {
					$keywords[] = $cat->cat_name;
				}
			}
		} elseif ( is_archive() ) {
			$keywords[] = single_term_title( '', false );
		}
		$keywords[] = get_bloginfo( 'name' );
		return array_unique( $keywords );
	}

	/**
	 * Description
	 *
	 * @since  1.0.0
	 * @return string $dsecription
	 */
	public static function description( $description = '' ) {
		global $post;
		if ( ! empty( $description ) ) {
			$description = $description;
		} elseif ( is_singular() ) {
			if ( ! empty( $post->post_excerpt ) ) {
				$description = $post->post_excerpt;
			} else {
				$description = wp_strip_all_tags( $post->post_content );
				$description = strip_shortcodes( $description );
				$description = str_replace( array( "\n", "\r", "\t" ), ' ', $description );
			}
		} elseif ( is_archive() ) {
			$description = get_the_archive_description() ? wp_strip_all_tags( get_the_archive_description() ) : BLOGNAME . '-' . single_term_title( '', false );
		} else {
			$description = get_bloginfo( 'description' );
		}
		return mb_substr( $description, 0, 200, 'utf8' );
	}

	/**
	 * keywords and Description html
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public static function html() {
		$keywords    = self::keywords( self::$args['keywords'] );
		$description = self::description( self::$args['description'] );
		echo '<meta name="keywords" content="' . esc_attr( implode( ',', $keywords ) ) . '">';
		echo '<meta name="description" content="' . esc_attr( $description ) . '">';
	}

	/**
	 * WordPress baidu submit
	 *
	 * @since  1.0.0
	 */
	public static function lerm_baidu_submit( $post_ID ) {

		// if submit success, add post meta 'Baidusubmit'，value is 1
		if ( array_key_exists( 'success', $result ) ) {
			add_post_meta( $post_ID, 'Baidusubmit', 1, true );
		}
	}

	/**
	 * Submit posts links to baidu zz
	 *
	 * @since  3.1.0
	 */
	public static function baidu_submit( $url, $site = '', $token = '' ) {
		$api = 'http://data.zz.baidu.com/urls?site=' . $site . '&token=' . $token;

		$response = wp_remote_get(
			$api,
			array(
				'method'  => 'POST',
				'body'    => $url,
				'headers' => 'Content-Type: text/plain',
			)
		);
		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( trim( $response ), true );
		return $response;
	}

	/**
	 * First submit post links to baidu zz
	 *
	 * @since  3.1.0
	 */
	public static function query_post( $offset ) {
		$offset = 0;

		$query = new \WP_Query(
			array(
				'post_type'      => 'any',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'offset'         => $offset,
			)
		);
		if ( $query->have_posts() ) {
			$count  = count( $query->posts );
			$number = $offset + $count;

			$urls = '';

			while ( $query->have_posts() ) {
				$query->the_post();
				if ( false === wp_cache_get( 'lerm_post_query' ) ) {
					$urls .= apply_filters( 'lerm_post_links', get_permalink() ) . "\n";
					wp_cache_set( 'lerm_post_query', $urls, 'lerm_cache', HOUR_IN_SECONDS );
				}
			}
			return $urls;
		}
	}
}
