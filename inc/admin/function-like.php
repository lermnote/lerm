<?php
/**
 * Post likes button ajax handler function.
 *
 * @since Lerm 2.0
 */
add_action( 'wp_ajax_nopriv_lerm_post_like', 'lerm_post_like' );
add_action( 'wp_ajax_lerm_post_like', 'lerm_post_like' );
function lerm_post_like() {

	// check ajax nonce
	check_ajax_referer( 'ajax_nonce', 'security' );

	$id         = $_POST['postID'];
	$like_count = (int) get_post_meta( $id, 'lerm_post_like', true );
	$expire     = time() + 604800;
	$domain     = ( 'localhost' !== $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : false; // make cookies work with localhost
		setcookie( 'post_like_' . $id, $id, $expire, '/', $domain, false );
	if ( ! $like_count || ! is_numeric( $like_count ) ) {
		update_post_meta( $id, 'lerm_post_like', 1 );
	} else {
		update_post_meta( $id, 'lerm_post_like', ( $like_count + 1 ) );
	}
	echo esc_attr( get_post_meta( $id, 'lerm_post_like', true ) );
	wp_die();
}


add_filter( 'manage_post_posts_columns', 'lerm_set_post_colunms' );
function lerm_set_post_colunms( $columns ) {
	$columns['like'] = esc_html__( 'Like', 'lerm' );
	return $columns;
}

add_action( 'manage_post_posts_custom_column', 'lerm_post_custom_column', 10, 2 );
function lerm_post_custom_column( $column, $post_id ) {
	switch ( $column ) {
		case 'like':
			$like_count = get_post_meta( $post_id, 'lerm_post_like', true );
			echo esc_attr( $like_count );
			break;
	}
}

/**
 * Add post columns
 *
 * @since 3.2.1
 */
add_action( 'add_meta_boxes', 'lerm_post_like_meta_box' );
function lerm_post_like_meta_box() {
	add_meta_box( 'post_like', esc_html__( 'Like', 'lerm' ), 'lerm_post_like_callback', 'post', 'side' );
}

function lerm_post_like_callback( $post ) {
	wp_nonce_field( 'lerm_save_like_data', 'lerm_post_like_meta_box_nonce' );
	$value = get_post_meta( $post->ID, 'lerm_post_like', true );
	echo '<label for = "lerm_like_field" >' . esc_html__( 'Post Like Count ', 'lerm' ) . '</label>';
	echo '<input type="text" id="lerm_like_field" name="lerm_like_field" value="' . esc_attr( $value ) . '" size="25" />';
}

// add_action( 'save_post', 'lerm_save_like_data' );
// function lerm_save_like_data( $post_id ) {
// if ( ! isset( $_POST['lerm_post_like_meta_box_nonce'] ) ) {
// return;
// }
// if ( ! wp_verify_nonce( $_POST['lerm_post_like_meta_box_nonce'], 'lerm_save_like_data' ) ) {
// return;
// }
// if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
// return;
// }
// if ( ! current_user_can( 'edit_post', $post_id ) ) {
// return;
// }
// if ( ! isset( $_POST['lerm_like_field'] ) ) {
// return;
// }

// $my_data = get_post_meta( $post_id, 'lerm_post_like', true );
// update_post_meta( $post_id, 'lerm_post_like', $my_data );
// }
