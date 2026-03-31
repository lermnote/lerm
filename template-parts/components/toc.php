<?php
/**
 * Table of Contents (TOC) template part.
 *
 * Usage: get_template_part( 'template-parts/components/toc' );
 *
 * Reads headings from the current post content and renders a collapsible TOC.
 * Controlled by:
 *   toc_enable         – master switch
 *   toc_min_headings   – skip TOC if fewer headings than this number
 *   toc_position       – 'before_content' | 'sidebar' | 'floating'
 *   toc_collapsed      – start collapsed
 *
 * @package Lerm
 */

$template_options = lerm_get_template_options();

if ( empty( $template_options['toc_enable'] ) ) {
	return;
}

$content = get_the_content();
if ( empty( $content ) ) {
	return;
}

// ── Extract headings ────────────────────────────────────────────────────────
preg_match_all( '/<h([2-6])[^>]*>(.*?)<\/h\1>/is', $content, $matches, PREG_SET_ORDER );

$min_headings = max( 1, (int) ( $template_options['toc_min_headings'] ?? 3 ) );
if ( count( $matches ) < $min_headings ) {
	return;
}

$position  = $template_options['toc_position'] ?? 'before_content';
$collapsed = ! empty( $template_options['toc_collapsed'] );

// The floating position is rendered separately via a fixed-position div
// appended to body — only render it there, not inline.
global $lerm_toc_rendered;
if ( $position === 'floating' && ! empty( $lerm_toc_rendered ) ) {
	return;
}

$extra_class = '';
if ( $position === 'floating' ) {
	$extra_class       = ' lerm-toc--floating';
	$lerm_toc_rendered = true;
}
if ( $collapsed ) {
	$extra_class .= ' is-collapsed';
}

// ── Build slug map (avoid duplicates) ───────────────────────────────────────
$slug_counts = array();
$items       = array();
foreach ( $matches as $m ) {
	$level                     = (int) $m[1];
	$raw_title                 = wp_strip_all_tags( $m[2] );
	$base_slug                 = sanitize_title( $raw_title );
	$slug_counts[ $base_slug ] = ( $slug_counts[ $base_slug ] ?? 0 ) + 1;
	$slug                      = $slug_counts[ $base_slug ] > 1
		? $base_slug . '-' . ( $slug_counts[ $base_slug ] - 1 )
		: $base_slug;
	$items[]                   = array(
		'level' => $level,
		'title' => $raw_title,
		'slug'  => $slug,
	);
}
?>
<nav class="lerm-toc<?php echo esc_attr( $extra_class ); ?>" aria-label="<?php esc_attr_e( 'Table of contents', 'lerm' ); ?>">
	<div class="lerm-toc__title" role="button" tabindex="0"
		aria-expanded="<?php echo $collapsed ? 'false' : 'true'; ?>"
		onclick="this.closest('.lerm-toc').classList.toggle('is-collapsed');this.setAttribute('aria-expanded',this.closest('.lerm-toc').classList.contains('is-collapsed')?'false':'true');">
		<?php esc_html_e( 'Contents', 'lerm' ); ?>
		<span class="lerm-toc__toggle" aria-hidden="true">&#9660;</span>
	</div>
	<ul class="lerm-toc__list">
		<?php foreach ( $items as $item ) : ?>
			<li class="lerm-toc-h<?php echo (int) $item['level']; ?>">
				<a href="#<?php echo esc_attr( $item['slug'] ); ?>">
					<?php echo esc_html( $item['title'] ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
