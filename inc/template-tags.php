<?php if ( ! defined( 'ABSPATH' ) ) {
	die;}
function lerm_post_meta( $location ) {
	if ( 'single_top' === $location ) {
		$arg = array_keys( (array) lerm_options( 'single_top', 'enabled' ) );
	}

	if ( 'summary_bottom' === $location ) {
		$arg = array_keys( (array) lerm_options( 'summary_meta', 'enabled' ) );
	}
	$post_meta = apply_filters( 'post_meta_show_on_post', $arg );

	if ( $post_meta ) {?>
		<ul class="list-unstyled mb-0 d-flex justify-content-center justify-content-md-start entry-meta small text-muted">
			<?php
			foreach ( $post_meta as $item ) {
				switch ( $item ) {
					case 'format':
						lerm_post_format();
						break;
					case 'publish_date':
						lerm_post_date();
						break;
					case 'categories':
						lerm_post_categories();
						break;
					case 'read':
						lerm_post_views_number();
						break;
					case 'comment':
						lerm_post_comments_number();
						break;
					case 'author':
						lerm_post_author();
						break;
				}
			}
			?>
		</ul>
		<?php
	}
}

function lerm_post_meta_list( $location = null ) {
	if ( 'single_top' === $location ) {
		$arg = array_keys( (array) lerm_options( 'single_top', 'enabled' ) );
	}

	if ( 'summary_bottom' === $location ) {
		$arg = array_keys( (array) lerm_options( 'summary_meta', 'enabled' ) );
	}
	$post_meta = apply_filters( 'post_meta_show_on_post', $arg );
	return $post_meta;
}

function lerm_post_format() {
	$format = get_post_format();
	if ( current_theme_supports( 'post-formats', $format ) ) {
		?>
		<li class="entry-format meta-item">
			<span>
				<span class="screen-reader-text"><?php _x( 'Format', 'Used before post format.', 'lerm' ); ?></span>
			</span>
			<span class="meta-text">
				<a href="<?php echo esc_url( get_post_format_link( $format ) ); ?>" class="entry-format-link"><?php echo esc_html( get_post_format_string( $format ) ); ?></a>
			</span>
		</li>
		<?php
	}
}

function lerm_post_author() {
	?>
		<li class="post-author meta-item">
			<span class="meta-icon">
				<span class="screen-reader-text"><?php esc_attr_e( 'Post author', 'lerm' ); ?></span>
				<i class="fa fa-user pr-1"></i>
			</span>
			<span class="meta-text">
				<?php
				printf(
					/* translators: %s: Author name */
					__( 'By %s', 'lerm' ),
					'<a href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author_meta( 'display_name' ) ) . '</a>'
				);
				?>
			</span>
		</li>
	<?php
}

function lerm_post_date() {
	?>
		<li class="post-date meta-item">
			<span>
				<i class="fa fa-calendar pr-1"></i>
				<a href="<?php the_permalink(); ?>">
					<?php the_time( get_option( 'date_format' ) ); ?>
				</a>
			</span>
		</li>
		<?php
}

function lerm_post_categories() {
	if ( has_category() ) {
		?>
		<li class="post-categories meta-item">
			<span class="meta-icon">
				<span class="screen-reader-text"><?php esc_attr_e( 'Categories', 'lerm' ); ?></span>
				<i class="fa fa-hdd pr-1"></i>
			</span>
			<span class="meta-text">
				<?php the_category( ', ' ); ?>
			</span>
		</li>
		<?php
	}
}

function lerm_post_views_number() {
	?>
	<li  class="post-views meta-item">
		<i class="fa fa-eye pr-1"></i>
		<span>
			<?php echo esc_html( post_views( '' ) ); ?>
		</span>
	</li>
	<?php
}

function lerm_post_comments_number() {
	if ( ! post_password_required() && ( comments_open() || get_comments_number() ) ) {
		?>
		<li  class="comments-link meta-item">
			<a href="<?php comments_link(); ?>">
				<i class="fa fa-comment pr-1"></i>
				<?php
				/* translators: %s: number of comments  */
				printf( _nx( '%s comment', '%s comments', get_comments_number(), 'comments title', 'lerm' ), esc_attr( number_format_i18n( get_comments_number() ) ) );
				?>
			</a>
		</li>
		  <?php
	}
}

function lerm_edit_link() {
	edit_post_link(
		sprintf(
			/* translators: %s: Name of current post */
			__( 'Edit<span class="screen-reader-text"> "%s"</span>', 'lerm' ),
			get_the_title()
		),
		'<span class="edit-link meta-item"><i class="fa fa-edit pr-1 pl-2"></i>',
		'</span>'
	);
}

/**
 * Displays the optional excerpt.
 *
 * @since Lerm 2.0
 */
function lerm_excerpt_length( $length ) {
	$length = lerm_options( 'excerpt_length' );
	return $length;
}
add_filter( 'excerpt_length', 'lerm_excerpt_length', 999 );
/**
 * Wether to show sidebar in webpage.
 *
 * @return string $layout
 */
function lerm_page_layout() {
	// page and post layout
	$metabox = get_post_meta( get_the_ID(), '_lerm_metabox_options', true );
	// global layout
	$layout = lerm_options( 'global_layout' );
	if ( wp_is_mobile() ) {
		$layout = 'mobile';
	}
	if ( is_singular() && ! empty( $metabox['page_layout'] ) ) {
		$layout = $metabox['page_layout'];
	}
	return $layout;
}

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array $classes[]
 */
function lerm_body_classes( $classes ) {
	$classes[] = 'body-bg';
	// Check singular
	if ( is_singular() ) {
		$classes[] = 'singular';
	}
	// Check for post thumbnail.
	if ( is_singular() && has_post_thumbnail() ) {
		$classes[] = 'has-post-thumbnail';
	}
	// Add class on front page.
	if ( is_front_page() && 'posts' !== get_option( 'show_on_front' ) ) {
		$classes[] = 'lerm-front-page';
	}
	// Output layout
	$classes[] = lerm_page_layout();
	return $classes;
}
add_filter( 'body_class', 'lerm_body_classes' );

function lerm_post_class( $classes ) {
	if ( ! is_singular() ) {
		$classes[] = 'summary card mx-3 mx-md-0 mb-3 p-0 p-md-3';
	}
	return $classes;
}
add_filter( 'post_class', 'lerm_post_class' );
