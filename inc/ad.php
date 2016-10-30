<?php
/**
 * 侧边栏广告
 *
 */
class Widget_Ad extends WP_Widget {
	function __construct() {
		$widget_ops = array(
			'classname' => 'widget_ad',
			'description' => __( 'Display the ads on your website.' ),
			'customize_selective_refresh' => true
		);
  parent::__construct( 'ad', __( '广告' ), $widget_ops);
	}

	function widget( $args, $instance ) {

		/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		$widget_ad = ! empty( $instance['ad'] ) ? $instance['ad'] : '';
		/**
		 * Filter the content of the ad widget.
		 *
		 */
		$ad = apply_filters( 'widget_ad', $widget_ad, $instance, $this );

		echo $args['before_widget'];
    if ( ! empty( $ad ) ) {
      echo $ad;
    }
    if ( ! empty( $title ) ) {
  		echo '<div class="card-img-overlay card-inverse"><h4 class="card-title text-center">' . $title . '</h4></div>';
  	}
		echo '</aside>';
	}

	/**
	 * Handles updating settings for the current Text widget instance.
	 *
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title']   = sanitize_text_field( $new_instance['title'] );
    $instance['ad'] =  $new_instance['ad'];

		return $instance;
	}

	/**
	 * Outputs the ad widget settings form.
	 *
	 */
	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'ad' =>'' ) );
		$title = sanitize_text_field( $instance['title'] );
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>

    <p><label for="<?php echo $this->get_field_id( 'ad' ); ?>"><?php _e( '路径:' ); ?></label>
    <textarea class="widefat" rows="3" id="<?php echo $this->get_field_id('ad'); ?>" name="<?php echo $this->get_field_name('ad'); ?>" type="text"><?php echo esc_attr( $instance['ad'] ); ?></textarea></p>

		<?php
	}
}
add_action('widgets_init', create_function('', 'return register_widget("Widget_ad");'));
