<?php
/**
 * Pagination for archive pages.
 *
 * @package Lerm https://lerm.net
 *
 * @since  3.0.0
 */
$query = $wp_query->query_vars;
foreach ( $query as $key => $value ) {
	if ( empty( $value ) || ! is_scalar( $value ) ) {
		unset( $query[ $key ] );
	}
}
$archive = 'data-archive=' . wp_json_encode( $query );

if ( $wp_query->max_num_pages > 1 ) : ?>
	<div class="d-grid col-12 navigation mb-3">
		<?php
		if ( ( lerm_options( 'load_more' ) || wp_is_mobile() ) && ( $wp_query->max_num_pages > 1 ) ) :
			?>
			<button class='btn btn-sm btn-custom more-posts wow loading-animate' data-current-page="<?php echo get_query_var( 'paged' ) ? esc_attr( get_query_var( 'paged' ) ) : 1; ?>" data-max-page="<?php echo esc_attr( $wp_query->max_num_pages ); ?>"<?php echo esc_attr( $archive ); ?>><?php esc_html_e( 'Load More', 'lerm' ); ?></button>
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
