<?php
/**
 * The template for displaying the footer.
 *
 * @package Lerm
 */

use function Lerm\Support\copyright_text;

$template_options = lerm_get_template_options();
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
			<span><?php copyright_text( 'long' ); ?></span>
			<?php if ( ! empty( $template_options['icp_num'] ) ) : ?>
				<span><a href="https://beian.miit.gov.cn"><?php echo esc_html( $template_options['icp_num'] ); ?></a></span>
			<?php endif; ?>
			<br>
			<span><?php echo esc_html__( 'Theme by', 'lerm' ); ?><a href="<?php echo esc_url( 'https://lerm.net/' ); ?>"> Lerm </a></span>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'depth'          => 1,
					'menu_class'     => 'footer-menu d-flex justify-content-center list-unstyled mb-0',
					'fallback_cb'    => false,
				)
			);
			?>
			<?php if ( ! empty( $template_options['copyright'] ) ) : ?>
				<div class="d-block"><?php echo esc_html( $template_options['copyright'] ); ?></div>
			<?php endif; ?>
		</div>
	</div>
</footer>
<div class="position-fixed d-grid gap-1 btn-group-sm" style="bottom: 4rem;right: 1rem">
	<a class="btn btn-custom" target="_blank" rel="noopener noreferrer" href="http://wpa.qq.com/msgrd?v=3&uin=825641026&site=qq&menu=yes" data-bs-toggle="tooltip" data-bs-placement="left" title="<?php echo esc_attr__( 'QQ live chat', 'lerm' ); ?>" role="button"><i class="fa fa-qq"></i></a>
	<button class="btn btn-custom" id="scroll-up" data-bs-toggle="tooltip" data-bs-placement="left" title="<?php echo esc_attr__( 'Back to top', 'lerm' ); ?>" role="button"><i class="fa fa-chevron-up"></i></button>
</div>
</div><!-- #page -->
<?php wp_footer(); ?>
</body>
</html>
