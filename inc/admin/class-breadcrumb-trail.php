<?php
/**
*
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
			'show_title'      => true,
			'labels'          => array(),
			'show_post_title' => false,
		);

		// Parse the arguments with the deaults.
		$this->args = apply_filters( 'breadcrumb_trail_args', wp_parse_args( $args, $defaults ) );

		// Set the labels and post taxonomy properties.
		$this->set_labels();
		// $this->set_post_taxonomy();

		// Let's find some items to add to the trail!
		$this->add_item();
	}


	public function breadcrumb_trail() {
		$breadcrumb    = '';
		$item_count    = count( $this->items );
		$item_position = 0;

		$breadcrumb  = '<nav aria-label="breadcrumb">';
		$breadcrumb .= '<ol class="breadcrumb mb-0" itemscope itemtype="http://schema.org/BreadcrumbList">';
		$item_class  = '';

		// Add the number of items and item list order schema.
		$breadcrumb .= sprintf( '<meta name="numberOfItems" content="%d" />', absint( $item_count ) );
		$breadcrumb .= '<meta name="itemListOrder" content="Ascending" />';

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
		echo $breadcrumb;
	}

	public function set_labels( $args = array() ) {
		$defaults = array(
			'home'  => __( 'Home', 'lerm' ),
			'paged' => __( 'Page %s', 'lerm' ),
			'post'  => __( 'Article', 'lerm' ),

		);
		$this->labels = apply_filters( 'breadcrumb_trail_labels', wp_parse_args( $this->args['labels'], $defaults ) );
	}

	public function add_item() {
		$this->add_site_home_link();

		//if view on homepage
		if ( is_home() ) {
			$this->add_home_item();
		}

		//post item
		elseif ( is_single() ) {
			$this->add_post_item();
		}

		//page item
		elseif ( is_page() ) {
			$this->add_page_item();
		}

		//archive item
		elseif ( is_archive() ) {
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

		//paged item
		$this->add_paged_items();
	}
	public function add_site_home_link() {
		$label         = $this->labels['home'];
		$this->items[] = sprintf( '<a href="%s" rel="home" >%s</a>', home_url(), $label );
	}
	public function add_home_item() {
	}

	public function add_post_item() {
		$cat  = get_the_category();
		$cat  = $cat[0];
		$cats = get_category_parents( $cat, true, '' );

		$this->items[] = $cats;
		if ( $post_title = single_post_title( '', false ) ) {
			if ( true === $this->args['show_post_title'] ) {
				$this->items[] = $post_title;
			} else {
				$this->items[] = $this->labels['post'];
			}
		}
	}

	public function add_page_item() {
		$page          = get_the_title();
		$this->items[] = $page;
	}

	public function add_archive_item() {
		$term = get_queried_object();
		$tax  = get_taxonomy( $term->taxonomy );

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
}
