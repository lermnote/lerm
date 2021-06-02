<?php
/**
 * Recent comments
 *
 * @since 1.0.0
 */

namespace Lerm\Inc\Widgets;

use WP_Widget;

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
		global $comment;
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
					'<a href="%s" class="vcard d-block" rel="external nofollow">%s<strong class="hidden-md-down comment_author">%s</strong><time class="float-end" datetime="%s" title="%s">' . esc_html__( '%s ago', 'lerm' ) . '</time></a>',
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
				$output .= '<li class="recentcomment">' . $avatar . '<span class="comment-content small">' . $content . '</span></li>';
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
<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'lerm' ); ?></label>
	<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>
<p><label
		for="<?php echo $this->get_field_id( 'number' ); ?>"><?php esc_html_e( 'Number of comments to show:', 'lerm' ); ?></label>
	<input class="tiny-text"
		id="<?php echo $this->get_field_id( 'number' ); ?>"
		name="<?php echo $this->get_field_name( 'number' ); ?>"
		type="number" step="1" min="1" value="<?php echo $number; ?>"
		size="3" /></p>
		<?php
	}
}
