<?php
/**
 * Share icon template
 * @since lerm 3.0.0
 */
$social_share = apply_filters( 'social_share_icons', lerm_options( 'social_share' ) );?>

<?php if ( $social_share ) { ?>
	<div class="social-share d-flex justify-content-center" data-initialized="true">
		<?php if ( in_array( 'weibo', $social_share, true ) ) { ?>
			<a href="#" class="social-share-icon icon-weibo btn-light btn-sm "><i class="fa fa-weibo"></i></a>
		<?php } ?>
		<?php if ( in_array( 'qq', $social_share, true ) ) { ?>
			<a href="#" class="social-share-icon icon-qq btn-sm btn-light"><i class="fa fa-qq"></i></a>
		<?php } ?>
		<?php if ( in_array( 'qzone', $social_share, true ) ) { ?>
			<a href="#" class="social-share-icon icon-qzone btn-sm btn-light"><i class="fa fa-qzone"></i></a>
		<?php } ?>
		<?php if ( in_array( 'wechat', $social_share, true ) ) { ?>
			<a href="javascript:" class="social-share-icon icon-wechat btn-sm btn-light" ><i class="fa fa-wechat"></i></a>
		<?php } ?>
		<?php if ( in_array( 'douban', $social_share, true ) ) { ?>
			<a href="#" class="social-share-icon icon-douban btn-sm btn-light"><i class="fa fa-douban"></i></a>
		<?php } ?>
		<?php if ( in_array( 'linkedin', $social_share, true ) ) { ?>
			<a href="#" class="social-share-icon icon-linkedin btn-sm btn-light"><i class="fa fa-linkedin"></i></a>
		<?php } ?>
		<?php if ( in_array( 'facebook', $social_share, true ) ) { ?>
			<a href="#" class="social-share-icon icon-facebook btn-sm btn-light"><i class="fa fa-facebook"></i></a>
		<?php } ?>
		<?php if ( in_array( 'twitter', $social_share, true ) ) { ?>
			<a href="#" class="social-share-icon icon-twitter btn-sm btn-light"><i class="fa fa-twitter"></i></a>
		<?php } ?>
		<?php if ( in_array( 'google_plus', $social_share, true ) ) { ?>
			<a href="#" class="social-share-icon icon-google btn-sm btn-light"><i class="fa fa-google-plus"></i></a>
		<?php } ?>
	</div>
	<?php
}

