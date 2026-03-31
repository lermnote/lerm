<?php
/**
 * Template Name: Login Page Template
 *
 * @package Lerm
 * @since  2.0
 */

$account_url = lerm_get_frontend_account_page_url();

if ( is_user_logged_in() ) {
	wp_safe_redirect( $account_url );
	exit;
}

$show_register = (bool) get_option( 'users_can_register' );
$active_tab    = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( (string) $_GET['tab'] ) ) : 'login';

if ( ! in_array( $active_tab, array( 'login', 'regist', 'reset' ), true ) ) {
	$active_tab = 'login';
}

if ( 'regist' === $active_tab && ! $show_register ) {
	$active_tab = 'login';
}

get_header();
?>

<div class="row py-3 login-page">
	<div class="col-lg-7 text-center text-lg-start d-none d-lg-block mb-3">
		<?php
		if ( have_posts() ) :
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/post/content', 'page' );
			endwhile;
		endif;
		?>
	</div>

	<div class="col-lg-4">
		<section class="tab-content card loading-animate animate__fadeIn p-3" id="myTabContent">
			<div id="myTab" role="tablist">
				<button class="<?php echo 'login' === $active_tab ? 'active ' : ''; ?>visually-hidden" id="login-tab" data-bs-toggle="tab" data-bs-target="#login-tab-pane" type="button" role="tab" aria-controls="login-tab-pane" aria-selected="<?php echo 'login' === $active_tab ? 'true' : 'false'; ?>">Login</button>
				<?php if ( $show_register ) : ?>
					<button class="<?php echo 'regist' === $active_tab ? 'active ' : ''; ?>visually-hidden" id="regist-tab" data-bs-toggle="tab" data-bs-target="#regist-tab-pane" type="button" role="tab" aria-controls="regist-tab-pane" aria-selected="<?php echo 'regist' === $active_tab ? 'true' : 'false'; ?>">Regist</button>
				<?php endif; ?>
				<button class="<?php echo 'reset' === $active_tab ? 'active ' : ''; ?>visually-hidden" id="forget-tab" data-bs-toggle="tab" data-bs-target="#forget-tab-pane" type="button" role="tab" aria-controls="forget-tab-pane" aria-selected="<?php echo 'reset' === $active_tab ? 'true' : 'false'; ?>">Forget</button>
			</div>

			<div class="login tab-pane fade <?php echo 'login' === $active_tab ? 'show active' : ''; ?>" id="login-tab-pane" role="tabpanel" aria-labelledby="login-tab" tabindex="0">
				<?php get_template_part( 'template-parts/forms/login' ); ?>
			</div>

			<?php if ( $show_register ) : ?>
				<div class="regist tab-pane fade <?php echo 'regist' === $active_tab ? 'show active' : ''; ?>" id="regist-tab-pane" role="tabpanel" aria-labelledby="regist-tab" tabindex="0">
					<?php get_template_part( 'template-parts/forms/regist' ); ?>
				</div>
			<?php endif; ?>

			<div class="forget tab-pane fade <?php echo 'reset' === $active_tab ? 'show active' : ''; ?>" id="forget-tab-pane" role="tabpanel" aria-labelledby="forget-tab" tabindex="0">
				<?php get_template_part( 'template-parts/forms/reset' ); ?>
			</div>
		</section>
	</div>
</div>

<?php
get_footer();
