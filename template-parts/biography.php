<?php
/**
 * The template part for displaying an Author biography
 *
 * @package Lerm
 * @since Lerm 2.0
 */
?>
<div class="author-info">
	<div class="author-avatar">
		<?php
		/**
		 * Filter the Lerm author bio avatar size.
		 *
		 * @since Lerm 2.0
		 *
		 * @param int $size The avatar height and width size in pixels.
		 */
		$author_bio_avatar_size = apply_filters( 'lerm_author_bio_avatar_size', 64 );

		echo get_avatar( get_the_author_meta( 'user_email' ), $author_bio_avatar_size );
		?>
	</div><!-- .author-avatar -->

	<div class="author-description">
		<h2 class="author-title"><span class="author-heading"><?php _e( '作者:', 'lerm' ); ?></span> <?php echo get_the_author(); ?></h2>
		<p class="author-bio text-muted">
			<?php the_author_meta( 'description' ); ?>
		</p><!-- .author-bio -->
		<p>
			<a class="author-link" href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" rel="author">
				<?php printf( __( '查看 %s的所有文章', 'lerm' ), get_the_author() ); ?>
			</a>
		</p>
	</div><!-- .author-description -->
</div><!-- .author-info -->
