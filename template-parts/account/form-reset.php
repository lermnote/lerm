<?php
/**
 * Password reset form Template
 *
 * @package Lerm https://lerm.net
 */
?>

<!--start reset-->
<form method="post" id="reset">
	<h2><?php echo esc_html__( 'PASSWORD RESET', 'lerm' ); ?></h2>
	<label for="reset-email"><?php echo esc_html__( 'Enter your email address and we\'ll send you an email with instructions to reset your password.', 'lerm' ); ?></label>
	<div class="form-floating mb-3">
		<input id="reset-email" type="email"  name="email"  required  class="form-control" placeholder="Email" aria-label="Email" aria-describedby="Email">
		<label for="floatingEmail"><?php echo esc_html__( 'Email', 'lerm' ); ?></label>
	</div>
	<input type="hidden" name="redirect_to" value="">
	<button id="reset-submit" type="submit" name='reset_submit' class="w-100 btn btn-primary" type="submit"><?php echo esc_html__( 'RESET PASSWORD', 'lerm' ); ?></button>
	<small id="reset-msg" class="invisible user-msg text-danger wow">#</small>
</form>
<hr class="my-3">
<div class="text-center"><span><?php echo esc_html__( 'Return to login', 'lerm' ); ?> </span> <a id="login-btn2" type="submit" name='btn_submit' class="my-4" type="submit"><?php echo esc_html__( 'Login', 'lerm' ); ?></a></div>
<!--end reset-->
