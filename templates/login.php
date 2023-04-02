<?php
/**
 * Template Name: Login Page Template
 *
 * @authors lerm http://lerm.net
 * @date    2016-10-26
 * @since lerm 2.0
 */
global $user_ID;
get_header();
?>
<style>
</style>
<main role="main" class="container login-page p-3 d-flex flex-column justify-content-center"><!--.container-->
	<div class="row align-items-center g-lg-5 py-5">
	<div class="col-lg-7 text-center text-lg-start">
		<h1 class="display-4 fw-bold lh-1 text-body-emphasis mb-3">Vertically centered hero sign-up form</h1>
		<p class="col-lg-10 fs-4">Below is an example form built entirely with Bootstrap’s form controls. Each required form group has a validation state that can be triggered by attempting to submit the form without completing it.</p>
	</div>
	<div class="col-md-10 mx-auto col-lg-5">
			<?php
			if ( ! is_user_logged_in() ) {
				if ( isset( $_POST['btn_submit'] ) ) {
					$username                     = sanitize_text_field( $_POST['username'] );
					$password                     = sanitize_text_field( $_POST['password'] );
					$login_array                  = array();
					$login_array['user_login']    = $username;
					$login_array['user_password'] = $password;

					$verify_user = wp_signon( $login_array, true );
					if ( ! is_wp_error( $verify_user ) ) {
						wp_safe_redirect( home_url() );
						exit;
					} else {
						echo '<p>' . esc_html__( 'Invalid Credentials', 'lerm' ) . '</p>';
					}
				} else {
					?>
					<form method="post" action="" class="p-4 p-md-5 border rounded-3 bg-body-tertiary" id="login-form" novalidate>
						<div class="form-floating mb-3">
							<input id="username" type="text" name="username" required class="form-control"  placeholder="name@example.com" aria-label="Username" aria-describedby="username">
							<label for="floatingInput"><?php echo esc_html__( 'Username', 'lerm' ); ?></label>
						</div>
						<div class="form-floating mb-3">
							<input id="password" type="password"  name="password"  required  class="form-control" class="form-control"  placeholder="Password" aria-label="Password" aria-describedby="password">
							<label for="floatingPassword"><?php echo esc_html__( 'Password', 'lerm' ); ?></label>
						</div>
						<div class="checkbox mb-3">
							<label>
								<input type="checkbox" value="remember-me">
								<?php echo esc_html__( 'Remember Me', 'lerm' ); ?>
							</label>
						</div>
						<button id="login" type="submit" name='btn_submit' class="w-100 btn btn-lg btn-primary" type="submit"><?php echo esc_html__( 'Sign in', 'lerm' ); ?></button>
						<hr class="my-4">
						<small id="login-message" class="login-message"></small>
						<div class="row justify-content-between py-3">
							<div class="col-4">
								<a href="#" class="link-underline-primary"><?php echo esc_html__( 'Forget', 'lerm' ); ?></a>
							</div>
							<div class="col-4">
								<a href="#" class="link-underline-secondary"><?php echo esc_html__( 'Regist', 'lerm' ); ?></a>
							</div>
						</div>
					</form>
					<?php
				}
			} else {
				echo '<p class="alert alert-warning" role="alert">您已登录！(<a href="' . wp_logout_url() . '" title="登出">登出？</a>)</p>';
			}
			?>
		</div>
	</div>
</main>
<?php
get_footer();
