<?php get_header(); ?>
<div class="col-sm-12 col-md-9">
  <?php if (have_posts()) : ?>
    <header class="card card-block text-center">
      <h2>
        <?php
						if ( is_day() ) :
							printf( __( '每日归档: %s', 'lerm' ), get_the_date() );

						elseif ( is_month() ) :
							printf( __( '月度归档: %s', 'lerm' ), get_the_date( _x( 'Y F', 'monthly archives date format', 'lerm' ) ) );

						elseif ( is_year() ) :
							printf( __( '年度归档: %s', 'lerm' ), get_the_date( _x( 'Y', 'yearly archives date format', 'lerm' ) ) );

						else :
							wp_title(' ');

						endif;
					?>
        </h2>
      <small>【文章归档: <?php wp_title(' '); ?>】</small>
    </header>
    <?php
			// Start the loop.
			while ( have_posts() ) : the_post();

				get_template_part( 'content', get_post_format() );

			// End the loop.
      endwhile; ?>
      <?php
        //pagination
        pagination() ?>
    <?php else:

      get_template_part( 'content', 'none' );?>

    <?php endif; ?>

</div><!-- .col-xs-12 .col-sm-9 -->
<?php get_sidebar();?>
<?php get_footer();?>
