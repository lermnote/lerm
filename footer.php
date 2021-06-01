<?php
/**
 * The template for displaying the footer
 *
 * @author lerm https://www.hansot.com
 * @date   2016-08-28 21:57:52
 * @since  1.0
 */
?>

<footer class="footer mt-3" itemscope="" itemtype="http://schema.org/WPFooter">
	<?php if ( ! is_404() || is_home() || is_front_page() ) : ?>
		<?php
		$footer_sidebars_count = (int) lerm_options( 'footer_sidebars_count' );
		if ( $footer_sidebars_count > 0 ) :
			?>
			<div class="footer-widget py-3">
				<div class="container">
					<div class="row">
						<div class="col-md-8 row">

						<?php
						$sidebar_count = $footer_sidebars_count;
						$widget_layout = floor( 12 / $sidebar_count );
						for ( $i = 1; $i <= $sidebar_count; $i++ ) {
							?>
								<div class="col-md-<?php echo esc_attr( $widget_layout ); ?> col-sm-4 col-4">
								<?php
								if ( 1 === $i ) {
									dynamic_sidebar( 'footer-sidebar' );
								}
									dynamic_sidebar( 'footer-sidebar-' . $i );
								?>
								</div>
							<?php
						}
						?>
						</div>
						<div class="col-md-4">
							<?php dynamic_sidebar( 'footer-sidebar-right' ); ?>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>
	<?php endif; ?>
	<div class="btn-group-vertical position-fixed btn-group-sm toolbar" style="bottom: 4rem;right: 1rem">
		<a class="btn btn-custom" target="_blank" href="http://wpa.qq.com/msgrd?v=3&uin=825641026&site=qq&menu=yes" data-toggle="tooltip" data-placement="left" title="QQ 在线咨询"  role="button"><i class="fa fa-qq"></i></a>
		<a class="btn btn-custom" id="scroll-up" href="javascript:void(0);"  data-toggle="tooltip" data-placement="left" title="飞回顶部"  role="button"><i class="fa fa-chevron-up" ></i></a>
	</div>
	<div class="colophon py-3 text-center">
		<div class="container">
			<span><?php lerm_create_copyright(); ?></span>
			<?php if ( lerm_options( 'icp_num' ) ) : ?>
				<span><a href="https://beian.miit.gov.cn"><?php echo esc_html( lerm_options( 'icp_num' ) ); ?></a></span>
			<?php endif; ?>
			<!--尊重原创，请保留作者链接，谢谢 -->
			<span><?php echo esc_html__( 'Theme By', 'lerm' ); ?><a href="<?php echo esc_url( 'https://www.hanost.com/', 'lerm' ); ?>"> Lerm </a></span>
			<?php if ( lerm_options( 'footer_menus' ) ) : ?>
				<div class="d-block">
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'footer',
							'menu'           => lerm_options( 'footer_menus' ),  //期望显示的菜单（输入名称或菜单id）
							'depth'          => 1,
							'menu_class'     => 'footer-menu d-flex justify-content-center list-unstyled mb-0',
						)
					);
					?>
				</div>
			<?php endif; ?>
			<?php if ( lerm_options( 'copyright' ) ) : ?>
				<div class="d-block"><?php echo esc_html( lerm_options( 'copyright' ) ); ?></div>
			<?php endif; ?>
		</div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
