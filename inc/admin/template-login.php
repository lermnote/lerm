<?php
if ( ! empty( $error ) ) {
	echo '<p class="ludou-error">' . $error . '</p>';
}
if ( ! is_user_logged_in() ) {
	?>
<form name="loginform" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" class="ludou-login">
	<p>
	  <label for="log">用户名<br />
		<input type="text" name="log" id="log" class="input" value="
		<?php
		if ( ! empty( $user_name ) ) {
			echo $user_name;}
		?>
		" size="20" />
	  </label>
	</p>
	<p>
	  <label for="pwd">密码(至少6位)<br />
		<input id="pwd" class="input" type="password" size="25" value="" name="pwd" />
	  </label>
	</p>

	<p class="forgetmenot">
	  <label for="rememberme">
		<input name="rememberme" type="checkbox" id="rememberme" value="1" <?php checked( $rememberme ); ?> />
		记住我
	  </label>
	</p>

	<p class="submit">
	  <input type="hidden" name="redirect_to" value="
	  <?php
		if ( isset( $_GET['r'] ) ) {
			echo $_GET['r'];}
		?>
		" />
	  <input type="hidden" name="ludou_token" value="<?php echo $token; ?>" />
	  <button class="button button-primary button-large" type="submit">登录</button>
	</p>
</form>
<?php } else {
	echo '<p class="ludou-error">您已登录！（<a href="' . wp_logout_url() . '" title="登出">登出？</a>）</p>';
} ?>
