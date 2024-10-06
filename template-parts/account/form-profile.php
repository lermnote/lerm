<?php
/**
 * Login form Template
 *
 * @package Lerm https://lerm.net
 */
$user   = wp_get_current_user();
$fields = array(
	'first-name'  => array( 'First name', 'first_name' ),
	'last-name'   => array( 'Last name', 'last_name' ),
	'nickname'    => array( 'Nickname', 'nickname' ),
	'user_email'  => array( 'Email address', 'user_email' ),
	'user_url'    => array( 'Website', 'user_url' ),
	'description' => array( 'Description', 'description' ),
);
?>
<form class="needs-validation" novalidate method="post" enctype="multipart/form-data" id="update-profile">
	<hr class="my-4">
	<h3>Update Information for &quot;<?php the_author_meta( 'nickname', $user->ID ); ?>&quot;</h3></br>

	<div  class="container text-center mb-3" style="position: relative; display: inline-block;">
		<?php echo get_avatar( get_current_user_id(), 128 ); ?>
		<input type="file" name="avatar" id="user_avatar"/>
		<label id="upload_text" style="position: absolute; left: 0; right: 0; bottom: 5px; display: none; align-items: center; justify-content: center; background: rgba(0, 0, 0, 0.5); color: white; font-size: 14px;">
			<?php esc_html__( 'Upload Avatar', 'lerm' ); ?>
		</label>
	</div>
	<div class="row g-3">
		<?php
		foreach ( $fields as $name => $field ) {
			?>
			<div class="col-md">
				<div class="form-floating">
					<input type="<?php echo esc_attr( 'user_email' === $name ? 'email' : 'text' ); ?>" name="<?php echo esc_attr( $name ); ?>" class="form-control" id="<?php echo esc_attr( $name ); ?>" placeholder="" value="<?php echo esc_attr( get_user_meta( $user->ID, $field[1], true ) ); ?>">
					<label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $field[0] ); ?></label>
				</div>
			</div>
			<?php
		}
		?>
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
				<input type="url" class="form-control" id="user-url" name="user_url" placeholder="your homepage" value='<?php the_author_meta( 'user_url', $user->ID ); ?>'>
				<label for="user-url" class="form-label">Website</label>
			</div>
		</div>
		<div class="col-12">
			<div class="form-floating">
				<textarea class="form-control" id="description" name="description" style="height: 100px"><?php the_author_meta( 'description', $user->ID ); ?></textarea>
				<label for="description">Description</label>
			</div>
		</div>
		<div class="col-12">
			<div class="form-floating">
				<input type="text" class="form-control" id="address" placeholder="1234 Main St" >
				<label for="address" class="form-label">Address</label>
			</div>
		</div>

		<button type="submit" name="update_profile" class="w-100 btn btn-primary btn-sm">Update Profile</button>
	</div>
</form>
