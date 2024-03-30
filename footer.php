<?php
/**
 * The template for displaying the footer
 *
 * @package Lerm https://lerm.net
 * @date   2016-08-28 21:57:52
 * @since  1.00
 */
?>

<footer class="footer card" itemscope="" itemtype="http://schema.org/WPFooter">
	<?php if ( ! is_404() && ( is_home() || is_front_page() ) ) : ?>
		<div class="container card-body">
			<?php dynamic_sidebar( 'footer-sidebar' ); ?>
		</div>
	<?php endif; ?>
	<div class="colophon py-3 text-center">
		<div class="container">
			<span><?php lerm_create_copyright(); ?></span>
			<?php if ( lerm_options( 'icp_num' ) ) : ?>
				<span><a href="https://beian.miit.gov.cn"><?php echo esc_html( lerm_options( 'icp_num' ) ); ?></a></span>
			<?php endif; ?>
			<!--尊重原创，请保留作者链接，谢谢 -->
			<br>
			<span><?php echo esc_html__( 'Theme By', 'lerm' ); ?><a href="<?php echo esc_url( 'https://www.hanost.com/', 'lerm' ); ?>"> Lerm </a></span>
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
			<?php if ( lerm_options( 'copyright' ) ) : ?>
				<div class="d-block"><?php echo esc_html( lerm_options( 'copyright' ) ); ?></div>
			<?php endif; ?>
		</div>
	</div>
</footer>
<div class="position-fixed d-grid gap-1 btn-group-sm" style="bottom: 4rem;right: 1rem">
	<a class="btn btn-custom" target="_blank" href="http://wpa.qq.com/msgrd?v=3&uin=825641026&site=qq&menu=yes" data-toggle="tooltip" data-placement="left" title="QQ 在线咨询"  role="button"><i class="fa fa-qq"></i></a>
	<a class="btn btn-custom" id="scroll-up" href="javascript:void(0);"  data-toggle="tooltip" data-placement="left" title="飞回顶部"  role="button"><i class="fa fa-chevron-up" ></i></a>
</div>
</div><!-- #page -->
<?php wp_footer(); ?>
</body>
</html>
