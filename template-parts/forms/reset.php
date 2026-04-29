<?php
/**
 * Password reset form template.
 *
 * @package Lerm
 */

$redirect_to = isset( $_GET['redirect_to'] ) ? wp_validate_redirect( wp_unslash( (string) $_GET['redirect_to'] ), '' ) : '';
$login_url   = lerm_get_frontend_auth_page_url( 'login' );

if ( '' !== $redirect_to ) {
	$login_url = add_query_arg( 'redirect_to', $redirect_to, $login_url );
}
?>

<form method="post" id="reset" action="reset">
	<h2><?php echo esc_html__( 'Password reset', 'lerm' ); ?></h2>
	<label for="reset-login"><?php echo esc_html__( 'Enter your username or email address and we\'ll send you a password reset link.', 'lerm' ); ?></label>

	<div class="form-floating mb-3">
		<input id="reset-login" type="text" name="login" required class="form-control" placeholder="<?php echo esc_attr__( 'Username or email', 'lerm' ); ?>" aria-label="<?php echo esc_attr__( 'Username or email', 'lerm' ); ?>">
		<label for="reset-login"><?php echo esc_html__( 'Username or email', 'lerm' ); ?></label>
	</div>

	<?php if ( '' !== $redirect_to ) : ?>
		<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">
	<?php endif; ?>

	<button id="reset-submit" type="submit" name="reset_submit" class="w-100 btn btn-primary"><?php echo esc_html__( 'Reset password', 'lerm' ); ?></button>
	<small id="reset-msg" class="invisible user-msg text-danger wow">#</small>
</form>

<hr class="my-3">
<div class="text-center">
	<span><?php echo esc_html__( 'Return to login', 'lerm' ); ?> </span>
	<a href="<?php echo esc_url( $login_url ); ?>" id="login-btn2" class="change-form my-4" data-form="login"><?php echo esc_html__( 'Login', 'lerm' ); ?></a>
</div>
