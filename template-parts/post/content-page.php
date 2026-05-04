<?php
/**
 * The template part for displaying content
 *
 * @package Lerm
 * @since Lerm 2.0
 */
use Lerm\View\PostMeta;
use function Lerm\Support\link_pagination;

$current_post_id  = get_the_ID();
$template_options = lerm_get_template_options();
$card_classes     = 'card';
?>
<article id="post-<?php echo esc_attr( $current_post_id ); ?>" <?php post_class( $card_classes ); ?> >
	<div class="content-area">
		<?php get_template_part( 'template-parts/layout/entry-header' ); ?>

		<div class="entry-content clearfix mb-3">
			<?php
			$continue_text = wp_kses(
				sprintf(
					/* translators: %s: post title inside screen-reader span */
					__( 'Continue reading<span class="screen-reader-text">"%s"</span>', 'lerm' ),
					get_the_title()
				),
				array( 'span' => array( 'class' => array() ) )
			);
			the_content( $continue_text );
			link_pagination();
			?>
		</div>
	</div>
</article>