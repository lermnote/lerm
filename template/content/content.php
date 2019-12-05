<?php
/**
 * The template part for displaying content
 *
 * @package Lerm
 * @since Lerm 2.0
 */
global $lerm;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> >
	<?php
	if ( ! is_singular() ) {
		lerm_thumbnail( array( 'fig_classes' => ' mr-2 mb-0' ) );
	}
	?>
	<div class="content-area
	<?php
	if ( ! is_singular() ) {
		echo ' d-flex flex-md-column flex-row';
	}
	?>
	">
		<header class="entry-header d-flex flex-column justify-content-between mb-md-2
		<?php
		if ( is_singular() ) {
			echo 'text-center pb-2 border-bottom';
		}
		?>
		">
			<?php
			if ( is_single() ) {
				the_title( '<h1 class="entry-title">', '</h1>' );
			} else {
				the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a><label class="sticky-label badge badge-danger">' . __( 'Sticky', 'lerm' ) . '</label></h2>' );
			}
			?>
			<?php
			echo '<small class="entry-meta text-muted">';
			if ( is_singular( 'post' ) ) {
				lerm_entry_meta( 'entry' );
			} else {
				lerm_entry_meta( 'summary' );
			}
			echo '</small>';
			?>
		</header>

		<?php if ( is_single() ) : ?>
		<div class="entry-content pt-2">
			<?php
				the_content(
					sprintf(
						__( 'Continue reading<span class="screen-reader-text">"%s"</span>', 'lerm' ),
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
			<?php else : ?>
				<div class="summary-content d-none d-md-block">
					<?php
						the_excerpt();
					?>
				</div>
			<?php endif; ?>
		</div>
		<?php if ( is_single() ) : ?>
		<footer>


			<div class="mt-5 mb-5">
			<div class="text-center position-relative">
				<div class="line" ></div>
				<span class="line-text">如果您觉得有用就请点赞和分享</span>
			</div>
				<div class="btn-toolbar  d-flex justify-content-center mt-4">
					<div class="text-center" id="like-button">
						<button data-action="ding" data-id="<?php the_ID(); ?>" class="like-button btn 
																			<?php
																			if ( isset( $_COOKIE[ 'post_like_' . $post->ID ] ) ) {
																				echo 'done';
																			}
																			?>
				">
							<span><i class="fa fa-heart"></i></span>
							<span class="count">
								<?php
								if ( get_post_meta( $post->ID, 'post_like', true ) ) {
									echo get_post_meta( $post->ID, 'post_like', true );
								} else {
									echo '0';
								}
								?>
				</span>
						</button>

					</div>
					<div class="share d-none d-md-block mr-3 ml-3 ">
						<button class="btn share-btn">
							<span><i class="fa fa-share"></i></span>
						</button>
						<div class="banner"></div>
					</div>
					<div class="donate d-none d-md-block" >
						<button class="donate-btn btn">
							<span><i class="fa fa-dollar"></i></span>
						</button>
						<div class="donate-qrcode">
							<img src="<?php echo lerm_options( 'donate-qrcode', 'url' ); ?>" alt="donate me" width="100" height="100">
						</div>
					</div>
				</div>
			</div>
			<small class="entry-info">
				<div class="entry-tags pb-2">
					<?php lerm_entry_tag(); ?>
				</div><!-- .entry-tags -->
				<div><i class="fa fa-exclamation-triangle pr-2 "></i> <strong>版权声明：</strong> <span>本文由<a href="<?php the_permalink(); ?>" rel="bookmark" title="本文固定链接 <?php the_permalink(); ?>"> <?php bloginfo( 'name' ); ?> </a> 整理发表，转载请注明出处</span> </div>
				<div><i class="fa fa-bullseye pr-2 "></i> <strong>转载信息：</strong> <span><a href="<?php the_permalink(); ?>" rel="bookmark" title="本文固定链接 <?php the_permalink(); ?>"> <?php the_title(); ?> | <?php bloginfo( 'name' ); ?></a></span> </div>
			</small>
		</footer>
		<?php endif; ?>
</article>
