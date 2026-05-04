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

		<?php
		if ( is_singular( 'post' ) ) :
			PostMeta::post_meta( array_keys( (array) ( $template_options['single_bottom']['enabled'] ?? array() ) ), 'justify-content-between mb-1' );

			$tag_list = get_the_tag_list(
				'<ul class="list-unstyled m-0 small text-muted"><li class="d-inline"><i class="fa fa-tags"> </i>#',
				'</li><li class="d-inline ms-2">#',
				'</li></ul>'
			);
			if ( $tag_list ) {
				echo wp_kses(
					$tag_list,
					array(
						'ul' => array( 'class' => array() ),
						'li' => array( 'class' => array() ),
						'i'  => array( 'class' => array() ),
						'a'  => array(
							'href'  => array(),
							'rel'   => array(),
							'class' => array(),
						),
					)
				);
			}
		endif;
		get_template_part( 'template-parts/layout/entry-footer' );
		?>
	</div>
</article>
