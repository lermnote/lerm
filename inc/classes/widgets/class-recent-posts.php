<?php
/**
 * Recent Posts
 *
 * @since 1.0.0
 */

namespace Lerm\Inc\Widgets;

use WP_Widget;
use WP_Query;

class Recent_Posts extends WP_Widget {

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
					<?php if ( null !== lerm_post_image() ) : ?>
						<div class="col-md-4 pe-2">
							<?php get_template_part( 'template-parts/content/featured-image' ); ?>
						</div>
					<?php endif; ?>
					<div class="<?php echo lerm_post_image() ? 'col-md-8' : 'col-md-12'; ?> d-flex flex-column justify-content-between pe-0">
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
		<p><label
				for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'lerm' ); ?></label>
			<input class="widefat"
				id="<?php echo $this->get_field_id( 'title' ); ?>"
				name="<?php echo $this->get_field_name( 'title' ); ?>"
				type="text" value="<?php echo $title; ?>" /></p>

		<p><label
				for="<?php echo $this->get_field_id( 'number' ); ?>"><?php esc_html_e( 'Number of posts to show:', 'lerm' ); ?></label>
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
