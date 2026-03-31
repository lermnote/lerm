<?php
/**
 * Register form template.
 *
 * @package Lerm
 */

use function Lerm\Support\float_form_input;

$redirect_to = isset( $_GET['redirect_to'] ) ? wp_validate_redirect( wp_unslash( (string) $_GET['redirect_to'] ), '' ) : '';
$login_url   = lerm_get_frontend_auth_page_url( 'login' );

if ( '' !== $redirect_to ) {
	$login_url = add_query_arg( 'redirect_to', $redirect_to, $login_url );
}

$forms = array(
	'username'         => array(
		'type'        => 'text',
		'name'        => 'username',
		'id'          => 'register-username',
		'placeholder' => esc_attr__( 'Choose a username', 'lerm' ),
		'label_text'  => __( 'Username', 'lerm' ),
	),
	'email'            => array(
		'type'        => 'email',
		'name'        => 'email',
		'id'          => 'register-email',
		'placeholder' => 'name@example.com',
		'label_text'  => __( 'Email', 'lerm' ),
	),
	'password'         => array(
		'container_class' => 'form-floating',
		'type'            => 'password',
		'name'            => 'password',
		'id'              => 'register-password',
		'placeholder'     => esc_attr__( 'Create a strong password', 'lerm' ),
		'label_text'      => __( 'Password', 'lerm' ),
	),
	'password_confirm' => array(
		'type'        => 'password',
		'name'        => 'password_confirm',
		'id'          => 'register-password-confirm',
		'placeholder' => esc_attr__( 'Confirm your password', 'lerm' ),
		'label_text'  => esc_html__( 'Confirm password', 'lerm' ),
	),
);
?>

<?php if ( get_option( 'users_can_register' ) ) : ?>
	<form method="post" id="regist" action="regist">
		<h2><?php echo esc_html__( 'Register', 'lerm' ); ?></h2>

		<?php if ( '' !== $redirect_to ) : ?>
			<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">
		<?php endif; ?>

		<?php
		echo float_form_input( $forms['username'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo float_form_input( $forms['email'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>

		<div class="input-group mb-3">
			<?php echo float_form_input( $forms['password'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<button class="btn btn-outline-secondary" id="regist-toggle" type="button" role="switch" aria-label="<?php echo esc_attr__( 'Show password', 'lerm' ); ?>" aria-checked="false"><?php echo esc_html__( 'Show', 'lerm' ); ?></button>
		</div>

		<?php echo float_form_input( $forms['password_confirm'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<button id="regist-submit" type="submit" name="regist_submit" class="w-100 btn btn-primary"><?php echo esc_html__( 'Register', 'lerm' ); ?></button>
		<small id="regist-msg" class="invisible user-msg text-danger wow">#</small>
	</form>

	<hr class="my-3">
	<div class="text-center">
		<span><?php echo esc_html__( 'Already have an account?', 'lerm' ); ?> </span>
		<a href="<?php echo esc_url( $login_url ); ?>" id="login-btn" name="btn_submit" class="change-form my-4" data-form="login">
			<?php echo esc_html__( 'Login', 'lerm' ); ?>
		</a>
	</div>
<?php endif; ?>
