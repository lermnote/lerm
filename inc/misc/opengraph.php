<?php // phpcs:disable WordPress.Files.FileName
/**
 * Open Graph class.
 *
 * @package Jetpack
 */
namespace Lerm\Inc\Misc;

use Lerm\Inc\Traits\Singleton;

class OpenGraph {

	use singleton;

	private static $image_width;
	private static $image_height;
	private static $description_length;
	private static $default_args;

	/**
	 * Constructor.
	 *
	 * Initializes the Lazyload class.
	 *
	 * @param array $params Optional parameters for lazy loading.
	 */
	public function __construct( $params = array() ) {
		self::$default_args = apply_filters( 'lerm_opengraph_args', wp_parse_args( $params, self::$default_args ) );
		self::hooks();
	}
	/**
	 * Initialize the class.
	 */
	public static function hooks() {
		// Set default configuration.
		self::$image_width        = absint( apply_filters( 'jetpack_open_graph_image_width', 200 ) );
		self::$image_height       = absint( apply_filters( 'jetpack_open_graph_image_height', 200 ) );
		self::$description_length = 197;

		// Register WordPress hooks.
		add_action( 'wp_head', array( __CLASS__, 'output_open_graph_tags' ) );
	}

	/**
	 * Outputs Open Graph meta tags to the page header.
	 */
	public static function output_open_graph_tags() {
		// Check if Open Graph is enabled.
		if ( ! apply_filters( 'jetpack_enable_opengraph', true ) ) {
			_deprecated_function( 'jetpack_enable_opengraph', '2.0.3', 'jetpack_enable_open_graph' );
			return;
		}

		$tags = self::generate_tags();

		// If tags are empty and skipping is allowed, exit.
		if ( empty( $tags ) && apply_filters( 'jetpack_open_graph_return_if_empty', true ) ) {
			return;
		}

		// Ensure the site name is always included.
		$tags['og:site_name'] = get_bloginfo( 'name' );

		// Output the Open Graph meta tags.
		self::print_meta_tags( $tags );
	}

	/**
	 * Generates Open Graph meta tags based on the current page context.
	 *
	 * @return array Open Graph meta tags.
	 */
	private static function generate_tags() {
		$tags = array();

		// Generate tags based on the page type.
		if ( is_home() || is_front_page() ) {
			$tags = self::get_home_tags();
		} elseif ( is_author() ) {
			$tags = self::get_author_tags();
		} elseif ( is_singular() ) {
			$tags = self::get_singular_tags();
		}

		// Allow developers to filter the tags.
		return apply_filters(
			'jetpack_open_graph_tags',
			$tags,
			array(
				'image_width'  => self::$image_width,
				'image_height' => self::$image_height,
			)
		);
	}

	/**
	 * Outputs the Open Graph meta tags in the page header.
	 *
	 * @param array $tags Open Graph meta tags.
	 */
	private static function print_meta_tags( $tags ) {
		$output = "\n<!-- Jetpack Open Graph Tags -->\n";

		foreach ( $tags as $property => $content ) {
			$output .= sprintf( '<meta property="%s" content="%s" />', esc_attr( $property ), esc_attr( $content ) ) . "\n";
		}

		$output .= "<!-- End Jetpack Open Graph Tags -->\n";

		// Output the tags.
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * Generates Open Graph meta tags for the homepage or front page.
	 *
	 * @return array Open Graph meta tags.
	 */
	private static function get_home_tags() {
		$tags = array(
			'og:type'        => 'website',
			'og:title'       => get_bloginfo( 'name' ),
			'og:description' => get_bloginfo( 'description' ),
			'og:url'         => home_url( '/' ),
		);

		// If the front page is a specific page, override the URL.
		if ( 'page' === get_option( 'show_on_front' ) && $front_page_id = get_option( 'page_on_front' ) ) {
			$tags['og:url'] = get_permalink( $front_page_id );
		}

		return $tags;
	}

	/**
	 * Generates Open Graph meta tags for an author archive page.
	 *
	 * @return array Open Graph meta tags.
	 */
	private static function get_author_tags() {
		$author = get_queried_object();

		return array(
			'og:type'            => 'profile',
			'og:title'           => $author->display_name,
			'og:url'             => $author->user_url ?: get_author_posts_url( $author->ID ),
			'og:description'     => $author->description,
			'profile:first_name' => get_the_author_meta( 'first_name', $author->ID ),
			'profile:last_name'  => get_the_author_meta( 'last_name', $author->ID ),
		);
	}

	/**
	 * Generates Open Graph meta tags for a single post or page.
	 *
	 * @return array Open Graph meta tags.
	 */
	private static function get_singular_tags() {
		global $post;

		$tags = array(
			'og:type'                => 'article',
			'og:title'               => get_the_title( $post->ID ) ?: '(no title)',
			'og:url'                 => get_permalink( $post->ID ),
			'og:description'         => self::get_post_description( $post ),
			'article:published_time' => gmdate( 'c', strtotime( $post->post_date_gmt ) ),
			'article:modified_time'  => gmdate( 'c', strtotime( $post->post_modified_gmt ) ),
		);

		// Add image tags if an image is available.
		if ( $image = self::get_image() ) {
			$tags = array_merge( $tags, self::format_image_tags( $image ) );
		}

		return $tags;
	}

	/**
	 * Gets the description of the post or page.
	 *
	 * @param WP_Post $post Post object.
	 * @return string Post description.
	 */
	private static function get_post_description( $post ) {
		if ( ! post_password_required( $post ) ) {
			$description = $post->post_excerpt ?: wp_trim_words( $post->post_content, 55 );
			return mb_substr( $description, 0, self::$description_length ) . '…';
		}

		return apply_filters( 'jetpack_open_graph_fallback_description', __( 'Visit the post for more.', 'jetpack' ), $post );
	}

	/**
	 * Retrieves the current page's image information.
	 *
	 * @return array Image information.
	 */
	private static function get_image() {
		// Example default image.
		return array(
			'src'      => 'https://s0.wp.com/i/blank.jpg',
			'width'    => self::$image_width,
			'height'   => self::$image_height,
			'alt_text' => 'Default image',
		);
	}

	/**
	 * Formats image-related Open Graph meta tags.
	 *
	 * @param array $image Image information.
	 * @return array Formatted meta tags.
	 */
	private static function format_image_tags( $image ) {
		$tags = array(
			'og:image' => $image['src'],
		);

		if ( ! empty( $image['width'] ) ) {
			$tags['og:image:width'] = $image['width'];
		}

		if ( ! empty( $image['height'] ) ) {
			$tags['og:image:height'] = $image['height'];
		}

		if ( ! empty( $image['alt_text'] ) ) {
			$tags['og:image:alt'] = $image['alt_text'];
		}

		return $tags;
	}
}

