<?php // phpcs:disable WordPress.Files.FileName
/**
 * Post Tags
 *
 * @package Lerm https://lerm.net
 *
 * @since lerm 3.0
 */

namespace Lerm\Inc;

use Lerm\Inc\Traits\Singleton;

class Tags {

	// Instance
	use singleton;

	public static $args = array(
		'location' => '',
		'class'    => '',
	);

	public function __construct( $params ) {
		self::$args = apply_filters( 'lerm_tags_', wp_parse_args( $params, self::$args ) );

	}
	public static function post_meta( $metas ) {

		$post_meta = apply_filters( 'post_meta_show_on_post', $metas );

		if ( ! empty( $post_meta ) && 'disabled' !== $post_meta[0] ) {
			?>
			<ul class="list-unstyled d-flex entry-meta small text-muted <?php echo esc_attr( self::$args['class'] ); ?> ">
				<?php
				foreach ( $post_meta as $item ) {
					if ( method_exists( __CLASS__, $item ) ) {
						?>
						<li class="meta-item">
							<?php call_user_func( array( __CLASS__, $item ) ); ?>
						</li>
						<?php
					}
				}
				?>
			</ul>
			<?php
		}
	}

	public static function format() {
		$format = get_post_format();
		if ( $format && current_theme_supports( 'post-formats', $format ) ) {
			?>
			<span>
				<span class="screen-reader-text"><?php _x( 'Format', 'Used before post format.', 'lerm' ); ?></span>
			</span>
			<span class="meta-text">
				<a href="<?php echo esc_url( get_post_format_link( $format ) ); ?>" class="entry-format-link"><?php echo esc_html( get_post_format_string( $format ) ); ?></a>
			</span>
			<?php
		}
	}

	public static function author() {
		?>
		<span class="meta-icon">
			<span class="screen-reader-text"><?php esc_html_e( 'Post author', 'lerm' ); ?></span>
			<i class="fa fa-user pe-1"></i>
		</span>
		<span class="meta-text">
			<?php
			printf(
				/* translators: %s: Author name */
				esc_html__( 'By %s', 'lerm' ),
				'<a href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author_meta( 'display_name' ) ) . '</a>'
			);
			?>
		</span>
		<?php
	}

	public static function publish_date() {
		?>
		<span>
			<i class="fa fa-calendar pe-1"></i>
			<a href="<?php the_permalink(); ?>">
				<?php the_time( get_option( 'date_format' ) ); ?>
			</a>
		</span>
		<?php
	}

	public static function categories() {
		$categories = get_the_category_list( ', ' );
		if ( $categories ) {
			?>
			<span class="meta-icon">
				<span class="screen-reader-text"><?php esc_html_e( 'Categories', 'lerm' ); ?></span>
				<i class="fa fa-hdd pe-1"></i>
			</span>
			<span class="meta-text">
				<?php echo wp_kses_post( $categories ); ?>
			</span>
			<?php
		}
	}
	public static function read() {
		$views = lerm_post_views( '' );
		if ( '' !== $views ) {
			?>
			<i class="fa fa-eye pe-1"></i>
			<span><?php echo esc_html( $views ); ?></span>
			<?php
		}
	}

	public static function responses() {
		if ( ! post_password_required() && ( comments_open() || get_comments_number() ) ) {
			?>
			<a href="<?php comments_link(); ?>">
				<i class="fa fa-comment pe-1"></i>
				<?php
				$comments_number = get_comments_number_text( esc_attr( get_comments_number() ) );
				echo esc_html( $comments_number );
				?>
			</a>
			<?php
		}
	}


	public static function edit_link() {
		edit_post_link(
			sprintf(
				/* translators: %s: Name of current post */
				esc_html__( 'Edit<span class="screen-reader-text"> "%s"</span>', 'lerm' ),
				get_the_title()
			),
			'<span class="edit-link meta-item"><i class="fa fa-edit pe-1 ps-2"></i>',
			'</span>'
		);
	}
}
