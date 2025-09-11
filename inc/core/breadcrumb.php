<?php // phpcs:disable WordPress.Files.FileName
namespace Lerm\Inc\Core;

use Lerm\Inc\Traits\Singleton;

class Breadcrumb {

	use singleton;

	private static $items         = array();
	private static $args          = array();
	private static $labels        = array();
	private static $post_taxonomy = array();

	public function __construct( $params = array() ) {
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

		self::$args = apply_filters( 'lerm_breadcrumb_args', wp_parse_args( $params, $default_args ) );

		self::set_labels();
		self::set_post_taxonomy();
		self::add_items();
		self::trail();
	}

	public function __toString() {
		return self::trail();
	}

	protected static function set_labels() {
		$defaults     = array(
			'browse'         => esc_html__( 'Browse:', 'lerm' ),
			'aria_label'     => esc_attr_x( 'breadcrumb', 'breadcrumbs aria label', 'lerm' ),
			'home'           => esc_html__( 'Home', 'lerm' ),
			'post'           => esc_html__( 'Article', 'lerm' ),
			'error_404'      => esc_html__( '404 Not Found', 'lerm' ),
			'archives'       => esc_html__( 'Archives', 'lerm' ),
			// Translators: %s is the search query.
			'search'         => esc_html__( 'Search results for: %s', 'lerm' ),
			// Translators: %s is the page number.
			'paged'          => esc_html__( 'Page %s', 'lerm' ),
			// Translators: %s is the page number.
			'paged_comments' => esc_html__( 'Comment Page %s', 'lerm' ),
			// "%s" is replaced with the translated date/time format.
			'archive_day'    => '%s',
			'archive_month'  => '%s',
			'archive_year'   => '%s',
			'archive_author' => '%s',
		);
		self::$labels = apply_filters( 'lerm_breadcrumb_labels', wp_parse_args( self::$args['labels'], $defaults ) );
	}

	public static function trail() {
		$breadcrumb    = '';
		$item_count    = count( self::$items );
		$item_position = 0;
			// Open the unordered list.
			$breadcrumb .= sprintf(
				'<%s style="--bs-breadcrumb-divider: \'%s\';" class="breadcrumb small mb-0 py-1" itemscope itemtype="http://schema.org/BreadcrumbList">',
				tag_escape( self::$args['list_tag'] ),
				esc_attr( self::$args['separator'] )
			);

			// Add the number of items and item list order schema.
			$breadcrumb .= sprintf( '<meta name="numberOfItems" content="%d" />', absint( $item_count ) );
			$breadcrumb .= '<meta name="itemListOrder" content="Ascending" />';

			// Loop through the items and add them to the list.
		foreach ( self::$items as $item ) {
			// Iterate the item position.
			++$item_position;
			// $item = $this->wrap_item_with_schema($item);
			// Check if the item is linked.
			preg_match( '/(<a.*?>)(.*?)(<\/a>)/i', $item, $matches );

			// Wrap the item text with appropriate itemprop.
			$item = ! empty( $matches ) ? sprintf( '%s<span itemprop="name">%s</span>%s', $matches[1], $matches[2], $matches[3] ) : sprintf( '<span itemprop="name">%s</span>', $item );

			// Wrap the item with its itemprop.
			$item = ! empty( $matches ) ? preg_replace( '/(<a.*?)([\'"])>/i', '$1$2 itemprop=$2item$2>', $item ) : sprintf( '<span itemprop="item">%s</span>', $item );

			// Add list item classes.
			$item_class = 'breadcrumb-item' . ( $item_count === $item_position ? ' active' : '' );

			// Create list item attributes.
			$attributes = 'itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem" class="' . $item_class . '"';

			// Build the meta position HTML.
			$meta = sprintf( '<meta itemprop="position" content="%s" />', absint( $item_position ) );

			// Build the list item.
			$breadcrumb .= sprintf( '<%1$s %2$s>%3$s%4$s</%1$s>', tag_escape( self::$args['item_tag'] ), $attributes, $item, $meta );
		}

			// Close the unordered list.
			$breadcrumb .= sprintf( '</%s>', tag_escape( self::$args['list_tag'] ) );

			// Wrap the breadcrumb trail.
			$breadcrumb = sprintf(
				'<%1$s role="navigation" aria-label="%2$s" itemprop="breadcrumb">%3$s%4$s%5$s</%1$s>',
				tag_escape( self::$args['container'] ),
				esc_attr( self::$labels['aria_label'] ),
				esc_attr( self::$args['before'] ),
				$breadcrumb,
				esc_attr( self::$args['after'] )
			);

		// Allow developers to filter the breadcrumb trail HTML.
		$breadcrumb = apply_filters( 'lerm_breadcrumb', $breadcrumb, self::$args );

		if ( false === self::$args['echo'] ) {
			return $breadcrumb;
		}
		echo $breadcrumb; // phpcs:ignore WordPress.Security.EscapeOutput -- Reason: $breadcrumb is safe.
	}

	private static function set_post_taxonomy() {
		$defaults = array( 'post' => 'category' );

		self::$post_taxonomy = apply_filters( 'lerm_breadcrumb_post_taxonomy', wp_parse_args( self::$args['post_taxonomy'], $defaults ) );
	}

	private static function add_items() {
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

		self::$items = array_unique( apply_filters( 'lerm_breadcrumb_items', self::$items, self::$args ) );
	}

	private static function add_front_page_items() {
		if ( self::$args['show_on_front'] || is_paged() || ( is_singular() && get_query_var( 'page' ) > 1 ) ) {
			self::add_network_home_link();

			if ( is_paged() ) {
				self::add_site_home_link();
			} elseif ( self::$args['show_title'] ) {
				self::$items[] = is_multisite() && self::$args['network'] ? get_bloginfo( 'name' ) : self::$labels['home'];
			}
		}
	}

	protected static function add_network_home_link() {
		if ( is_multisite() && ! is_main_site() && self::$args['network'] ) {
			self::$items[] = sprintf( '<a href="%s" rel="home">%s</a>', esc_url( network_home_url() ), self::$labels['home'] );
		}
	}

	protected static function add_site_home_link() {
		$network = is_multisite() && ! is_main_site() && self::$args['network'];
		$label   = $network ? get_bloginfo( 'name' ) : self::$labels['home'];
		$rel     = $network ? '' : ' rel="home"';

		self::$items[] = sprintf( '<a href="%s"%s>%s</a>', esc_url( user_trailingslashit( home_url() ) ), $rel, $label );
	}

	/**
	 * Adds items for the posts page (i.e., is_home()) to the items array.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @return void
	 */
	protected static function add_blog_items() {
		$post  = get_queried_object();
		$title = get_the_title( $post->ID );

		if ( isset( $post->post_parent ) && $post->post_parent > 0 ) {
			self::add_page_parents( $post->post_parent );
		}

		if ( is_paged() ) {
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $post->ID ) ), $title );
		} else {
			self::$items[] = ( $title && self::$args['show_title'] ) ? $title : self::$labels['home'];
		}
	}

	protected static function add_singular_items() {
		$post = get_queried_object();

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

		$is_paged = ( get_query_var( 'page' ) > 1 || is_paged() ) || ( get_option( 'page_comments' ) && absint( get_query_var( 'cpage' ) > 1 ) );

		if ( $is_paged ) {
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $post->ID ) ), single_post_title( '', false ) );
		} else {
			self::$items[] = self::$args['show_title'] ? single_post_title( '', false ) : self::$labels['post'];
		}
	}

	protected static function add_page_parents( $parent_id ) {
		$parents = array();
		while ( $parent_id ) {
			// Get the post by ID.
			$parent = get_post( $parent_id );

			// If we hit a page that's set as the front page, bail.
			if ( 'page' === $parent->post_type && 'page' === get_option( 'show_on_front' ) && intval( get_option( 'page_on_front' ) ) === $parent_id ) {
				break;
			}

			// Add the formatted post link to the array of parents.
			$parents[] = sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $parent_id ) ), get_the_title( $parent_id ) );
			// If there's no longer a post parent, break out of the loop.
			if ( $parent->post_parent <= 0 ) {
				break;
			}
			// Change the post ID to the parent post to continue looping.
			$parent_id = $parent->post_parent;
		}

		// Get the post hierarchy based off the final parent post.
		self::add_post_hierarchy( $parent_id );

		// Merge the parent items into the items array.
		if ( ! empty( $parents ) ) {
			self::$items = array_merge( self::$items, array_reverse( $parents ) );
		}
	}

	protected static function add_post_hierarchy( $post_id ) {
		$post_type = get_post_type( $post_id );

		if ( 'post' === $post_type ) {
			self::add_post_terms( $post_id, self::$post_taxonomy[ $post_type ] );
		} else {
			$post_type_object = get_post_type_object( $post_type );

			if ( ! empty( $post_type_object->has_archive ) ) {
				self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_post_type_archive_link( $post_type ) ), post_type_archive_title( '', false ) );
			}
		}
	}

	protected static function add_post_terms( $post_id, $taxonomy ) {
		// Get the post categories.
		$terms = get_the_terms( $post_id, $taxonomy );

		// Check that categories were returned.
		if ( $terms || ! is_wp_error( $terms ) ) {
			// Sort the terms by ID in ascending order.
			$terms = wp_list_sort( $terms, 'term_id' );
			$term  = array_pop( $terms );

			// If the category has a parent, add the hierarchy to the trail.
			self::add_term_parents( $term, $taxonomy );
		}
	}

	protected static function add_term_parents( $term_id, $taxonomy ) {
		// Set up some default arrays.
		$parents = array();
		// While there is a parent ID, add the parent term link to the $parents array.
		while ( $term_id ) {
			// Get the parent term.
			$term = get_term( $term_id, $taxonomy );

			if ( ! $term || is_wp_error( $term ) ) {
				break;
			}

			// Add the formatted term link to the array of parent terms.
			$parents[] = sprintf( '<a href="%s">%s</a>', esc_url( get_term_link( $term, $taxonomy ) ), $term->name );

			if ( $term->parent <= 0 ) {
				break;
			}
			// Set the parent term's parent as the parent ID.
			$term_id = $term->parent;
		}
		// If we have parent terms, reverse the array to put them in the proper order for the trail.
		if ( ! empty( $parents ) ) {
			self::$items = array_merge( self::$items, array_reverse( $parents ) );
		}
	}

	private static function add_archive_items() {
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

	protected static function add_term_archive_items() {
		$term      = get_queried_object();
		$taxonomy  = get_taxonomy( $term->taxonomy );
		$post_type = $taxonomy->object_type[0] ?? '';

		// If there's a single post type for the taxonomy, use it.
		if ( $post_type && post_type_exists( $post_type ) ) {

			// If the post type is 'post'.
			if ( 'post' === $post_type ) {
				$post_id = get_option( 'page_for_posts' );
				if ( 'posts' !== get_option( 'show_on_front' ) && $post_id > 0 ) {
					self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $post_id ) ), get_the_title( $post_id ) );
				}
			} else {
				// If the post type is not 'post'.
				$post_type_object = get_post_type_object( $post_type );

				if ( ! empty( $post_type_object->has_archive ) ) {
					self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_post_type_archive_link( $post_type ) ), post_type_archive_title( '', false ) );
				}
			}
		}

		// If the taxonomy is hierarchical, list its parent terms.
		if ( is_taxonomy_hierarchical( $term->taxonomy ) && $term->parent ) {
			self::add_term_parents( $term->parent, $term->taxonomy );
		}

		// Add the term name to the trail end.
		if ( is_paged() ) {
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_term_link( $term, $term->taxonomy ) ), single_term_title( '', false ) );
		} elseif ( self::$args['show_title'] ) {
			self::$items[] = single_term_title( '', false );
		}
	}
	/**
	 * Adds the items to the trail items array for search results.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @return void
	 */
	protected static function add_search_items() {
		$search_query = sprintf( self::$labels['search'], get_search_query() );

		if ( is_paged() ) {
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_search_link() ), $search_query );
		} else {
			self::$items[] = $search_query;
		}
	}

	protected static function add_author_archive_items() {
		self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( get_option( 'page_for_posts' ) ) ), self::$labels['archives'] );
		self::$items[] = sprintf( self::$labels['archive_author'], get_the_author_meta( 'display_name', get_query_var( 'author' ) ) );
	}

	protected static function add_post_type_archive_items() {
		$post_type = get_query_var( 'post_type' );

		if ( 'post' !== $post_type ) {
			$post_type_object = get_post_type_object( $post_type );
			self::$items[]    = $post_type_object->labels->name;
		}

		// Get the post type object.
		$post_type_object = get_post_type_object( get_query_var( 'post_type' ) );

		// Add the post type [plural] name to the trail end.
		if ( is_paged() ) {
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_post_type_archive_link( $post_type_object->name ) ), post_type_archive_title( '', false ) );
		} else {
			self::$items[] = post_type_archive_title( '', false );
		}
	}

	/**
	 * Adds the items to the trail items array for date archives.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @param string $type Archive type ('day', 'month', 'year').
	 * @return void
	 */
	protected static function add_date_archive_items() {
		if ( is_day() ) {
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_year_link( get_the_time( 'Y' ) ) ), get_the_time( esc_html_x( 'Y', 'yearly archives date format', 'lerm' ) ) );
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_month_link( get_the_time( 'Y' ), get_the_time( 'm' ) ) ), get_the_time( esc_html_x( 'F', 'monthly archives date format', 'lerm' ) ) );
			self::$items[] = get_the_time( esc_html_x( 'j', 'daily archives date format', 'lerm' ) );
		} elseif ( is_month() ) {
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_year_link( get_the_time( 'Y' ) ) ), get_the_time( esc_html_x( 'Y', 'yearly archives date format', 'lerm' ) ) );
			self::$items[] = get_the_time( esc_html_x( 'F', 'monthly archives date format', 'lerm' ) );
		} elseif ( is_year() ) {
			self::$items[] = get_the_time( esc_html_x( 'Y', 'yearly archives date format', 'lerm' ) );
		}
	}

	protected static function add_paged_items() {
		// If viewing a paged singular post.
		if ( is_singular() ) {
			$page_number         = absint( get_query_var( 'page' ) );
			$comment_page_number = absint( get_query_var( 'cpage' ) );
			if ( $page_number ) {
				self::$items[] = sprintf( self::$labels['paged'], number_format_i18n( absint( $page_number ) ) );
			} elseif ( get_option( 'page_comments' ) && $comment_page_number ) {
				// If viewing a singular post with paged comments.
				self::$items[] = sprintf( self::$labels['paged_comments'], number_format_i18n( absint( $comment_page_number ) ) );
			}
		} elseif ( is_paged() ) {
			// If viewing a paged archive-type page.
			self::$items[] = sprintf( self::$labels['paged'], number_format_i18n( absint( get_query_var( 'paged' ) ) ) );
		}
	}
}
