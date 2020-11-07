<?php
/**
 * Handles the theme's login page.
 *
 * @author     Lerm
 * @since      2.0
 */
function lerm_login_style() {
	$url        = 'https://www.bing.com/HPImageArchive.aspx?format=js&idx=0&n=1&mkt=';
	$resolution = '1920x1080';
	$request    = wp_remote_get( $url );
	$data       = wp_remote_retrieve_body( $request );
	$json       = json_decode( trim( $data ), true );
	if ( $json ) {
		$images = $json['images'];
		foreach ( $images as $image ) {
			$urlbase   = $image['urlbase'];
			$image_url = 'https://www.bing.com' . $urlbase . '_' . $resolution . '.jpg';
		}
	} ?>
	<style type="text/css">
		.login {
			background:rgba(255,255,255,.6) url(<?php echo esc_url( $image_url ); ?>) no-repeat center;
			height:auto;
			/* filter: blur(0.1px); */
		}
		#login {
			background-color:rgba(255,255,255,0.6);
			margin:8% auto 0;
			padding:4rem 2rem;
			box-shadow:0 0 5px 1px rgba(0,0,0,0.5);
			-moz-box-shadow:0 0 5px 1px rgba(0,0,0,0.5);
			-webkit-box-shadow:0 0 5px 1px rgba(0,0,0,0.5)
		}
		.login form {
			padding:0;
			border: 0 none;
			box-shadow:none;
			background-color: initial;
		}
		.login h1 a {
			font-size:inherit;
			background:url(<?php echo esc_url( LERM_URI . 'favicon.ico' ); ?>) no-repeat center;
			background-size:100px;
		}
		.login label{
			color: #000;
			font-weight: 800;
		}
		.login #nav a,.login #backtoblog a{
			color: #000;
		}
		.login #nav,.login #backtoblog {
			display:inline-block
		}
	</style>
	<?php
}
add_action( 'login_head', 'lerm_login_style' );

//logo link
add_filter(
	'login_headerurl',
	function () {
		return home_url();
	}
);

//登陆用户名和密码错误提示
add_filter(
	'login_errors',
	function () {
		return __( 'Incorrect username or password', 'lerm' );
	}
);
