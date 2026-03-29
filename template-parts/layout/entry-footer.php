<?php
/**
 * Template part: post entry footer (like button + comments + social share).
 *
 * @package Lerm
 */

use Lerm\View\LikeButton;
use function Lerm\View\lerm_social_icons;

global $post;

$template_options = lerm_get_template_options();
?>
<footer class="mt-5">
	<div class="line-text d-flex justify-content-center align-items-center">
		<?php esc_html_e( 'If this post helped you, please like and share it.', 'lerm' ); ?>
	</div>
	<div class="btn-toolbar d-flex justify-content-center mt-4 mb-3">
		<div class="text-center">
		<?php
			LikeButton::render(
				(int) $post->ID,
				false,
				array( 'class' => 'btn btn-sm' )
			);
			?>
			<a href="<?php comments_link(); ?>" class="btn btn-custom btn-sm entry-comment-btn">
				<i class="fa fa-comment"></i>
				<?php
				/* translators: %s = comment number */
				printf( esc_html( _nx( '%s comment', '%s comments', get_comments_number(), 'comments title', 'lerm' ) ), esc_html( number_format_i18n( get_comments_number() ) ) );
				?>
			</a>
		</div><!-- like -->
	</div><!-- toolbar -->
	<?php lerm_social_icons( (array) $template_options['social_share'] ); ?>
</footer>
