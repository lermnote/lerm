<?php
if ( ! defined( 'ABSPATH' ) ) {
	die; }
function lerm_post_meta( $location ) {
	$meta_settings = array(
		'single_top'     => array(
			'option' => 'single_top',
			'class'  => 'justify-content-center  mb-0 ',
		),
		'single_bottom'  => array(
			'option' => 'single_bottom',
			'class'  => 'justify-content-between mb-1',
		),
		'summary_bottom' => array(
			'option' => 'summary_meta',
			'class'  => ' justify-content-center justify-content-sm-start mb-0 ',
		),
	);

	$arg     = array();
	$classes = '';
	if ( isset( $meta_settings[ $location ] ) && is_singular() ) {
		$arg     = array_keys( (array) lerm_options( $meta_settings[ $location ]['option'], 'enabled' ) );
		$classes = $meta_settings[ $location ]['class'];
	}

	$post_meta = apply_filters( 'post_meta_show_on_post', $arg );

	if ( isset( $post_meta[0] ) && 'disabled' !== $post_meta[0] ) {
		$post_meta_items = array(
			'format'       => 'lerm_post_format',
			'publish_date' => 'lerm_post_date',
			'categories'   => 'lerm_post_categories',
			'read'         => 'lerm_post_views_number',
			'comment'      => 'lerm_post_comments_number',
			'author'       => 'lerm_post_author',
		);

		?>
		<ul class="list-unstyled d-flex <?php echo esc_html( $classes ); ?> entry-meta small text-muted">
			<?php
			foreach ( $post_meta as $item ) {
				if ( isset( $post_meta_items[ $item ] ) ) {
					$post_meta_items[ $item ]();
				}
			}
			?>
		</ul>
		<?php
	}
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
		</li>
	<?php
}

function lerm_post_date() {
	?>
		<li class="post-date meta-item">
			<span>
				<i class="fa fa-calendar pe-1"></i>
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
				<i class="fa fa-hdd pe-1"></i>
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
		<i class="fa fa-eye pe-1"></i>
		<span>
			<?php echo esc_html( lerm_post_views( '' ) ); ?>
		</span>
	</li>
	<?php
}

function lerm_post_comments_number() {
	if ( ! post_password_required() && ( comments_open() || get_comments_number() ) ) {
		?>
		<li  class="comments-link meta-item">
			<a href="<?php comments_link(); ?>">
				<i class="fa fa-comment pe-1"></i>
				<?php
				/* translators: %s: number of comments  */
				printf( _nx( '%s comment', '%s comments', esc_attr( get_comments_number() ), 'comments title', 'lerm' ), esc_attr( number_format_i18n( get_comments_number() ) ) );
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
		'<span class="edit-link meta-item"><i class="fa fa-edit pe-1 ps-2"></i>',
		'</span>'
	);
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
	if ( is_single() || is_page() ) {
		$classes[] = 'singular';
		if ( has_post_thumbnail() ) {
			$classes[] = 'has-post-thumbnail';
		}
	}
	// Add class on front page.
	if ( is_front_page() && 'posts' !== get_option( 'show_on_front' ) ) {
		$lerm_front_page = get_option( 'page_on_front' );
		if ( $lerm_front_page && is_page( $lerm_front_page ) ) {
			$classes[] = 'lerm-front-page';
		}
	}
	// Output layout
	$classes[]    = lerm_site_layout();
	$layout_style = lerm_options( 'layout_style' );
	if ( $layout_style ) {
		$classes[] = $layout_style;
	}
	return $classes;
}
add_filter( 'body_class', 'lerm_body_classes' );

// add CSS class in WordPress post list and single page
function lerm_post_class( $classes ) {
	$loading_animate = lerm_options( 'loading-animate' );

	if ( ! is_singular() ) {
		$classes[] = implode( ' ', array( 'entry', 'p-3', 'mb-2' ) );
	} else {
		$classes[] = implode( ' ', array( 'summary', 'mb-3', 'p-0', 'p-md-3' ) );
	}

	if ( $loading_animate ) {
		$classes[] = implode( ' ', array( 'loading-animate', 'fadeIn' ) );
	}

	return $classes;
}
add_filter( 'post_class', 'lerm_post_class' );

/**
 * Share icon template
 *
 * @since lerm 3.0.0
 */
function lerm_social_icons( $icons = array( 'weibo', 'wechat', 'qq' ) ) {
	if ( ! empty( $icons ) && is_array( $icons ) ) {
		?>
		<div class="social-share d-flex justify-content-center" data-initialized="true">
			<?php foreach ( $icons as &$icon ) : ?>
				<a href="#" class="social-share-icon icon-<?php echo esc_attr( $icon ); ?> btn-light btn-sm">
					<i class="fa fa-<?php echo esc_attr( $icon ); ?>"></i>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
