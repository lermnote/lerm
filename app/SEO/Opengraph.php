<?php // phpcs:disable WordPress.Files.FileName
/**
 * Open Graph meta tags generator for WordPress.
 *
 * This class generates Open Graph and Twitter Card meta tags for different page types
 * (homepage, single posts, author archives, taxonomies, etc.) to improve social media
 * sharing previews.
 *
 * @package Lerm
 */
namespace Lerm\SEO;

use Lerm\Traits\Singleton;

class OpenGraph {
	use Singleton;

	/**
	 * Default Open Graph configuration.
	 *
	 * @var array Default settings for OG images and description length.
	 */
	private static array $args = array(
		'default_og_image'   => 'https://s0.wp.com/i/blank.jpg',
		'og_image_width'     => 1200,
		'og_image_height'    => 630,
		'description_length' => 197,
	);

	/**
	 * Constructor.
	 *
	 * @param array $params Optional parameters to override default settings.
	 */
	public function __construct( $params = array() ) {
		self::$default_args = apply_filters( 'lerm_opengraph_args', wp_parse_args( $params, self::$args ) );
		$this->hooks();
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public static function hooks() {
		add_action( 'wp_head', array( __CLASS__, 'output_open_graph_tags' ) );
	}

	/**
	 * Output Open Graph and Twitter Card meta tags to the page header.
	 *
	 * This is the main public method that generates and outputs all meta tags.
	 *
	 * @return void
	 */
	public static function output_open_graph_tags(): void {
		if ( ! apply_filters( 'lerm_enable_opengraph', true ) ) {
			return;
		}

		$og      = self::generate_og_tags();
		$twitter = self::generate_twitter_tags( $og );

		// If tags are empty and skipping is allowed, exit.
		if ( empty( $tags ) ) {
			return;
		}
		echo PHP_EOL . '<!-- Open Graph / Twitter Card -->' . PHP_EOL;

		// Ensure the site name is always included.
		$tags['og:site_name'] = get_bloginfo( 'name' );

		// Output Open Graph meta tags
		foreach ( $og as $property => $content ) {
			if ( is_array( $content ) ) {
				// Output multi-value fields (e.g., article:tag) individually
				foreach ( array_unique( array_filter( $content ) ) as $val ) {
					echo '<meta property="' . esc_attr( $property ) . '" content="' . esc_attr( (string) $val ) . '">' . PHP_EOL;
				}
			} else {
				echo '<meta property="' . esc_attr( $property ) . '" content="' . esc_attr( (string) $content ) . '">' . PHP_EOL;
			}
		}

		// Output Twitter Card meta tags
		foreach ( $twitter as $name => $content ) {
			echo '<meta name="' . esc_attr( $name ) . '" content="' . esc_attr( (string) $content ) . '">' . PHP_EOL;
		}

		echo '<!-- /Open Graph / Twitter Card -->' . PHP_EOL . PHP_EOL;
	}

	/**
	 * Generate Open Graph meta tags based on the current page context.
	 *
	 * Determines the page type and delegates to the appropriate tag generator method.
	 *
	 * @return array Open Graph meta tags.
	 */
	private static function generate_og_tags(): array {
		// Generate tags based on the page type
		if ( is_home() || is_front_page() ) {
			return self::home_tags();
		}
		if ( is_author() ) {
			return self::author_tags();
		}
		if ( is_singular() ) {
			return self::singular_tags();
		}
		if ( is_archive() ) {
			return self::archive_tags();
		}
		return array();
	}

	/**
	 * Generate Open Graph meta tags for the homepage.
	 *
	 * @return array Open Graph meta tags.
	 */
	private static function home_tags(): array {
		$tags = array(
			'og:type'        => 'website',
			'og:title'       => get_bloginfo( 'name' ),
			'og:description' => get_bloginfo( 'description' ),
			'og:url'         => home_url( '/' ),
			'og:site_name'   => get_bloginfo( 'name' ),
		);

		// Handle static front page
		if ( 'page' === get_option( 'show_on_front' ) ) {
			if ( $front_page_id = (int) get_option( 'page_on_front' ) ) {
				$tags['og:url'] = (string) get_permalink( $front_page_id );
			}
		}

		$image = self::get_image( 0 );
		return array_merge( $tags, self::image_tags( $image ) );
	}

	/**
	 * Generate Open Graph meta tags for an author archive page.
	 *
	 * @return array Open Graph meta tags.
	 */
	private static function author_tags(): array {
		$author = get_queried_object();
		if ( ! $author instanceof \WP_User ) {
			return array();
		}
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
	 * Generate Open Graph meta tags for a single post or page.
	 *
	 * @return array Open Graph meta tags.
	 */
	private static function singular_tags(): array {
		global $post;

		if ( ! isset( $post ) ) {
			return array();
		}
		$description = MetaTags::description();

		$tags = array(
			'og:type'                => 'article',
			'og:title'               => get_the_title( $post->ID ) ?: get_bloginfo( 'name' ),
			'og:url'                 => (string) get_permalink( $post->ID ),
			'og:description'         => $description,
			'og:site_name'           => get_bloginfo( 'name' ),
			'article:published_time' => gmdate( 'c', strtotime( $post->post_date_gmt ) ),
			'article:modified_time'  => gmdate( 'c', strtotime( $post->post_modified_gmt ) ),
		);

		$author_id              = (int) $post->post_author;
		$tags['article:author'] = (string) get_author_posts_url( $author_id );

		// Post tags (multi-value array, output individually in output_open_graph_tags)
		$terms = get_the_tags( $post->ID );
		if ( $terms && ! is_wp_error( $terms ) ) {
			$tags['article:tag'] = wp_list_pluck( $terms, 'name' );
		}

		// Primary category as article:section
		$cats = get_the_category( $post->ID );
		if ( $cats ) {
			$tags['article:section'] = $cats[0]->name;
		}

		$image = self::get_image( $post->ID );
		return array_merge( $tags, self::image_tags( $image ) );
	}

	/**
	 * Generate Open Graph meta tags for archive pages (categories, tags, etc.).
	 *
	 * @return array Open Graph meta tags.
	 */
	private static function archive_tags(): array {
		$term  = get_queried_object();
		$title = single_term_title( '', false ) ?: get_bloginfo( 'name' );

		$desc = '';
		if ( $term instanceof \WP_Term && $term->description ) {
			$desc = wp_strip_all_tags( $term->description );
		}
		$desc = $desc ?: ( get_bloginfo( 'name' ) . ' - ' . $title );

		return array(
			'og:type'        => 'website',
			'og:title'       => $title,
			'og:description' => mb_substr( $desc, 0, 197, 'UTF-8' ),
			'og:url'         => (string) get_term_link( $term ),
			'og:site_name'   => get_bloginfo( 'name' ),
		);
	}

	/**
	 * Generate Twitter Card meta tags based on Open Graph tags.
	 *
	 * @param array $og Open Graph tags to use as source.
	 * @return array Twitter Card meta tags.
	 */
	private static function generate_twitter_tags( array $og ): array {
		$image = is_array( $og['og:image'] ?? null )
			? ( $og['og:image'][0] ?? self::$args['default_og_image'] )
			: (string) ( $og['og:image'] ?? self::$args['default_og_image'] );

		return array(
			'twitter:card'        => 'summary_large_image',
			'twitter:title'       => (string) ( $og['og:title'] ?? get_bloginfo( 'name' ) ),
			'twitter:description' => (string) ( $og['og:description'] ?? '' ),
			'twitter:image'       => $image,
		);
	}

	/**
	 * Retrieve the current page's image information.
	 *
	 * Gets the featured image for posts/pages, or uses the default OG image.
	 *
	 * @param int $post_id Post ID to get image for. Use 0 for homepage.
	 * @return array Image information containing src, width, height, and alt.
	 */
	private static function get_image( int $post_id ): array {
		$src    = '';
		$width  = (int) self::$args['og_image_width'];
		$height = (int) self::$args['og_image_height'];
		$alt    = '';

		if ( $post_id > 0 && has_post_thumbnail( $post_id ) ) {
			$thumb = wp_get_attachment_image_src(
				(int) get_post_thumbnail_id( $post_id ),
				'full'
			);
			if ( $thumb ) {
				[ $src, $width, $height ] = $thumb;
				$alt = (string) get_post_meta(
					(int) get_post_thumbnail_id( $post_id ),
					'_wp_attachment_image_alt',
					true
				);
				if ( ! $alt ) {
					$alt = get_the_title( $post_id );
				}
			}
		}

		if ( ! $src ) {
			$src = (string) self::$args['default_og_image'];
		}

		return compact( 'src', 'width', 'height', 'alt' );
	}

	/**
	 * Generate image-related Open Graph meta tags.
	 *
	 * @param array $image Image information from get_image().
	 * @return array Open Graph image tags.
	 */
	private static function image_tags( array $image ): array {
		$tags = array(
			'og:image'        => $image['src'],
			'og:image:width'  => $image['width'],
			'og:image:height' => $image['height'],
		);
		if ( $image['alt'] ) {
			$tags['og:image:alt'] = $image['alt'];
		}
		return $tags;
	}
}
