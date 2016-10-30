<?php
//添加侧边栏
function lerm_widgets_init() {
  register_sidebar( array(
    'name'          => __( '首页侧边栏', 'lerm' ),
    'id'            => 'home-sidebar',
    'description'   => __( 'Add widgets here to appear in your sidebar.', 'lerm' ),
    'before_widget' => '<aside id="%1$s" class="card %2$s">',
    'after_widget'  => '</aside>',
    'before_title'  => '<h3 class="card-header"><i class="fa fa-bars" aria-hidden="true"></i> ',
    'after_title'   => '</h3>',
  ) );
  register_sidebar( array(
    'name'          => __( '文章页侧边栏', 'lerm' ),
    'id'            => 'single-sidebar',
    'description'   => __( 'Appears at the side of the content on posts and pages.', 'lerm' ),
    'before_widget' => '<aside id="%1$s" class="card %2$s">',
    'after_widget'  => '</aside>',
    'before_title'  => '<h3 class="card-header"><i class="fa fa-bars" aria-hidden="true"></i> ',
    'after_title'   => '</h3>',
  ) );
  register_sidebar( array(
    'name'          => __( '页面侧边栏', 'lerm' ),
    'id'            => 'page-sidebar',
    'description'   => __( 'Appears at the side of the content on posts and pages.', 'lerm' ),
    'before_widget' => '<aside id="%1$s" class="card %2$s">',
    'after_widget'  => '</aside>',
    'before_title'  => '<h3 class="card-header"><i class="fa fa-bars" aria-hidden="true"></i> ',
    'after_title'   => '</h3>',
  ) );
  register_sidebar( array(
    'name'          => __( 'Bottom  Area Left', 'lerm' ),
    'id'            => 'bottom-sidebar-left',
    'description'   => __( 'Appears at the bottom of the content on posts and pages.', 'lerm' ),
    'before_widget' => '<section id="%1$s" class="text-center %2$s">',
    'after_widget'  => '</section>',
    'before_title'  => '<h4>',
    'after_title'   => '</h4>',
  ) );
  register_sidebar( array(
    'name'          => __( 'Bottom  Area Right', 'lerm' ),
    'id'            => 'bottom-sidebar-right',
    'description'   => __( 'Appears at the bottom of the content on posts and pages.', 'lerm' ),
    'before_widget' => '<section id="%1$s" class="%2$s">',
    'after_widget'  => '</section>',
    'before_title'  => '<h4>',
    'after_title'   => '</h4>',
  ) );
}
add_action( 'widgets_init', 'lerm_widgets_init' );
 /*
 * 自定义标签云
 */
function lerm_widget_tag_cloud_args($args){
  $args['largest']  = 1;
  $args['smallest'] = 1;
  $args['unit']     = 'em';
  $args['number']   = 22;
  $args['orderby']  = 'count';
  $args['order']  = 'DESC';
  return $args;
}
  add_filter( 'widget_tag_cloud_args', 'lerm_widget_tag_cloud_args' );

/**
 * 近期评论
 */
class Recent_Comments extends WP_Widget{
  function __construct(){
    $widget_ops = array(
      'classname' => 'widget_recent_comments',
      'description' => __('Your site&#8217;s most recent comments.'),
      'customize_selective_refresh' => true
    );
    parent::__construct('recent-comments', __('Recent Comments'), $widget_ops);
    $this->alt_option_name = 'widget_recent_comments';
  }
  function widget($args, $instance){
    global $wpdb, $comments, $comment;
    $cache = wp_cache_get('my_widget_recent_comments', 'widget');
    if (!is_array($cache)) {
      $cache = array();
    }
    if (!isset($args['widget_id'])) {
      $args['widget_id'] = $this->id;
    }
    if (isset($cache[$args['widget_id']])) {
      echo $cache[$args['widget_id']];
      return;
    }
    extract($args, EXTR_SKIP);
    $output = '';
    $title  = apply_filters('widget_title', empty($instance['title']) ? __('Recent Comments') : $instance['title'], $instance, $this->id_base);
    if (empty($instance['number']) || !($number = absint($instance['number']))) {
      $number = 5;
    }
    $comments = $wpdb->get_results("SELECT * FROM {$wpdb->comments} WHERE user_id !=2 and comment_approved = '1' and comment_type not in ('pingback','trackback') ORDER BY comment_date_gmt DESC LIMIT {$number}");
    $output .= $before_widget;
    if ($title) {
      $output .= $before_title . $title . $after_title;
    }
    $output .= '<ul>';
    if ($comments) {
      $post_ids = array_unique(wp_list_pluck($comments, 'comment_post_ID'));
      _prime_post_caches($post_ids, strpos(get_option('permalink_structure'), '%category%'), false);
      foreach ((array) $comments as $comment) {
        $avatar  = '<a href="' . esc_url(get_comment_link($comment->comment_ID)) . '" class="vcard">' . get_avatar($comment, 28) . '</a>';
        $author  = '<strong class="hidden-md-down"><a href="' . esc_url(get_comment_link($comment->comment_ID)) . '">' . get_comment_author() . '</a></strong>';
        $content = apply_filters('get_comment_text', $comment->comment_content);
        //$content = mb_strimwidth(strip_tags($content), 0, '25', '...', 'UTF-8');
        $content = convert_smilies($content);
        $output .= '<li class="recentcomment">' . $avatar . $author .'<span>' . $content .'</span></li>';
      }
    }
    $output .= '</ul>';
    $output .= $after_widget;
    echo $output;
    $cache[$args['widget_id']] = $output;
    wp_cache_set('recent_comments', $cache, 'widget');
  }
  function update($new_instance, $old_instance){
    $instance           = $old_instance;
    $instance['title']  = sanitize_text_field($new_instance['title']);
    $instance['number'] = absint($new_instance['number']);
    return $instance;
  }
  function form($instance){
    $title  = isset($instance['title']) ? $instance['title'] : '';
    $number = isset($instance['number']) ? absint($instance['number']) : 5;?>
    <p><label for="<?php echo $this->get_field_id('title');?>"><?php _e('Title:');?></label>
    <input class="widefat" id="<?php echo $this->get_field_id('title');?>" name="<?php echo $this->get_field_name('title');?>" type="text" value="<?php echo esc_attr($title);?>" /></p>
    <p><label for="<?php echo $this->get_field_id('number');?>"><?php _e('Number of comments to show:');?></label>
    <input class="tiny-text" id="<?php echo $this->get_field_id('number');?>" name="<?php echo $this->get_field_name('number');?>" type="number" step="1" min="1" value="<?php echo $number;?>" size="3" /></p>
    <?php
  }
}
add_action('widgets_init', create_function('', 'return register_widget("Recent_Comments");'));

/**
 * 社会化组件
 */
class Social_Media extends WP_Widget {
  function __construct(){
    $widget_ops  = array(
      'classname' => 'widget_social',
      'description' => __( 'Display the Social media on your website.' ),
      'customize_selective_refresh' => true,
    );
    parent::__construct('Social-media', __('社会化组件'), $widget_ops);
  }
  function widget( $args, $instance ) {
    extract( $args );
    $title  = apply_filters('widget_title', $instance['title'] );
    $weibo  = of_get_option('weibo');
    $github = of_get_option('github');
    $weixin = of_get_option('qrcode');
    $rss    = of_get_option('rss');
    echo $before_widget;
    if ( $title )
      echo $args['before_title'] . $title . $args['after_title'];
    echo '<div class="card-block">';
    if ( $weibo )
      echo $weibo='<a class="social" href="' . esc_url( $weibo ) . '"><i class="fa fa-weibo fa-2x" aria-hidden="true"></i></a>';
    if ( $github )
      echo $github='<a class="social" href="' . esc_url( $github ) . '"><i class="fa fa-github fa-2x" aria-hidden="true"></i></a>';
    if ( $weixin )
      echo $weixin='<a class="social weixin" href="javascript:"><img class="qrcode" src="'. $weixin .'" alt="微信二维码"><i class="fa fa-weixin fa-2x" aria-hidden="true"></i></a>';
    if ( $rss )
      echo $rss='<a class="social" href="' . esc_url( $rss ) . '"><i class="fa fa-rss fa-2x" aria-hidden="true"></i></a>';
    echo "</div>";
    echo $args['after_widget'];
  }
  function update( $new_instance, $old_instance ){
    $instance = $old_instance;
    $instance['title']  = sanitize_text_field( $new_instance['title'] );
    return $instance;
  }
  function form( $instance ){
    $instance = wp_parse_args( (array) $instance, array('title'  => '') );
    $title= sanitize_text_field( $instance['title'] );?>

    <p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:'); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $instance['title']; ?>"/></p>
  <?php
  }
}
add_action('widgets_init', create_function('', 'return register_widget("Social_Media");'));

/**
 * 图片公告
 *
 */
class Widget_Picture extends WP_Widget {
	function __construct() {
		$widget_ops = array(
			'classname' => 'widget_picture',
			'description' => __( 'Display the pictures on your website.' ),
			'customize_selective_refresh' => true
		);
  parent::__construct( 'picture', __( '图片公告' ), $widget_ops);
	}

	function widget( $args, $instance ) {

		/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		$widget_picture = ! empty( $instance['picture'] ) ? $instance['picture'] : '';
    $widget_link    = ! empty( $instance['link'] ) ? $instance['link'] : '';
    $widget_text    = ! empty( $instance['text'] ) ? $instance['text'] : '';
		/**
		 * Filter the content of the picture widget.
		 *
		 */
		$picture = apply_filters( 'widget_picture', $widget_picture, $instance, $this );
    $link    = apply_filters( 'widget_link', $widget_link, $instance, $this );
    $text    = apply_filters( 'widget_text', $widget_text, $instance, $this );

		echo $args['before_widget'];
    if ( ! empty( $picture ) ) {
      echo '<img class="img-rounded" src="' . $picture . '" alt="'.$title.'">';
    }
    if ( ! empty( $title ) ) {
  		echo '<div class="card-img-overlay card-inverse"><h4 class="card-title text-center">' . $title . '</h4>';
  	}
    if ( ! empty( $text ) ) {
      echo '<p class="card-title">' . $text . '</p>';
    }
    echo "</div>";
		echo $args['after_widget'];
	}

	/**
	 * Handles updating settings for the current Text widget instance.
	 *
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title']   = sanitize_text_field( $new_instance['title'] );
    $instance['link']    =  $new_instance['link'];
    $instance['picture'] =  $new_instance['picture'];
    $instance['text']    =  $new_instance['text'] ;
		return $instance;
	}

	/**
	 * Outputs the picture widget settings form.
	 *
	 */
	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'link' =>'', 'picture' =>'', 'text' => '' ) );
		$title = sanitize_text_field( $instance['title'] );
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>

    <p><label for="<?php echo $this->get_field_id( 'picture' ); ?>"><?php _e( '图片路径:' ); ?></label>
    <input class="widefat" rows="3" id="<?php echo $this->get_field_id('picture'); ?>" name="<?php echo $this->get_field_name('picture'); ?>" type="text" value="<?php echo esc_attr( $instance['picture'] ); ?>"></p>

    <p><label for="<?php echo $this->get_field_id( 'text' ); ?>"><?php _e( 'Content:' ); ?></label>
		<textarea class="widefat" rows="3" id="<?php echo $this->get_field_id('text'); ?>" name="<?php echo $this->get_field_name('text'); ?>"><?php echo esc_textarea( $instance['text'] ); ?></textarea></p>
		<?php
	}
}
add_action('widgets_init', create_function('', 'return register_widget("Widget_Picture");'));
//近期文章

class Recent_Posts extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'classname' => 'widget_recent_entries',
			'description' => __( 'Your site&#8217;s most recent Posts.' ),
			'customize_selective_refresh' => true,
		);
		parent::__construct( 'recent-posts', __( 'Recent Posts' ), $widget_ops );
		$this->alt_option_name = 'widget_recent_entries';
	}
  function widget( $args, $instance ) {
		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}

		$title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : __( 'Recent Posts' );

		/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 5;
		if ( ! $number )
			$number = 5;
		$show_date = isset( $instance['show_date'] ) ? $instance['show_date'] : false;

		$r = new WP_Query( apply_filters( 'widget_posts_args', array(
			'posts_per_page'      => $number,
			'no_found_rows'       => true,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true
		) ) );

		if ($r->have_posts()) :
		?>
		<?php echo $args['before_widget']; ?>
		<?php if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		} ?>
		<ul>
		<?php while ( $r->have_posts() ) : $r->the_post(); ?>
			<li class="widget-post">
        <?php lerm_thumbnail() ?>
				<a href="<?php the_permalink(); ?>"><?php get_the_title() ? the_title() : the_ID(); ?></a>
			<?php if ( $show_date ) : ?>
				<span class="post-date"><?php echo get_the_date(); ?></span>
			<?php endif; ?>
			</li>
		<?php endwhile; ?>
		</ul>
		<?php echo $args['after_widget']; ?>
		<?php
		// Reset the global $the_post as this query will have stomped on it
		wp_reset_postdata();

		endif;
	}


	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['number'] = (int) $new_instance['number'];
		$instance['show_date'] = isset( $new_instance['show_date'] ) ? (bool) $new_instance['show_date'] : false;
		return $instance;
	}

	function form( $instance ) {
		$title     = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number    = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		$show_date = isset( $instance['show_date'] ) ? (bool) $instance['show_date'] : false;
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of posts to show:' ); ?></label>
		<input class="tiny-text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" step="1" min="1" value="<?php echo $number; ?>" size="3" /></p>

		<p><input class="checkbox" type="checkbox"<?php checked( $show_date ); ?> id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php _e( 'Display post date?' ); ?></label></p>
<?php
	}
}
add_action('widgets_init', create_function('', 'return register_widget("Recent_Posts");'));
/**
 * 热门文章
 */
class Popular_Post extends WP_Widget{
  function __construct(){
    $widget_ops  = array(
      'classname' => 'widget_popular_post',
      'description' => __('Display the popular posts on your website.')
    );
    parent::__construct('popular-posts', __('热门文章'), $widget_ops);
  }
  function widget($args, $instance){
    extract($args);

    $title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : __( '热门文章' );
    $title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

    $number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 5;
		if ( ! $number )
			$number = 5;
      $query = new WP_Query( apply_filters( 'widget_posts_args', array(
        'posts_per_page'      => $number,
        'orderby'             => 'comment_count',
        'ignore_sticky_posts' => true
      ) ) );

    if ($query->have_posts()) :
      echo $args['before_widget'];

      if ($title) {
        echo $args['before_title'] . $title . $args['after_title'];
      }?>

      <ul>
        <?php while ( $query->have_posts() ) : $query->the_post(); ?>

          <li class='widget-post'>
            <?php lerm_thumbnail() ?>
            <a href="<?php the_permalink(); ?>"><?php get_the_title() ? the_title() : the_ID(); ?></a>
          </li>

        <?php endwhile; ?>
      </ul>

      <?php echo $args['after_widget'];?>

    <?php
    wp_reset_postdata();
    endif;
  }
  function update($new_instance, $old_instance){
    $instance             = $old_instance;
    $instance['title']    = strip_tags($new_instance['title']);
    $instance['show_num'] = strip_tags($new_instance['show_num']);
    return $instance;
  }
  function form($instance){
    $title  = isset($instance['title']) ? esc_attr( $instance['title'] ) : '';
    $number = isset($instance['number']) ? absint($instance['number']) : 5;?>
    <p><label for="<?php echo $this->get_field_id('title');?>"><?php _e('Title:');?></label>
    <input class="widefat" id="<?php echo $this->get_field_id('title');?>" name="<?php echo $this->get_field_name('title');?>" type="text" value="<?php echo esc_attr($title);?>" /></p>
    <p><label for="<?php echo $this->get_field_id('show_num');?>"><?php _e('Number of posts to show:');?></label>
    <input class="tiny-text" id="<?php echo $this->get_field_id('number');?>" name="<?php echo $this->get_field_name('number');?>" type="number" step="1" min="1" value="<?php echo $number;?>" size="3" /></p>
    <?php
  }
}
add_action('widgets_init', create_function('', 'return register_widget("Popular_Post");'));
