<?php
/**
 * Template Name: Login Page Template
 *
* @package Lerm https://lerm.net
 * @date    2016-10-26
 * @since lerm 2.0
 */
if ( is_user_logged_in() ) {
	wp_safe_redirect( home_url() );
	exit;
}
global $user_ID;
get_header();
?>
<style>
	.login-page {
		background-image: url(http://lerm.local/wp-content/uploads/2020/12/0030_Calque-2.png);
		background-size: cover;
		background-repeat: no-repeat;
		background-position: center;
	}
</style>
<main role="main" class="container"><!--.container-->
	<div class="row py-3">
		<div class="col-lg-7 text-center text-lg-start d-none d-lg-block mb-3">
			<?php
			if ( have_posts() ) :
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content/content', 'page' );
				endwhile;
				?>
			<?php endif; ?>
		</div>

		<div class="col-lg-4">
			<section class="tab-content card loading-animate p-3" id="myTabContent">
				<div id="myTab" role="tablist">
					<button class="active visually-hidden" id="login-tab" data-bs-toggle="tab" data-bs-target="#login-tab-pane" type="hidden" role="tab" aria-controls="login-tab-pane" aria-selected="true">Login</button>
					<button class="visually-hidden" id="forget-tab" data-bs-toggle="tab" data-bs-target="#forget-tab-pane" type="hidden" role="tab" aria-controls="forget-tab-pane" aria-selected="false">Forget</button>
					<?php if ( get_option( 'users_can_register' ) ) : ?>
						<!-- <button class="visually-hidden" id="regist-tab" data-bs-toggle="tab" data-bs-target="#regist-tab-pane" type="hidden" role="tab" aria-controls="regist-tab-pane" aria-selected="false">Regist</button> -->
					<?php endif; ?>
				</div>

				<!--start lgoin-->
				<div class="login tab-pane fade show active" id="login-tab-pane" role="tabpanel" aria-labelledby="login-tab" tabindex="0">
					<?php get_template_part( 'template-parts/account/form', 'login' ); ?>
				</div><!--end lgoin-->

				<?php if ( get_option( 'users_can_register' ) ) : ?>
					<!--start regist-->
					<div class="regist tab-pane fade" id="regist-tab-pane" role="tabpanel" aria-labelledby="regist-tab" tabindex="0">
						<?php get_template_part( 'template-parts/account/form', 'regist' ); ?>
					</div><!--end regist-->
				<?php endif; ?>

				<!--start forget-->
				<div class="forget tab-pane fade" id="forget-tab-pane" role="tabpanel" aria-labelledby="forget-tab" tabindex="0">
					<?php get_template_part( 'template-parts/account/form', 'reset' ); ?>
				</div><!--end forget-->
			</section>
		</div>
	</div>
</main>
<script>
	<?php //if ( ! is_user_logged_in() ) : ?>
	// document.addEventListener("DOMContentLoaded", function (e) {
	// 	document.getElementById("forget-btn").addEventListener('click',function(e){
	// 		document.getElementById("forget-tab").click();
	// 	})
	// 	<?php //if ( get_option( 'users_can_register' ) ) : ?>
	// 		document.getElementById("regist-btn").addEventListener('click',function(e){
	// 			document.getElementById("regist-tab").click();
	// 		})
	// 		document.getElementById("login-btn").addEventListener('click',function(e){
	// 		document.getElementById("login-tab").click();
	// 	})
	// 	<?php //endif; ?>

	// 	document.getElementById("login-btn2").addEventListener('click',function(e){
	// 		document.getElementById("login-tab").click();
	// 	})
	// })
	<?php //endif; ?>
</script>
<?php
get_footer();
