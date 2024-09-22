<?php
/**
 * Template Name: User Profile Page
 *
* @package Lerm https://lerm.net
 * @date    2016-10-26
 * @since lerm 2.0
 */
get_header();
require_once LERM_DIR . 'inc/classes/user/class-user-profile.php';
\Lerm\Inc\User_Profile::instance();
$user_meta = \Lerm\Inc\User_Profile::get_user_meta_data();
// var_dump($user_meta );
// Check if user is logged in
if ( ! is_user_logged_in() ) {
	wp_safe_redirect( home_url() ); // Redirect to homepage if user is not logged in
	exit;
}
?>

<main role="main" class="container login-page align-items-center"><!--.container-->
	<div class="card mb-3">
	<img src="http://lerm.net/wp-content/uploads/2019/05/bing-e1569245501792.jpg" class="card-img-top" alt="...">


		<div class="card-body">
			<div href="#" class="d-flex gap-3 align-items-end" aria-current="true">
				<img src="<?php echo esc_attr( $user_meta['avatar'] ); ?>" alt="<?php echo $user_meta['username']; ?>" id="avatar-preview" class="flex-shrink-0 bg-light" width="128" height="128" style="margin-top:-5rem; cursor: pointer;">
				<input type="file" class="visually-hidden" id="avatar-upload" accept="image/*">
				<!-- <input
	  id="avatar-upload"
	  type="image"
	  src="http://lerm.net/wp-content/uploads/2019/05/bing-e1569245501792.jpg"
	  alt="<?php echo $user_meta['username']; ?>"
	  width="128" height="128" style="margin-top:-5rem;" /> -->
				<div>
					<h2 class="card-title"><?php echo $user_meta['username']; ?> <small class="badge text-bg-light opacity-50 text-nowrap">学前班</small></h4>
					<p class="card-text opacity-50 text-nowrap"><?php echo $user_meta['description']; ?></p >
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-3">
			<div class="nav flex-column nav-pills me-3  card  mb-auto " id="v-pills-tab" role="tablist" aria-orientation="vertical">
				<button class="nav-link active" id="v-pills-home-tab" data-bs-toggle="pill" data-bs-target="#v-pills-home" type="button" role="tab" aria-controls="v-pills-home" aria-selected="true">Home</button>
				<!-- <button class="nav-link" id="v-pills-profile-tab" data-bs-toggle="pill" data-bs-target="#v-pills-profile" type="button" role="tab" aria-controls="v-pills-profile" aria-selected="false">Profile</button> -->
				<!-- <button class="nav-link" id="v-pills-disabled-tab" data-bs-toggle="pill" data-bs-target="#v-pills-disabled" type="button" role="tab" aria-controls="v-pills-disabled" aria-selected="false" disabled>Disabled</button> -->
				<button class="nav-link" id="v-pills-messages-tab" data-bs-toggle="pill" data-bs-target="#v-pills-messages" type="button" role="tab" aria-controls="v-pills-messages" aria-selected="false">Messages</button>
				<button class="nav-link visually-hidden" id="v-pills-settings-tab" data-bs-toggle="pill" data-bs-target="#v-pills-settings" type="button" role="tab" aria-controls="v-pills-settings" aria-selected="false">Settings</button>
			</div>
		</div>
		<div class="col-md-9">

			<div class="card tab-content" id="v-pills-tabContent">
				<!-- start home -->
				<div class="card-body tab-pane fade show active" id="v-pills-home" role="tabpanel" aria-labelledby="v-pills-home-tab" tabindex="0">
					<a id="v-pills-settings-tab2" data-bs-toggle="pill" data-bs-target="#v-pills-settings" type="button" role="tab" aria-controls="v-pills-settings" aria-selected="false">Change</a>
					<ul class="list-group list-group-flush">
						<li class="list-group-item border-0 py-3"><span class="opacity-50">昵称： </span><?php echo $user_meta['username']; ?></li>
						<li class="list-group-item border-0 py-3"><span class="opacity-50">性别： </span><?php echo $user_meta['gender']; ?></li>
						<li class="list-group-item border-0 py-3"><span class="opacity-50">邮箱： </span> <?php echo $user_meta['email']; ?></li>
						<li class="list-group-item border-0 py-3"><span class="opacity-50">描述： </span> <?php echo $user_meta['description']; ?></li>
						<li class="list-group-item border-0 py-3"><span class="opacity-50">网址： </span> <?php echo $user_meta['website']; ?></li>
						<li class="list-group-item border-0 py-3"><span class="opacity-50">简介： </span> </li>
					</ul>
				</div>
				<!-- end home -->
				<!-- start settings -->
				<div class="card-body tab-pane fade" id="v-pills-settings" role="tabpanel" aria-labelledby="v-pills-settings-tab" tabindex="0">
					<a class="icon-link icon-link-hover" id="v-pills-home-tab2" data-bs-toggle="pill" data-bs-target="#v-pills-settings" type="button" role="tab" aria-controls="v-pills-settings" aria-selected="false">
						<svg class="bi" aria-hidden="true"><use xlink:href="#arrow-left"></use></svg>
						Back to home
					</a>
					<form class="needs-validation" novalidate method="post">
						<div class="row g-3">
							<div class="col-12">
								<label for="username" class="form-label">Username</label>
								<div class="input-group has-validation">
									<span class="input-group-text">@</span>
									<input type="text" class="form-control" id="username" name="username" placeholder="Username" required value='<?php echo $user_meta['username']; ?>'>
									<div class="invalid-feedback">
										Your username is required.
									</div>
								</div>
							</div>
							<div class="col-12">
								<label for="email" class="form-label">Email<span class="text-body-secondary"></span></label>
								<input type="email" class="form-control" id="email" name="email" placeholder="you@example.com" value='<?php echo $user_meta['email']; ?>'>
								<div class="invalid-feedback">
									Please enter a valid email address for shipping updates.
								</div>
							</div>
							<div class="my-3">
								<div class="btn-group" role="group" aria-label="Basic radio toggle button group">
									<input type="radio" class="btn-check" name="gender" id="female" autocomplete="off" value="female">
									<label class="btn btn-outline-primary" for="female">Female</label>

									<input type="radio" class="btn-check" name="gender" id="male" autocomplete="off" value="male">
									<label class="btn btn-outline-primary" for="male">Male</label>
								</div>
							</div>
							<div class="col-12">
								<label for="description">Description</label>
								<textarea class="form-control" id="description" name="description"><?php echo $user_meta['description']; ?></textarea>
							</div>

							<div class="col-12">
								<label for="website" class="form-label">Website <span class="text-body-secondary"></span></label>
								<input type="text" class="form-control" id="website" name="website" placeholder="your homepage" value='<?php echo $user_meta['website']; ?>'>
							</div>

							<div class="col-12">
								<label for="address" class="form-label">Address</label>
								<input type="text" class="form-control" id="address" placeholder="1234 Main St" required >
								<div class="invalid-feedback">
									Please enter your shipping address.
								</div>
							</div>
						</div>

						<hr class="my-4">
						<button type="submit" name="update_profile" class="w-100 btn btn-primary btn-lg">Update Profile</button>
					</form>
				</div>
				<!-- start meassage -->
				<div class="card-body tab-pane fade" id="v-pills-messages" role="tabpanel" aria-labelledby="v-pills-messages-tab" tabindex="0">
					<div class="list-group list-group-flush border-bottom scrollarea">
						<a href="#" class="list-group-item list-group-item-action active py-3 lh-sm" aria-current="true">
							<div class="d-flex w-100 align-items-center justify-content-between">
							<strong class="mb-1">List group item heading</strong>
							<small>Wed</small>
							</div>
							<div class="col-10 mb-1 small">Some placeholder content in a paragraph below the heading and date.</div>
						</a>
						<a href="#" class="list-group-item list-group-item-action py-3 lh-sm">
							<div class="d-flex w-100 align-items-center justify-content-between">
							<strong class="mb-1">List group item heading</strong>
							<small class="text-body-secondary">Tues</small>
							</div>
							<div class="col-10 mb-1 small">Some placeholder content in a paragraph below the heading and date.</div>
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</main>
<script>
		document.addEventListener("DOMContentLoaded", function (e) {
			document.getElementById("v-pills-settings-tab2").addEventListener('click',function(e){
				document.getElementById("v-pills-settings-tab").click();
			})
			document.getElementById("v-pills-home-tab2").addEventListener('click',function(e){
				document.getElementById("v-pills-home-tab").click();
			})

			const avatarPreview = document.getElementById('avatar-preview');
			const fileInput = document.getElementById('avatar-upload');

			avatarPreview.addEventListener('click', function () {
				fileInput.click();
			});

			fileInput.addEventListener('change', function () {
				if (fileInput.files && fileInput.files[0]) {
					const reader = new FileReader();

					reader.onload = function (e) {
						fileInput.src = e.target.result;
					}

					reader.readAsDataURL(fileInput.files[0]);
				}
			});
		})
	</script>
<?php
get_footer();
