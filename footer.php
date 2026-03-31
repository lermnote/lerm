<?php
/**
 * The template for displaying the footer.
 *
 * @package Lerm
 */

use function Lerm\Support\copyright_text;

$template_options  = lerm_get_template_options();
$social_positions  = (array) ( $template_options['social_profiles_position'] ?? array( 'footer', 'author_bio' ) );
$social_new_tab    = ! isset( $template_options['social_open_new_tab'] ) || ! empty( $template_options['social_open_new_tab'] );
$footer_menu_id    = (int) ( $template_options['footer_menus'] ?? 0 );
$footer_menu_args  = array(
	'depth'       => 1,
	'menu_class'  => 'footer-menu d-flex justify-content-center list-unstyled mb-0',
	'fallback_cb' => false,
);

if ( $footer_menu_id > 0 ) {
	$footer_menu_args['menu'] = $footer_menu_id;
} else {
	$footer_menu_args['theme_location'] = 'footer';
}
?>
</main>
<footer class="card footer" itemscope="" itemtype="http://schema.org/WPFooter">
	<?php if ( ! is_404() && ( is_home() || is_front_page() ) ) : ?>
		<div class="container card-body">
			<?php dynamic_sidebar( 'footer-sidebar' ); ?>
		</div>
	<?php endif; ?>

	<div class="colophon py-3 text-center">
		<div class="container">
			<?php if ( in_array( 'footer', $social_positions, true ) ) : ?>
				<?php lerm_social_profile_links( $template_options, $social_new_tab ); ?>
			<?php endif; ?>

			<span><?php copyright_text( 'short' ); ?></span>

			<?php if ( ! empty( $template_options['icp_num'] ) ) : ?>
				<span><a href="https://beian.miit.gov.cn"><?php echo esc_html( $template_options['icp_num'] ); ?></a></span>
			<?php endif; ?>

			<br>
			<span><?php echo esc_html__( 'Theme by', 'lerm' ); ?><a href="<?php echo esc_url( 'https://lerm.net/' ); ?>"> Lerm </a></span>

			<?php wp_nav_menu( $footer_menu_args ); ?>

			<?php if ( ! empty( $template_options['copyright'] ) ) : ?>
				<div class="d-block"><?php echo wp_kses_post( $template_options['copyright'] ); ?></div>
			<?php endif; ?>
		</div>
	</div>
</footer>

<div class="position-fixed d-grid gap-1 btn-group-sm" style="bottom: 4rem;right: 1rem">
	<?php if ( ! empty( $template_options['qq_chat_enable'] ) && ! empty( $template_options['qq_chat_number'] ) ) : ?>
		<a class="btn btn-custom" target="_blank" rel="noopener noreferrer"
			href="<?php echo esc_url( 'http://wpa.qq.com/msgrd?v=3&uin=' . rawurlencode( $template_options['qq_chat_number'] ) . '&site=qq&menu=yes' ); ?>"
			data-bs-toggle="tooltip" data-bs-placement="left"
			title="<?php echo esc_attr__( 'QQ live chat', 'lerm' ); ?>"
			role="button"><i class="fa fa-qq"></i></a>
	<?php endif; ?>

	<?php if ( ! isset( $template_options['back_to_top'] ) || ! empty( $template_options['back_to_top'] ) ) : ?>
		<button class="btn btn-custom" id="scroll-up"
			data-threshold="<?php echo (int) ( $template_options['back_to_top_threshold'] ?? 400 ); ?>"
			data-bs-toggle="tooltip" data-bs-placement="left"
			title="<?php echo esc_attr__( 'Back to top', 'lerm' ); ?>"
			role="button" style="display:none;"><i class="fa fa-chevron-up"></i></button>
	<?php endif; ?>

	<?php if ( ! empty( $template_options['dark_mode_enable'] ) && ( $template_options['dark_mode_toggle_position'] ?? 'navbar' ) === 'sidebar' ) : ?>
		<button id="lerm-dark-toggle" class="btn btn-custom"
			aria-label="<?php esc_attr_e( 'Toggle dark mode', 'lerm' ); ?>">
			<i class="fa fa-moon-o" aria-hidden="true"></i>
		</button>
	<?php endif; ?>
</div>
</div><!-- #page -->

<?php if ( ! empty( $template_options['dark_mode_enable'] ) ) : ?>
	<script>
	(function(){
		var btn = document.getElementById('lerm-dark-toggle');
		if (!btn) return;
		btn.addEventListener('click', function(){
			var cur  = document.documentElement.getAttribute('data-bs-theme') || 'light';
			var next = cur === 'dark' ? 'light' : 'dark';
			document.documentElement.setAttribute('data-bs-theme', next);
			localStorage.setItem('lerm-color-scheme', next);
		});
	})();
	</script>
<?php endif; ?>

<?php wp_footer(); ?>

<?php if ( ! empty( $template_options['footer_scripts'] ) ) : ?>
	<?php echo $template_options['footer_scripts']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endif; ?>
</body>
</html>
