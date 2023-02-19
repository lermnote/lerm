<?php
/**
 * Post like button ajax handler functions.
 *
 * @author Lerm https://www.hanost.com
 * @since  Lerm 2.0
 */
add_action( 'wp_ajax_nopriv_lerm_post_like', 'lerm_post_like' );
add_action( 'wp_ajax_lerm_post_like', 'lerm_post_like' );
// function lerm_post_like() {
// 	// check ajax nonce
// 	if ( check_ajax_referer( 'ajax_nonce', 'security', false ) ) {
// 		$id = $_POST['post_ID'];
// 	}

// 	$like_count = (int) get_post_meta( $id, 'lerm_post_like', true );
// 	$expire     = time() + 604800;//a week
// 	$domain     = ( 'localhost' !== $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : false; // make cookies work with localhost
// 	setcookie( 'post_like_' . $id, $id, $expire, '/', $domain, '/; samesite=strict', true );

// 	if ( ! $like_count || ! is_numeric( $like_count ) ) {
// 		update_post_meta( $id, 'lerm_post_like', 1 );
// 	} else {
// 		update_post_meta( $id, 'lerm_post_like', ( $like_count + 1 ) );
// 	}

// 	echo esc_attr( get_post_meta( $id, 'lerm_post_like', true ) );
// 	wp_die();
// }

function lerm_post_like() {
	if ( check_ajax_referer( 'ajax_nonce', 'security', false ) ) {
		$id = absint( $_POST['post_ID'] );

		if ( ! isset( $_COOKIE[ 'post_like_' . $id ] ) ) {
			$like_count = (int) get_post_meta( $id, 'lerm_post_like', true );
			$like_count++;
			update_post_meta( $id, 'lerm_post_like', $like_count );

			setcookie( 'post_like_' . $id, $id, time() + 604800, '/', ( 'localhost' !== $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : false ), true, true );
		}

		echo absint( get_post_meta( $id, 'lerm_post_like', true ) );
		wp_die();
	}
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
