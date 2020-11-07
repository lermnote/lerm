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
<main role="main" class="container login-page align-items-center p-5"><!--.container-->
	<div class="row">
		<div class="col-lg-6  bg-info">
			<div class="info d-flex align-items-center p-5">
				<div class="brand d-flex align-items-center">

					<?php the_custom_logo(); ?>
						<!-- .navbar-brand  begin -->
					<div class="masthead">
						<?php
						$lerm_blogname = lerm_options( 'blogname' ) ? lerm_options( 'blogname' ) : get_bloginfo( 'name' );
						if ( is_front_page() || is_home() ) :
							?>
							<h1 class="site-title"><a href="<?php echo esc_url( home_url( '' ) ); ?>" rel="home"><?php echo esc_html( $lerm_blogname ); ?></a></h1>
						<?php else : ?>
							<p class="site-title h1"><a href="<?php echo esc_url( home_url( '' ) ); ?>" rel="home"><?php echo esc_html( $lerm_blogname ); ?></a></p>
							<?php
						endif;

						$description = lerm_options( 'blogdesc' ) ? lerm_options( 'blogdesc' ) : get_bloginfo( 'description' );
						if ( ! wp_is_mobile() && $description || is_customize_preview() ) :
							?>
							<span class="site-description small d-none d-md-block text-muted"><?php echo esc_html( $description ); ?></span>
						<?php endif; ?>
						<!-- .navbar-brand end -->
					</div>
				</div><!-- logo end -->
			</div>
		</div>
		<div class="col-lg-6 bg-white">
			<div class="form d-flex align-items-center p-5">
		<?php
		if ( ! $user_ID ) {
			if ( $_POST ) {
				$username = esc_sql( $_POST['username'] );
				$password = esc_sql( $_POST['password'] );

				$login_array                  = array();
				$login_array['user_login']    = $username;
				$login_array['user_password'] = $password;

				$verify_user = wp_signon( $login_array, true );
				if ( ! is_wp_error( $verify_user ) ) {
					echo "<script>window.location ='" . esc_url( site_url() ) . "'</script>";
				} else {
					echo '<p>Invalid Credentials</p>';
				}
			} else {
				?>
				<form method="post" action="" class="form-validate" id="loginFrom" novalidate="novalidate">
					<div class="form-group">
						<input id="username" type="text" name="username" required="" data-msg="请输入用户名" placeholder="用户名" value="admin" class="input-material">
					</div>
					<div class="form-group">
						<input id="password" type="password" name="password" required="" data-msg="请输入密码" placeholder="密码" class="input-material">
					</div>
					<button id="login" type="submit" name='btn_submit' class="btn btn-primary"><?php echo esc_html__( 'Login', 'lerm' ); ?></button>
					<div class="d-inline">
						<div class="custom-control custom-checkbox " style="float: right;">
							<input type="checkbox" class="custom-control-input" id="check2">
							<label class="custom-control-label" for="check2"><?php echo esc_html__( 'Auto Login', 'lerm' ); ?></label>
						</div>
						<div class="custom-control custom-checkbox " style="float: right;">
							<input type="checkbox" class="custom-control-input" id="check1">
							<label class="custom-control-label" for="check1"><?php echo esc_html__( 'Remember Me', 'lerm' ); ?>&nbsp;&nbsp;</label>
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
