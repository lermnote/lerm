<?php
if ( ! defined( 'ABSPATH' ) ) {
	die; }


/**
 * Share icon template
 *
 * @since lerm 3.0.0
 */
function lerm_social_icons( $icons = array( 'weibo', 'wechat', 'qq' ) ) {
	if ( ! empty( $icons ) && is_array( $icons ) ) {
		?>
		<div class="social-share d-flex justify-content-center gap-1" data-initialized="true">
			<?php foreach ( $icons as &$icon ) : ?>
				<a href="#" class="social-share-icon icon-<?php echo esc_attr( $icon ); ?> btn-light btn-sm">
					<i class="fa fa-<?php echo esc_attr( $icon ); ?>"></i>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
