<?php
/**
 * Template Name: User Profile Page
 *
* @package Lerm https://lerm.net
 * @date    2016-10-26
 * @since lerm 2.0
 */
get_header();
\Lerm\Inc\Ajax\UserProfile::instance();
$user_meta = \Lerm\Inc\Ajax\UserProfile::get_user_meta_data();

// Check if user is logged in
if ( ! is_user_logged_in() ) {
	wp_safe_redirect( home_url() ); // Redirect to homepage if user is not logged in
	exit;
}
?>
<main role="main" class="container login-page align-items-center"><!--.container-->
	<div class="card mb-3" aria-hidden="true">
	<img src="http://lerm.local/wp-content/uploads/2024/09/2024082513474928.jpg" class="card-img-top rounded" alt="...">
		<div class="card-body">
			<div class="d-flex gap-3 align-items-start" aria-current="true">
				<img src="<?php echo esc_attr( $user_meta['avatar'] ); ?>" alt="<?php echo esc_attr( $user_meta['username'] ); ?>" id="avatar-preview" class="flex-shrink-0 bg-light" width="128" height="128" style="margin-top:-5rem; cursor: pointer;">
				<div>
					<h2 class="card-title">
						<i class="fa fa-heart"></i>
						<?php echo esc_attr( $user_meta['username'] ); ?>
						<small class="badge text-bg-light opacity-50 text-nowrap">学前班</small>
						<a class="btn btn-outline-primary btn-sm" id="v-pills-settings-tab2" data-bs-toggle="pill" data-bs-target="#v-pills-settings" type="button" role="tab" aria-controls="v-pills-settings" aria-selected="false">Edit</a>
					</h2>
					<p class="card-text opacity-50 text-nowrap">
						<?php echo esc_attr( $user_meta['website'] ); ?>
						<a class="btn-sm " data-bs-toggle="collapse" href="#collapseExample" role="button" aria-expanded="false" aria-controls="collapseExample">
							<i class="fa fa-chevron-down"></i>
						</a>
					</p>
					<div class="collapse" id="collapseExample">
						<p class="card-text opacity-50 text-nowrap"><?php echo esc_attr( $user_meta['email'] ); ?></p >
						<p class="card-text opacity-50 text-nowrap"><?php echo esc_attr( $user_meta['description'] ); ?></p >
						<p class="card-text opacity-50 text-nowrap"><?php echo esc_attr( $user_meta['website'] ); ?></p >
						<p class="card-text opacity-50 text-nowrap"><?php echo esc_attr( $user_meta['website'] ); ?></p >
					</div>
				</div>
			</div>

			<div class="nav nav-pills " id="v-pills-tab" role="tablist" >
				<button class="nav-link visually-hidden" id="v-pills-settings-tab" data-bs-toggle="tab" data-bs-target="#v-pills-settings" type="button" role="tab" aria-controls="v-pills-settings" aria-selected="false">Settings</button>
			</div>
			<div class="tab-content" id="v-pills-tabContent">
				<!-- start settings -->
				<div class="tab-pane fade" id="v-pills-settings" role="tabpanel" aria-labelledby="v-pills-settings-tab" tabindex="0">
					<hr class="my-4">
					<?php get_template_part( 'template-parts/account/form', 'profile' ); ?>
				</div><!-- end settings -->

				<!-- start meassage -->
				<div class="tab-pane fade" id="v-pills-messages">
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
		})
	</script>
<?php
get_footer();
