<?php if ( ! defined( 'ABSPATH' ) ) {
	die; }

if ( ! function_exists( 'lerm_entry_meta' ) ) :
	/**
	 * Prints entry-date, entry_taxonomies, entry-format for current post.
	 *
	 * Create your own lerm_entry_taxonomies() function to override in a child theme.
	 *
	 * @since Lerm 2.0
	 */
	function lerm_entry_meta( $arg ) {
		$meta = array(
			'modified_date' => lerm_entry_date( 'modified' ),
			'publish_date'  => lerm_entry_date( 'publish' ),
			'category'      => lerm_entry_taxonomies(),
			'read'          => lerm_post_views_number(),
			'comment'       => lerm_post_comments_number(),
			'format'        => lerm_post_format(),
		);
		$arr  = array();
		switch ( $arg ) {
			case 'summary':
				$arr = array_keys( (array) lerm_options( 'summary_meta', 'enabled' ) );
				break;
			case 'entry':
					$arr = array_keys( (array) lerm_options( 'entry_meta', 'enabled' ) );
				break;
		}

		foreach ( $arr as $value ) {
			echo $meta[ $value ];
		}
		if ( in_array( get_post_type(), array( 'post', 'attachment' ), true ) ) {
			lerm_entry_date( 'publish' );
			lerm_entry_date( 'modified' );
		}

		if ( 'post' === get_post_type() ) {
			if ( is_single() ) {
				lerm_entry_taxonomies();
				lerm_edit_link();
			}
		}
	}
endif;

if ( ! function_exists( 'lerm_post_format' ) ) :
		/**
		 * Prints HTML with post format for current post.
		 *
		 * Create your own lerm_entry_taxonomies() function to override in a child theme.
		 *
		 * @since Lerm 2.0
		 */
	function lerm_post_format() {
		$format = get_post_format();
		if ( current_theme_supports( 'post-formats', $format ) ) {
			return sprintf(
				'<span class="entry-format meta-item">%1$s<a href="%2$s" class="entry-format-link">%3$s</a></span>',
				sprintf( '<span class="screen-reader-text">%s</span>', _x( 'Format', 'Used before post format.', 'lerm' ) ),
				esc_url( get_post_format_link( $format ) ),
				get_post_format_string( $format )
			);
		}
	}
endif;

if ( ! function_exists( 'lerm_post_views_number' ) ) :
		/**
		 * Prints HTML with date for current post.
		 *
		 * Create your own lerm_entry_taxonomies() function to override in a child theme.
		 *
		 * @since Lerm 2.0
		 */
	function lerm_post_views_number() {
		if ( ( is_home() || is_category() || is_singular() ) || ! is_singular() && ! post_password_required() && ( comments_open() || get_comments_number() ) ) {
			return sprintf( '<span class="post-views meta-item"><i class="fa fa-eye pr-1 pl-2"></i>%1$s</span>', post_views( '' ) );
		}
	}
endif;
if ( ! function_exists( 'lerm_post_comments_number' ) ) :

	function lerm_post_comments_number() {
		/* translators: %s: search term */
		return sprintf( '<a class="comments-link meta-item" href="%1$s"><i class="fa fa-comment pr-1 pl-2"></i>%2$s</a>', get_comments_link(), sprintf( _nx( '%s', '%s', 'comments title', 'lerm' ), number_format_i18n( get_comments_number() ) ) );
	}

endif;

if ( ! function_exists( 'lerm_entry_date' ) ) :
		/**
		 * Prints HTML with date for current post.
		 *
		 * Create your own lerm_entry_taxonomies() function to override in a child theme.
		 *
		 * @since Lerm 2.0
		 */
	function lerm_entry_date( $arg ) {
		if ( 'publish' === $arg ) {
			$time_string = '<time class="entry-published meta-item" datetime="%1$s" title="%2$s"><i class="fa fa-calendar pr-1"></i>%2$s</time>';}
		if ( 'modified' === $arg ) {
			$time_string = '<time class="updated meta-item" datetime="%3$s"><i class="fa fa-calendar pr-1"></i>%4$s</time>';
		}

		$time_string = sprintf(
			$time_string,
			get_the_date( DATE_W3C ),
			get_the_date(),
			get_the_modified_date( DATE_W3C ),
			get_the_modified_date()
		);

		return sprintf(
			'%1$s%2$s',
			_x( '', 'Used before publish date.', 'lerm' ),
			// esc_url( get_permalink() ),//time-link
					$time_string
		);
	}
endif;

if ( ! function_exists( 'lerm_entry_taxonomies' ) ) :
		/**
		 * Prints HTML with category and tags for current post.
		 *
		 * Create your own lerm_entry_taxonomies() function to override in a child theme.
		 *
		 * @since Lerm 2.0
		 */
	function lerm_entry_taxonomies() {
		$cat_list = get_the_category_list( _x( ', ', 'Used between list items, there is a space after the comma.', 'lerm' ) );
		if ( $cat_list && lerm_categorized_blog() ) {
			return sprintf(
				'<span class="category-link meta-item"><i class="fa fa-th pr-1"></i>%s</span>',
				$cat_list
			);
		}
	}
endif;

if ( ! function_exists( 'lerm_edit_link' ) ) :
	function lerm_edit_link() {
		edit_post_link(
			sprintf(
				/* translators: %s: Name of current post */
				__( 'Edit<span class="screen-reader-text"> "%s"</span>', 'lerm' ),
				get_the_title()
			),
			'<span class="edit-link meta-item"><i class="fa fa-edit pr-1 pl-2"></i>',
			'</span>'
		);
	}
endif;
/**
 * Determines whether blog/site has more than one category.
 *
 * Create your own lerm_categorized_blog() function to override in a child theme.
 *
 * @since Lerm 2.0
 */
function lerm_categorized_blog() {
	if ( false === ( $all_the_cool_cats = get_transient( 'lerm_categories' ) ) ) {
		// Create an array of all the categories that are attached to posts.
		$all_the_cool_cats = get_categories(
			array(
				'fields' => 'ids',
				// We only need to know if there is more than one category.
				'number' => 2,
			)
		);

		// Count the number of categories that are attached to the posts.
		$all_the_cool_cats = count( $all_the_cool_cats );

		set_transient( 'lerm_categories', $all_the_cool_cats );
	}

	if ( $all_the_cool_cats > 1 ) {
		// This blog has more than 1 category so lerm_categorized_blog should return true.
		return true;
	} else {
		// This blog has only 1 category so lerm_categorized_blog should return false.
		return false;
	}
}

function lerm_category_transient_flusher() {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	// Like, beat it. Dig?
	delete_transient( 'lerm_categories' );
}
add_action( 'edit_category', 'lerm_category_transient_flusher' );
add_action( 'save_post', 'lerm_category_transient_flusher' );

/**
 * Displays the optional excerpt.
 *
 * @since Lerm 2.0
 */
function lerm_excerpt_length( $length ) {
	$length = lerm_options( 'excerpt_length' );
	return $length;
}
add_filter( 'excerpt_length', 'lerm_excerpt_length', 999 );

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function lerm_body_classes( $classes ) {
	$classes[] = 'body-bg';
	// Add class on front page.
	if ( is_front_page() && 'posts' !== get_option( 'show_on_front' ) ) {
		$classes[] = 'lerm-front-page';
	}

	// layout body classes
	$classes[] = lerm_page_layout();

	return $classes;
}
add_filter( 'body_class', 'lerm_body_classes' );

function lerm_post_class( $classes ) {
	$classes[] = '';
	if ( ! is_singular() ) {
		// $classes[] = 'summary d-flex pt-3 pb-3 ml-3 mr-3';
		$classes[] = 'summary d-flex p-3 mb-2';
	}

	if ( is_singular( array( 'post', 'page' ) ) ) {
		$classes[] = ' ';
	}
	return $classes;
}
add_filter( 'post_class', 'lerm_post_class' );
