<?php
//Register Sidebars
function lerm_widgets_init() {
	register_sidebar(
		array(
			'name'          => __( 'HomePage Sidebar', 'lerm' ),
			'id'            => 'home-sidebar',
			'description'   => __( 'Add widgets here to appear in your sidebar.', 'lerm' ),
			'before_widget' => '<section id="%1$s" class="widget mb-3 pl-3 pr-3 pb-3 pt-3 %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="widget-title"><span class="wrap fa pt-2 pb-2 pl-2 pr-2 m-0 d-inline-block">',
			'after_title'   => '</span></h3>',
		)
	);
	$widgets = lerm_options( 'register_sidebars', '' );
	// lerm_options('register_sidebars', '')  ? lerm_options('register_sidebars', ''):'';
	if ( isset( $widgets ) && ! empty( $widgets ) ) {
		foreach ( $widgets as $key => $value ) {
			$sidebar_id = 'sidebar' . preg_replace( '/ /', '', Chinese_to_PY::getPY( $value['sidebar_title'], 'all' ) );
			if ( ! empty( $value['sidebar_title'] ) ) {
				register_sidebar(
					array(
						'name'          => $value['sidebar_title'],
						'id'            => $sidebar_id,
						'description'   => __( 'Cutsom register sidebar', 'lerm' ),
						'before_widget' => '<section id="%1$s" class="widget mb-3 pl-3 pr-3 pb-3 pt-3 %2$s">',
						'after_widget'  => '</section>',
						'before_title'  => '<h3 class="widget-title"><span class="wrap fa pt-2 pb-2 pl-2 pr-2 m-0 d-inline-block">',
						'after_title'   => '</span></h3>',
					)
				);
			}
		}
	}
	$footer_sidebars_count = (int) lerm_options( 'footer_sidebars_count', '' );
	register_sidebars(
		$footer_sidebars_count,
		array(
			'name'          => __( 'Sidebar %d', 'lerm' ),
			'id'            => 'footer-sidebar',
			'description'   => 'Sidebar show  ',
			'class'         => '',
			'before_widget' => '<section id="%1$s" class="footer-widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h4 class="footer-widget-title">',
			'after_title'   => '</h4>',
		)
	);
}
add_action( 'widgets_init', 'lerm_widgets_init' );
 /*
 * Costum tags cloud
 */
function lerm_widget_tag_cloud_args( $args ) {
	$args['largest']  = 1.25;
	$args['smallest'] = 0.95;
	$args['unit']     = 'em';
	$args['number']   = 22;
	$args['orderby']  = 'count';
	$args['order']    = 'DESC';
	return $args;
}
add_filter( 'widget_tag_cloud_args', 'lerm_widget_tag_cloud_args' );

/**
 * Recent comments
 */
class Recent_Comments extends WP_Widget {

	public function __construct() {
		$widget_ops = array(
			'classname'                   => 'widget_recent_comments',
			'description'                 => __( 'Your site&#8217;s most recent comments.', 'lerm' ),
			'customize_selective_refresh' => true,
		);
		parent::__construct( 'recent-comments', __( 'Recent Comments', 'lerm' ), $widget_ops );
		$this->alt_option_name = 'widget_recent_comments';
	}
	public function widget( $args, $instance ) {
		global $wpdb, $comments, $comment;
		$cache = wp_cache_get( 'my_widget_recent_comments', 'widget' );
		if ( ! is_array( $cache ) ) {
			$cache = array();
		}
		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}
		if ( isset( $cache[ $args['widget_id'] ] ) ) {
			echo $cache[ $args['widget_id'] ];
			return;
		}
		extract( $args, EXTR_SKIP );
		$output = '';
		$title  = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Recent Comments', 'lerm' ) : $instance['title'], $instance, $this->id_base );
		if ( empty( $instance['number'] ) || ! ( $number = absint( $instance['number'] ) ) ) {
			$number = 5;
		}
		$comments = $wpdb->get_results( "SELECT * FROM {$wpdb->comments} WHERE user_id !=2 and comment_approved = '1' and comment_type not in ('pingback','trackback') ORDER BY comment_date_gmt DESC LIMIT {$number}" );
		$output  .= $before_widget;
		if ( $title ) {
			$output .= $before_title . $title . $after_title;
		}
		$output .= '<ul>';
		if ( $comments ) {
			$post_ids = array_unique( wp_list_pluck( $comments, 'comment_post_ID' ) );
			_prime_post_caches( $post_ids, strpos( get_option( 'permalink_structure' ), '%category%' ), false );
			foreach ( (array) $comments as $comment ) {
				$avatar = sprintf(
					'<a href="%s" class="vcard d-block" rel="external nofollow">%s<strong class="hidden-md-down comment_author">%s</strong><time class="comment_time comment-published float-right" date_time="%s" title="%s">' . __( '%s ago', 'lerm' ) . '</time></a>',
					esc_url( get_comment_link( $comment->comment_ID ) ),
					get_avatar( $comment, 32 ),
					get_comment_author(),
					get_comment_date( 'c' ),
					get_comment_time( _x( 'l, F j, Y, g:i a', 'comment time format', 'lerm' ) ),
					human_time_diff( get_comment_time( 'U' ), current_time( 'timestamp' ) )
				);
				//$author  = '';
				$content = apply_filters( 'get_comment_text', $comment->comment_content );
				//$content = mb_strimwidth(strip_tags($content), 0, '25', '...', 'UTF-8');
				$content = convert_smilies( $content );
				$output .= '<li class="recentcomment pt-2 pb-2 border-bottom">' . $avatar . '<span class="comment-content small">' . $content . '</span></li>';
			}
		}
		$output .= '</ul>';
		$output .= $after_widget;
		echo $output;
		$cache[ $args['widget_id'] ] = $output;
		wp_cache_set( 'recent_comments', $cache, 'widget' );
	}
	public function update( $new_instance, $old_instance ) {
		$instance           = $old_instance;
		$instance['title']  = sanitize_text_field( $new_instance['title'] );
		$instance['number'] = absint( $new_instance['number'] );
		return $instance;
	}
	public function form( $instance ) {
		$title  = isset( $instance['title'] ) ? $instance['title'] : '';
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5; ?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_attr_e( 'Title:', 'lerm' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>
		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php esc_attr_e( 'Number of comments to show:', 'lerm' ); ?></label>
		<input class="tiny-text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" step="1" min="1" value="<?php echo $number; ?>" size="3" /></p>
		<?php
	}
}
add_action(
	'widgets_init',
	function () {
		register_widget( 'Recent_Comments' );
	}
);
/**
 * Recent Posts
 *
 */

class Lerm_Recent_Posts extends WP_Widget {

	public function __construct() {
		$widget_ops = array(
			'classname'                   => 'widget_posts',
			'description'                 => __( 'Your site&#8217;s most recent Posts.', 'lerm' ),
			'customize_selective_refresh' => true,
		);
		parent::__construct( 'recent-posts', __( 'Recent Posts', 'lerm' ), $widget_ops );
		$this->alt_option_name = 'widget_posts';
	}
	public function widget( $args, $instance ) {
		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}

		$title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : __( 'Recent Posts', 'lerm' );

		/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 5;
		if ( ! $number ) {
			$number = 5;
		}
		$show_date = isset( $instance['show_date'] ) ? $instance['show_date'] : false;

		$r = new WP_Query(
			apply_filters(
				'widget_posts_args',
				array(
					'posts_per_page'      => $number,
					'no_found_rows'       => true,
					'post_status'         => 'publish',
					'ignore_sticky_posts' => true,
					'tax_query'           => array(
						array(
							'taxonomy' => 'post_format',
							'field'    => 'slug',
							'operator' => 'NOT IN',
							'terms'    => array( 'post-format-aside', 'post-format-video', 'post-format-gallery', 'post-format-audio', 'post-format-link', 'post-format-image', 'post-format-aside', 'post-format-status', 'post-format-chat', 'post-format-quote' ),
						),
					),
				)
			)
		);

		if ( $r->have_posts() ) :
			?>
			<?php echo $args['before_widget']; ?>
			<?php
			if ( $title ) {
				echo $args['before_title'] . $title . $args['after_title'];
			}
			?>
	  <ul>
			<?php
			while ( $r->have_posts() ) :
				$r->the_post();
				?>
		<li class="widget-post d-flex">
		<?php
	lerm_thumb_nail(
		array(
			'classes'=>'widget-thumbnail',
			'height' => '60',
			'width'  => '100',
		)
	);
	?>
		  <div class="d-flex flex-column justify-content-between">
			  <a href="<?php the_permalink(); ?>"><?php get_the_title() ? the_title() : the_ID(); ?></a>
				<?php if ( $show_date ) : ?>
				  <span class="post-date text-muted"><?php echo get_the_date(); ?></span>
				<?php endif; ?>
		</div>
		</li>
		<?php endwhile; ?>
	  </ul>
			<?php echo $args['after_widget']; ?>
			<?php
			// Reset the global $the_post as this query will have stomped on it
			wp_reset_postdata();

		endif;
	}


	public function update( $new_instance, $old_instance ) {
		$instance              = $old_instance;
		$instance['title']     = sanitize_text_field( $new_instance['title'] );
		$instance['number']    = (int) $new_instance['number'];
		$instance['show_date'] = isset( $new_instance['show_date'] ) ? (bool) $new_instance['show_date'] : false;
		return $instance;
	}

	public function form( $instance ) {
		$title     = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number    = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		$show_date = isset( $instance['show_date'] ) ? (bool) $instance['show_date'] : false;
		?>
	  <p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_attr_e( 'Title:', 'lerm' ); ?></label>
	  <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

	  <p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php esc_attr_e( 'Number of posts to show:', 'lerm' ); ?></label>
	  <input class="tiny-text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" step="1" min="1" value="<?php echo $number; ?>" size="3" /></p>

	  <p><input class="checkbox" type="checkbox"<?php checked( $show_date ); ?> id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" />
	  <label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php esc_attr_e( 'Display post date?', 'lerm' ); ?></label></p>
		<?php
	}
}
add_action(
	'widgets_init',
	function () {
		register_widget( 'Lerm_Recent_Posts' );
	}
);

/**
 * Popular posts
 */
class Lerm_Popular_Post extends WP_Widget {

	public function __construct() {
		$widget_ops = array(
			'classname'   => 'widget_popular_entries',
			'description' => __( 'Display the popular posts on your website.', 'lerm' ),
		);
		parent::__construct( 'popular-posts', __( 'Popular Post', 'lerm' ), $widget_ops );
	}
	public function widget( $args, $instance ) {
		extract( $args );

		$title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : __( 'Popular Post', 'lerm' );
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$number    = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 5;
		$show_date = isset( $instance['show_date'] ) ? $instance['show_date'] : false;
		if ( ! $number ) {
			$number = 5;
		}
		$query = new WP_Query(
			apply_filters(
				'widget_posts_args',
				array(
					'posts_per_page'      => $number,
					'orderby'             => 'comment_count',
					'post_status'         => 'publish',
					'ignore_sticky_posts' => true,
				)
			)
		);

		if ( $query->have_posts() ) :
			echo $args['before_widget'];

			if ( $title ) {
				echo $args['before_title'] . $title . $args['after_title'];
			}
			?>

		<ul>
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				?>

			<li class="widget-post d-flex">
				<?php 	lerm_thumb_nail(
		array(
			'classes'=>'widget-thumbnail',
			'height' => '60',
			'width'  => '100',
		)
	); ?>
			  <div class="d-flex flex-column justify-content-between">
			  <a href="<?php the_permalink(); ?>"><?php get_the_title() ? the_title() : the_ID(); ?></a>
				<?php if ( $show_date ) : ?>
				  <span class="post-date text-muted"><?php echo get_the_date(); ?></span>
				<?php endif; ?>
		</div>
			</li>
			<?php endwhile; ?>
		</ul>

			<?php echo $args['after_widget']; ?>

			<?php
			wp_reset_postdata();
		endif;
	}
	public function update( $new_instance, $old_instance ) {
		$instance              = $old_instance;
		$instance['title']     = strip_tags( $new_instance['title'] );
		$instance['show_num']  = strip_tags( $new_instance['show_num'] );
		$instance['show_date'] = isset( $new_instance['show_date'] ) ? (bool) $new_instance['show_date'] : false;
		return $instance;
	}
	public function form( $instance ) {
		$title     = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number    = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		$show_date = isset( $instance['show_date'] ) ? (bool) $instance['show_date'] : false;
		?>
	  <p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_attr_e( 'Title:', 'lerm' ); ?></label>
	  <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>

	  <p><label for="<?php echo $this->get_field_id( 'show_num' ); ?>"><?php esc_attr_e( 'Number of posts to show:', 'lerm' ); ?></label>
	  <input class="tiny-text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" step="1" min="1" value="<?php echo $number; ?>" size="3" /></p>
	  <p><input class="checkbox" type="checkbox"<?php checked( $show_date ); ?> id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" />
	  <label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php esc_attr_e( 'Display post date?', 'lerm' ); ?></label></p>

		<?php
	}
}
add_action(
	'widgets_init',
	function () {
		register_widget( 'Lerm_Popular_Post' );
	}
);

/**
 * Custom Widget for displaying specific post formats
 *
 * Displays posts from Aside, Quote, Video, Audio, Image, Gallery, and Link formats.
 *
 */

class Lerm_Ephemera_Widget extends WP_Widget {

	private $formats = array( 'aside', 'image', 'video', 'audio', 'quote', 'link', 'gallery' );

	public function __construct() {
		parent::__construct(
			'widget_lerm_ephemera',
			__( 'Lerm Ephemera', 'lerm' ),
			array(
				'classname'                   => 'widget_ephemera',
				'description'                 => __( 'Use this widget to list your recent Aside, Quote, Video, Audio, Image, Gallery, and Link posts.', 'lerm' ),
				'customize_selective_refresh' => true,
			)
		);

		if ( is_active_widget( false, false, $this->id_base ) || is_customize_preview() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}
	}


	public function enqueue_scripts() {
		/** This filter is documented in wp-includes/media.php */
		$audio_library = apply_filters( 'wp_audio_shortcode_library', 'mediaelement' );
		/** This filter is documented in wp-includes/media.php */
		$video_library = apply_filters( 'wp_video_shortcode_library', 'mediaelement' );
		if ( in_array( 'mediaelement', array( $video_library, $audio_library ), true ) ) {
			wp_enqueue_style( 'wp-mediaelement' );
			wp_enqueue_script( 'wp-mediaelement' );
		}
	}


	public function widget( $args, $instance ) {
		$format = isset( $instance['format'] ) && in_array( $instance['format'], $this->formats ) ? $instance['format'] : 'aside';

		switch ( $format ) {
			case 'image':
				$format_string      = __( 'Images', 'lerm' );
				$format_string_more = __( 'More images', 'lerm' );
				break;
			case 'video':
				$format_string      = __( 'Videos', 'lerm' );
				$format_string_more = __( 'More videos', 'lerm' );
				break;
			case 'audio':
				$format_string      = __( 'Audio', 'lerm' );
				$format_string_more = __( 'More audio', 'lerm' );
				break;
			case 'quote':
				$format_string      = __( 'Quotes', 'lerm' );
				$format_string_more = __( 'More quotes', 'lerm' );
				break;
			case 'link':
				$format_string      = __( 'Links', 'lerm' );
				$format_string_more = __( 'More links', 'lerm' );
				break;
			case 'status':
				$format_string      = __( 'Statuses', 'lerm' );
				$format_string_more = __( 'More statuses', 'lerm' );
				break;
			case 'gallery':
				$format_string      = __( 'Galleries', 'lerm' );
				$format_string_more = __( 'More galleries', 'lerm' );
				break;
			case 'aside':
			default:
				$format_string      = __( 'Asides', 'lerm' );
				$format_string_more = __( 'More asides', 'lerm' );
				break;
		}

		$number = empty( $instance['number'] ) ? 2 : absint( $instance['number'] );
		$title  = apply_filters( 'widget_title', empty( $instance['title'] ) ? $format_string : $instance['title'], $instance, $this->id_base );

		$ephemera = new WP_Query(
			array(
				'order'          => 'DESC',
				'posts_per_page' => $number,
				'no_found_rows'  => true,
				'post_status'    => 'publish',
				'post__not_in'   => get_option( 'sticky_posts' ),
				'tax_query'      => array(
					array(
						'taxonomy' => 'post_format',
						'terms'    => array( "post-format-$format" ),
						'field'    => 'slug',
						'operator' => 'IN',
					),
				),
			)
		);

		if ( $ephemera->have_posts() ) :
			$tmp_content_width        = $GLOBALS['content_width'];
			$GLOBALS['content_width'] = 306;

			echo $args['before_widget'];
			?>
		<h3 class="widget-title <?php echo esc_attr( $format ); ?>">
			<span class="wrap">
				<a class="entry-format" href="<?php echo esc_url( get_post_format_link( $format ) ); ?>"><?php echo esc_html( $title ); ?></a></span>
		</h3>
		<ol>

			<?php
			while ( $ephemera->have_posts() ) :
				$ephemera->the_post();
				$tmp_more        = $GLOBALS['more'];
				$GLOBALS['more'] = 0;
				?>
			<li>
			<article <?php post_class(); ?>>
				<div class="entry-content">
						<?php
						if ( has_post_format( 'gallery' ) ) :

							if ( post_password_required() ) :
								the_content( __( 'Continue reading <span class="meta-nav">&rarr;</span>', 'lerm' ) ); else :
									$images = array();

									$galleries = get_post_galleries( get_the_ID(), false );
									if ( isset( $galleries[0]['ids'] ) ) {
															$images = explode( ',', $galleries[0]['ids'] );
									}

									if ( ! $images ) :
												$images = get_posts(
													array(
														'fields'         => 'ids',
														'numberposts'    => -1,
														'order'          => 'ASC',
														'orderby'        => 'menu_order',
														'post_mime_type' => 'image',
														'post_parent'    => get_the_ID(),
														'post_type'      => 'attachment',
													)
												);
			endif;

									$total_images = count( $images );

									if ( has_post_thumbnail() ) :
											$post_thumbnail = get_the_post_thumbnail(); elseif ( $total_images > 0 ) :
												$image          = reset( $images );
												$post_thumbnail = wp_get_attachment_image( $image, 'post-thumbnail' );
			endif;

											if ( ! empty( $post_thumbnail ) ) :
												?>
						  <a href="<?php the_permalink(); ?>"><?php echo $post_thumbnail; ?></a>
														<?php endif; ?>
						<p class="wp-caption-text">
									<?php
									printf(
										_n( 'This gallery contains <a href="%1$s" rel="bookmark">%2$s photo</a>.', 'This gallery contains <a href="%1$s" rel="bookmark">%2$s photos</a>.', $total_images, 'lerm' ),
										esc_url( get_permalink() ),
										number_format_i18n( $total_images )
									);
									?>
						</p>
									<?php
							endif; else :
									the_content( __( 'Continue reading <span class="meta-nav">&rarr;</span>', 'lerm' ) );
			endif;
							?>
					  </div><!-- .entry-content -->

					<header class="entry-header">
						<div class="entry-meta">
							<?php
							if ( ! has_post_format( 'link' ) ) :
								the_title( '<h1 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h1>' );
			endif;

							printf(
								'<span class="entry-date"><a href="%1$s" rel="bookmark"><time class="entry-date" datetime="%2$s">%3$s</time></a></span> <span class="byline"><span class="author vcard"><a class="url fn n" href="%4$s" rel="author">%5$s</a></span></span>',
								esc_url( get_permalink() ),
								esc_attr( get_the_date( 'c' ) ),
								esc_html( get_the_date() ),
								esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
								get_the_author()
							);

							if ( ! post_password_required() && ( comments_open() || get_comments_number() ) ) :
								?>
								<span class="comments-link"><?php comments_popup_link( __( 'Leave a comment', 'lerm' ), __( '1 Comment', 'lerm' ), __( '% Comments', 'lerm' ) ); ?></span>
																<?php endif; ?>
					  </div><!-- .entry-meta -->
				</header><!-- .entry-header -->
			</article><!-- #post-## -->
		  </li>
				<?php endwhile; ?>

		</ol>
		<a class="post-format-archive-link" href="<?php echo esc_url( get_post_format_link( $format ) ); ?>">
			<?php
			/* translators: used with More archives link */
			printf( __( '%s <span class="meta-nav">&rarr;</span>', 'lerm' ), $format_string_more );
			?>
		</a>
			<?php

			echo $args['after_widget'];

			// Reset the post globals as this query will have stomped on it.
			wp_reset_postdata();

			$GLOBALS['more']          = $tmp_more;
			$GLOBALS['content_width'] = $tmp_content_width;

		endif; // End check for ephemeral posts.
	}


	public function update( $new_instance, $instance ) {
		$instance['title']  = strip_tags( $new_instance['title'] );
		$instance['number'] = empty( $new_instance['number'] ) ? 2 : absint( $new_instance['number'] );
		if ( in_array( $new_instance['format'], $this->formats ) ) {
			$instance['format'] = $new_instance['format'];
		}

		return $instance;
	}

	public function form( $instance ) {
		$title  = empty( $instance['title'] ) ? '' : esc_attr( $instance['title'] );
		$number = empty( $instance['number'] ) ? 2 : absint( $instance['number'] );
		$format = isset( $instance['format'] ) && in_array( $instance['format'], $this->formats ) ? $instance['format'] : 'aside';
		?>
		<p><label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'lerm' ); ?></label>
		<input id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>"></p>

		<p><label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"><?php esc_attr_e( 'Number of posts to show:', 'lerm' ); ?></label>
		<input id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>" type="text" value="<?php echo esc_attr( $number ); ?>" size="3"></p>

		<p><label for="<?php echo esc_attr( $this->get_field_id( 'format' ) ); ?>"><?php esc_attr_e( 'Post format to show:', 'lerm' ); ?></label>
		<select id="<?php echo esc_attr( $this->get_field_id( 'format' ) ); ?>" class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'format' ) ); ?>">
		  <?php foreach ( $this->formats as $slug ) : ?>
		  <option value="<?php echo esc_attr( $slug ); ?>"<?php selected( $format, $slug ); ?>><?php echo esc_html( get_post_format_string( $slug ) ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}
}

// end class

add_action(
	'widgets_init',
	function () {
		register_widget( 'Lerm_Ephemera_Widget' );
	}
);
