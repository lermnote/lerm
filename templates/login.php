<?php
/**
 * Template Name: Login Page Template
 *
* @package Lerm https://lerm.net
 * @date    2016-10-26
 * @since lerm 2.0
 */
global $user_ID;
get_header();
?>
<style>
</style>

<main role="main" class="container login-page p-3 d-flex flex-column justify-content-center"><!--.container-->
	<div class="row  g-lg-5 py-5">
		<div class="col-lg-7 text-center text-lg-start">
			<h1 class="display-4 fw-bold lh-1 text-body-emphasis mb-3">Vertically centered hero sign-up form</h1>
			<p class="col-lg-10 fs-4">Below is an example form built entirely with Bootstrap’s form controls. Each required form group has a validation state that can be triggered by attempting to submit the form without completing it.</p>
		</div>
		<div class=" col-md-10 col-lg-4 mx-auto tab-content  border rounded-3 bg-body-tertiary p-4 p-md-5" id="myTabContent">
			<div  id="myTab" class=" d-flex" role="tablist">
				<button class="nav-link active visually-hidden" id="login-tab" data-bs-toggle="tab" data-bs-target="#login-tab-pane" type="button" role="tab" aria-controls="login-tab-pane" aria-selected="true">Login</button>
				<button class="nav-link visually-hidden" id="regist-tab" data-bs-toggle="tab" data-bs-target="#regist-tab-pane" type="button" role="tab" aria-controls="regist-tab-pane" aria-selected="false">Regist</button>
				<button class="nav-link visually-hidden" id="forget-tab" data-bs-toggle="tab" data-bs-target="#forget-tab-pane" type="button" role="tab" aria-controls="forget-tab-pane" aria-selected="false">Forget</button>
			</div>
			<!--start lgoin-->
			<div class="login tab-pane fade show active" id="login-tab-pane" role="tabpanel" aria-labelledby="login-tab" tabindex="0">

				<form method="post" action="" class=" " id="login-form">
					<h2><?php echo esc_html__( 'Login', 'lerm' ); ?></h2>
					<div class="form-floating mb-3">
						<input id="username" type="text" name="username" required class="form-control"  placeholder="name@example.com" aria-label="Username" aria-describedby="username">
						<label for="floatingInput"><?php echo esc_html__( 'Username', 'lerm' ); ?></label>
					</div>
					<div class="form-floating mb-3">
						<input id="password" type="password"  name="password"  required  class="form-control" class="form-control"  placeholder="Password" aria-label="Password" aria-describedby="password">
						<label for="floatingPassword"><?php echo esc_html__( 'Password', 'lerm' ); ?></label>
					</div>
					<div class="checkbox mb-3 d-flex justify-content-between align-items-center">
						<label>
							<input id="rememberme" name="rememberme" type="checkbox" value="rememberme">
							<?php echo esc_html__( 'Remember Me', 'lerm' ); ?>
						</label>
						<a id="forget-btn" type="button" title="forget password" class=""><?php echo esc_html__( 'Forget password?', 'lerm' ); ?></a>
					</div>
					<button id="login" type="submit" name='btn_submit' class="w-100 btn btn-primary my-4" type="submit"><?php echo esc_html__( 'LOGIN', 'lerm' ); ?></button>
					<small id="login-message" class="text-danger wow">message</small>
				</form>
				<hr class="my-4">
				<div class="text-center "><span><?php echo esc_html__( 'Don\'t have an account?', 'lerm' ); ?> </span> <a id="regist-btn" type="submit" name='btn_submit' class="my-4" type="submit"><?php echo esc_html__( 'Register', 'lerm' ); ?></a></div>
				<div class="text-center py-3">
					<label class="py-3" for=""><?php echo esc_html__( 'or sign up with', 'lerm' ); ?></label>
					<div class="social-share d-flex justify-content-around gap-1 " data-initialized="true">
						<a href="#" class="social-share-icon icon-weibo btn-light btn-sm" target="_blank">
							<i class="fa fa-weibo"></i>
						</a>
						<a href="#" class="social-share-icon icon-qq btn-light btn-sm"  target="_blank">
							<i class="fa fa-qq"></i>
						</a>
						<a href="#" class="social-share-icon icon-facebook btn-light btn-sm" target="_blank">
							<i class="fa fa-facebook"></i>
						</a>
						<a href="#" class="social-share-icon icon-twitter btn-light btn-sm" target="_blank">
							<i class="fa fa-twitter"></i>
						</a>
						<a href="#" class="social-share-icon icon-github btn-light btn-sm">
							<i class="fa fa-github"></i>
						</a>
					</div>
				</div>
			</div><!--end lgoin-->
			<!--start regist-->
			<div class="regist tab-pane fade" id="regist-tab-pane" role="tabpanel" aria-labelledby="regist-tab" tabindex="0">
				<form method="post" action="" class="" id="regist-form">
					<h2><?php echo esc_html__( 'Regist', 'lerm' ); ?></h2>
					<div class="form-floating mb-3">
						<input id="username2" type="text" name="username" required class="form-control"  placeholder="name@example.com" aria-label="Username" aria-describedby="username">
						<label for="floatingInput"><?php echo esc_html__( 'Username', 'lerm' ); ?></label>
					</div>
					<div class="form-floating mb-3">
						<input id="email2" type="email"  name="email"  required  class="form-control" class="form-control"  placeholder="Email" aria-label="Email" aria-describedby="Email">
						<label for="floatingEmail"><?php echo esc_html__( 'Email', 'lerm' ); ?></label>
					</div>
					<div class="form-floating mb-3">
						<input id="password2" type="password"  name="password"  required  class="form-control" class="form-control"  placeholder="Password" aria-label="Password" aria-describedby="password">
						<label for="floatingPassword"><?php echo esc_html__( 'Password', 'lerm' ); ?></label>
					</div>
					<div class="form-floating mb-3">
						<input id="password3" type="password"  name="password"  required  class="form-control" class="form-control"  placeholder="Password" aria-label="Password" aria-describedby="password">
						<label for="floatingPassword"><?php echo esc_html__( 'Password Again', 'lerm' ); ?></label>
					</div>

					<div  class="d-flex justify-content-between align-items-center py-3">
						<div class="form-floating col-lg">
							<input id="captcha2" type="captcha"  name="captcha" required class="form-control" class="form-control"  placeholder="Captcha" aria-label="Captcha" aria-describedby="captcha">
							<label for="floatingCaptcha"><?php echo esc_html__( 'Captcha', 'lerm' ); ?></label>
						</div>
						<a class="" type="button" id="button-addon2"><?php echo esc_html__( 'Get Email Captcha', 'lerm' ); ?></a>
					</div>
					<button id="regist" type="submit" name='btn_submit' class="w-100 btn btn-primary my-4" type="submit"><?php echo esc_html__( 'REGISTER', 'lerm' ); ?></button>
					<small id="regist-message" class="regist-message">message</small>
				</form>
				<hr class="my-4">
				<div class="text-center "><span><?php echo esc_html__( 'Have already an account?', 'lerm' ); ?> </span> <a id="login-btn" type="submit" name='btn_submit' class="my-4" type="submit"><?php echo esc_html__( 'Login', 'lerm' ); ?></a></div>
			</div><!--end regist-->

			<!--start forget-->
			<div class="forget tab-pane fade" id="forget-tab-pane" role="tabpanel" aria-labelledby="forget-tab" tabindex="0">
				<form method="post" action="" class=" " id="forget-form">
					<h2><?php echo esc_html__( 'PASSWORD RESET', 'lerm' ); ?></h2>
					<label for="email3"><?php echo esc_html__( 'Enter your email address and we\'ll send you an email with instructions to reset your password.', 'lerm' ); ?></label>
					<div class="form-floating mb-3">
						<input id="email3" type="email"  name="email"  required  class="form-control" class="form-control"  placeholder="Email" aria-label="Email" aria-describedby="Email">
						<label for="floatingEmail"><?php echo esc_html__( 'Email', 'lerm' ); ?></label>
					</div>

					<button id="forget" type="submit" name='btn_submit' class="w-100 btn btn-primary my-4" type="submit"><?php echo esc_html__( 'RESET PASSWORD', 'lerm' ); ?></button>
					<small id="forget-message" class="forget-message">message</small>
				</form>
				<hr class="my-4">
				<a id="login-btn2" type="submit" name='btn_submit' class=" my-4" type="submit"><?php echo esc_html__( 'Login', 'lerm' ); ?></a>
			</div><!--end forget-->
		</div>
	</div>
</main>

<script>
	document.addEventListener("DOMContentLoaded", function (e) {
		document.getElementById("forget-btn").addEventListener('click',function(e){
			document.getElementById("forget-tab").click();
		})
		document.getElementById("regist-btn").addEventListener('click',function(e){
			document.getElementById("regist-tab").click();
		})
		document.getElementById("login-btn").addEventListener('click',function(e){
			document.getElementById("login-tab").click();
		})
		document.getElementById("login-btn2").addEventListener('click',function(e){
			document.getElementById("login-tab").click();
		})
	})
</script>

<?php
get_footer();
