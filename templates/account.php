<?php
/**
 * Template Name: Login Page Template
 *
* @package Lerm https://lerm.net
 * @date    2016-10-26
 * @since lerm 2.0
 */
global $user_ID;
get_header();
?>
<?php
if ( ! is_user_logged_in() ) :
	;
	?>
	<style>
		.login-page {
			background-image: url(http://lerm.local/wp-content/uploads/2020/12/0030_Calque-2.png);
			background-size: cover;
			background-repeat: no-repeat;
			background-position: center;
		}
	</style>

<main role="main" class="container-fluid login-page d-flex flex-column justify-content-center bg-body-tertiary bg-opacity-75"><!--.container-->
	<div class="container">
		<div class="row g-lg-5 py-5">
			<div class="col-lg-7 text-center text-lg-start">
				<blockquote class="display-5 fw-bold lh-1 text-body-emphasis mb-3">
					<p>The better we get at getting better, the faster we will get better.</p>
					<p class="display-6" >—Douglas Engelbart</p>
				</blockquote>
			</div>
			<div class="col-md-10 col-lg-4 mx-auto tab-content border rounded-3 bg-body-tertiary p-4 p-md-5" id="myTabContent">
				<div id="myTab" role="tablist">
					<button class="active visually-hidden" id="login-tab" data-bs-toggle="tab" data-bs-target="#login-tab-pane" type="hidden" role="tab" aria-controls="login-tab-pane" aria-selected="true">Login</button>
					<button class="visually-hidden" id="regist-tab" data-bs-toggle="tab" data-bs-target="#regist-tab-pane" type="hidden" role="tab" aria-controls="regist-tab-pane" aria-selected="false">Regist</button>
					<button class="visually-hidden" id="forget-tab" data-bs-toggle="tab" data-bs-target="#forget-tab-pane" type="hidden" role="tab" aria-controls="forget-tab-pane" aria-selected="false">Forget</button>
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

			</div>
		</div>
	</div>
</main>
	<?php
else :
	get_template_part( 'templates/user' );
endif;
?>
<script>
	<?php if ( ! is_user_logged_in() ) : ?>
	document.addEventListener("DOMContentLoaded", function (e) {
		document.getElementById("forget-btn").addEventListener('click',function(e){
			document.getElementById("forget-tab").click();
		})
		<?php if ( get_option( 'users_can_register' ) ) : ?>
			document.getElementById("regist-btn").addEventListener('click',function(e){
				document.getElementById("regist-tab").click();
			})
		<?php endif; ?>
		document.getElementById("login-btn").addEventListener('click',function(e){
			document.getElementById("login-tab").click();
		})
		document.getElementById("login-btn2").addEventListener('click',function(e){
			document.getElementById("login-tab").click();
		})
	})
	<?php endif; ?>
</script>

<?php
get_footer();
