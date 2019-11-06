<?php
if ( is_admin() || in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) ) || ! $lerm['lazyload'] ) {
	return;
}

add_action( 'template_redirect', 'lerm_lazyload' );
function lerm_lazyload() {
	ob_start( 'lerm_lazyload_content' );
}

function lerm_lazyload_content( $content ) {
	$regexp = '/<img([^<>]*)\.(bmp|gif|jpeg|jpg|png)([^<>]*)>/i';

	return preg_replace_callback( $regexp, 'lerm_lazyload_match', $content );
}
function lerm_lazyload_match( $matches ) {
	$lazyimg = $matches[0];
	// if (stripos($lazyimg, 'src') === false ) {
	// return $lazyimg;
	// }

	if ( ( stripos( $lazyimg, 'skip_lazyload' ) !== false ) || ( stripos( $lazyimg, 'custom-logo' ) !== false ) || ( stripos( $lazyimg, 'slider' ) !== false ) || ( stripos( $lazyimg, 'avatar' ) !== false ) || ( stripos( $lazyimg, 'qrcode' ) !== false ) ) {
		return $lazyimg;
	}
	if ( stripos( $lazyimg, 'slider' ) !== false ) {
		return $lazyimg;
	}

	if ( stripos( $lazyimg, 'class=' ) === false ) {
		$lazyimg = preg_replace(
			'/<img(.*)>/i',
			'<img class="lazy"$1>',
			$lazyimg
		);
	} else {
		$lazyimg = preg_replace(
			"/<img(.*)class=['\"]([\w\-\s]*)['\"](.*)>/i",
			'<img$1class="$2 lazy"$3>',
			$lazyimg
		);
	}

	if ( stripos( $lazyimg, 'srcset=' ) ) {
		if ( ! stripos( $lazyimg, 'data-srcset=' ) ) {
			$regexp  = "/<img([^<>]*)srcset=['\"]([^<>'\"]*)['\"]([^<>]*)>/i";
			$replace = '<img$1srcset="data:image/gif;base64,R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="$2" data-srcset="$2"$3>';
			$lazyimg = preg_replace( $regexp, $replace, $lazyimg );
		}
	} else {
		$regexp  = "/<img([^<>]*)src=['\"]([^<>'\"]*)\.(bmp|gif|jpeg|jpg|png)([^<>'\"]*)['\"]([^<>]*)>/i";
		$replace = '<img$1src="data:image/gif;base64,R0lGODdhAQABAPAAAMPDwwAAACwAAAAAAQABAAACAkQBADs="data-src="$2.$3$4"$5>';
		$lazyimg = preg_replace( $regexp, $replace, $lazyimg );
	}
	return $lazyimg;
}
