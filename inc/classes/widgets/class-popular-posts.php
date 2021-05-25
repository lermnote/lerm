<?php
/**
 * Popular posts
 *
 * @since 1.0.0
 */
namespace Lerm\Inc\Widgets;

use WP_Widget;
use WP_Query;

class Popular_Posts extends WP_Widget {

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
		<div class="col-md-4 p-0">
					<?php
					lerm_thumb_nail(
						array(
							'classes' => 'widget-thumbnail',
							'height'  => '60',
							'width'   => '100',
						)
					);
					?>
		</div>
		<div class="col-md-8 pe-0 d-flex flex-column justify-content-between">
			<a href="<?php the_permalink(); ?>"><?php get_the_title() ? the_title() : the_ID(); ?></a>
					<?php if ( $show_date ) : ?>
			<span class="post-date text-muted small"><?php echo get_the_date(); ?></span>
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
<p><label
		for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'lerm' ); ?></label>
	<input class="widefat"
		id="<?php echo $this->get_field_id( 'title' ); ?>"
		name="<?php echo $this->get_field_name( 'title' ); ?>"
		type="text" value="<?php echo esc_attr( $title ); ?>" /></p>

<p><label
		for="<?php echo $this->get_field_id( 'show_num' ); ?>"><?php esc_html_e( 'Number of posts to show:', 'lerm' ); ?></label>
	<input class="tiny-text"
		id="<?php echo $this->get_field_id( 'number' ); ?>"
		name="<?php echo $this->get_field_name( 'number' ); ?>"
		type="number" step="1" min="1" value="<?php echo $number; ?>"
		size="3" /></p>
<p><input class="checkbox" type="checkbox" <?php checked( $show_date ); ?> id="<?php echo $this->get_field_id( 'show_date' ); ?>"
	name="<?php echo $this->get_field_name( 'show_date' ); ?>"
	/>
	<label
		for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php esc_html_e( 'Display post date?', 'lerm' ); ?></label>
</p>
		<?php
	}
}