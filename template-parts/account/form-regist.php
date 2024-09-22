<?php
/**
 * Register form template
 *
 * @package Lerm https://lerm.net
 */
$forms = array(
	'username'  => array(
		'type'        => 'text',
		'name'        => 'username',
		'id'          => 'username',
		'placeholder' => 'name@example.com',
		'label_text'  => __( 'Username' ),
	),
	'email'     => array(
		'type'        => 'email',
		'name'        => 'email',
		'id'          => 'regist-email',
		'placeholder' => 'name@example.com',
		'label_text'  => __( 'Email' ),
	),
	'password'  => array(
		'container_class' => 'form-floating',
		'type'            => 'password',
		'name'            => 'regist_password',
		'id'              => 'regist-password',
		'placeholder'     => '>8,numbers,upercase,lowercase,[#.?!@$%^&*-]',
		'label_text'      => __( 'Password' ),
	),
	'password2' => array(
		'type'        => 'password',
		'name'        => 'confirm_password',
		'id'          => 'regist-confirm-password',
		'placeholder' => 'Enter your password again',
		'label_text'  => esc_html__( 'Password Again', 'lerm' ),
	),
);
?>

<?php if ( get_option( 'users_can_register' ) ) : ?>
	<!--start regist-->
	<form method="post" id="regist">
		<h2><?php echo esc_html__( 'Regist', 'lerm' ); ?></h2>
		<?php
		echo float_form_input( $forms['username'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- Reason: has been escaped.
		echo float_form_input( $forms['email'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- Reason: has been escaped.
		?>
		<div class="input-group  mb-3">
			<?php
			echo float_form_input( $forms['password'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- Reason: has been escaped.
			?>
			<button class="btn btn-outline-secondary" id="regist-toggle" type="button" role="switch" aria-label="Show password" aria-checked="false">Show</button>
		</div>
		<?php
		echo float_form_input( $forms['password2'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- Reason: has been escaped.
		?>
		<div  class="input-group mb-3">
			<div class="form-floating">
				<input id="captcha2" type="captcha"  name="captcha" required class="form-control" placeholder="Captcha" >
				<label for="floatingCaptcha"><?php echo esc_html__( 'Captcha', 'lerm' ); ?></label>
			</div>
			<image src="http://lerm.local/wp-content/uploads/2020/12/0030_Calque-2.png" class="ms-3 py-1" type="button" id="button-addon2" width='80' height='58' alt="<?php echo esc_html__( 'Get Email Captcha', 'lerm' ); ?>"></image>
		</div>
		<button id="regist-submit" type="submit" name='regist_submit' class="w-100 btn btn-primary" type="submit"><?php echo esc_html__( 'REGISTER', 'lerm' ); ?></button>
		<small id="regist-msg" class="invisible user-msg text-danger wow">#</small>
	</form>
	<hr class="my-3">
	<div class="text-center "><span><?php echo esc_html__( 'Have already an account?', 'lerm' ); ?> </span> <a id="login-btn" type="submit" name='btn_submit' class="my-4" type="submit"><?php echo esc_html__( 'Login', 'lerm' ); ?></a></div>
	<!-- </div> -->
<?php endif; ?>
