<?php
/**
 * Display post header
 *
 * @package Lerm
 */
if ( empty( get_the_title() ) ) {
	return;
}
use Lerm\View\PostMeta;
use function Lerm\View\lerm_social_icons;

$template_options = lerm_get_template_options();
$share_position   = (string) ( $template_options['share_position'] ?? 'bottom' );
?>

<header class="entry-header d-flex flex-column text-center mb-2">
	<?php
	the_title( '<h1 class="entry-title">', '</h1>' );
	if ( is_singular( 'post' ) ) :
		PostMeta::post_meta( array_keys( (array) ( $template_options['single_top']['enabled'] ?? array() ) ), 'justify-content-center  mb-0 ' );

		if ( in_array( $share_position, array( 'top', 'both' ), true ) ) :
			?>
			<div class="mt-3">
				<?php lerm_social_icons( (array) $template_options['social_share'] ); ?>
			</div>
			<?php
		endif;
	endif;
	?>
</header>
