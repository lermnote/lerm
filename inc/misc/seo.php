<?php // phpcs:disable WordPress.Files.FileName

namespace Lerm\Inc\Misc;

use Lerm\Inc\Traits\Singleton;

/**
 * todo
 * 1.site-sep
 * 2.social
 * 3.验证码 百度 bing 谷歌
 * 4.面包屑导航
 * 5.seo标题
 */
class SEO {

	use singleton;

	protected static $args = array(
		'baidu_submit' => false,
		'submit_url'   => '',
		'submit_token' => '',
		'post_urls'    => array(),
		'separator'    => '',
		'keywords'     => array(),
		'description'  => '',
		'html_slug'    => false,
	);

	/**
	 * Constructor
	 *
	 * @param array $params Optional parameters.
	 */
	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_seo_args', wp_parse_args( $params, self::$args ) );
		self::hooks();
	}

	public static function hooks() {
		add_filter( 'document_title_separator', array( __CLASS__, 'title_separator' ), 15, 1 );
		add_action( 'wp_head', array( __CLASS__, 'output_meta_tags' ), 1 );

		if ( self::$args['html_slug'] ) {
			add_filter( 'user_trailingslashit', array( __CLASS__, 'trailingslashit' ), 10, 2 );
			add_action( 'init', array( __CLASS__, 'html_page_permalink' ), -1 );
		}

		if ( self::$args['baidu_submit'] ) {
			add_action( 'publish_post', array( __CLASS__, 'baidu_submit' ) );
		}
	}

	/**
	 * Modify trailing slash for URLs.
	 *
	 * @param string $string The URL.
	 * @param string $type_of_url The type of URL.
	 * @return string Modified URL.
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

	/**
	 * Add .html to page permalinks.
	 */
	public static function html_page_permalink() {
		global $wp_rewrite;
		if ( ! strpos( $wp_rewrite->get_page_permastruct(), '.html' ) ) {
			$wp_rewrite->page_structure .= '.html';
		}
	}

	/**
	 * Set the title separator.
	 *
	 * @param string $sep The current title separator.
	 * @return string The modified title separator.
	 */
	public static function title_separator( $sep ) {
		return self::$args['separator'] ? str_replace( '-', self::$args['separator'], $sep ) : $sep;
	}

	/**
	 * Generate keywords for the meta tag.
	 *
	 * @param array $keywords Additional keywords.
	 * @return array The generated keywords.
	 */
	public static function keywords( $keywords = array() ) {
		global $post;

		if ( ! empty( $keywords ) ) {
			$keywords = array_merge( $keywords, (array) self::$args['keywords'] );
		} elseif ( is_singular() ) {
			if ( has_tag() ) {
				$keywords = wp_list_pluck( get_the_tags(), 'name' );
			} else {
				$keywords = wp_list_pluck( get_the_category( $post->ID ), 'cat_name' );
			}
		} elseif ( is_archive() ) {
			$keywords[] = single_term_title( '', false );
		}

		$keywords[] = get_bloginfo( 'name' );

		return array_unique( $keywords );
	}

	/**
	 * Generate description for the meta tag.
	 *
	 * @param string $description Additional description.
	 * @return string The generated description.
	 */
	public static function description( $description = '' ) {
		global $post;

		if ( ! empty( $description ) ) {
			return mb_substr( $description, 0, 200, 'UTF-8' );
		}

		if ( is_singular() ) {
			if ( ! empty( $post->post_excerpt ) ) {
				$description = $post->post_excerpt;
			} else {
				$description = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
			}
		} elseif ( is_archive() ) {
			$description = wp_strip_all_tags( get_the_archive_description() ) ? wp_strip_all_tags( get_the_archive_description() ) : get_bloginfo( 'name' ) . ' - ' . single_term_title( '', false );
		} else {
			$description = get_bloginfo( 'description' );
		}

		return mb_substr( $description, 0, 200, 'UTF-8' );
	}

	/**
	 * Output meta tags for SEO.
	 */
	public static function output_meta_tags() {
		$keywords    = implode( ',', self::$args['keywords'] );
		$description = self::description();

		echo '<meta name="keywords" content="' . esc_attr( $keywords ) . '">';
		echo '<meta name="description" content="' . esc_attr( $description ) . '">';
	}

	/**
	 * Submit post to Baidu when published.
	 *
	 * @param int $post_ID The post ID.
	 */
	public function post_submit( $post_ID ) {
		if ( ! empty( $post_ID ) ) {
			$post_url = get_permalink( $post_ID );
			$this->baidu_submit( $post_url, self::$args['submit_url'], self::$args['submit_token'] );
		}
	}
	/**
	 * Submit posts links to baidu zz
	 *
	 * @since  3.1.0
	 */
	public static function baidu_submit( $url, $site = '', $token = '' ) {
		$api      = 'http://data.zz.baidu.com/urls?site=' . esc_url( $site ) . '&token=' . esc_attr( $token );
		$response = wp_remote_post(
			$api,
			array(
				'headers' => array( 'Content-Type' => 'text/plain' ),
				'body'    => $url,
			),
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$response_body = wp_remote_retrieve_body( $response );
		$response      = json_decode( trim( $response_body ), true );

		return $response;
	}

	/**
	 * First submit post links to baidu zz
	 *
	 * @since  3.1.0
	 */
	public static function first_submit( $offset = 0 ) {
		$query = new \WP_Query(
			array(
				'post_type'      => 'any',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'offset'         => $offset,
			)
		);
		if ( $query->have_posts() ) {
			$urls = array();

			while ( $query->have_posts() ) {
				$query->the_post();
				$urls[] = get_permalink();

			}
			wp_reset_postdata();
			self::baidu_submit( implode( "\n", $urls ), self::$args['submit_url'], self::$args['submit_token'] );
		}
	}
}
