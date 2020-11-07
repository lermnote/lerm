<?php
/**
 * Template Name: Registration Page Template
 *
 * @authors lerm http://lerm.net
 * @date    2016-10-26
 * @since lerm 2.0
 */
get_header(); ?>
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
				<!-- <form method="post" action="login.html" class="form-validate" id="loginFrom" novalidate="novalidate">
					<div class="form-group">
						<input id="login-username" type="text" name="userName" required="" data-msg="请输入用户名" placeholder="用户名" value="admin" class="input-material">
					</div>
					<div class="form-group">
						<input id="login-password" type="password" name="passWord" required="" data-msg="请输入密码" placeholder="密码" class="input-material">
					</div>
					<button id="login" type="submit" class="btn btn-primary">登录</button>
					<div class="d-inline">
								<!-- <input type="checkbox"  id="check1"/>&nbsp;<span>记住密码</span>
								<input type="checkbox" id="check2"/>&nbsp;<span>自动登录</span> -->
						<!-- <div class="custom-control custom-checkbox " style="float: right;">
							<input type="checkbox" class="custom-control-input" id="check2">
							<label class="custom-control-label" for="check2">自动登录</label>
						</div>
						<div class="custom-control custom-checkbox " style="float: right;">
							<input type="checkbox" class="custom-control-input" id="check1">
							<label class="custom-control-label" for="check1">记住密码&nbsp;&nbsp;</label>
						</div>
					</div>
				</form> -->
				<?php
				wp_login_form(
					array(
						'redirect'       => ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
						'remember'       => true,
						'value_remember' => true,
					)
				);
				?>
			</div>
		</div>
	</div>
</main>
<?php
get_footer();
