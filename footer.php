<footer class="footer mt-3" itemscope="" itemtype="http://schema.org/WPFooter">
	<?php if ( ! is_404() || is_home() || is_front_page() ) : ?>
		<?php
		$footer_sidebars_count = (int) lerm_options( 'footer_sidebars_count' );
		if ( $footer_sidebars_count > 0 ) :
			?>
			<div class="footer-widget p-2">
				<div class="container">
					<div class="row">
						<?php
							$sidebar_count = $footer_sidebars_count;
							$widget_layout = 12 / ( $footer_sidebars_count );
						for ( $i = 1; $i < $sidebar_count; $i++ ) {
							?>
								<div class="col-md-<?php echo esc_attr( $widget_layout ); ?> col-4 ">
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
				</div>
			</div>
		<?php endif; ?>
	<?php endif; ?>
	<div class="btn-group-vertical position-fixed toolbar" style="bottom: 4rem;right: 1rem">
		<a type="button" class="tool-btn btn btn-custom" target="_blank" href="http://wpa.qq.com/msgrd?v=3&uin=825641026&site=qq&menu=yes" data-toggle="tooltip" data-placement="left" title="QQ 在线咨询"><i class="fa fa-qq"></i></a>
		<a type="button" class="tool-btn btn btn-custom" id="scroll-up" href="javascript:;"  data-toggle="tooltip" data-placement="left" title="飞回顶部"><i class="fa fa-chevron-up"></i></a>
	</div>
	<div class="copyright p-3 text-center">
		<div class="container">
			<?php echo esc_html( lerm_options( 'copyright' ) ); ?>
			<!--制作不易，请保留作者链接，谢谢 -->
			<a href="<?php echo esc_url( 'http://lerm.net/', 'lerm' ); ?>">
				<?php esc_html__( 'Theme By Lerm', 'lerm' ); ?>
			</a>
		</div>
	</div>
</footer>
<?php wp_footer(); ?>
<div class="menu-backdrop fade"></div>
<div class="alert alert-warning alert-dismissible fade show " role="alert" style="position: fixed;right: 0;bottom: 0;z-index: 1030;">
	This website uses cookies.
	<button type="button" class="close" data-dismiss="alert" aria-label="Close">
		<span aria-hidden="true">&times;</span>
	</button>
</div>
</body>
</html>
