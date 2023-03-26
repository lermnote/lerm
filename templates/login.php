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
	.site-header{
		position: absolute;
		clip: rect(1px, 1px, 1px, 1px);
	}
	.login-page{
		min-height: 100vh;
	}
	.login-page .info, .login-page .form {
		min-height: 70vh;
	}
	@media (max-width: 991px) {
		.login-page .info, .login-page .form {
			min-height: auto !important;
		}
	}
	.login-page .form form{
		width: 100%
	}
	input.input-material {
	width: 100%;
	border: none;
	border-bottom: 1px solid #eee;
	padding: 10px 0;
	}

	input.input-material.is-invalid {
	border-color: #dc3545 !important;
	}

	input.input-material:focus {
		border-color: #796AEE;
	}

	input.input-material ~ label {
	color: #aaa;
	position: absolute;
	top: 14px;
	left: 0;
	cursor: text;
	-webkit-transition: all 0.2s;
	transition: all 0.2s;
	font-weight: 300;
	}

	input.input-material ~ label.active {
	font-size: 0.8rem;
	top: -10px;
	color: #796AEE;
	}

	input.input-material.is-invalid ~ label {
	color: #dc3545;
	}

	.form-group-material {
	position: relative;
	margin-bottom: 30px;
	}

</style>
<main role="main" class="container login-page p-3 d-flex flex-column justify-content-center"><!--.container-->
	<div class="row justify-content-end">
		<div class="col-lg-4 bg-white card">
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
					<div class="card-body">
						<h2 class="h2 text-center py-3"><?php echo esc_html__( 'Login', 'lerm' ); ?></h2>

						<form method="post" action="" class="form-validate" id="loginFrom" novalidate>
						<div class="input-group mb-3">
							<span class="input-group-text" id="basic-addon1"><i class="fa fa-star"></i></span>
							<input id="username" type="text" name="username" required class="form-control" placeholder="Username" aria-label="Username" aria-describedby="basic-addon1">
						</div>
						<div class="input-group mb-3">
							<span class="input-group-text" id="basic-addon1"><i class="fa fa-star"></i></span>
							<input id="password" type="password"  name="password"  required  class="form-control" placeholder="Password" aria-label="Username" aria-describedby="basic-addon1">
						</div>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
							<label class="form-check-label" for="flexCheckDefault">
							<?php echo esc_html__( 'Remember Me', 'lerm' ); ?>
							</label>
						</div>
						<div class="d-grid gap-2">
							<button id="login" type="submit" name='btn_submit' class="btn btn-primary"><?php echo esc_html__( 'Login', 'lerm' ); ?></button>
						</div>
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
			}
			?>
			</div>
		</div>
	</div>
</main>
<?php
get_footer();
