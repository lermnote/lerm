<?php
/**
 * Profile form template.
 *
 * @package Lerm
 */

$user   = wp_get_current_user();
$fields = array(
	'first-name'  => array( esc_html__( 'First name', 'lerm' ), 'first_name' ),
	'last-name'   => array( esc_html__( 'Last name', 'lerm' ), 'last_name' ),
	'nickname'    => array( esc_html__( 'Nickname', 'lerm' ), 'nickname' ),
	'user_email'  => array( esc_html__( 'Email address', 'lerm' ), 'user_email' ),
	'user_url'    => array( esc_html__( 'Website', 'lerm' ), 'user_url' ),
	'description' => array( esc_html__( 'Description', 'lerm' ), 'description' ),
);
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
			<div class="btn-group" role="group" aria-label="<?php echo esc_attr__( 'Gender options', 'lerm' ); ?>">
				<input type="radio" class="btn-check" name="gender" id="female" autocomplete="off" value="female">
				<label class="btn btn-outline-primary" for="female"><?php esc_html_e( 'Female', 'lerm' ); ?></label>
				<input type="radio" class="btn-check" name="gender" id="male" autocomplete="off" value="male">
				<label class="btn btn-outline-primary" for="male"><?php esc_html_e( 'Male', 'lerm' ); ?></label>
			</div>
		</div>
		<div class="col-12">
			<div class="form-floating">
				<input type="url" class="form-control" id="user-url" name="user_url" placeholder="<?php echo esc_attr__( 'Your homepage', 'lerm' ); ?>" value="<?php echo esc_attr( get_the_author_meta( 'user_url', $user->ID ) ); ?>">
				<label for="user-url" class="form-label"><?php esc_html_e( 'Website', 'lerm' ); ?></label>
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
				<input type="text" class="form-control" id="address" placeholder="<?php echo esc_attr__( '1234 Main St', 'lerm' ); ?>">
				<label for="address" class="form-label"><?php esc_html_e( 'Address', 'lerm' ); ?></label>
			</div>
		</div>
		<div>
			<button type="submit" name="update_profile" class="btn btn-primary btn-sm"><?php esc_html_e( 'Save changes', 'lerm' ); ?></button>
			<button type="button" name="cancel" class="btn btn-danger btn-sm"><?php esc_html_e( 'Cancel', 'lerm' ); ?></button>
		</div>
	</div>
</form>
