<?php
/**
 * Login form template.
 *
 * @package Lerm
 */

use function Lerm\Support\float_form_input;

$forms = array(
	'username' => array(
		'type'        => 'text',
		'name'        => 'username',
		'id'          => 'login-username',
		'placeholder' => esc_attr__( 'Enter your username', 'lerm' ),
		'label_text'  => __( 'Username', 'lerm' ),
	),
	'password' => array(
		'type'        => 'password',
		'name'        => 'password',
		'id'          => 'login-password',
		'placeholder' => esc_attr__( 'Enter your password', 'lerm' ),
		'label_text'  => __( 'Password', 'lerm' ),
	),
);

$urememberme = array(
	'container_class' => 'checkbox',
	'class'           => '',
	'type'            => 'checkbox',
	'name'            => 'rememberme',
	'id'              => 'rememberme',
	'placeholder'     => '',
	'label_text'      => esc_html__( 'Remember me', 'lerm' ),
	'required'        => '',
	'input_attrs'     => array(
		'value' => 'rememberme',
	),
);
?>

<form method="post" id="login" action="login">
	<h2><?php echo esc_html__( 'Login', 'lerm' ); ?></h2>
	<?php
	foreach ( $forms as $form ) {
		echo float_form_input( $form ); // phpcs:ignore WordPress.Security.EscapeOutput -- Escaped in helper.
	}
	?>
	<div class="d-flex justify-content-between align-items-center mb-3">
		<?php echo float_form_input( $urememberme ); // phpcs:ignore WordPress.Security.EscapeOutput -- Escaped in helper. ?>
		<a href="<?php echo esc_url( home_url( '/reset/' ) ); ?>" class="change-form" id="forget-btn" type="button" title="<?php echo esc_attr__( 'Forgot password', 'lerm' ); ?>" data-form="reset"><?php echo esc_html__( 'Forgot password?', 'lerm' ); ?></a>
	</div>
	<button id="login-submit" type="submit" name="btn_submit" class="btn btn-primary w-100 mb-3"><?php echo esc_html__( 'Log in', 'lerm' ); ?></button>
	<small id="login-msg" class="user-msg text-danger wow invisible">#</small>
</form>
<?php if ( get_option( 'users_can_register' ) ) : ?>
	<hr class="my-3">
	<div class="text-center">
		<span><?php echo esc_html__( 'Don\'t have an account?', 'lerm' ); ?> </span>
		<a class="change-form my-4" href="<?php echo esc_url( home_url( '/register/' ) ); ?>" id="regist-btn" data-form="regist">
			<?php echo esc_html__( 'Register', 'lerm' ); ?>
		</a>
	</div>
<?php endif; ?>

<div class="text-center py-3">
	<label class="py-3" for=""><?php echo esc_html__( 'Or continue with', 'lerm' ); ?></label>
	<div class="social-share d-flex justify-content-around gap-1" data-initialized="true">
		<a href="#" class="social-share-icon icon-weibo btn-light btn-sm" target="_blank" rel="noreferrer">
			<i class="fa fa-weibo"></i>
		</a>
		<a href="#" class="social-share-icon icon-qq btn-light btn-sm" target="_blank" rel="noreferrer">
			<i class="fa fa-qq"></i>
		</a>
		<a href="#" class="social-share-icon icon-facebook btn-light btn-sm" target="_blank" rel="noreferrer">
			<i class="fa fa-facebook"></i>
		</a>
		<a href="#" class="social-share-icon icon-twitter btn-light btn-sm" target="_blank" rel="noreferrer">
			<i class="fa fa-twitter"></i>
		</a>
		<a href="#" class="social-share-icon icon-github btn-light btn-sm" rel="noreferrer">
			<i class="fa fa-github"></i>
		</a>
	</div>
</div>
