<?php

//require_once dirname( __DIR__ ) . '/../../../../wp-load.php';

// function is_valid_url( $url ) {
// 	return filter_var( $url, FILTER_VALIDATE_URL ) !== false;
// }

// function fetch_and_cache_favicon( $url, $cache_file, $cache_filename, $default_ico ) {
// 	$response = wp_remote_get(
// 		$url,
// 		array(
// 			'timeout'   => 2,  // 设置超时时间
// 			'sslverify' => true,  // 启用 SSL 验证
// 		)
// 	);

// 	if ( is_wp_error( $response ) ) {
// 		$error_message = $response->get_error_message();
// 		log_error( "Error fetching favicon: $error_message" );
// 		@unlink( $cache_file );
// 		pk_favicon_put_default_and_output( $cache_file, $cache_filename, $default_ico );
// 		return false;
// 	}

// 	$status_code = wp_remote_retrieve_response_code( $response );
// 	if ( $status_code != 200 ) {
// 		log_error( "Invalid HTTP response: $status_code" );
// 		@unlink( $cache_file );
// 		pk_favicon_put_default_and_output( $cache_file, $cache_filename, $default_ico );
// 		return false;
// 	}

// 	$body = wp_remote_retrieve_body( $response );

// 	// 将获取的图标数据写入缓存文件
// 	file_put_contents( $cache_file, $body );

// 	// 验证文件是否为有效的图标
// 	if ( ! getimagesize( $cache_file ) ) {
// 		@unlink( $cache_file );
// 		pk_favicon_put_default_and_output( $cache_file, $cache_filename, $default_ico );
// 		return false;
// 	}

// 	return true;
// }

// function send_redirect( $url, $status_code = 301 ) {
// 	header( "HTTP/1.1 $status_code Moved Permanently" );
// 	header( "Location: $url" );
// 	exit();
// }

// function pk_favicon_get_ico_contents( $cache_file, $cache_filename ) {
// 	if ( pk_favicon_validate( $cache_file ) ) {
// 		send_redirect( 'cache/' . $cache_filename );
// 	}
// 	send_redirect( 'assets/img/favicon.ico' );
// }

// function pk_favicon_validate( $cache_file ) {
// 	return file_exists( $cache_file ) && getimagesize( $cache_file );
// }

// function pk_favicon_put_default_and_output( $cache_file, $cache_filename, $default_ico ) {
// 	$data = file_get_contents( $default_ico );
// 	if ( $data === false ) {
// 		log_error( "Failed to load default favicon: $default_ico" );
// 	}
// 	file_put_contents( $cache_file, $data );
// 	send_redirect( 'cache/' . $cache_filename );
// }

// function log_error( $message ) {
// 	error_log( $message, 3, '/path/to/your/error.log' ); // 记录错误到日志文件
// }

// $url = @$_GET['url'];

// // if ( empty( $url ) ) {
// // 	die( 'Website URL is empty' );
// // }

// // if ( ! is_valid_url( $url ) ) {
// // 	die( 'Invalid URL format.' );
// // }

// //$exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(1) FROM $wpdb->links WHERE link_url LIKE %s", '%' . $wpdb->esc_like( $url ) . '%' ) );

// // if ( ! $exists ) {
// // 	die( 'Invalid URL: ' . $url );
// // }

// $cache_file     = dirname( __FILE__ ) . '/../cache/icon-' . md5( $url ) . '.ico';
// $cache_filename = 'icon-' . md5( $url ) . '.ico';

// // 从 URL 获取 favicon 并缓存
// if ( fetch_and_cache_favicon( $url . '/favicon.ico', $cache_file, $cache_filename, dirname( __FILE__ ) . '/../assets/img/favicon.ico' ) ) {
// 	pk_favicon_get_ico_contents( $cache_file, $cache_filename );
// }
