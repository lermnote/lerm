<?php // phpcs:disable WordPress.Files.FileName
/**
 * Breadcrumb navigation.
 *
 * @package Lerm
 * @since 1.0.0
 */

declare( strict_types = 1 );

namespace Lerm\View;

use Lerm\Traits\Singleton;

class Breadcrumb {
	use Singleton;

	private static array $items         = array();
	private static array $args          = array();
	private static array $labels        = array();
	private static array $post_taxonomy = array();

	/**
	 * Constructor.
	 *
	 * @param array $params Optional parameters.
	 */
	public function __construct( array $params = array() ) {
		$default_args = array(
			'container'     => 'nav',
			'before'        => '',
			'after'         => '',
			'list_tag'      => 'ol',
			'item_tag'      => 'li',
			'separator'     => '/',
			'show_on_front' => false,
			'network'       => false,
			'show_title'    => true,
			'labels'        => array(),
			'post_taxonomy' => array(),
			'echo'          => true,
		);

		self::$args = (array) apply_filters( 'lerm_breadcrumb_args', wp_parse_args( $params, $default_args ) );

		self::set_labels();
		self::set_post_taxonomy();
		self::add_items();
		self::trail();
	}

	public function __toString(): string {
		return (string) self::trail();
	}

	/**
	 * Set default labels.
	 */
	protected static function set_labels(): void {
		$defaults     = array(
			'browse'         => esc_html__( 'Browse:', 'lerm' ),
			'aria_label'     => esc_attr_x( 'breadcrumb', 'breadcrumbs aria label', 'lerm' ),
			'home'           => esc_html__( 'Home', 'lerm' ),
			'post'           => esc_html__( 'Article', 'lerm' ),
			'error_404'      => esc_html__( '404 Not Found', 'lerm' ),
			'archives'       => esc_html__( 'Archives', 'lerm' ),
			/* translators: %s is the search query */
			'search'         => esc_html__( 'Search results for: %s', 'lerm' ),
			/* translators: %s is the page number */
			'paged'          => esc_html__( 'Page %s', 'lerm' ),
			/* translators: %s is the comment page number */
			'paged_comments' => esc_html__( 'Comment Page %s', 'lerm' ),
			'archive_day'    => '%s',
			'archive_month'  => '%s',
			'archive_year'   => '%s',
			'archive_author' => '%s',
		);
		self::$labels = (array) apply_filters( 'lerm_breadcrumb_labels', wp_parse_args( self::$args['labels'], $defaults ) );
	}

	/**
	 * Generate breadcrumb HTML.
	 *
	 * @return string Breadcrumb HTML.
	 */
	public static function trail(): string {
		$breadcrumb    = '';
		$item_count    = count( self::$items );
		$item_position = 0;

		// Open the list.
		$breadcrumb .= sprintf(
			'<%s style="--bs-breadcrumb-divider: \'%s\';" class="breadcrumb small mb-0 py-1" itemscope itemtype="http://schema.org/BreadcrumbList">',
			tag_escape( self::$args['list_tag'] ),
			esc_attr( self::$args['separator'] )
		);

		// Add schema metadata.
		$breadcrumb .= sprintf( '<meta name="numberOfItems" content="%d" />', absint( $item_count ) );
		$breadcrumb .= '<meta name="itemListOrder" content="Ascending" />';

		// Loop through items.
		foreach ( self::$items as $item ) {
			++$item_position;
			preg_match( '/(<a.*?>)(.*?)(<\/a>)/i', $item, $matches );

			// Wrap item text with schema.
			$item = ! empty( $matches ) ? sprintf( '%s<span itemprop="name">%s</span>%s', $matches[1], $matches[2], $matches[3] ) : sprintf( '<span itemprop="name">%s</span>', $item );
			$item = ! empty( $matches ) ? preg_replace( '/(<a.*?)([\'"])>/i', '$1$2 itemprop=$2item$2>', $item ) : sprintf( '<span itemprop="item">%s</span>', $item );

			// Add item classes and attributes.
			$item_class = 'breadcrumb-item' . ( $item_count === $item_position ? ' active' : '' );
			$attributes = 'itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem" class="' . esc_attr( $item_class ) . '"';
			$meta       = sprintf( '<meta itemprop="position" content="%s" />', absint( $item_position ) );

			// Build list item.
			$breadcrumb .= sprintf( '<%1$s %2$s>%3$s%4$s</%1$s>', tag_escape( self::$args['item_tag'] ), $attributes, $item, $meta );
		}

		// Close the list.
		$breadcrumb .= sprintf( '</%s>', tag_escape( self::$args['list_tag'] ) );

		// Wrap breadcrumb.
		$breadcrumb = sprintf(
			'<%1$s role="navigation" aria-label="%2$s" itemprop="breadcrumb">%3$s%4$s%5$s</%1$s>',
			tag_escape( self::$args['container'] ),
			esc_attr( self::$labels['aria_label'] ),
			self::$args['before'],
			$breadcrumb,
			self::$args['after']
		);

		$breadcrumb = (string) apply_filters( 'lerm_breadcrumb', $breadcrumb, self::$args );

		if ( false === self::$args['echo'] ) {
			return $breadcrumb;
		}

		echo $breadcrumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Set post taxonomy mapping.
	 */
	private static function set_post_taxonomy(): void {
		$defaults            = array( 'post' => 'category' );
		self::$post_taxonomy = (array) apply_filters( 'lerm_breadcrumb_post_taxonomy', wp_parse_args( self::$args['post_taxonomy'], $defaults ) );
	}

	/**
	 * Add breadcrumb items based on current page.
	 */
	private static function add_items(): void {
		if ( is_front_page() ) {
			self::add_front_page_items();
		} else {
			self::add_network_home_link();
			self::add_site_home_link();

			if ( is_home() ) {
				self::add_blog_items();
			} elseif ( is_singular() ) {
				self::add_singular_items();
			} elseif ( is_archive() ) {
				self::add_archive_items();
			} elseif ( is_search() ) {
				self::add_search_items();
			} elseif ( is_404() ) {
				self::$items[] = self::$labels['error_404'];
			}
		}

		self::add_paged_items();

		self::$items = array_unique( (array) apply_filters( 'lerm_breadcrumb_items', self::$items, self::$args ) );
	}

	/**
	 * Add front page items.
	 */
	private static function add_front_page_items(): void {
		if ( self::$args['show_on_front'] || is_paged() || ( is_singular() && get_query_var( 'page' ) > 1 ) ) {
			self::add_network_home_link();

			if ( is_paged() ) {
				self::add_site_home_link();
			} elseif ( self::$args['show_title'] ) {
				self::$items[] = is_multisite() && self::$args['network'] ? get_bloginfo( 'name' ) : self::$labels['home'];
			}
		}
	}

	/**
	 * Add network home link.
	 */
	protected static function add_network_home_link(): void {
		if ( is_multisite() && ! is_main_site() && self::$args['network'] ) {
			self::$items[] = sprintf( '<a href="%s" rel="home">%s</a>', esc_url( network_home_url() ), esc_html( self::$labels['home'] ) );
		}
	}

	/**
	 * Add site home link.
	 */
	protected static function add_site_home_link(): void {
		$network = is_multisite() && ! is_main_site() && self::$args['network'];
		$label   = $network ? get_bloginfo( 'name' ) : self::$labels['home'];
		$rel     = $network ? '' : ' rel="home"';

		self::$items[] = sprintf( '<a href="%s"%s>%s</a>', esc_url( user_trailingslashit( home_url() ) ), $rel, esc_html( $label ) );
	}

	/**
	 * Add items for the posts page.
	 */
	protected static function add_blog_items(): void {
		$post = get_queried_object();

		if ( ! $post ) {
			return;
		}

		$title = get_the_title( $post->ID );

		if ( isset( $post->post_parent ) && $post->post_parent > 0 ) {
			self::add_page_parents( $post->post_parent );
		}

		if ( is_paged() ) {
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $post->ID ) ), esc_html( $title ) );
		} else {
			self::$items[] = ( $title && self::$args['show_title'] ) ? $title : self::$labels['home'];
		}
	}

	/**
	 * Add singular post items.
	 */
	protected static function add_singular_items(): void {
		$post = get_queried_object();

		if ( ! $post ) {
			return;
		}

		// If the post has a parent, follow the parent trail.
		if ( $post->post_parent > 0 ) {
			self::add_page_parents( $post->post_parent );
		} else {
			// If the post doesn't have a parent, get its hierarchy based off the post type.
			self::add_post_hierarchy( $post->ID );
		}

		// Display terms for specific post type taxonomy if requested.
		if ( ! empty( self::$post_taxonomy[ $post->post_type ] ) ) {
			self::add_post_terms( $post->ID, self::$post_taxonomy[ $post->post_type ] );
		}

		$is_paged = ( get_query_var( 'page' ) > 1 || is_paged() ) || ( get_option( 'page_comments' ) && absint( get_query_var( 'cpage' ) ) > 1 );

		if ( $is_paged ) {
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $post->ID ) ), esc_html( single_post_title( '', false ) ) );
		} else {
			self::$items[] = self::$args['show_title'] ? single_post_title( '', false ) : self::$labels['post'];
		}
	}

	/**
	 * Add page parent items.
	 *
	 * @param int $parent_id Parent page ID.
	 */
	protected static function add_page_parents( int $parent_id ): void {
		$parents = array();
		while ( $parent_id ) {
			$parent = get_post( $parent_id );

			if ( ! $parent ) {
				break;
			}

			// If we hit a page that's set as the front page, bail.
			if ( 'page' === $parent->post_type && 'page' === get_option( 'show_on_front' ) && (int) get_option( 'page_on_front' ) === $parent_id ) {
				break;
			}

			$parents[] = sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $parent_id ) ), get_the_title( $parent_id ) );

			if ( $parent->post_parent <= 0 ) {
				break;
			}

			$parent_id = $parent->post_parent;
		}

		self::add_post_hierarchy( $parent_id );

		if ( ! empty( $parents ) ) {
			self::$items = array_merge( self::$items, array_reverse( $parents ) );
		}
	}

	/**
	 * Add post hierarchy items.
	 *
	 * @param int $post_id Post ID.
	 */
	protected static function add_post_hierarchy( int $post_id ): void {
		$post_type = get_post_type( $post_id );

		if ( 'post' === $post_type ) {
			self::add_post_terms( $post_id, self::$post_taxonomy[ $post_type ] );
		} else {
			$post_type_object = get_post_type_object( $post_type );

			if ( $post_type_object && ! empty( $post_type_object->has_archive ) ) {
				self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_post_type_archive_link( $post_type ) ), esc_html( post_type_archive_title( '', false ) ) );
			}
		}
	}

	/**
	 * Add post terms to breadcrumb.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Taxonomy name.
	 */
	protected static function add_post_terms( int $post_id, string $taxonomy ): void {
		$terms = get_the_terms( $post_id, $taxonomy );

		if ( $terms && ! is_wp_error( $terms ) ) {
			$terms = wp_list_sort( $terms, 'term_id' );
			$term  = array_pop( $terms );

			if ( $term ) {
				self::add_term_parents( $term->term_id, $taxonomy );
			}
		}
	}

	/**
	 * Add term parent items.
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy name.
	 */
	protected static function add_term_parents( int $term_id, string $taxonomy ): void {
		$parents = array();

		while ( $term_id ) {
			$term = get_term( $term_id, $taxonomy );

			if ( ! $term || is_wp_error( $term ) ) {
				break;
			}

			$parents[] = sprintf( '<a href="%s">%s</a>', esc_url( get_term_link( $term, $taxonomy ) ), esc_html( $term->name ) );

			if ( $term->parent <= 0 ) {
				break;
			}

			$term_id = $term->parent;
		}

		if ( ! empty( $parents ) ) {
			self::$items = array_merge( self::$items, array_reverse( $parents ) );
		}
	}

	/**
	 * Add archive items.
	 */
	private static function add_archive_items(): void {
		if ( is_date() ) {
			self::add_date_archive_items();
		} elseif ( is_author() ) {
			self::add_author_archive_items();
		} elseif ( is_post_type_archive() ) {
			self::add_post_type_archive_items();
		} elseif ( is_category() || is_tag() || is_tax() ) {
			self::add_term_archive_items();
		} else {
			self::$items[] = self::$labels['archives'];
		}
	}

	/**
	 * Add term archive items.
	 */
	protected static function add_term_archive_items(): void {
		$term = get_queried_object();

		if ( ! $term ) {
			return;
		}

		$taxonomy  = get_taxonomy( $term->taxonomy );
		$post_type = $taxonomy->object_type[0] ?? '';

		// If there's a single post type for the taxonomy, use it.
		if ( $post_type && post_type_exists( $post_type ) ) {
			if ( 'post' === $post_type ) {
				$post_id = (int) get_option( 'page_for_posts' );
				if ( 'posts' !== get_option( 'show_on_front' ) && $post_id > 0 ) {
					self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $post_id ) ), get_the_title( $post_id ) );
				}
			} else {
				$post_type_object = get_post_type_object( $post_type );

				if ( $post_type_object && ! empty( $post_type_object->has_archive ) ) {
					self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_post_type_archive_link( $post_type ) ), esc_html( post_type_archive_title( '', false ) ) );
				}
			}
		}

		// If the taxonomy is hierarchical, list its parent terms.
		if ( is_taxonomy_hierarchical( $term->taxonomy ) && $term->parent ) {
			self::add_term_parents( $term->parent, $term->taxonomy );
		}

		// Add the term name to the trail end.
		if ( is_paged() ) {
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_term_link( $term, $term->taxonomy ) ), esc_html( single_term_title( '', false ) ) );
		} elseif ( self::$args['show_title'] ) {
			self::$items[] = single_term_title( '', false );
		}
	}
	/**
	 * Add search items.
	 */
	protected static function add_search_items(): void {
		$search_query = sprintf( self::$labels['search'], get_search_query() );

		if ( is_paged() ) {
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_search_link() ), esc_html( $search_query ) );
		} else {
			self::$items[] = $search_query;
		}
	}

	/**
	 * Add author archive items.
	 */
	protected static function add_author_archive_items(): void {
		$page_for_posts = (int) get_option( 'page_for_posts' );
		if ( $page_for_posts > 0 ) {
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $page_for_posts ) ), esc_html( self::$labels['archives'] ) );
		}
		self::$items[] = sprintf( self::$labels['archive_author'], esc_html( get_the_author_meta( 'display_name', (int) get_query_var( 'author' ) ) ) );
	}

	/**
	 * Add post type archive items.
	 */
	protected static function add_post_type_archive_items(): void {
		$post_type = get_query_var( 'post_type' );

		if ( ! $post_type ) {
			return;
		}

		$post_type_object = get_post_type_object( $post_type );

		if ( ! $post_type_object ) {
			return;
		}

		if ( 'post' !== $post_type ) {
			self::$items[] = $post_type_object->labels->name;
		}

		if ( is_paged() ) {
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_post_type_archive_link( $post_type_object->name ) ), esc_html( post_type_archive_title( '', false ) ) );
		} else {
			self::$items[] = post_type_archive_title( '', false );
		}
	}

	/**
	 * Add date archive items.
	 */
	protected static function add_date_archive_items(): void {
		if ( is_day() ) {
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_year_link( get_the_time( 'Y' ) ) ), esc_html( get_the_time( esc_html_x( 'Y', 'yearly archives date format', 'lerm' ) ) ) );
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_month_link( get_the_time( 'Y' ), get_the_time( 'm' ) ) ), esc_html( get_the_time( esc_html_x( 'F', 'monthly archives date format', 'lerm' ) ) ) );
			self::$items[] = esc_html( get_the_time( esc_html_x( 'j', 'daily archives date format', 'lerm' ) ) );
		} elseif ( is_month() ) {
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_year_link( get_the_time( 'Y' ) ) ), esc_html( get_the_time( esc_html_x( 'Y', 'yearly archives date format', 'lerm' ) ) ) );
			self::$items[] = esc_html( get_the_time( esc_html_x( 'F', 'monthly archives date format', 'lerm' ) ) );
		} elseif ( is_year() ) {
			self::$items[] = esc_html( get_the_time( esc_html_x( 'Y', 'yearly archives date format', 'lerm' ) ) );
		}
	}

	/**
	 * Add paged items.
	 */
	protected static function add_paged_items(): void {
		if ( is_singular() ) {
			$page_number         = absint( get_query_var( 'page' ) );
			$comment_page_number = absint( get_query_var( 'cpage' ) );

			if ( $page_number ) {
				self::$items[] = sprintf( self::$labels['paged'], number_format_i18n( $page_number ) );
			} elseif ( get_option( 'page_comments' ) && $comment_page_number ) {
				self::$items[] = sprintf( self::$labels['paged_comments'], number_format_i18n( $comment_page_number ) );
			}
		} elseif ( is_paged() ) {
			self::$items[] = sprintf( self::$labels['paged'], number_format_i18n( absint( get_query_var( 'paged' ) ) ) );
		}
	}
}
