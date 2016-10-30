<?php
if ( ! function_exists( 'lerm_entry_meta' ) ) :
	/**
	 * Prints entry-date, entry_taxonomies, entry-format for current post.
	 *
	 * Create your own lerm_entry_taxonomies() function to override in a child theme.
	 *
	 * @since Lerm 2.0
	 */
	function lerm_entry_meta() {
		if ( in_array( get_post_type(), array( 'post', 'attachment' ) ) ) {
			lerm_entry_date();
		}
		/*$format = get_post_format();
		if ( current_theme_supports( 'post-formats', $format ) ) {
			printf( '<span class="entry-format">%1$s<a href="%2$s">%3$s</a></span>',
				sprintf( '<span class="screen-reader-text">%s</span>', _x( 'Format', 'Used before post format.', 'lerm' ) ),
				esc_url( get_post_format_link( $format ) ),
				get_post_format_string( $format )
			);
		}*/

		if ( 'post' === get_post_type() ) {
			if (is_single()) {
				lerm_entry_taxonomies();
			}
		}

		if ( is_home() || is_single() || is_category() ) {
			echo '&nbsp;<span><i class="fa fa-eye" aria-hidden="true"></i>' . get_post_views(get_the_ID()) . '</span>';
		}

		if ( (is_home()|| is_category() || is_singular()) || ! is_singular() && ! post_password_required() && ( comments_open() || get_comments_number() ) ) {
			$comment='<i class="fa fa-comments" aria-hidden="true"></i>';
			echo '&nbsp;<span>';
			comments_popup_link($comment . '0', $comment . '1', $comment . '%');
			echo '</span>';
		}
	}
endif;

if ( ! function_exists( 'lerm_entry_date' ) ) :

	function lerm_entry_date() {
		$time_string = '<time class="entry-date published update" datetime="%1$s">%2$s</time>';

		$time_string = sprintf( $time_string,
			esc_attr( get_the_date( 'c' ) ),
			get_the_date(),
			esc_attr( get_the_modified_date( 'c' ) ),
			get_the_modified_date()
		);

		printf( '<span>%1$s%2$s</span>',
			_x( '<i class="fa fa-calendar" aria-hidden="true"></i>','Used before publish date.', 'lerm' ),
			//esc_url( get_permalink() ),//time-link
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
			printf( '<span>%1$s%2$s</span>',
				_x( ' <i class="fa fa-th" aria-hidden="true"></i>', 'Used before category names.', 'lerm' ),
				$cat_list
			);
		}
	}
endif;

if ( ! function_exists( 'lerm_excerpt' ) ) :
	/**
	 * Displays the optional excerpt.
	 *
	 * Wraps the excerpt in a div element.
	 *
	 * @since Lerm 2.0
	 */
	function lerm_excerpt( $class = 'hidden-xs-down' ) {
		$class = esc_attr( $class );

		if ( has_excerpt() || is_category() || is_archive() || is_home() || is_search() ) : ?>
      <div class="<?php echo $class; ?>">
				<?php the_excerpt(); ?>
			</div><!-- .<?php echo $class; ?> -->
		<?php endif;
	}
endif;
	/**
	 * Displays the optional excerpt.
	 *
	 * @since Lerm 2.0
	 */
function lerm_excerpt_length( $length ) {
  return 99;
}
add_filter( 'excerpt_length', 'lerm_excerpt_length', 999 );
/**
 * Replaces "[...]" (appended to automatically generated excerpts) with ... and a 'Continue reading' link.
 *
 * @since Lerm 2.0
 */
function lerm_excerpt_more() {
	$link = sprintf( '<a href="%1$s" class="label label-primary pull-right more">%2$s</a>',
		esc_url( get_permalink( get_the_ID() ) ),
		/* translators: %s: Name of current post */
		sprintf( __( '阅读全文', 'lerm' ), get_the_title( get_the_ID() ) )
	);
	return ' &hellip; ' . $link;
}
add_filter( 'excerpt_more', 'lerm_excerpt_more' );


//连接数量
if (of_get_option('post_tag_link')) {
	$match_num_from = of_get_option('match_num_from'); //一个关键字少于多少不替换
	$match_num_to = of_get_option('match_num_to'); //一个关键字最多替换
	//按长度排序
	function tag_sort($a, $b) {
		if ( $a->name == $b->name ) return 0;
		return ( strlen($a->name) > strlen($b->name) ) ? -1 : 1;
	}
	//改变标签关键字
	function tag_link($content) {
		global $match_num_from,$match_num_to;
		$posttags = get_the_tags();
		if ($posttags) {
			usort($posttags, "tag_sort");
			foreach($posttags as $tag) {
				$link = get_tag_link($tag->term_id);
				$keyword = $tag->name;
				//连接代码
				$cleankeyword = stripslashes($keyword);
				$url = "<a href=\"$link\" title=\"".str_replace('%s',addcslashes($cleankeyword, '$'),__('View all posts in %s'))."\"";
				$url .= ' target="_blank" class="tag_link"';
				$url .= ">".addcslashes($cleankeyword, '$')."</a>";
				$limit = rand($match_num_from,$match_num_to);
				//不连接的 代码
				$content = preg_replace( '|(<a[^>]+>)(.*)('.$ex_word.')(.*)(</a[^>]*>)|U'.$case, '$1$2%&&&&&%$4$5', $content);
				$content = preg_replace( '|(<img)(.*?)('.$ex_word.')(.*?)(>)|U'.$case, '$1$2%&&&&&%$4$5', $content);
				$cleankeyword = preg_quote($cleankeyword,'\'');
				$regEx = '\'(?!((<.*?)|(<a.*?)))('. $cleankeyword . ')(?!(([^<>]*?)>)|([^>]*?</a>))\'s' . $case;
				$content = preg_replace($regEx,$url,$content,$limit);
				$content = str_replace( '%&&&&&%', stripslashes($ex_word), $content);
			}
		}
		return $content;
	}
	add_filter('the_content','tag_link',1);
}
//指定关键词内链
//function content_keywords_link($text){
//	$replace = array(
//		'乐朦'      => '<a href="'.esc_url( home_url( '/') ).'" rel="home">乐朦</a>',
//	);
//	$text = str_replace(array_keys($replace), $replace, $text);
//	return $text;
//}
//add_filter('the_content', 'content_keywords_link');

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
		$all_the_cool_cats = get_categories( array(
			'fields'     => 'ids',
			// We only need to know if there is more than one category.
			'number'     => 2,
		) );

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
add_action( 'save_post',     'lerm_category_transient_flusher' );
