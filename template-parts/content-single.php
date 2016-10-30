<?php
/**
 * The template part for displaying single posts
 *
 * @package lerm
 * @date  2016-10-26
 * @since Lerm 2.0
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('card'); ?>>
	<header class="card-header text-center">
		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
		<small>
			<?php lerm_entry_meta() ?>
			<?php
				edit_post_link(
					sprintf(
						/* translators: %s: Name of current post */
						__( '编辑<span class="screen-reader-text"> "%s"</span>', 'lerm' ),
						get_the_title()
					),
				'<span class="edit-link">',
					'</span>'
				);
			?>

		</small>
	</header><!-- .entry-header -->

	<div class="card-block entry-content">
		<?php
			the_content();?>

			<div class="entry-tags">
				<?php the_tags('<i class="fa fa-tags"></i>' , '&nbsp', ''); ?>
			</div><!-- .entry-tags -->
    <?php
			wp_link_pages( array(
				'before'      => '<div class="page-links"><span class="page-links-title">' . __( 'Pages:', 'lerm' ) . '</span>',
				'after'       => '</div>',
				'link_before' => '<span>',
				'link_after'  => '</span>',
				'pagelink'    => '<span class="screen-reader-text">' . __( 'Page', 'lerm' ) . ' </span>%',
				'separator'   => '<span class="screen-reader-text">, </span>',
			) );
		?>
	</div><!-- .entry-content -->

	<footer class="card-footer entry-footer">
		<div class="post-like">
		<a href="javascript:;" data-action="ding" data-id="<?php the_ID(); ?>" class="specsZan <?php if(isset($_COOKIE['post_like_'.$post->ID])) echo 'done';?>">
			<i class="fa fa-heart"></i> <span class="count">
				<?php if( get_post_meta($post->ID,'post_like',true) ){
							echo get_post_meta($post->ID,'post_like',true);
						} else {
							echo '0';
						}?></span>
			</a>
			<?php get_template_part( 'inc/share' ); ?>
		</div>

	</footer><!-- .entry-footer -->
</article><!-- #post-## -->
