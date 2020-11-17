<?php
if ( ! defined( 'ABSPATH' ) ) {
	die; }
function lerm_post_meta( $location ) {
	if ( 'single_top' === $location ) {
		$arg           = array_keys( (array) lerm_options( 'single_top', 'enabled' ) );
		$align_classes = 'justify-content-center';
	}

	if ( 'summary_bottom' === $location ) {
		$arg           = array_keys( (array) lerm_options( 'summary_meta', 'enabled' ) );
		$align_classes = ' justify-content-center justify-content-sm-start ';
	}
	$post_meta = apply_filters( 'post_meta_show_on_post', $arg );

	if ( $post_meta ) {?>
		<ul class="list-unstyled mb-0 d-flex <?php echo esc_html( $align_classes ); ?> entry-meta small text-muted" style="z-index:1">
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
				<span class="screen-reader-text"><?php esc_html_e( 'Post author', 'lerm' ); ?></span>
				<i class="fa fa-user pr-1"></i>
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
				<span class="screen-reader-text"><?php esc_html_e( 'Categories', 'lerm' ); ?></span>
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
	$classes[] = lerm_site_layout();
	return $classes;
}
add_filter( 'body_class', 'lerm_body_classes' );

function lerm_post_class( $classes ) {
	// $classes[] = 'card';

	if ( ! is_singular() ) {
		$classes[] = 'summary';
		$classes[] = 'mb-3 p-0 p-md-3';
		if ( lerm_options( 'loading-animate' ) ) {
			$classes[] = 'loading-animate';
			$classes[] = 'fadeInUp';
		}
	} else {
		$classes[] = 'entry';
		$classes[] = ' p-3 mb-2';
	}
	return $classes;
}
add_filter( 'post_class', 'lerm_post_class' );

/**
 * Share icon template
 * 
 * @since lerm 3.0.0
 */
function social_icons( $icons = array() ) {
	if ( $icons ) { ?>
		<div class="social-share d-flex justify-content-center" data-initialized="true">

			<?php foreach ( $icons as $icon ) { ?>
				<a href="#" class="social-share-icon icon-<?php echo $icon; ?> btn-light btn-sm "><i class="fa fa-<?php echo  $icon; ?>"></i></a>
				<?php
			} ?>
		
		</div>
		<?php
	}
}
