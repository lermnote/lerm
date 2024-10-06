<?php
/**
 * Login form Template
 *
 * @package Lerm https://lerm.net
 */
$forms = array(
	'username' => array(
		'type'        => 'text',
		'name'        => 'username',
		'id'          => 'login-username',
		'placeholder' => 'name@example.com',
		'label_text'  => __( 'Username' ),
	),
	'password' => array(
		'type'        => 'password',
		'name'        => 'password',
		'id'          => 'login-password',
		'placeholder' => 'Enter your password',
		'label_text'  => __( 'Password' ),
	),
);

$urememberme = array(
	'container_class' => 'checkbox',
	'class'           => '',
	'type'            => 'checkbox',
	'name'            => 'rememberme',
	'id'              => 'rememberme',
	'placeholder'     => '',
	'label_text'      => esc_html__( 'Remember Me', 'lerm' ),
	'required'        => '',
	'input_attrs'     => 'value="rememberme"',
);
?>

<!--start lgoin-->
<form method="post" id="login">
	<h2><?php echo esc_html__( 'Login', 'lerm' ); ?></h2>
	<?php
	foreach ( $forms as $key => $form ) {
		echo float_form_input( $form ); // phpcs:ignore WordPress.Security.EscapeOutput -- Reason: has been escaped.
	}
	?>
	<div class="d-flex justify-content-between align-items-center mb-3 ">
		<?php echo float_form_input( $urememberme ); // phpcs:ignore WordPress.Security.EscapeOutput -- Reason: has been escaped. ?>
		<a id="forget-btn" type="button" title="forget password" ><?php echo esc_html__( 'Forget password?', 'lerm' ); ?></a>
	</div>
	<button id="login-submit" type="submit" name='btn_submit' class="btn btn-primary w-100 mb-3" type="submit"><?php echo esc_html__( 'LOGIN', 'lerm' ); ?></button>
	<small id="login-msg" class="user-msg text-danger wow invisible">#</small>
</form>
<?php if ( get_option( 'users_can_register' ) ) : ?>
	<hr class="my-3">
	<div class="text-center ">
		<span><?php echo esc_html__( 'Don\'t have an account?', 'lerm' ); ?> </span>
		<a id="regist-btn" type="submit" name='btn_submit' class="my-4" type="submit">
			<?php echo esc_html__( 'Register', 'lerm' ); ?>
		</a>
	</div>
<?php endif; ?>

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
<!--end lgoin-->
