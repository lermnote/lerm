<?php
/**
 * Shows a pagination for post page.
 *
 * @since  3.0.0
 * @return void
 */
function lerm_link_pagination() {
	wp_link_pages(
		array(
			'previouspagelink' => '<span class="screen-reader-text">' . __( 'Previous page', 'lerm' ) . '</span>',
			'nextpagelink'     => '<span class="screen-reader-text">' . __( 'Next page', 'lerm' ),
			'pagelink'         => esc_html__( 'Page %', 'lerm' ),
		)
	);
}

/**
 *  Get copyright of website
 *
 * @param string $type type of copyright,'short', 'long'.
 *
 * @since lerm 3.0
 */
function lerm_create_copyright( string $type = 'short' ) {
	$output   = '';
	$blogname = '<strong>' . get_bloginfo( 'name' ) . '</strong>';

	$all_posts  = get_posts( 'post_status=publish&order=ASC' );
	$first_post = $all_posts[0];
	$first_date = $first_post->post_date_gmt;
	$date       = esc_html( substr( $first_date, 0, 4 ) . ( ( substr( $first_date, 0, 4 ) === gmdate( 'Y' ) ) || ( 'short' === $type ) ? '' : '-' . gmdate( 'Y ' ) ) );
	if ( 'short' === $type ) {
		$output = sprintf(
			'&copy; %1$s %2$s',
			$date,
			$blogname
		);
	} else {
		$output = sprintf(
			'Copyright &copy; %1$s %2$s All rights reserved',
			$date,
			$blogname
		);
	}
	echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
