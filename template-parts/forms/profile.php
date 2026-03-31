<?php
/**
 * Profile form template.
 *
 * @package Lerm
 */

$user    = wp_get_current_user();
$gender  = (string) get_user_meta( $user->ID, 'gender', true );
$address = (string) get_user_meta( $user->ID, 'address', true );
?>

<form class="needs-validation" novalidate method="post" enctype="multipart/form-data" id="update-profile">
	<hr class="my-4">
	<h3>
		<?php
		printf(
			/* translators: %s: user nickname */
			esc_html__( 'Update profile for "%s"', 'lerm' ),
			esc_html( get_the_author_meta( 'nickname', $user->ID ) )
		);
		?>
	</h3>

	<div class="container text-center mb-3" style="position: relative; display: inline-block;">
		<?php echo get_avatar( get_current_user_id(), 128 ); ?>
		<input type="file" name="avatar" id="user_avatar">
		<label id="upload_text" style="position: absolute; left: 0; right: 0; bottom: 5px; display: none; align-items: center; justify-content: center; background: rgba(0, 0, 0, 0.5); color: white; font-size: 14px;">
			<?php esc_html_e( 'Upload avatar', 'lerm' ); ?>
		</label>
	</div>

	<div class="row g-3">
		<div class="col-md-6">
			<div class="form-floating">
				<input type="text" name="first_name" class="form-control" id="first_name" value="<?php echo esc_attr( get_user_meta( $user->ID, 'first_name', true ) ); ?>">
				<label for="first_name"><?php esc_html_e( 'First name', 'lerm' ); ?></label>
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-floating">
				<input type="text" name="last_name" class="form-control" id="last_name" value="<?php echo esc_attr( get_user_meta( $user->ID, 'last_name', true ) ); ?>">
				<label for="last_name"><?php esc_html_e( 'Last name', 'lerm' ); ?></label>
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-floating">
				<input type="text" name="nickname" class="form-control" id="nickname" value="<?php echo esc_attr( get_user_meta( $user->ID, 'nickname', true ) ); ?>">
				<label for="nickname"><?php esc_html_e( 'Nickname', 'lerm' ); ?></label>
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-floating">
				<input type="email" name="user_email" class="form-control" id="user_email" value="<?php echo esc_attr( $user->user_email ); ?>">
				<label for="user_email"><?php esc_html_e( 'Email address', 'lerm' ); ?></label>
			</div>
		</div>

		<div class="col-12">
			<div class="btn-group" role="group" aria-label="<?php echo esc_attr__( 'Gender options', 'lerm' ); ?>">
				<input type="radio" class="btn-check" name="gender" id="female" autocomplete="off" value="female" <?php checked( $gender, 'female' ); ?>>
				<label class="btn btn-outline-primary" for="female"><?php esc_html_e( 'Female', 'lerm' ); ?></label>

				<input type="radio" class="btn-check" name="gender" id="male" autocomplete="off" value="male" <?php checked( $gender, 'male' ); ?>>
				<label class="btn btn-outline-primary" for="male"><?php esc_html_e( 'Male', 'lerm' ); ?></label>
			</div>
		</div>

		<div class="col-12">
			<div class="form-floating">
				<input type="url" class="form-control" id="user_url" name="user_url" placeholder="<?php echo esc_attr__( 'Your homepage', 'lerm' ); ?>" value="<?php echo esc_attr( get_the_author_meta( 'user_url', $user->ID ) ); ?>">
				<label for="user_url" class="form-label"><?php esc_html_e( 'Website', 'lerm' ); ?></label>
			</div>
		</div>

		<div class="col-12">
			<div class="form-floating">
				<textarea class="form-control" id="description" name="description" style="height: 100px"><?php echo esc_textarea( get_the_author_meta( 'description', $user->ID ) ); ?></textarea>
				<label for="description"><?php esc_html_e( 'Description', 'lerm' ); ?></label>
			</div>
		</div>

		<div class="col-12">
			<div class="form-floating">
				<input type="text" class="form-control" id="address" name="address" placeholder="<?php echo esc_attr__( '1234 Main St', 'lerm' ); ?>" value="<?php echo esc_attr( $address ); ?>">
				<label for="address" class="form-label"><?php esc_html_e( 'Address', 'lerm' ); ?></label>
			</div>
		</div>

		<div class="col-12 d-flex gap-2">
			<button type="submit" name="update_profile" class="btn btn-primary btn-sm"><?php esc_html_e( 'Save changes', 'lerm' ); ?></button>
			<a href="<?php echo esc_url( lerm_get_frontend_account_page_url() ); ?>" class="btn btn-outline-secondary btn-sm"><?php esc_html_e( 'Cancel', 'lerm' ); ?></a>
		</div>

		<div class="col-12">
			<small id="update-profile-msg" class="user-msg text-danger wow invisible">#</small>
		</div>
	</div>
</form>
