<?php
/**
 * Login form Template
 *
 * @package Lerm https://lerm.net
 */
$user_meta = \Lerm\Inc\Ajax\UserProfile::get_user_meta_data();
?>
<form class="needs-validation" novalidate method="post">
	<div class="row g-3">
		<div class="col-md">
			<div class="form-floating">
			<input type="username" class="form-control" id="floatingInputGrid" placeholder="" value="<?php echo esc_attr( $user_meta['username'] ); ?>">
			<label for="floatingInputGrid">Username</label>
			</div>
		</div>
		<div class="col-md">
			<div class="form-floating">
			<input type="email" class="form-control" id="floatingInputGrid" placeholder="name@example.com" value="<?php echo esc_attr( $user_meta['email'] ); ?>">
			<label for="floatingInputGrid">Email address</label>
			</div>
		</div>
		<div class="col-12">
			<div class="btn-group" role="group" aria-label="Basic radio toggle button group">
				<input type="radio" class="btn-check" name="gender" id="female" autocomplete="off" value="female">
				<label class="btn btn-outline-primary" for="female">Female</label>
				<input type="radio" class="btn-check" name="gender" id="male" autocomplete="off" value="male">
				<label class="btn btn-outline-primary" for="male">Male</label>
			</div>
		</div>
		<div class="col-12">
			<div class="form-floating">
				<input type="text" class="form-control" id="website" name="website" placeholder="your homepage" value='<?php echo esc_attr( $user_meta['website'] ); ?>'>
				<label for="website" class="form-label">Website</label>
			</div>
		</div>
		<div class="col-12">
			<div class="form-floating">
				<input type="text" class="form-control" id="address" placeholder="1234 Main St" >
				<label for="address" class="form-label">Address</label>
			</div>
		</div>
		<div class="col-12">
			<div class="form-floating">
				<textarea class="form-control" id="description" name="description" style="height: 100px"><?php echo esc_attr( $user_meta['description'] ); ?></textarea>
				<label for="description">Description</label>
			</div>
		</div>
		<button type="submit" name="update_profile" class="w-100 btn btn-primary btn-sm">Update Profile</button>
	</div>
</form>
