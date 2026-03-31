<?php
/**
 * The template for displaying the footer.
 *
 * @package Lerm
 */

use function Lerm\Support\copyright_text;

$template_options = lerm_get_template_options();
// ── Social profiles helper (shared across positions) ────────────────────────
if ( ! function_exists( 'lerm_social_profile_links' ) ) :
	function lerm_social_profile_links( array $opts, bool $new_tab = true ): void {
		$target = $new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';
		$links  = array(
			'social_weibo'     => array( 'fa fa-weibo', '微博' ),
			'social_wechat'    => array( 'fa fa-weixin', '微信' ),
			'social_qq'        => array( 'fa fa-qq', 'QQ' ),
			'social_bilibili'  => array( 'lerm-icon-bilibili', 'Bilibili' ),
			'social_zhihu'     => array( 'lerm-icon-zhihu', '知乎' ),
			'social_douban'    => array( 'lerm-icon-douban', '豆瓣' ),
			'social_github'    => array( 'fa fa-github', 'GitHub' ),
			'social_twitter'   => array( 'fa fa-twitter', 'X / Twitter' ),
			'social_linkedin'  => array( 'fa fa-linkedin', 'LinkedIn' ),
			'social_instagram' => array( 'fa fa-instagram', 'Instagram' ),
			'social_youtube'   => array( 'fa fa-youtube-play', 'YouTube' ),
			'social_email'     => array( 'fa fa-envelope', 'Email' ),
		);
		$html   = '';
		foreach ( $links as $key => $meta ) {
			$url = trim( $opts[ $key ] ?? '' );
			if ( '' === $url ) {
				continue;
			}
			if ( $key === 'social_email' && ! str_starts_with( $url, 'mailto:' ) ) {
				$url = 'mailto:' . $url;
			}
			$html .= sprintf(
				'<a class="social-link" href="%s"%s aria-label="%s"><i class="%s" aria-hidden="true"></i></a>',
				esc_url( $url ),
				$target,
				esc_attr( $meta[1] ),
				esc_attr( $meta[0] )
			);
		}
		if ( ! empty( $opts['social_rss'] ) ) {
			$html .= sprintf(
				'<a class="social-link" href="%s"%s aria-label="RSS"><i class="fa fa-rss" aria-hidden="true"></i></a>',
				esc_url( get_feed_link() ),
				$target
			);
		}
		if ( $html ) {
			echo '<div class="lerm-social-links d-flex gap-2 justify-content-center flex-wrap">' . $html . '</div>';
		}
	}
endif;

$social_positions = (array) ( $template_options['social_profiles_position'] ?? array( 'footer', 'author_bio' ) );
$social_new_tab   = (bool) ( $template_options['social_open_new_tab'] ?? true );
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
			<?php
				// Social links – footer position
			if ( in_array( 'footer', $social_positions, true ) ) :
				lerm_social_profile_links( $template_options, $social_new_tab );
				endif;
			?>
			<span><?php copyright_text( 'short' ); ?></span>
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
 
	<?php
	// Dark mode toggle – floating position
	if ( ! empty( $template_options['dark_mode_enable'] ) && ( $template_options['dark_mode_toggle_position'] ?? 'navbar' ) === 'sidebar' ) :
		?>
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
	<?php echo $template_options['footer_scripts']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
<?php endif; ?>
</body>
</html>
