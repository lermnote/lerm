<?php // phpcs:disable WordPress.Files.FileName
/**
 * Post Tags
 *
 * @package Lerm https://lerm.net
 *
 * @since lerm 3.0
 */

namespace Lerm\Inc\Core;

use Lerm\Inc\Traits\Singleton;

class Tags {

	use singleton;

	// Default arguments
	protected static $args = array(
		'location' => '',
		'class'    => '',
	);

	/**
	 * Constructor
	 *
	 * @param array $params Optional parameters.
	 */
	public function __construct( $params ) {
		self::$args = apply_filters( 'lerm_tags_args', wp_parse_args( $params, self::$args ) );

	}

	/**
	 * Display post metadata
	 *
	 * @param array $metas Metadata to display.
	 */
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

	/**
	 * Display post format
	 */
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

	/**
	 * Display post author
	 */
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

	/**
	 * Display publish date
	 */
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

	/**
	 * Display post categories
	 */
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

	/**
	 * Display the page views of the current post.
	 */
	public static function read() {
		global $post;

		// Ensure we have a valid post object with an ID
		if ( ! isset( $post->ID ) ) {
			return;
		}

		$post_ID       = $post->ID;
		$transient_key = 'pageviews_' . $post_ID;
		$views         = get_transient( $transient_key );

		if ( false === $views ) {
			$views = (int) get_post_meta( $post_ID, 'pageviews', true );
			$views = max( 0, $views );
		}

		$formatted_views = number_format( $views );

		if ( '' !== $formatted_views ) {
			?>
			<i class="fa fa-eye pe-1"></i>
			<span><?php echo esc_html( $formatted_views ); ?></span>
			<?php
		}
	}

	/**
	 * Display comments link
	 */
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

	/**
	 * Display edit post link
	 */
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
