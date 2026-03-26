<?php
/**
 * Template part: post entry footer (like button + comments + social share)
 *
 * @package Lerm
 */
use Lerm\View\LikeButton;
use function Lerm\View\lerm_social_icons;
global $post;

?>
<footer class="mt-5">
	<div class="line-text d-flex justify-content-center align-items-center">如果您觉得有用就请点赞和分享</div>
	<div class="btn-toolbar d-flex justify-content-center mt-4 mb-3">
		<div class="text-center">
		<?php
			LikeButton::render(
				(int) $post->ID,
				false,
				array( 'class' => 'btn btn-sm' )
			);
			?>
			<a href="<?php comments_link(); ?>"  class="btn btn-custom btn-sm entry-comment-btn">
				<i class="fa fa-comment"></i>
					<?php
					/* translators: %s = comment number */
					printf( esc_html( _nx( '%s comment', '%s comments', get_comments_number(), 'comments title', 'lerm' ) ), esc_html( number_format_i18n( get_comments_number() ) ) );
					?>
			</a>
		</div><!-- like -->
	</div><!-- toolbar -->
	<?php lerm_social_icons( array( 'social' => lerm_options( 'social_share' ) ) ); ?>
</footer>
