<?php
/**
* Breadcrub trail on any type of page
*/
function breadcrumb_trail( $args = array() ) {
	$breadcrumb = new Lerm_Breadcrumb_Trail( $args );

	return $breadcrumb->breadcrumb_trail();
}


class Lerm_Breadcrumb_Trail {

	public $items = array();

	public function __construct( $args = array() ) {
		$defaults = array(
			'before'          => '',
			'after'           => '',
			'show_title'      => false,
			'labels'          => array(),
			'show_post_title' => false,
			'show_on_home'    => false,
		);

		// Parse the arguments with the deaults.
		$this->args = apply_filters( 'breadcrumb_trail_args', wp_parse_args( $args, $defaults ) );

		// Set the labels.
		$this->set_labels();
		// $this->set_post_taxonomy();

		// add some items to  to the trail!
		$this->add_item();
	}


	public function breadcrumb_trail() {
		$breadcrumb    = '';
		$item_count    = count( $this->items );
		$item_position = 0;

		$breadcrumb  = '<nav aria-label="breadcrumb">';
		$breadcrumb .= '<ol class="breadcrumb small mb-0 py-1 bg-inherit" itemscope itemtype="http://schema.org/BreadcrumbList">';
		$item_class  = '';

		foreach ( $this->items as $item ) {
			++$item_position;

			// Check if the item is linked.
			preg_match( '/(<a.*?>)(.*?)(<\/a>)/i', $item, $matches );

			// Wrap the item text with appropriate itemprop.
			$item       = ! empty( $matches ) ? sprintf( '%s<span itemprop="name">%s</span>%s', $matches[1], $matches[2], $matches[3] ) : sprintf( '<span itemprop="name">%s</span>', $item );
			$item_class = 'breadcrumb-item ';
			if ( 1 === $item_position ) {
				$item_class .= 'home';
			} elseif ( $item_count === $item_position ) {
				$item_class .= 'active';
			} else {
				$item_class .= 'middle';
			}

			$attribute   = 'class="' . $item_class . '" itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem"';
			$meta        = sprintf( '<meta itemprop="position" content="%s" />', absint( $item_position ) );
			$breadcrumb .= sprintf( '<li %1$s>%2$s%3$s</li>', $attribute, $item, $meta );
		}

		$breadcrumb .= '</ol>';
		$breadcrumb .= '</nav>';

		$breadcrumb = sprintf(
			'%1$s%2$s%3$s',
			$this->args['before'],
			$breadcrumb,
			$this->args['after']
		);

		//output breadcrumbs
		echo $breadcrumb; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function set_labels( $args = array() ) {
		$defaults = array(
			'home'     => esc_html__( 'Home', 'lerm' ),
			'paged'    => esc_html__( 'Page %s', 'lerm' ),
			'post'     => esc_html__( 'Article', 'lerm' ),
			'archives' => esc_html__( 'Archives', 'lerm' ),
			'blog'     => esc_html__( 'Blog', 'lerm' ),

		);
		$this->labels = apply_filters( 'breadcrumb_trail_labels', wp_parse_args( $this->args['labels'], $defaults ) );
	}
	/**
	 * Sets the `$post_taxonomy` property.  This is an array of post types (key) and taxonomies (value).
	 * The taxonomy's terms are shown on the singular post view if set.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @return void
	 */
	protected function set_post_taxonomy() {

		$defaults = array();

		// If post permalink is set to `%postname%`, use the `category` taxonomy.
		if ( '%postname%' === trim( get_option( 'permalink_structure' ), '/' ) ) {
			$defaults['post'] = 'category';
		}

		$this->post_taxonomy = apply_filters( 'breadcrumb_trail_post_taxonomy', wp_parse_args( $this->args['post_taxonomy'], $defaults ) );
	}

	public function add_item() {
		if ( is_front_page() ) {
			$this->add_front_page_item();
		} else {
			$this->add_site_home_link();
			// if view on homepage
			if ( is_home() ) {
				$this->add_blog_items();
			} elseif ( is_singular() ) {
				// singular item
				$this->add_singular_item();
			} elseif ( is_archive() ) {
				//archive item
				if ( is_post_type_archive() ) {
					$this->add_post_type_archive_item();
				} elseif ( is_category() || is_tag() || is_tax() ) {
					$this->add_archive_item();
				} elseif ( is_author() ) {
					$this->add_author_item();
				} elseif ( is_year() ) {
					$this->add_year_item();
				} elseif ( is_month() ) {
					$this->add_month_item();
				} elseif ( is_day() ) {
					$this->add_day_item();
				}
			} elseif ( is_search() ) {
				$this->add_search_item();
			} elseif ( is_404() ) {
				$this->add_404_item();
			}
		}
		//Add paged item
		$this->add_paged_items();

		$this->items = array_unique( apply_filters( 'breadcrumb_trail_items', $this->items, $this->args ) );
	}
	public function add_front_page_item() {
		

	}
	protected function add_site_home_link() {
		$label         = $this->labels['home'];
		$this->items[] = sprintf( '<a href="%s" rel="home" >%s</a>', home_url(), $label );
	}

	protected function add_blog_items() {

		// Get the post ID and post.
		$post_id = get_queried_object_id();
		$post    = get_post( $post_id );

		// If the post has parents, add them to the trail.
		if ( 0 < $post->post_parent ) {
			$this->add_post_parents( $post->post_parent );
		}

		// Get the page title.
		$title = get_the_title( $post_id );

		// Add the posts page item.
		if ( is_paged() ) {
			$this->items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $post_id ) ), $title );

		} elseif ( $title && true === $this->args['show_title'] ) {
			$this->items[] = $title;
		}
	}
	protected function add_singular_item() {

		$post    = get_queried_object();
		$post_id = get_queried_object_id();

		if ( 0 < $post->post_parent ) {
			$this->add_post_parents( $post->post_parent );
		}

		//add post category to post.
		if ( $cats  = get_the_category() ) {
			$this->items[] = get_category_parents( $cats[0], true, '' );
		}

		// End with the post title.
		if ( $post_title = single_post_title( '', false ) ) {

			if ( ( 1 < get_query_var( 'page' ) || is_paged() ) || ( get_option( 'page_comments' ) && 1 < absint( get_query_var( 'cpage' ) ) ) ) {
				$this->items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $post_id ) ), $post_title );

			} elseif ( true === $this->args['show_title'] || is_page() ) {
				$this->items[] = $post_title;
			}else{
				$this->items[] = $this->labels['post'];
			}
		}
	}

	protected function add_archive_item() {

		// Get some taxonomy and term variables.
		$term = get_queried_object();
		$tax  = get_taxonomy( $term->taxonomy );

		// If the taxonomy is hierarchical, list its parent terms.
		if ( is_taxonomy_hierarchical( $term->taxonomy ) && $term->parent ) {
			$this->add_term_parents( $term->parent, $term->taxonomy );
		}

		if ( is_paged() ) {
			$this->items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_term_link( $term, $term->taxonomy ) ), single_term_title( '', false ) );
		} else {
			$this->items[] = single_term_title( '', false );
		}
	}

	public function add_post_type_archive_item() {
		$this->items[] = post_type_archive_title( '', false );
	}

	public function add_author_item() {
		$user_id = get_query_var( 'author' );

		if ( is_paged() ) {
			$this->items[] = sprintf(
				'<a href="%s" rel="author">%s</a>',
				esc_url( get_author_posts_url( $user_id ) ),
				get_the_author_meta( 'display_name', $user_id )
			);
		} else {
			$this->items[] = get_the_author_meta( 'display_name', $user_id );
		}
	}

	public function add_year_item() {
		$year = get_the_date( _x( 'Y', 'yearly archives date format', 'lerm' ) );

		if ( is_paged() ) {
			$this->items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_year_link( get_the_time( 'Y' ) ) ), $year );
		} else {
			$this->items[] = $year;
		}
	}

	public function add_month_item() {
		$year  = get_the_date( _x( 'Y', 'yearly archives date format', 'lerm' ) );
		$month = get_the_date( _x( 'F', 'monthly archives date format', 'lerm' ) );

		$this->items[] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( get_year_link( get_the_time( 'Y' ) ) ),
			$year
		);
		if ( is_paged() ) {
			$this->items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_month_link( get_the_time( 'Y' ), get_the_time( 'm' ) ) ), $month );
		} else {
			$this->items[] = $month;
		}
	}

	public function add_day_item() {
		$year  = get_the_date( _x( 'Y', 'yearly archives date format', 'lerm' ) );
		$month = get_the_date( _x( 'F', 'monthly archives date format', 'lerm' ) );
		$day   = get_the_date( _x( 'j', 'daily archives date format', 'lerm' ) );

		$this->items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_year_link( get_the_time( 'Y' ) ) ), $year );

		$this->items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_month_link( get_the_time( 'Y' ), get_the_time( 'm' ) ) ), $month );

		if ( is_paged() ) {
			$this->items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_day_link( get_the_time( 'Y' ), get_the_time( 'm' ), get_the_time( 'd' ) ) ), $day );
		} else {
			$this->items[] = $day;
		}
	}

	public function add_search_item() {
	}

	public function add_404_item() {
	}
	public function add_paged_items() {

		// If viewing a paged singular post.
		if ( is_singular() && 1 < get_query_var( 'page' ) && true === $this->args['show_title'] ) {
			$this->items[] = sprintf( 'Page %s', number_format_i18n( absint( get_query_var( 'page' ) ) ) );
		}

		// If viewing a singular post with paged comments.
		elseif ( is_singular() && get_option( 'page_comments' ) && 1 < get_query_var( 'cpage' ) ) {
			$this->items[] = sprintf( 'Page %s', number_format_i18n( absint( get_query_var( 'cpage' ) ) ) );
		}

		// If viewing a paged archive-type page.
		elseif ( is_paged() && true === $this->args['show_title'] ) {
			$this->items[] = sprintf( 'Page %s', number_format_i18n( absint( get_query_var( 'paged' ) ) ) );
		}
	}
	/**
	 * Adds a specific post's hierarchy to the items array.  The hierarchy is determined by post type's
	 * rewrite arguments and whether it has an archive page.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @param  int    $post_id
	 * @return void
	 */
	protected function add_post_hierarchy( $post_id ) {

		// Get the post type.
		$post_type        = get_post_type( $post_id );
		$post_type_object = get_post_type_object( $post_type );

		// If this is the 'post' post type, get the rewrite front items and map the rewrite tags.
		if ( 'post' === $post_type ) {

			// Add $wp_rewrite->front to the trail.
			$this->add_rewrite_front_items();

			// Map the rewrite tags.
			$this->map_rewrite_tags( $post_id, get_option( 'permalink_structure' ) );
		}

		// If the post type has rewrite rules.
		elseif ( false !== $post_type_object->rewrite ) {

			// If 'with_front' is true, add $wp_rewrite->front to the trail.
			if ( $post_type_object->rewrite['with_front'] ) {
				$this->add_rewrite_front_items();
			}

			// If there's a path, check for parents.
			if ( ! empty( $post_type_object->rewrite['slug'] ) ) {
				$this->add_path_parents( $post_type_object->rewrite['slug'] );
			}
		}

		// If there's an archive page, add it to the trail.
		if ( $post_type_object->has_archive ) {

			// Add support for a non-standard label of 'archive_title' (special use case).
			$label = ! empty( $post_type_object->labels->archive_title ) ? $post_type_object->labels->archive_title : $post_type_object->labels->name;

			// Core filter hook.
			$label = apply_filters( 'post_type_archive_title', $label, $post_type_object->name );

			$this->items[] = sprintf( '<a href="%s">%s</a>', esc_url( get_post_type_archive_link( $post_type ) ), $label );
		}

		// Map the rewrite tags if there's a `%` in the slug.
		if ( 'post' !== $post_type && ! empty( $post_type_object->rewrite['slug'] ) && false !== strpos( $post_type_object->rewrite['slug'], '%' ) ) {
			$this->map_rewrite_tags( $post_id, $post_type_object->rewrite['slug'] );
		}
	}

	protected function add_post_parents( $post_id ) {
		$parents = array();

		while ( $post_id ) {
			$post = get_post( $post_id );

			// Add the formatted term link to the array of parent terms.
			$parents[] = sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $post_id ) ), get_the_title( $post_id ) );

			// Set the parent term's parent as the parent ID.
			$post_id = $post->parent;
		}
		// Get the post hierarchy based off the final parent post.
		$this->add_post_hierarchy( $post_id );
			// Display terms for specific post type taxonomy if requested.
		if ( ! empty( $this->post_taxonomy[ $post->post_type ] ) ) {
			$this->add_post_terms( $post_id, $this->post_taxonomy[ $post->post_type ] );
		}
		$this->items = array_merge( $this->items, array_reverse( $parents ) );
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
	protected function add_term_parents( $term_id, $taxonomy ) {

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
			$this->items = array_merge( $this->items, array_reverse( $parents ) );
		}
	}
}
