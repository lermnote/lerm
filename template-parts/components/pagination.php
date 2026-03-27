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

			$_lm_paged     = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
			$_lm_per_page  = absint( $wp_query->get( 'posts_per_page' ) ) ? absint( $wp_query->get( 'posts_per_page' ) ) : (int) get_option( 'posts_per_page', 10 );
			$_lm_cat       = absint( $wp_query->get( 'cat' ) );
			$_lm_tag       = absint( $wp_query->get( 'tag_id' ) );
			$_lm_post_type = esc_attr( $wp_query->get( 'post_type' ) ? $wp_query->get( 'post_type' ) : 'post' );

if ( $wp_query->max_num_pages > 1 ) : ?>
	<div class="d-grid col-12 navigation mb-3">
		<?php
		if ( ( lerm_options( 'load_more' ) || wp_is_mobile() ) && ( $wp_query->max_num_pages > 1 ) ) :
			?>
			<button class='btn btn-sm btn-custom more-posts' data-paged="<?php echo esc_attr( $_lm_paged ); ?>" data-current-page="<?php echo get_query_var( 'paged' ) ? esc_attr( get_query_var( 'paged' ) ) : 1; ?>" data-max-page="<?php echo esc_attr( $wp_query->max_num_pages ); ?>"<?php echo esc_attr( $archive ); ?>><?php esc_html_e( 'Load More', 'lerm' ); ?></button>
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
<?php
/**
 * Pagination for archive pages.
 *
 * @package Lerm https://lerm.net
 *
 * @since  3.0.0
 */
// $query = $wp_query->query_vars;
// foreach ( $query as $key => $value ) {
//  if ( empty( $value ) || ! is_scalar( $value ) ) {
//      unset( $query[ $key ] );
//  }
// }
// // data-archive removed: PostsController reads individual params, not a JSON blob

// if ( $wp_query->max_num_pages > 1 ) :
?>
	<!-- <div class="d-grid col-12 navigation mb-3"> -->
		<?php
		//      if ( ( lerm_options( 'load_more' ) || wp_is_mobile() ) && ( $wp_query->max_num_pages > 1 ) ) :
		//
		?>
			<?php
			//          $_lm_paged     = get_query_var( 'paged' ) ?: 1;
			//          $_lm_per_page  = absint( $wp_query->get( 'posts_per_page' ) ) ?: (int) get_option( 'posts_per_page', 10 );
			//          $_lm_cat       = absint( $wp_query->get( 'cat' ) );
			//          $_lm_tag       = absint( $wp_query->get( 'tag_id' ) );
			//          $_lm_post_type = esc_attr( $wp_query->get( 'post_type' ) ?: 'post' );
			//
			?>
	<!-- <button class='btn btn-sm btn-custom more-posts wow loading-animate' -->
				<!-- data-page="<?php //echo esc_attr( $_lm_paged ); ?>"
				data-per-page="<?php //echo esc_attr( $_lm_per_page ); ?>"
				data-category="<?php //echo esc_attr( $_lm_cat ); ?>"
				data-tag="<?php //echo esc_attr( $_lm_tag ); ?>" 				data-post-type="<?php //echo esc_attr( $_lm_post_type ); ?>"
				data-max-page="<?php //echo esc_attr( $wp_query->max_num_pages ); ?>">
				<?php //esc_html_e( 'Load More', 'lerm' ); ?> -->
<!-- // 			</button> -->
			<?php
			//      else :
			//          the_posts_pagination(
			//              array(
			//                  'mid_size' => 7,
			//                  'type'     => 'plain',
			//              )
			//          );
			//      endif;
			//
			?>
<!-- // 	</div> -->
<?php //endif; ?>