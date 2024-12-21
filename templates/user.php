<?php
/**
 * Template Name: User Profile Page
 *
* @package Lerm https://lerm.net
 * @date    2016-10-26
 * @since lerm 2.0
 */
if ( ! is_user_logged_in() ) {
	wp_safe_redirect( home_url() ); // Redirect to homepage if user is not logged in
	exit;
}

$user_id = get_current_user_id();

$profile_data = array(
	'nickname'    => get_the_author_meta( 'nickname', $user_id ),
	'user_url'    => get_the_author_meta( 'user_url', $user_id ),
	'user_email'  => get_the_author_meta( 'user_email', $user_id ),
	'description' => get_the_author_meta( 'description', $user_id ),
);

get_header();
?>
<main role="main" class="container login-page align-items-center"><!--.container-->
	<?php get_template_part( 'template-parts/components/breadcrumb' ); ?>
	<div <?php lerm_row_class(); ?>><!--.row-->
		<div id="primary" <?php lerm_column_class(); ?>><!--.col-md-12 .col-lg-8-->
			<div class="site-main card mb-3" aria-hidden="true">
				<img src="https://images.unsplash.com/photo-1552652893-2aa10a0ab4df?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" class="card-img-top rounded bg-success" alt=" " style="min-height: 176px; height: 176px;">
				<div class="card-body">
					<div class="d-flex gap-3 align-items-start" aria-current="true">
						<figure class="figure" class="rounded" style="margin-top:-5rem; cursor: pointer;">
							<?php echo get_avatar( $user_id, 128 ); ?>
						</figure>
						<div>
							<h2 class="card-title">
								<i class="li li-heart"></i>
								<?php echo esc_html( $profile_data['nickname'] ); ?>
								<small class="badge text-bg-light opacity-50 text-nowrap">学前班</small>
								<span role="tablist" >
									<a class="btn btn-outline-primary btn-sm" id="v-pills-settings-tab" data-bs-toggle="tab" data-bs-target="#v-pills-settings" type="button" role="tab" aria-controls="v-pills-settings" aria-selected="false">Edit</a>
								</span>
							</h2>
							<p class="card-text opacity-50 text-nowrap">
								<?php echo esc_url( $profile_data['user_url'] ); ?>
								<a class="btn-sm" data-bs-toggle="collapse" href="#collapseExample" role="button" aria-expanded="false" aria-controls="collapseExample">
									<i class="li li-chevron-down"></i>
								</a>
							</p>
							<div class="collapse" id="collapseExample">
								<p class="card-text opacity-50 text-nowrap"><?php echo esc_html( $profile_data['user_email'] ); ?></p>
								<p class="card-text opacity-50 text-nowrap"><?php echo esc_html( $profile_data['description'] ); ?></p>
							</div>
						</div>
					</div>

					<div class="tab-content" id="v-pills-tabContent">
						<!-- start settings -->
						<div class="tab-pane fade" id="v-pills-settings" role="tabpanel" aria-labelledby="v-pills-settings-tab" tabindex="0">
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
		</div>
		<?php get_sidebar(); ?>
	</div>
</main>
<?php
get_footer();
