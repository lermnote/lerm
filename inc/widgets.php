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
	$widgets = lerm_options( 'register_sidebars' );
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
	$footer_sidebars_count = (int) lerm_options( 'footer_sidebars_count' );
	register_sidebars(
		$footer_sidebars_count,
		array(
			/* translators: %d: number of sidebar*/
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

/**
 * Custmm tags cloud
 *
 * @since 1.0.0
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
 *
 * @since 1.0.0
 */
class Lerm_Recent_Comments extends WP_Widget {
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
		global  $comment;
		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}
		$output = '';
		$title  = ( ! empty( $instance['title'] ) ) ? $instance['title'] : __( 'Recent Comments', 'lerm' );
		$title  = apply_filters( 'widget_title', $title, $instance, $this->id_base );
		$number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 5;
		if ( ! $number ) {
			$number = 5;
		}
		$comments = get_comments(
			apply_filters(
				'widget_comments_args',
				array(
					'number'      => $number,
					'status'      => 'approve',
					'post_status' => 'publish',
				),
				$instance
			)
		);

		$output .= $args['before_widget'];

		if ( $title ) {
			$output .= $args['before_title'] . $title . $args['after_title'];
		}
		$output .= '<ul>';
		if ( is_array( $comments ) && $comments ) {
			$post_ids = array_unique( wp_list_pluck( $comments, 'comment_post_ID' ) );
			_prime_post_caches( $post_ids, strpos( get_option( 'permalink_structure' ), '%category%' ), false );
			foreach ( (array) $comments as $comment ) {
				$avatar = sprintf(
					'<a href="%s" class="vcard d-block" rel="external nofollow">%s<strong class="hidden-md-down comment_author">%s</strong><time class="float-right" datetime="%s" title="%s">' . esc_html__( '%s ago', 'lerm' ) . '</time></a>',
					esc_url( get_comment_link( $comment ) ),
					get_avatar( $comment, 32 ),
					get_comment_author( $comment ),
					get_comment_date( 'c' ),
					/* Translators: 1 = comment date, 2 = comment time */
					sprintf( __( '%1$s at %2$s', 'lerm' ), get_comment_date( '', $comment ), get_comment_time() ),
					human_time_diff( get_comment_time( 'U' ), current_time( 'timestamp' ) )
				);
				$content = apply_filters( 'get_comment_text', $comment->comment_content );
				$content = convert_smilies( $content );
				$output .= '<li class="recentcomment pt-2 pb-2 border-bottom">' . $avatar . '<span class="comment-content small">' . $content . '</span></li>';
			}
		}
		$output .= '</ul>';
		$output .= $args['after_widget'];
		echo $output;
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
		register_widget( 'Lerm_Recent_Comments' );
	}
);

/**
 * Recent Posts
 *
 * @since 1.0.0
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
			echo $args['before_widget'];
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
								'classes' => 'widget-thumbnail',
								'height'  => '60',
								'width'   => '100',
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
 *
 * @since 1.0.0
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
		// extract( $args );

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
						<?php
						lerm_thumb_nail(
							array(
								'classes' => 'widget-thumbnail',
								'height'  => '60',
								'width'   => '100',
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
