<?php
/**
 * The template for displaying the header
 *
 * Displays all of the head element and everything up until the "row" div.
 *
 * @package Lerm https://lerm.net
 *
 * @since  1.0
 */
?>
<?php $template_options = lerm_get_template_options(); ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="<?php echo esc_attr( $template_options['header_bg_color'] ); ?>">
	<?php wp_head(); ?>
		<?php if ( ! empty( $template_options['head_scripts'] ) ) : ?>
			<?php echo $template_options['head_scripts']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<?php endif; ?>
</head>
<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<?php
	// ── Reading progress bar ───────────────────────────────────────────────
	if ( ! empty( $template_options['reading_progress'] ) && is_singular( 'post' ) ) :
		$bar_color  = esc_attr( $template_options['reading_progress_color'] ?? '#0084ba' );
		$bar_height = (int) ( $template_options['reading_progress_height'] ?? 3 );
		?>
		<div id="reading-progress-bar" style="position:fixed;top:0;left:0;width:0;height:<?php echo $bar_height; ?>px;background:<?php echo $bar_color; ?>;z-index:9999;transition:width .1s linear;" aria-hidden="true"></div>
		<script>
		(function(){
			var bar = document.getElementById('reading-progress-bar');
			if (!bar) return;
			function update(){
				var scrollTop = window.scrollY || document.documentElement.scrollTop;
				var docH = document.documentElement.scrollHeight - document.documentElement.clientHeight;
				bar.style.width = docH > 0 ? Math.min(100, (scrollTop / docH) * 100) + '%' : '0';
			}
			window.addEventListener('scroll', update, {passive:true});
			update();
		})();
		</script>
	<?php endif; ?>
 
	<?php
	// ── Dark mode bootstrap ────────────────────────────────────────────────
	if ( ! empty( $template_options['dark_mode_enable'] ) ) :
		$dm_default = $template_options['dark_mode_default'] ?? 'system';
		?>
		<script>
		(function(){
			var stored = localStorage.getItem('lerm-color-scheme');
			var pref   = stored || '<?php echo esc_js( $dm_default ); ?>';
			if (pref === 'system') {
				pref = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
			}
			document.documentElement.setAttribute('data-bs-theme', pref);
		})();
		</script>
	<?php endif; ?>
	<div id="page" class="site">
		<header id="site-header" class="card site-header mb-3 <?php echo ! empty( $template_options['sticky_header'] ) ? ' site-header--sticky' : ''; ?><?php echo ! empty( $template_options['transparent_header'] ) ? ' site-header--transparent' : ''; ?>"
		data-shrink="<?php echo ( ! empty( $template_options['sticky_header'] ) && ! empty( $template_options['sticky_header_shrink'] ) ) ? 'true' : 'false'; ?>"
		itemscope="" itemtype="https://schema.org/WPHeader">
			<nav id="site-navigation" class="navbar navbar-expand-lg p-0">
				<div class="container">
					<!-- .navbar-brand  begin -->
					<?php get_template_part( 'template-parts/layout/site-brand' ); ?>
					<!-- .navbar-brand end -->
					<?php get_template_part( 'template-parts/layout/site-nav' ); ?>
					<?php
					// ── Dark mode toggle – navbar position ─────────────────
					if ( ! empty( $template_options['dark_mode_enable'] ) && ( $template_options['dark_mode_toggle_position'] ?? 'navbar' ) === 'navbar' ) :
						?>
						<button id="lerm-dark-toggle" class="btn btn-sm btn-outline-secondary ms-2" aria-label="<?php esc_attr_e( 'Toggle dark mode', 'lerm' ); ?>">
							<i class="fa fa-moon" aria-hidden="true"></i>
						</button>
					<?php endif; ?>
				</div><!-- .container -->
			</nav>
		</header>
		<?php
		if ( 'full_width' === $template_options['slide_position'] ) {
			get_template_part( 'template-parts/components/carousel' );
		}
		?>
		<main role="main" class="container" id="page-ajax"><!--.container-->
