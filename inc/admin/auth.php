<?php function add_auth(){ ?>


	<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel">New message</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<form>
				<div class="form-group">
					<label for="recipient-name" class="col-form-label">Recipient:</label>
					<input type="text" class="form-control" id="recipient-name">
				</div>
				<div class="form-group">
					<label for="message-text" class="col-form-label">Message:</label>
					<textarea class="form-control" id="message-text"></textarea>
				</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				<button type="button" class="btn btn-primary">Send message</button>
			</div>
		</div>
	</div>
</div>
	<?php
}
// add_action( 'wp_footer', 'add_auth' );


class Lerm_Frontend_Login {
	public function login( $args = array() ) {
		$default    = array(
			'login'         => true,
			'register'      => true,
			'login_page'    => '/login.html',
			'register_page' => '/register.html',
		);
		$this->args = apply_filter( 'lerm_frontend_login_args', wp_parse_args( $args, $default ) );
		$this->add_action( 'init', 'redirect' );
	}
	public function redirect() {
		global $pagenow;
		$login_page  = home_url( $this->login_page );
		$page_viewed = basename( $_SERVER['REQUEST_URI'] );
		if ( 'wp-login.php' === $pagenow && 'GET' === $_SERVER['REQUEST_METHOD'] ) {
			wp_safe_redirect( $login_page );
			exit;
		}
	}
}


function redirect() {
	global $pagenow;
	$login_page  = home_url( '/login.html' );
	$page_viewed = basename( $_SERVER['REQUEST_URI'] );
	if ( 'wp-login.php' === $pagenow && 'GET' === $_SERVER['REQUEST_METHOD'] ) {
		wp_safe_redirect( $login_page );
		exit;
	}
}
// add_action( 'init', 'redirect' );


function login_failed() {
	$login_page = home_url( '/login.html' );
	wp_redirect( $login_page . '?login=failed' );
	exit;
}
//add_action( 'wp_login_failed', 'login_failed' );

function verify_username_password( $user, $username, $password ) {
	$login_page = home_url( '/login.html' );
	if ( $username == '' || $password == '' ) {
		wp_redirect( $login_page . '?login=empty' );
		exit;
	}
}
  ////add_filter( 'authenticate', 'verify_username_password', 1, 3 );

function logout_page() {
	$login_page = home_url( '' );
	wp_redirect( $login_page . '?login=false' );
	exit;
}
add_action( 'wp_logout', 'logout_page' );
