<?php
/**
 * Login form template.
 *
 * @package Lerm
 */

use function Lerm\Support\float_form_input;

$redirect_to  = isset( $_GET['redirect_to'] ) ? wp_validate_redirect( wp_unslash( (string) $_GET['redirect_to'] ), '' ) : '';
$reset_url    = lerm_get_frontend_auth_page_url( 'reset' );
$register_url = lerm_get_frontend_auth_page_url( 'regist' );

if ( '' !== $redirect_to ) {
	$reset_url    = add_query_arg( 'redirect_to', $redirect_to, $reset_url );
	$register_url = add_query_arg( 'redirect_to', $redirect_to, $register_url );
}

$forms = array(
	'username' => array(
		'type'        => 'text',
		'name'        => 'username',
		'id'          => 'login-username',
		'placeholder' => esc_attr__( 'Enter your username or email', 'lerm' ),
		'label_text'  => __( 'Username or email', 'lerm' ),
	),
	'password' => array(
		'type'        => 'password',
		'name'        => 'password',
		'id'          => 'login-password',
		'placeholder' => esc_attr__( 'Enter your password', 'lerm' ),
		'label_text'  => __( 'Password', 'lerm' ),
	),
);

$remember_me = array(
	'container_class' => 'checkbox',
	'class'           => '',
	'type'            => 'checkbox',
	'name'            => 'remember',
	'id'              => 'remember',
	'placeholder'     => '',
	'label_text'      => esc_html__( 'Remember me', 'lerm' ),
	'required'        => '',
	'input_attrs'     => array(
		'value' => '1',
	),
);
?>

<form method="post" id="login" action="login">
	<h2><?php echo esc_html__( 'Login', 'lerm' ); ?></h2>

	<?php if ( '' !== $redirect_to ) : ?>
		<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">
	<?php endif; ?>

	<?php
	foreach ( $forms as $form ) {
		echo float_form_input( $form ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>

	<div class="d-flex justify-content-between align-items-center mb-3">
		<?php echo float_form_input( $remember_me ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<a href="<?php echo esc_url( $reset_url ); ?>" class="change-form" id="forget-btn" title="<?php echo esc_attr__( 'Forgot password', 'lerm' ); ?>" data-form="reset">
			<?php echo esc_html__( 'Forgot password?', 'lerm' ); ?>
		</a>
	</div>

	<button id="login-submit" type="submit" name="btn_submit" class="btn btn-primary w-100 mb-3"><?php echo esc_html__( 'Log in', 'lerm' ); ?></button>
	<small id="login-msg" class="user-msg text-danger wow invisible">#</small>
</form>

<?php if ( get_option( 'users_can_register' ) ) : ?>
	<hr class="my-3">
	<div class="text-center">
		<span><?php echo esc_html__( 'Don\'t have an account?', 'lerm' ); ?> </span>
		<a class="change-form my-4" href="<?php echo esc_url( $register_url ); ?>" id="regist-btn" data-form="regist">
			<?php echo esc_html__( 'Register', 'lerm' ); ?>
		</a>
	</div>
<?php endif; ?>
