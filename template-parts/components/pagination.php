<?php
/**
 * Pagination for archive pages.
 *
 * @package Lerm https://lerm.net
 *
 * @since 3.0.0
 */

$default_posts_per_page = (int) get_option( 'posts_per_page', 10 );
$posts_per_page         = absint( $wp_query->get( 'posts_per_page' ) ) ? absint( $wp_query->get( 'posts_per_page' ) ) : $default_posts_per_page;
$archive_data           = array(
	'cat'         => absint( $wp_query->get( 'cat' ) ) ? absint( $wp_query->get( 'cat' ) ) : null,
	'tag_id'      => absint( $wp_query->get( 'tag_id' ) ) ? absint( $wp_query->get( 'tag_id' ) ) : null,
	'author'      => absint( $wp_query->get( 'author' ) ) ? absint( $wp_query->get( 'author' ) ) : null,
	'post_parent' => absint( $wp_query->get( 'post_parent' ) ) ? absint( $wp_query->get( 'post_parent' ) ) : null,
	'year'        => absint( $wp_query->get( 'year' ) ) ? absint( $wp_query->get( 'year' ) ) : null,
	'monthnum'    => absint( $wp_query->get( 'monthnum' ) ) ? absint( $wp_query->get( 'monthnum' ) ) : null,
	'day'         => absint( $wp_query->get( 'day' ) ) ? absint( $wp_query->get( 'day' ) ) : null,
);

foreach ( array( 'category_name', 'tag', 'taxonomy', 'term', 'author_name', 'name', 's' ) as $key ) {
	$value = $wp_query->get( $key );
	if ( is_scalar( $value ) && '' !== (string) $value ) {
		$archive_data[ $key ] = (string) $value;
	}
}

$_post_type = $wp_query->get( 'post_type' );
if ( is_scalar( $_post_type ) && '' !== (string) $_post_type && 'post' !== $_post_type ) {
	$archive_data['post_type'] = (string) $_post_type;
}

$_orderby = $wp_query->get( 'orderby' );
if ( is_scalar( $_orderby ) && '' !== (string) $_orderby && 'date' !== $_orderby ) {
	$archive_data['orderby'] = (string) $_orderby;
}

$_order = strtoupper( (string) $wp_query->get( 'order' ) );
if ( in_array( $_order, array( 'ASC', 'DESC' ), true ) && 'DESC' !== $_order ) {
	$archive_data['order'] = $_order;
}

if ( $posts_per_page !== $default_posts_per_page ) {
	$archive_data['posts_per_page'] = $posts_per_page;
}

$archive_data_attrs = '';
foreach ( $archive_data as $key => $value ) {
	if ( null === $value ) {
		continue;
	}

	$archive_data_attrs .= sprintf( ' data-%1$s="%2$s"', esc_attr( $key ), esc_attr( (string) $value ) );
}

$_lm_next_page = ( get_query_var( 'paged' ) ? (int) get_query_var( 'paged' ) : 1 ) + 1;
$template_options = lerm_get_template_options();

if ( $wp_query->max_num_pages > 1 ) :
	?>
	<div class="d-grid col-12 navigation mb-3">
		<?php
		if ( ! empty( $template_options['load_more'] ) || wp_is_mobile() ) :
			?>
			<button
				type="button"
				class="btn btn-sm btn-custom more-posts"
				data-page="<?php echo esc_attr( $_lm_next_page ); ?>"
				<?php echo $archive_data_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				aria-controls="main"
			><?php esc_html_e( 'Load More', 'lerm' ); ?></button>
			<?php
		else :
			the_posts_pagination(
				array(
					'mid_size' => 7,
					'type'     => 'plain',
				)
			);
		endif;
		?>
	</div>
<?php endif; ?>
