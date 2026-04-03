<?php // phpcs:disable WordPress.Files.FileName
/**
 * Post Tags / Post Meta (optimized)
 *
 * @package Lerm https://lerm.net
 *
 * @since lerm 3.0
 */

declare(strict_types=1);

namespace Lerm\View;

use Lerm\Http\Rest\Repository\ViewsRepository;
use Lerm\Traits\Singleton;

class PostMeta {

	use Singleton;

	/**
	 * Default arguments.
	 *
	 * @var array
	 */
	protected static array $args = array(
		'location' => '',
		'class'    => '',
	);

	/**
	 * Constructor.
	 *
	 * @param array $params Optional parameters.
	 */
	public function __construct( array $params = array() ) {
		self::$args = (array) apply_filters( 'lerm_tags_args', wp_parse_args( $params, self::$args ) );
	}

	/**
	 * Output post metadata list.
	 *
	 * @param array $metas Metadata to display.
	 * @param string $css_class Optional CSS classes to add.
	 * @return void
	 */
	public static function post_meta( array $metas = array(), string $css_class = '' ): void {
		$post_meta = (array) apply_filters( 'post_meta_show_on_post', $metas );

		// if nothing to show or explicitly disabled, return early
		if ( empty( $post_meta ) || 'disabled' === ( $post_meta[0] ?? '' ) ) {
			return;
		}

		$classes = trim( 'list-unstyled d-flex entry-meta small text-muted ' . (string) self::$args['class'] . ' ' . $css_class );

		echo '<ul class="' . esc_attr( $classes ) . '">';

		foreach ( $post_meta as $item ) {
			if ( is_string( $item ) && is_callable( array( __CLASS__, $item ) ) ) {
				echo '<li class="meta-item">';
				call_user_func( array( __CLASS__, $item ) );
				echo '</li>';
			}
		}

		echo '</ul>';
	}

	/**
	 * Display post format.
	 *
	 * @return void
	 */
	public static function format(): void {
		$format = get_post_format();
		if ( $format && current_theme_supports( 'post-formats', $format ) ) {
			$format_link = get_post_format_link( $format );
			$format_name = get_post_format_string( $format );
			?>
			<span>
				<span class="screen-reader-text"><?php esc_html_e( 'Format', 'lerm' ); ?></span>
			</span>
			<span class="meta-text">
				<a href="<?php echo esc_url( $format_link ); ?>" class="entry-format-link"><?php echo esc_html( $format_name ); ?></a>
			</span>
			<?php
		}
	}

	/**
	 * Display post author.
	 *
	 * @return void
	 */
	public static function author(): void {
		$author_id   = get_the_author_meta( 'ID' );
		$author_name = get_the_author_meta( 'display_name' );
		$author_url  = get_author_posts_url( $author_id );

		$author_link = '<a href="' . esc_url( $author_url ) . '">' . esc_html( $author_name ) . '</a>';
		?>
		<span class="meta-icon">
			<span class="screen-reader-text"><?php esc_html_e( 'Post author', 'lerm' ); ?></span>
			<i class="fa fa-user pe-1"></i>
		</span>
		<span class="meta-text">
			<?php
			// $author_link contains safe HTML (anchor) built with esc_url() and esc_html().
			// Allow the anchor tag while escaping everything else.
			echo wp_kses(
				/* translators: %s: Author link */
				sprintf( __( 'By %s', 'lerm' ), $author_link ),
				array(
					'a'    => array(
						'href'  => array(),
						'title' => array(),
						'rel'   => array(),
						'class' => array(),
					),
					'span' => array(
						'class' => array(),
					),
				)
			);
			?>
		</span>
		<?php
	}

	/**
	 * Display publish date.
	 *
	 * @return void
	 */
	public static function publish_date(): void {
		$permalink = get_permalink();
		$date      = get_the_date(); // returns formatted date
		?>
		<span>
			<i class="fa fa-calendar pe-1"></i>
			<a href="<?php echo esc_url( $permalink ); ?>">
				<?php echo esc_html( $date ); ?>
			</a>
		</span>
		<?php
	}

	/**
	 * Display post categories.
	 *
	 * @return void
	 */
	public static function categories(): void {
		$categories = get_the_category_list( ', ' );
		if ( $categories ) {
			?>
			<span class="meta-icon">
				<span class="screen-reader-text"><?php esc_html_e( 'Categories', 'lerm' ); ?></span>
				<i class="fa fa-folder pe-1"></i>
			</span>
			<span class="meta-text">
				<?php echo wp_kses_post( $categories ); ?>
			</span>
			<?php
		}
	}

	/**
	 * Display the page views of the current post.
	 *
	 * @return void
	 */
	public static function read(): void {
		global $post;
		$template_options = function_exists( 'lerm_get_template_options' ) ? \lerm_get_template_options() : array();

		if ( empty( $post->ID ) || empty( $template_options['post_views_enable'] ) ) {
			return;
		}

		$post_ID = (int) $post->ID;
		$views   = max( 0, ViewsRepository::get_count( $post_ID ) );

		$formatted_views = number_format_i18n( $views );

		// show only when we have a number (can be 0)
		if ( '' !== $formatted_views ) {
			?>
			<i class="fa fa-eye pe-1"></i>
			<span class="js-post-views-count" data-post-id="<?php echo esc_attr( (string) $post_ID ); ?>">
				<?php echo esc_html( $formatted_views ); ?>
			</span>
			<?php
		}
	}

	/**
	 * Display comments link and count text.
	 *
	 * @return void
	 */
	public static function responses(): void {
		if ( post_password_required() ) {
			return;
		}

		if ( comments_open() || get_comments_number() ) {
			$comments_link   = get_comments_link();
			$comments_number = get_comments_number_text( esc_attr( get_comments_number() ) );
			?>
			<a href="<?php echo esc_url( $comments_link ); ?>">
				<i class="fa fa-comment pe-1"></i>
				<?php echo esc_html( $comments_number ); ?>
			</a>
			<?php
		}
	}

	/**
	 * Display edit post link for users with permission.
	 *
	 * @return void
	 */
	public static function edit_link(): void {
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
