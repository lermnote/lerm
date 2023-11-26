<?php
/*
PHP Grab Favicon
================

> This `PHP Favicon Grabber` use a given url, save a copy (if wished) and return the image path.

How it Works
------------

1. Check if the favicon already exists local or no save is wished, if so return path & filename
2. Else load URL and try to match the favicon location with regex
3. If we have a match the favicon link will be made absolute
4. If we have no favicon we try to get one in domain root
5. If there is still no favicon we randomly try google, faviconkit & favicongrabber API
6. If favicon should be saved try to load the favicon URL
7. If wished save the Favicon for the next time and return the path & filename

How to Use
----------

```PHP
$url = 'example.com';

$grap_favicon = array(
'URL' => $url,   // URL of the Page we like to get the Favicon from
'SAVE'=> true,   // Save Favicon copy local (true) or return only favicon url (false)
'DIR' => './',   // Local Dir the copy of the Favicon should be saved
'TRY' => true,   // Try to get the Favicon frome the page (true) or only use the APIs (false)
'DEV' => null,   // Give all Debug-Messages ('debug') or only make the work (null)
);

echo '<img src="'.grap_favicon($grap_favicon).'">';
```

Todo
----
Optional split the download dir into several sub-dirs (MD5 segment of filename e.g. /af/cd/example.com.png) if there are a lot of favicons.

Infos about Favicon
-------------------
https://github.com/audreyr/favicon-cheat-sheet


 */
function grap_favicon( $options = array() ) {

	// Ini Vars
	$url       = ( isset( $options['URL'] ) ) ? $options['URL'] : 'gaffling.com';
	$save      = ( isset( $options['SAVE'] ) ) ? $options['SAVE'] : true;
	$directory = ( isset( $options['DIR'] ) ) ? $options['DIR'] : './';
	$try_self  = ( isset( $options['TRY'] ) ) ? $options['TRY'] : true;

	// URL to lower case
	$url = strtolower( $url );

	// Get the Domain from the URL
	$domain = wp_parse_url( $url, PHP_URL_HOST );
	$scheme = wp_parse_url( $url, PHP_URL_SCHEME );

	$dir = wp_upload_dir();
	// Make Path & Filename
	$file_path = preg_replace( '#\/\/#', '/', $dir['path'] . '/' . $domain . '.png' );

	// If Favicon not already exists local
	if ( ! file_exists( $file_path ) || 0 === filesize( $file_path ) ) {

		// If $try_self == TRUE ONLY USE APIs
		if ( isset( $try_self ) && true === $try_self ) {

			// Load Page
			$html = lerm_load( $url );

			// Find Favicon with RegEx
			$regex = '/((<link[^>]+rel=.(icon|shortcut icon|alternate icon)[^>]+>))/i';

			if ( preg_match( $regex, $html, $matches ) ) {

				$regex = '/href=(\'|\")(.*?)\1/i';
				if ( isset( $matches[1] ) && preg_match( $regex, $matches[1], $match_url ) ) {

					if ( isset( $match_url[2] ) ) {
						// Build Favicon Link
						$favicon = rel2abs( trim( $match_url[2] ), $scheme . '://' . $domain );

					}
				}
			}
		} // END If $try_self == TRUE ONLY USE APIs
        $favicon= '';
		// // If no think works: Get the Favicon from API
		// if ( ! isset( $favicon ) || empty( $favicon ) ) {
		// Select API by Random
		// $random = wp_rand( 1, 3 );

		// Faviconkit API
		// if ( 1 === $random || empty( $favicon ) ) {
		// $favicon = 'https://api.faviconkit.com/' . $domain . '/16';
		// }

		// Favicongrabber API
		// if ( 2 === $random || empty( $favicon ) ) {
		// $echo = json_decode( load( 'http://favicongrabber.com/api/grab/' . $domain, false ), true );

		// Get Favicon URL from Array out of json data (@ if something went wrong)
		// $favicon = $echo['icons']['0']['src'];
		// }

		// Google API (check also md5() later)
		// if ( 3 === $random ) {
		// $favicon = 'https://api.clowntool.cn/getico/?url=' . $domain;
		// }
		// } // END If nothink works: Get the Favicon from API

		// Write Favicon local
		global $wp_filesystem;
		WP_Filesystem();
		// If Favicon should be saved
		if ( isset( $save ) && true === $save ) {
			// Load Favicon
			$content = load( $favicon );

			// If Google API don't know and deliver a default Favicon (World)
			if ( isset( $random ) && 3 === $random &&
				md5( $content ) === '3ca64f83fdcf25135d87e08af65e68c9' ) {
				$domain = 'default'; // so we don't save a default icon for every domain again

			}
			// Write
			$fh = fopen( $file_path, 'wb' );
			fwrite( $fh, $content );
			fclose( $fh );

		}
	} else {
		// END If Favicon not already exists local
		$favicon = $dir['url'] . '/' . $domain . '.png';
	}

	// Return Favicon Url
	clearstatcache();
	return $favicon;
} // END MAIN Function

/* HELPER load use curl or file_get_contents (both with user_agent) and fopen/fread as fallback */
function lerm_load( $url ) {
	$info = parse_url( $url );
	if ( $info['scheme'] && 'https' === $info['scheme'] ) {
		$port = 443;
	} else {
		$port = 80;
	}

	$fp = fsockopen( $info['host'], $port, $errno, $errstr, 30 );

	if ( ! $fp ) {
		echo "$errstr ($errno)<br />\n";
	} else {
		$out  = "GET / HTTP/1.1\r\n";
		$out .= 'Host:' . $info['host'] . "\r\n";
		$out .= "Connection: Close\r\n\r\n";
		fwrite( $fp, $out );
		while ( ! feof( $fp ) ) {
			var_dump( fgets( $fp, 4096000 ) );
		}
		fclose( $fp );
	}

	// $response = wp_remote_get( $url );
	// $ch = curl_init();
	// curl_setopt($ch, CURLOPT_HEADER, false);
	// curl_setopt($ch, CURLOPT_URL, $url);
	// curl_setopt($ch, CURLOPT_SSLVERSION,3);
	// $result = curl_exec($ch);
	// curl_close($ch);
	// ; // $contents = file_get_contents( $url );
	// echo '<pre>';
	// var_dump( $result );
	// echo '</pre>';
	// $domain = wp_parse_url( $url, PHP_URL_HOST );
	// $fp     = fsockopen( $domain, 443, $errno, $errstr, 30 );
	// if ( ! $fp ) {
	// echo "$errstr ($errno)<br />\n";
	// } else {
	// $out  = "GET / HTTP/1.1\r\n";
	// $out .= "Host: $domain\r\n";
	// $out .= "Connection: Close\r\n\r\n";
	// fwrite( $fp, $out );
	// while ( ! feof( $fp ) ) {
	// echo fgets( $fp, 128 );
	// }
	// fclose( $fp );
	// }
	// echo '<pre>';
	// print_r( $fp );
	// echo '</pre>';
	// // if ( is_wp_error( $response ) ) {
	// // $error_message = $response->get_error_message();
	// // echo $error_message;
	// // }
	// if ( ! is_wp_error( $response ) || ( is_array( $response ) && 200 === $response['response']['code'] ) ) {

	// $result = wp_remote_retrieve_body( $response );

	// } else {

	// $context = array(
	// 'http' => array(
	// 'user_agent' => 'FaviconBot/1.0 (+http://' . $_SERVER['SERVER_NAME'] . '/)',
	// ),
	// );
	// $context = stream_context_create( $context );
	// // 	$contents = file_get_contents($url, false, $context);
	// //    echo '<pre>';
	// // print_r( $contents );
	// // echo '</pre>';
	// if ( ! function_exists( 'file_get_contents' ) ) {
	// var_dump('true');
	// $fh     = fopen( $url, 'r', false, $context );
	// $result = '';
	// while ( ! feof( $fh ) ) {
	// $result .= fread( $fh, 128 ); // Because filesize() will not work on URLS?
	// }
	// fclose( $fh );
	// }
	// }
	// return $result;
}

/* HELPER: Change URL from relative to absolute */
function rel2abs( $rel, $base ) {
	extract( wp_parse_url( $base ) );

	if ( 0 === strpos( $rel, '//' ) ) {
		return $scheme . ':' . $rel;
	}
	if ( null !== wp_parse_url( $rel, PHP_URL_SCHEME ) ) {
		return $rel;
	}
	if ( 0 === strpos( $rel, '#' ) || 0 === strpos( $rel, '?' ) ) {
		return $base . $rel;
	}
	if ( null !== $path ) {
		$path = preg_replace( '#/[^/]*$#', '', $path );
		if ( 0 === strpos( $rel, '/' ) ) {
			$path = '';
		}
	}

	$abs = $host . $path . '/' . $rel;
	$abs = preg_replace( '/(\/\.?\/)/', '/', $abs );
	$abs = preg_replace( '/\/(?!\.\.)[^\/]+\/\.\.\//', '/', $abs );
	return $scheme . '://' . $abs;
}
