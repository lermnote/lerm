<?php
/**
 * Breadcrumb Trail - A breadcrumb menu script for WordPress.
 *
 * Breadcrumb Trail is a script for showing a breadcrumb trail for any type of page.  It tries to
 * anticipate any type of structure and display the best possible trail that matches your site's
 * permalink structure.  While not perfect, it attempts to fill in the gaps left by many other
 * breadcrumb scripts.
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License as published by the Free Software Foundation; either version 2 of the License,
 * or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @package   BreadcrumbTrail
 * @version   1.1.0
 * @author    Justin Tadlock <justin@justintadlock.com>
 * @copyright Copyright (c) 2008 - 2017, Justin Tadlock
 * @link      https://themehybrid.com/plugins/breadcrumb-trail
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

/**
 * Creates a breadcrumbs menu for the site based on the current page that's being viewed by the user.
 *
 * @since  0.6.0
 * @access public
 */
namespace Lerm\Inc;

class Breadcrumb {
	/**
	 * Array of items belonging to the current breadcrumb trail.
	 *
	 * @since  0.1.0
	 * @access public
	 * @var    array
	 */
	public static $items = array();

	/**
	 * Sets up the breadcrumb trail properties.  Calls the `breadcrumb::add_items()` method
	 * to creat the array of breadcrumb items.
	 *
	 * @since  0.6.0
	 * @access public
	 * @var    array $args  {
	 *     @type string    $container      Container HTML element. nav|div
	 *     @type string    $before         String to output before breadcrumb menu.
	 *     @type string    $after          String to output after breadcrumb menu.
	 *     @type string    $list_tag       The HTML tag to use for the list wrapper.
	 *     @type string    $item_tag       The HTML tag to use for the item wrapper.
	 *     @type bool      $show_on_front  Whether to show when `is_front_page()`.
	 *     @type bool      $network        Whether to link to the network main site (multisite only).
	 *     @type bool      $show_title     Whether to show the title (last item) in the trail.
	 *     @type array     $labels         Text labels. @see breadcrumb::set_labels()
	 *     @type array     $post_taxonomy  Taxonomies to use for post types. @see breadcrumb::set_post_taxonomy()
	 *     @type bool      $echo           Whether to print or return the breadcrumbs.
	 * }
	 */
	public static $args = array(
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

	/**
	 * Array of text labels.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    array
	 */
	public static $labels = array();

	/**
	 * Array of post types (key) and taxonomies (value) to use for single post views.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    array
	 */
	public static $post_taxonomy = array();

	/**
	 * Magic method to use in case someone tries to output the layout object as a string.
	 * We'll just return the trail HTML.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return string
	 */
	public function __toString() {
		return self::trail();
	}

	public function __construct( $params = array() ) {
		// Parse the arguments with the deaults.
		self::$args = apply_filters( 'lerm_breadcrumb_args', wp_parse_args( $params, self::$args ) );
		// Set the labels and post taxonomy properties.
		self::set_labels();
		self::set_post_taxonomy();
		// Let's find some items to add to the trail!
		self::add_items();

		self::trail();
	}

	// instance
	public static function instance( $params = array() ) {
		return new self( $params );
	}

	/* ====== Public Methods ====== */

	/**
	 * Formats the HTML output for the breadcrumb trail.
	 *
	 * @since  0.6.0
	 * @access public
	 * @return string
	 */
	public static function trail() {
		// Set up variables that we'll need.
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
			// Check if the item is linked.
			preg_match( '/(<a.*?>)(.*?)(<\/a>)/i', $item, $matches );

			// Wrap the item text with appropriate itemprop.
			$item = ! empty( $matches ) ? sprintf( '%s<span itemprop="name">%s</span>%s', $matches[1], $matches[2], $matches[3] ) : sprintf( '<span itemprop="name">%s</span>', $item );

			// Wrap the item with its itemprop.
			$item = ! empty( $matches ) ? preg_replace( '/(<a.*?)([\'"])>/i', '$1$2 itemprop=$2item$2>', $item ) : sprintf( '<span itemprop="item">%s</span>', $item );

			// Add list item classes.
			$item_class = 'breadcrumb-item';

			if ( $item_count === $item_position ) {
				$item_class .= ' active';
			}

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

	/* ====== Protected Methods ====== */

	/**
	 * Sets the labels property.  Parses the inputted labels array with the defaults.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @return void
	 */
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
			// Translators: Weekly archive title. %s is the week date format.
			'archive_week'   => esc_html__( 'Week %s', 'lerm' ),
			// "%s" is replaced with the translated date/time format.
			'archive_day'    => '%s',
			'archive_month'  => '%s',
			'archive_year'   => '%s',
		);
		self::$labels = apply_filters( 'lerm_breadcrumb_labels', wp_parse_args( self::$args['labels'], $defaults ) );
	}

	/**
	 * Sets the `$post_taxonomy` property.  This is an array of post types (key) and taxonomies (value).
	 * The taxonomy's terms are shown on the singular post view if set.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @return void
	 */
	protected static function set_post_taxonomy() {
		$defaults = array();
		// If post permalink is set to `%postname%`, use the `category` taxonomy.
		if ( '%postname%' === trim( get_option( 'permalink_structure' ), '/' ) ) {
			$defaults['post'] = 'category';
		}
		self::$post_taxonomy = apply_filters( 'lerm_breadcrumb_post_taxonomy', wp_parse_args( self::$args['post_taxonomy'], $defaults ) );
	}

	/**
	 * Runs through the various WordPress conditional tags to check the current page being viewed.  Once
	 * a condition is met, a specific method is launched to add items to the `$items` array.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @return void
	 */
	protected static function add_items() {
		// If viewing the front page.
		if ( is_front_page() ) {
			self::add_front_page_items();
		} else {
			// Add the network and site home links.
			self::add_network_home_link();
			self::add_site_home_link();
			// If viewing the home/blog page.
			if ( is_home() ) {
				self::add_blog_items();
			} elseif ( is_singular() ) {
				// If viewing a single post.
				self::add_singular_items();
			} elseif ( is_archive() ) {
				// If viewing an archive page.
				if ( is_post_type_archive() ) {
					self::add_post_type_archive_items();
				} elseif ( is_category() || is_tag() || is_tax() ) {
					self::add_term_archive_items();
				} elseif ( is_author() ) {
					self::add_user_archive_items();
				} elseif ( is_day() ) {
					self::add_day_archive_items();
				} elseif ( get_query_var( 'w' ) ) {
					self::add_week_archive_items();
				} elseif ( is_month() ) {
					self::add_month_archive_items();
				} elseif ( is_year() ) {
					self::add_year_archive_items();
				} else {
					self::add_default_archive_items();
				}
			} elseif ( is_search() ) {
				// If viewing a search results page.
				self::add_search_items();
			} elseif ( is_404() ) {
				// If viewing the 404 page.
				self::add_404_items();
			}
		}
		// Add paged items if they exist.
		self::add_paged_items();

		// Allow developers to overwrite the items for the breadcrumb trail.
		self::$items = array_unique( apply_filters( 'breadcrumb_items', self::$items, self::$args ) );
	}

	/**
	 * Gets front items based on $wp_rewrite->front.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @return void
	 */
	protected static function add_rewrite_front_items() {
		global $wp_rewrite;
		if ( $wp_rewrite->front ) {
			self::add_path_parents( $wp_rewrite->front );
		}
	}

	/**
	 * Adds the page/paged number to the items array.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @return void
	 */
	protected static function add_paged_items() {
		// If viewing a paged singular post.
		if ( is_singular() && 1 < get_query_var( 'page' ) && true === self::$args['show_title'] ) {
			self::$items[] = sprintf( self::$labels['paged'], number_format_i18n( absint( get_query_var( 'page' ) ) ) );
		} elseif ( is_singular() && get_option( 'page_comments' ) && 1 < get_query_var( 'cpage' ) ) {
			// If viewing a singular post with paged comments.
			self::$items[] = sprintf( self::$labels['paged_comments'], number_format_i18n( absint( get_query_var( 'cpage' ) ) ) );
		} elseif ( is_paged() && true === self::$args['show_title'] ) {
			// If viewing a paged archive-type page.
			self::$items[] = sprintf( self::$labels['paged'], number_format_i18n( absint( get_query_var( 'paged' ) ) ) );
		}
	}

	/**
	 * Adds the network (all sites) home page link to the items array.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @return void
	 */
	protected static function add_network_home_link() {
		if ( is_multisite() && ! is_main_site() && true === self::$args['network'] ) {
			self::$items[] = sprintf( '<a href="%s" rel="home">%s</a>', esc_url( network_home_url() ), self::$labels['home'] );
		}
	}

	/**
	 * Adds the current site's home page link to the items array.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @return void
	 */
	protected static function add_site_home_link() {
		$network = is_multisite() && ! is_main_site() && true === self::$args['network'];
		$label   = $network ? get_bloginfo( 'name' ) : self::$labels['home'];
		$rel     = $network ? '' : ' rel="home"';

		self::$items[] = sprintf( '<a href="%s"%s>%s</a>', esc_url( user_trailingslashit( home_url() ) ), $rel, $label );
	}

	/**
	 * Adds items for the front page to the items array.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @return void
	 */
	protected static function add_front_page_items() {
		// Only show front items if the 'show_on_front' argument is set to 'true'.
		if ( true === self::$args['show_on_front'] || is_paged() || ( is_singular() && 1 < get_query_var( 'page' ) ) ) {
			// Add network home link.
			self::add_network_home_link();
			// If on a paged view, add the site home link.
			if ( is_paged() ) {
				self::add_site_home_link();
			} elseif ( true === self::$args['show_title'] ) {
				// If on the main front page, add the network home title.
				self::$items[] = is_multisite() && true === self::$args['network'] ? get_bloginfo( 'name' ) : self::$labels['home'];
			}
		}
	}

	/**
	 * Adds items for the posts page (i.e., is_home()) to the items array.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @return void
	 */
	protected static function add_blog_items() {
		// Get the post ID and post.
		$post_id = get_queried_object_id();
		$post    = get_post( $post_id );
		// If the post has parents, add them to the trail.
		if ( 0 < $post->post_parent ) {
			self::add_post_parents( $post->post_parent );
		}
		// Get the page title.
		$title = get_the_title( $post_id );
		// Add the posts page item.
		if ( is_paged() ) {
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $post_id ) ), $title );
		} elseif ( $title ) {
			self::$items[] = $title;
		}
	}

	/**
	 * Adds singular post items to the items array.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @return void
	 */
	protected static function add_singular_items() {
		// Get the queried post.
		$post    = get_queried_object();
		$post_id = $post->ID;

		$page_id = get_option( 'page_for_posts' );

		if ( 'posts' !== get_option( 'show_on_front' ) && 0 < $page_id ) {
			if ( 0 < get_post( $page_id )->post_parent ) {
				self::add_post_parents( $page_id );
			}
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $page_id ) ), get_the_title( $page_id ) );
		}
		// Get the post category.
		$cats = get_the_category( $post->ID );
		// Get the post title.
		$post_title = single_post_title( '', false );
		// If the post has a parent, follow the parent trail.
		if ( 0 < $post->post_parent ) {
			self::add_post_parents( $post->post_parent );
		} else {
			// If the post doesn't have a parent, get its hierarchy based off the post type.
			self::add_post_hierarchy( $post_id );
		}
		// Display terms for specific post type taxonomy if requested.
		if ( ! empty( self::$post_taxonomy[ $post->post_type ] ) ) {
			self::add_post_terms( $post_id, self::$post_taxonomy[ $post->post_type ] );
		}
		// End with the post title.
		if ( $post_title ) {
			if ( ( 1 < get_query_var( 'page' ) || is_paged() ) || ( get_option( 'page_comments' ) && 1 < absint( get_query_var( 'cpage' ) ) ) ) {
				self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $post_id ) ), $post_title );
			} elseif ( true === self::$args['show_title'] ) {
				self::$items[] = $post_title;
			} else {
				self::$items[] = self::$labels['post'];
			}
		}
	}

	/**
	 * Adds the items to the trail items array for taxonomy term archives.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @global object $wp_rewrite
	 * @return void
	 */
	protected static function add_term_archive_items() {
		global $wp_rewrite;
		// Get some taxonomy and term variables.
		$term           = get_queried_object();
		$taxonomy       = get_taxonomy( $term->taxonomy );
		$done_post_type = false;
		// If there are rewrite rules for the taxonomy.
		if ( false !== $taxonomy->rewrite ) {
			// If 'with_front' is true, dd $wp_rewrite->front to the trail.
			if ( $taxonomy->rewrite['with_front'] && $wp_rewrite->front ) {
				self::add_rewrite_front_items();
			}
			// Get parent pages by path if they exist.
			self::add_path_parents( $taxonomy->rewrite['slug'] );
			// Add post type archive if its 'has_archive' matches the taxonomy rewrite 'slug'.
			if ( $taxonomy->rewrite['slug'] ) {
				$slug = trim( $taxonomy->rewrite['slug'], '/' );
				// Deals with the situation if the slug has a '/' between multiple
				// strings. For example, "movies/genres" where "movies" is the post
				// type archive.
				$matches = explode( '/', $slug );
				// If matches are found for the path.
				if ( isset( $matches ) ) {
					// Reverse the array of matches to search for posts in the proper order.
					$matches = array_reverse( $matches );
					// Loop through each of the path matches.
					foreach ( $matches as $match ) {
						// If a match is found.
						$slug = $match;
						// Get public post types that match the rewrite slug.
						$post_types = self::get_post_types_by_slug( $match );
						if ( ! empty( $post_types ) ) {
							$post_type_object = $post_types[0];
							// Add support for a non-standard label of 'archive_title' (special use case).
							$label = ! empty( $post_type_object->labels->archive_title ) ? $post_type_object->labels->archive_title : $post_type_object->labels->name;
							// Core filter hook.
							$label = apply_filters( 'post_type_archive_title', $label, $post_type_object->name );
							// Add the post type archive link to the trail.
							self::$items[]  = sprintf( '<a href="%s">%s</a>', esc_url( get_post_type_archive_link( $post_type_object->name ) ), $label );
							$done_post_type = true;
							// Break out of the loop.
							break;
						}
					}
				}
			}
		}
		// If there's a single post type for the taxonomy, use it.
		if ( false === $done_post_type && 1 === count( $taxonomy->object_type ) && post_type_exists( $taxonomy->object_type[0] ) ) {
			// If the post type is 'post'.
			if ( 'post' === $taxonomy->object_type[0] ) {
				$post_id = get_option( 'page_for_posts' );
				if ( 'posts' !== get_option( 'show_on_front' ) && 0 < $post_id ) {
					self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $post_id ) ), get_the_title( $post_id ) );
				}
				// If the post type is not 'post'.
			} else {
				$post_type_object = get_post_type_object( $taxonomy->object_type[0] );
				$label            = ! empty( $post_type_object->labels->archive_title ) ? $post_type_object->labels->archive_title : $post_type_object->labels->name;
				// Core filter hook.
				$label = apply_filters( 'post_type_archive_title', $label, $post_type_object->name );

				self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_post_type_archive_link( $post_type_object->name ) ), $label );
			}
		}
		// If the taxonomy is hierarchical, list its parent terms.
		if ( is_taxonomy_hierarchical( $term->taxonomy ) && $term->parent ) {
			self::add_term_parents( $term->parent, $term->taxonomy );
		}
		// Add the term name to the trail end.
		if ( is_paged() ) {
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_term_link( $term, $term->taxonomy ) ), single_term_title( '', false ) );

		} elseif ( true === self::$args['show_title'] ) {
			self::$items[] = single_term_title( '', false );
		}
	}

	/**
	 * Adds the items to the trail items array for post type archives.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @return void
	 */
	protected static function add_post_type_archive_items() {
		// Get the post type object.
		$post_type_object = get_post_type_object( get_query_var( 'post_type' ) );

		if ( false !== $post_type_object->rewrite ) {
			// If 'with_front' is true, add $wp_rewrite->front to the trail.
			if ( $post_type_object->rewrite['with_front'] ) {
				self::add_rewrite_front_items();
			}
			// If there's a rewrite slug, check for parents.
			if ( ! empty( $post_type_object->rewrite['slug'] ) ) {
				self::add_path_parents( $post_type_object->rewrite['slug'] );
			}
		}
		// Add the post type [plural] name to the trail end.
		if ( is_paged() || is_author() ) {
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_post_type_archive_link( $post_type_object->name ) ), post_type_archive_title( '', false ) );
		} elseif ( true === self::$args['show_title'] ) {
			self::$items[] = post_type_archive_title( '', false );
		}
		// If viewing a post type archive by author.
		if ( is_author() ) {
			self::add_user_archive_items();
		}
	}

	/**
	 * Adds the items to the trail items array for user (author) archives.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @global object $wp_rewrite
	 * @return void
	 */
	protected static function add_user_archive_items() {
		global $wp_rewrite;
		// Add $wp_rewrite->front to the trail.
		self::add_rewrite_front_items();
		// Get the user ID.
		$user_id = get_query_var( 'author' );
		// If $author_base exists, check for parent pages.
		if ( ! empty( $wp_rewrite->author_base ) && ! is_post_type_archive() ) {
			self::add_path_parents( $wp_rewrite->author_base );
		}
		// Add the author's display name to the trail end.
		if ( is_paged() ) {
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_author_posts_url( $user_id ) ), get_the_author_meta( 'display_name', $user_id ) );

		} elseif ( true === self::$args['show_title'] ) {
			self::$items[] = get_the_author_meta( 'display_name', $user_id );
		}
	}

	/**
	 * Adds the items to the trail items array for day archives.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @return void
	 */
	protected static function add_day_archive_items() {
		// Add $wp_rewrite->front to the trail.
		self::add_rewrite_front_items();
		// Get year, month, and day.
		$year  = sprintf( self::$labels['archive_year'], get_the_time( esc_html_x( 'Y', 'yearly archives date format', 'lerm' ) ) );
		$month = sprintf( self::$labels['archive_month'], get_the_time( esc_html_x( 'F', 'monthly archives date format', 'lerm' ) ) );
		$day   = sprintf( self::$labels['archive_day'], get_the_time( esc_html_x( 'j', 'daily archives date format', 'lerm' ) ) );
		// Add the year and month items.
		self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_year_link( get_the_time( 'Y' ) ) ), $year );
		self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_month_link( get_the_time( 'Y' ), get_the_time( 'm' ) ) ), $month );
		// Add the day item.
		if ( is_paged() ) {
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_day_link( get_the_time( 'Y' ) ), get_the_time( 'm' ), get_the_time( 'd' ) ), $day );
		} elseif ( true === self::$args['show_title'] ) {
			self::$items[] = $day;
		}
	}

	/**
	 * Adds the items to the trail items array for week archives.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @return void
	 */
	protected static function add_week_archive_items() {
		// Add $wp_rewrite->front to the trail.
		self::add_rewrite_front_items();
		// Get the year and week.
		$year = sprintf( self::$labels['archive_year'], get_the_time( esc_html_x( 'Y', 'yearly archives date format', 'lerm' ) ) );
		$week = sprintf( self::$labels['archive_week'], get_the_time( esc_html_x( 'W', 'weekly archives date format', 'lerm' ) ) );
		// Add the year item.
		self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_year_link( get_the_time( 'Y' ) ) ), $year );
		// Add the week item.
		if ( is_paged() ) {
			self::$items[] = esc_url(
				get_archives_link(
					add_query_arg(
						array(
							'm' => get_the_time( 'Y' ),
							'w' => get_the_time( 'W' ),
						),
						home_url()
					),
					$week,
					false
				)
			);
		} elseif ( true === self::$args['show_title'] ) {
			self::$items[] = $week;
		}
	}

	/**
	 * Adds the items to the trail items array for month archives.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @return void
	 */
	protected static function add_month_archive_items() {
		// Add $wp_rewrite->front to the trail.
		self::add_rewrite_front_items();
		// Get the year and month.
		$year  = sprintf( self::$labels['archive_year'], get_the_time( esc_html_x( 'Y', 'yearly archives date format', 'lerm' ) ) );
		$month = sprintf( self::$labels['archive_month'], get_the_time( esc_html_x( 'F', 'monthly archives date format', 'lerm' ) ) );
		// Add the year item.
		self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_year_link( get_the_time( 'Y' ) ) ), $year );
		// Add the month item.
		if ( is_paged() ) {
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_month_link( get_the_time( 'Y' ), get_the_time( 'm' ) ) ), $month );
		} elseif ( true === self::$args['show_title'] ) {
			self::$items[] = $month;
		}
	}

	/**
	 * Adds the items to the trail items array for year archives.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @return void
	 */
	protected static function add_year_archive_items() {
		// Add $wp_rewrite->front to the trail.
		self::add_rewrite_front_items();
		// Get the year.
		$year = sprintf( self::$labels['archive_year'], get_the_time( esc_html_x( 'Y', 'yearly archives date format', 'lerm' ) ) );
		// Add the year item.
		if ( is_paged() ) {
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_year_link( get_the_time( 'Y' ) ) ), $year );
		} elseif ( true === self::$args['show_title'] ) {
			self::$items[] = $year;
		}
	}

	/**
	 * Adds the items to the trail items array for archives that don't have a more specific method
	 * defined in this class.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @return void
	 */
	protected static function add_default_archive_items() {
		// If this is a date-/time-based archive, add $wp_rewrite->front to the trail.
		if ( is_date() || is_time() ) {
			self::add_rewrite_front_items();
		}
		if ( true === self::$args['show_title'] ) {
			self::$items[] = self::$labels['archives'];
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
		if ( is_paged() ) {
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_search_link() ), sprintf( self::$labels['search'], get_search_query() ) );
		} elseif ( true === self::$args['show_title'] ) {
			self::$items[] = sprintf( self::$labels['search'], get_search_query() );
		}
	}

	/**
	 * Adds the items to the trail items array for 404 pages.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @return void
	 */
	protected static function add_404_items() {
		if ( true === self::$args['show_title'] ) {
			self::$items[] = self::$labels['error_404'];
		}
	}

	/**
	 * Adds a specific post's parents to the items array.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @param  int $post_id
	 * @return void
	 */
	protected static function add_post_parents( $post_id ) {
		$parents = array();
		while ( $post_id ) {
			// Get the post by ID.
			$post = get_post( $post_id );
			// If we hit a page that's set as the front page, bail.
			if ( 'page' === $post->post_type && 'page' === get_option( 'show_on_front' ) && get_option( 'page_on_front' ) === $post_id ) {
				break;
			}
			// Add the formatted post link to the array of parents.
			$parents[] = sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $post_id ) ), get_the_title( $post_id ) );
			// If there's no longer a post parent, break out of the loop.
			if ( 0 >= $post->post_parent ) {
				break;
			}
			// Change the post ID to the parent post to continue looping.
			$post_id = $post->post_parent;
		}
		// Get the post hierarchy based off the final parent post.
		self::add_post_hierarchy( $post_id );
		// Display terms for specific post type taxonomy if requested.
		if ( ! empty( self::$post_taxonomy[ $post->post_type ] ) ) {
			self::add_post_terms( $post_id, self::$post_taxonomy[ $post->post_type ] );
		}
		// Merge the parent items into the items array.
		self::$items = array_merge( self::$items, array_reverse( $parents ) );
	}

	/**
	 * Adds a specific post's hierarchy to the items array.  The hierarchy is determined by post type's
	 * rewrite arguments and whether it has an archive page.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @param  int $post_id
	 * @return void
	 */
	protected static function add_post_hierarchy( $post_id ) {
		// Get the post type.
		$post_type        = get_post_type( $post_id );
		$post_type_object = get_post_type_object( $post_type );
		// If this is the 'post' post type, get the rewrite front items and map the rewrite tags.
		if ( 'post' === $post_type ) {
			// Add $wp_rewrite->front to the trail.
			self::add_rewrite_front_items();
			// Map the rewrite tags.
			self::map_rewrite_tags( $post_id, get_option( 'permalink_structure' ) );
		} elseif ( false !== $post_type_object->rewrite ) {
			// If the post type has rewrite rules.
			// If 'with_front' is true, add $wp_rewrite->front to the trail.
			if ( $post_type_object->rewrite['with_front'] ) {
				self::add_rewrite_front_items();
			}
			// If there's a path, check for parents.
			if ( ! empty( $post_type_object->rewrite['slug'] ) ) {
				self::add_path_parents( $post_type_object->rewrite['slug'] );
			}
		}
		// If there's an archive page, add it to the trail.
		if ( $post_type_object->has_archive ) {
			// Add support for a non-standard label of 'archive_title' (special use case).
			$label = ! empty( $post_type_object->labels->archive_title ) ? $post_type_object->labels->archive_title : $post_type_object->labels->name;
			// Core filter hook.
			$label = apply_filters( 'post_type_archive_title', $label, $post_type_object->name );

			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_post_type_archive_link( $post_type ) ), $label );
		}
		// Map the rewrite tags if there's a `%` in the slug.
		if ( 'post' !== $post_type && ! empty( $post_type_object->rewrite['slug'] ) && false !== strpos( $post_type_object->rewrite['slug'], '%' ) ) {
			self::map_rewrite_tags( $post_id, $post_type_object->rewrite['slug'] );
		}
	}

	/**
	 * Gets post types by slug.  This is needed because the get_post_types() function doesn't exactly
	 * match the 'has_archive' argument when it's set as a string instead of a boolean.
	 *
	 * @since  0.6.0
	 * @access protected
	 * @param  int $slug  The post type archive slug to search for.
	 * @return void
	 */
	protected static function get_post_types_by_slug( $slug ) {
		$return = array();

		$post_types = get_post_types( array(), 'objects' );

		foreach ( $post_types as $type ) {

			if ( $slug === $type->has_archive || ( true === $type->has_archive && $slug === $type->rewrite['slug'] ) ) {
				$return[] = $type;
			}
		}
		return $return;
	}

	/**
	 * Adds a post's terms from a specific taxonomy to the items array.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @param  int    $post_id  The ID of the post to get the terms for.
	 * @param  string $taxonomy The taxonomy to get the terms from.
	 * @return void
	 */
	protected static function add_post_terms( $post_id, $taxonomy ) {
		// Get the post type.
		$post_type = get_post_type( $post_id );
		// Get the post categories.
		$terms = get_the_terms( $post_id, $taxonomy );
		// Check that categories were returned.
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( 0 < $term->parent ) {
					self::add_post_terms( $term, $taxonomy );
				}
			}
			// Sort the terms by ID and get the first category.
			if ( function_exists( 'wp_list_sort' ) ) {
				$terms = wp_list_sort( $terms, 'term_id' );
			} else {
				usort( $terms, '_usort_terms_by_ID' );
			}
			$term = get_term( end( $terms ), $taxonomy );

			// If the category has a parent, add the hierarchy to the trail.
			if ( 0 < $term->parent ) {
				self::add_term_parents( $term->parent, $taxonomy );
			}
			// Add the category archive link to the trail.
			self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_term_link( $term, $taxonomy ) ), $term->name );
		}
	}

	/**
	 * Get parent posts by path.  Currently, this method only supports getting parents of the 'page'
	 * post type.  The goal of this function is to create a clear path back to home given what would
	 * normally be a "ghost" directory.  If any page matches the given path, it'll be added.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @param  string $path The path (slug) to search for posts by.
	 * @return void
	 */
	public static function add_path_parents( $path ) {
		// Trim '/' off $path in case we just got a simple '/' instead of a real path.
		$path = trim( $path, '/' );
		// If there's no path, return.
		if ( empty( $path ) ) {
			return;
		}
		// Get parent post by the path.
		$post = get_page_by_path( $path );

		if ( ! empty( $post ) ) {
			self::add_post_parents( $post->ID );
		} elseif ( is_null( $post ) ) {
			// Separate post names into separate paths by '/'.
			$path = trim( $path, '/' );
			preg_match_all( '/\/.*?\z/', $path, $matches );
			// If matches are found for the path.
			if ( isset( $matches ) ) {
				// Reverse the array of matches to search for posts in the proper order.
				$matches = array_reverse( $matches );
				// Loop through each of the path matches.
				foreach ( $matches as $match ) {
					// If a match is found.
					if ( isset( $match[0] ) ) {
						// Get the parent post by the given path.
						$path = str_replace( $match[0], '', $path );
						$post = get_page_by_path( trim( $path, '/' ) );

						// If a parent post is found, set the $post_id and break out of the loop.
						if ( ! empty( $post ) && 0 < $post->ID ) {
							self::add_post_parents( $post->ID );
							break;
						}
					}
				}
			}
		}
	}

	/**
	 * Searches for term parents of hierarchical taxonomies.  This function is similar to the WordPress
	 * function get_category_parents() but handles any type of taxonomy.
	 *
	 * @since  1.0.0
	 * @param  int    $term_id  ID of the term to get the parents of.
	 * @param  string $taxonomy Name of the taxonomy for the given term.
	 * @return void
	 */
	public static function add_term_parents( $term_id, $taxonomy ) {
		// Set up some default arrays.
		$parents = array();
		// While there is a parent ID, add the parent term link to the $parents array.
		while ( $term_id ) {
			// Get the parent term.
			$term = get_term( $term_id, $taxonomy );
			// Add the formatted term link to the array of parent terms.
			$parents[] = sprintf( '<a href="%s">%s</a>', esc_url( get_term_link( $term, $taxonomy ) ), $term->name );
			// Set the parent term's parent as the parent ID.
			$term_id = $term->parent;
		}
		// If we have parent terms, reverse the array to put them in the proper order for the trail.
		if ( ! empty( $parents ) ) {
			self::$items = array_merge( self::$items, array_reverse( $parents ) );
		}
	}

	/**
	 * Turns %tag% from permalink structures into usable links for the breadcrumb trail.  This feels kind of
	 * hackish for now because we're checking for specific %tag% examples and only doing it for the 'post'
	 * post type.  In the future, maybe it'll handle a wider variety of possibilities, especially for custom post
	 * types.
	 *
	 * @since  0.6.0
	 * @access protected
	 * @param  int    $post_id ID of the post whose parents we want.
	 * @param  string $path    Path of a potential parent page.
	 * @param  array  $args    Mixed arguments for the menu.
	 * @return array
	 */
	protected static function map_rewrite_tags( $post_id, $path ) {
		$post = get_post( $post_id );
		// Trim '/' from both sides of the $path.
		$path = trim( $path, '/' );
		// Split the $path into an array of strings.
		$matches = explode( '/', $path );
		// If matches are found for the path.
		if ( is_array( $matches ) ) {
			// Loop through each of the matches, adding each to the $trail array.
			foreach ( $matches as $match ) {
				// Trim any '/' from the $match.
				$tag = trim( $match, '/' );
				// If using the %year% tag, add a link to the yearly archive.
				if ( '%year%' === $tag ) {
					self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_year_link( get_the_time( 'Y', $post_id ) ) ), sprintf( self::$labels['archive_year'], get_the_time( esc_html_x( 'Y', 'yearly archives date format', 'lerm' ) ) ) );
				} elseif ( '%monthnum%' === $tag ) {
					// If using the %monthnum% tag, add a link to the monthly archive.
					self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_month_link( get_the_time( 'Y', $post_id ), get_the_time( 'm', $post_id ) ) ), sprintf( self::$labels['archive_month'], get_the_time( esc_html_x( 'F', 'monthly archives date format', 'lerm' ) ) ) );
				} elseif ( '%day%' === $tag ) {
					// If using the %day% tag, add a link to the daily archive.
					self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_day_link( get_the_time( 'Y', $post_id ), get_the_time( 'm', $post_id ), get_the_time( 'd', $post_id ) ) ), sprintf( self::$labels['archive_day'], get_the_time( esc_html_x( 'j', 'daily archives date format', 'lerm' ) ) ) );
				} elseif ( '%author%' === $tag ) {
					// If using the %author% tag, add a link to the post author archive.
					self::$items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_author_posts_url( $post->post_author ) ), get_the_author_meta( 'display_name', $post->post_author ) );
				} elseif ( taxonomy_exists( trim( $tag, '%' ) ) ) {
					// If using the %category% tag, add a link to the first category archive to match permalinks.
					// Force override terms in this post type.
					self::$post_taxonomy[ $post->post_type ] = false;
					// Add the post categories.
					self::add_post_terms( $post_id, trim( $tag, '%' ) );
				}
			}
		}
	}
}
