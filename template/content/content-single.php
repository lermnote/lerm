<?php
/**
 * The template part for displaying content
 *
 * @package Lerm
 * @since Lerm 2.0
 */
global $lerm;
$like_class = isset( $_COOKIE[ 'post_like_' . $post->ID ] ) ? 'done' : '';
$like_count = get_post_meta( $post->ID, 'lerm_post_like', true ) ? get_post_meta( $post->ID, 'lerm_post_like', true ) : 0;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry p-3 mb-2' ); ?> >
	<header class="entry-header d-flex flex-column justify-content-between mb-md-2 text-center pb-2">
		<?php
		the_title( '<h1 class="entry-title test">', '</h1>' );
		echo '<small class="entry-meta text-muted">';
		lerm_entry_meta( 'single' );
		echo '</small>';
		?>
	</header>

	<div class="entry-content pt-2">
		<?php
		the_content(
			sprintf(
				// translators: %s is the title
				__( 'Continue reading <span class="screen-reader-text">"%s"</span>', 'lerm' ),
				get_the_title()
			)
		);
		?>
		<?php
			wp_link_pages(
				array(
					'before'      => '<div class="page-links"><span class="page-links-title">' . __( 'Pages:', 'lerm' ) . '</span>',
					'after'       => '</div>',
					'link_before' => '<span>',
					'link_after'  => '</span>',
					'pagelink'    => '<span class="screen-reader-text">' . __( 'Page', 'lerm' ) . ' </span>%',
					'separator'   => '<span class="screen-reader-text">, </span>',
				)
			);
			?>
	</div>
	<footer>
		<div class="text-center position-relative mt-5">
			<div class="line" ></div>
			<span class="line-text">如果您觉得有用就请点赞和分享</span>
		</div>
		<div class="btn-toolbar  d-flex justify-content-center mt-4 mb-3">
			<div class="text-center">
				<button  id="like-button" data-id="<?php the_ID(); ?>" class="like-button btn <?php echo esc_attr( $like_class ); ?>">
					<span><i class="fa fa-heart"></i></span>
					<span class="count">
						<?php echo esc_attr( $like_count ); ?>
					</span>
				</button>
				<button class="btn-custom btn entry-comment-btn "><?php echo lerm_post_comments_number(); ?> </button>
			</div><!-- like -->
		</div><!-- toolbar -->
		<div class="d-flex justify-content-between">
			<div class="donate d-none d-md-block" >
				<button class="donate-btn btn">
					<span><i class="fa fa-dollar"></i></span>
				</button>
				<div class="donate-qrcode">
					<img src="<?php echo esc_attr( lerm_options( 'donate-qrcode', 'url' ) ); ?>" alt="donate me" width="100" height="100">
				</div>
			</div><!-- donate -->
			<div id="share" class="d-flex justify-content-end">
				<ul class="d-flex list-unstyled mb-0">
					<li title="分享到QQ空间" class="mr-2">
						<a class="qzone border rounded p-1 text-primary">
							<i class="fa fa-qq"></i>
						</a>
					</li>
					<li title="分享到新浪微博" class="mr-2">
						<a class="tsina border rounded p-1 text-danger">
							<i class="fa fa-weibo"></i>
						</a>
					</li>
					<li title="分享到微信" class="share-code position-relative mr-2">
						<a class="wechat border rounded p-1 text-success">
							<i class="fa fa-wechat"></i>
						</a>
						<div id="share-qrcode" class="towdimcodelayer"></div>
					</li>
				</ul>
			</div>
		</div>
	</footer>
</article>
