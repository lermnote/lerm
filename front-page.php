<?php
/**
 * The homepage file
 *
 * This is the most generic template file in a WordPress theme and one
 * of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query,
 * e.g., it puts together the home page when no home.php file exists.
 *
 *
 * @authors lerm http://lerm.net
 * @date    2018-01-30
 * @since   Lerm 3.0
 */

get_header();
$lerm_total_posts = get_option( 'posts_per_page' ); /* number of latest posts to show */?>
<main style="margin-top: -15px">

		<img class="img-fluid" src="http://localhost/wordpress/wp-content/uploads/2018/01/os.5fa9522a5093653829a3a96186a0ef39.png">
	</div>
	<section class="bg-dark">
	<div class="embed-responsive embed-responsive-16by9">
  <iframe class="embed-responsive-item" src="http://player.youku.com/embed/XNDI4NzgzMTI2OA==" allowfullscreen></iframe>
</div>
	</section>
	<section class="bg-light pb-5">
		<?php
		$recent_posts = new WP_Query(
			array(
				'posts_per_page'      => $lerm_total_posts,
				'post_status'         => 'publish',
				'post_type'           => 'post',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'tax_query'           => array(
					array(
						'taxonomy' => 'post_format',
						'terms'    => array( 'post-format-quote', 'post-format-aside' ),
						'field'    => 'slug',
						'operator' => 'NOT IN',
					),
				),
			)
		);
		?>
		<div class="container">
			<header class="text-center section-header">
				<h2 class="mt-3 mb-0 pb-3">Latest news</h2>
			</header>
				<div class="card-deck-wrapper mb-3">
	  <div class="card-deck">
		<div class="card">
		  <img class="card-img-top" src="./assets/img/8.jpg" alt="Card image cap">
		  <div class="card-body">
			<h4 class="card-title">Card title</h4>
			<p class="card-text">This is a longer card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.</p>
			<p class="card-text"><small class="text-muted">Last updated 3 mins ago</small></p>
		  </div>
		</div>
		<div class="card">
		  <img class="card-img-top" src="./assets/img/9.jpg" alt="Card image cap">
		  <div class="card-body">
			<h4 class="card-title">Card title</h4>
			<p class="card-text">This card has supporting text below as a natural lead-in to additional content.</p>
			<p class="card-text"><small class="text-muted">Last updated 3 mins ago</small></p>
		  </div>
		</div>
		<div class="card">
		  <img class="card-img-top" src="./assets/img/10.jpg" alt="Card image cap">
		  <div class="card-body">
			<h4 class="card-title">Card title</h4>
			<p class="card-text">This is a wider card with supporting text below as a natural lead-in to additional content. This card has even longer content than the first to show that equal height action.</p>
			<p class="card-text"><small class="text-muted">Last updated 3 mins ago</small></p>
		  </div>
		</div>
		<div class="card">
		  <img class="card-img-top" src="./assets/img/8.jpg" alt="Card image cap">
		  <div class="card-body">
			<h4 class="card-title">Card title</h4>
			<p class="card-text">This is a wider card with supporting text below as a natural lead-in to additional content. This card has even longer content than the first to show that equal height action.</p>
			<p class="card-text"><small class="text-muted">Last updated 3 mins ago</small></p>
		  </div>
		</div>
	  </div>
	</div>
			<div id="latest_posts_carousel" class="carousel slide" data-ride="carousel">
				<div class="carousel-inner card-deck">

					<?php
					$latest_posts = 0;
					while ( $recent_posts->have_posts() ) :
						$recent_posts->the_post();
						$latest_posts++;
						if ( $latest_posts % 4 == 1 ) :
							$clsss = $latest_posts == 1 ? 'active' : '';
							?>
							<div class="carousel-item <?php echo $clsss; ?>">
								<div class="row m-0 justify-content-md-center mt-3" >
						<?php endif; ?>
								<div class="text-center card">
									<?php
									lerm_thumbnail(
										array(
											'fig_classes' => 'mb-0',
											'img_class'   => 'card-img-top',
										)
									);
									the_title( '<h2 class="entry-title card-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
									?>
									<!-- <p  class="card-text"> -->
										<?php //the_excerpt(); ?>
									<!-- </p> -->
								</div>
						<?php if ( $latest_posts % 4 == 0 || $latest_posts == $lerm_total_posts ) : ?>
							</div>
						</div>
						<?php endif; ?>
						<?php
					endwhile;

					wp_reset_postdata();
					?>
				</div>
			</div>
			<?php get_sidebar(); ?>  
		</div>
	</section>
</main>
<?php
get_footer();
