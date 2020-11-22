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
//posts show on top
$recent_posts = new WP_Query(
	array(
		'posts_per_page'      => 6,
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
	<div class="row">
		<div class="col-md-8">
			<div class="mb-4">
				<?php //lerm_carousel( array() ); ?>
			</div>
			<div class="row row-cols-1 row-cols-md-3">
				<?php if ( $recent_posts->have_posts() ) : ?>
					<?php
					while ( $recent_posts->have_posts() ) :
						$recent_posts->the_post();
						?>
				<div class="col mb-4">
					<div class="card h-100">
						<?php the_post_thumbnail(); ?>
						<div class="card-body">
							<h5 class="card-title">
								<?php the_title( '<a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a>' ); ?>
							</h5>
						</div>
						<?php //get_template_part( 'template/content/content', 'excerpt' ); ?>
					</div>
				</div>
				<?php endwhile; ?>
				<?php endif; ?>
			</div><!-- row row-cols-1 row-cols-md-3 -->
			<div class="card mb-3">
				<div class="row no-gutters">
					<div class="col-md-4">
						<svg class="bd-placeholder-img" width="100%" height="250" xmlns="http://www.w3.org/2000/svg"
							preserveAspectRatio="xMidYMid slice" focusable="false" role="img"
							aria-label="Placeholder: Image">
							<title>Placeholder</title>
							<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%" fill="#dee2e6"
								dy=".3em">Image</text>
						</svg>
					</div>
					<div class="col-md-8">
						<div class="card-body">
							<h5 class="card-title">Card title</h5>
							<p class="card-text">This is a wider card with supporting text below as a natural lead-in to
								additional content. This content is a little bit longer.</p>
							<p class="card-text"><small class="text-muted">Last updated 3 mins ago</small></p>
						</div>
					</div>
				</div>
			</div>
		</div><!-- .col-md-8 -->
		<div class="col-md-4">
			<div class="row row-cols-1">
				<div class="col mb-4">
					<div class="card h-100">
						<svg class="bd-placeholder-img card-img-top" width="100%" height="180"
							xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false"
							role="img" aria-label="Placeholder: Image cap">
							<title>Placeholder</title>
							<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%" fill="#dee2e6"
								dy=".3em">Image cap</text>
						</svg>
						<div class="card-body">
							<h5 class="card-title">Card title</h5>
							<p class="card-text">This is a short card.</p>
						</div>
					</div>
				</div>
				<div class="col mb-4">
					<div class="card h-100">
						<svg class="bd-placeholder-img card-img-top" width="100%" height="180"
							xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false"
							role="img" aria-label="Placeholder: Image cap">
							<title>Placeholder</title>
							<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%" fill="#dee2e6"
								dy=".3em">Image cap</text>
						</svg>
						<div class="card-body">
							<h5 class="card-title">Card title</h5>
							<p class="card-text">This is a longer card with supporting text below as a natural lead-in
								to additional content.</p>
						</div>
					</div>
				</div>
				<div class="col mb-4">
					<div class="card h-100">
						<svg class="bd-placeholder-img card-img-top" width="100%" height="180"
							xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false"
							role="img" aria-label="Placeholder: Image cap">
							<title>Placeholder</title>
							<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%" fill="#dee2e6"
								dy=".3em">Image cap</text>
						</svg>
						<div class="card-body">
							<h5 class="card-title">Card title</h5>
							<p class="card-text">This is a longer card with supporting text below as a natural lead-in
								to additional content. This content is a little bit longer.</p>
						</div>
					</div>
				</div>
				<div id="carouselExampleSlidesOnly" class="carousel slide" data-ride="carousel">
					<div class="carousel-inner">
						<div class="carousel-item active">
							<div class="col mb-4">
								<div class="card h-100">
									<svg class="bd-placeholder-img card-img-top" width="100%" height="180"
										xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice"
										focusable="false" role="img" aria-label="Placeholder: Image cap">
										<title>Placeholder</title>
										<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%"
											fill="#dee2e6" dy=".3em">Image cap</text>
									</svg>
									<div class="card-body">
										<h5 class="card-title">Card title</h5>
										<p class="card-text">This is a longer card with supporting text below as a
											natural lead-in to additional content. This content is a little bit longer.
										</p>
									</div>
								</div>
							</div>
						</div>
						<div class="carousel-item">
							<div class="col mb-4">
								<div class="card h-100">
									<svg class="bd-placeholder-img card-img-top" width="100%" height="180"
										xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice"
										focusable="false" role="img" aria-label="Placeholder: Image cap">
										<title>Placeholder</title>
										<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%"
											fill="#dee2e6" dy=".3em">Image cap</text>
									</svg>
									<div class="card-body">
										<h5 class="card-title">Card title 1</h5>
										<p class="card-text">This is a longer card with supporting text below as a
											natural lead-in to additional content. This content is a little bit longer.
										</p>
									</div>
								</div>
							</div>
						</div>
						<div class="carousel-item">
							<div class="col mb-4">
								<div class="card h-100">
									<svg class="bd-placeholder-img card-img-top" width="100%" height="180"
										xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice"
										focusable="false" role="img" aria-label="Placeholder: Image cap">
										<title>Placeholder</title>
										<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%"
											fill="#dee2e6" dy=".3em">Image cap</text>
									</svg>
									<div class="card-body">
										<h5 class="card-title">Card title 2</h5>
										<p class="card-text">This is a longer card with supporting text below as a
											natural lead-in to additional content. This content is a little bit longer.
										</p>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

		</div><!-- col-md-4 -->
	</div><!-- row -->
	<div class="card bg-dark text-white mb-4">
		<svg class="bd-placeholder-img bd-placeholder-img-lg card-img" width="100%" height="270"
			xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img"
			aria-label="Placeholder: Card image">
			<title>Placeholder</title>
			<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%" fill="#dee2e6" dy=".3em">Card
				image</text>
		</svg>
		<div class="card-img-overlay">
			<h5 class="card-title">Card title</h5>
			<p class="card-text">This is a wider card with supporting text below as a natural lead-in to additional
				content. This content is a little bit longer.</p>
			<p class="card-text">Last updated 3 mins ago</p>
		</div>
	</div>
	<div class="row row-cols-2 row-cols-md-4">
		<div class="col mb-4">
			<div class="card h-100">
				<svg class="bd-placeholder-img card-img-top" width="100%" height="180"
					xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img"
					aria-label="Placeholder: Image cap">
					<title>Placeholder</title>
					<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%" fill="#dee2e6"
						dy=".3em">Image cap</text>
				</svg>
				<div class="card-body">
					<h5 class="card-title">Card title</h5>
					<p class="card-text">This is a longer card with supporting text below as a natural lead-in to
						additional content. This content is a little bit longer.</p>
				</div>
			</div>
		</div>
		<div class="col mb-4">
			<div class="card h-100">
				<svg class="bd-placeholder-img card-img-top" width="100%" height="180"
					xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img"
					aria-label="Placeholder: Image cap">
					<title>Placeholder</title>
					<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%" fill="#dee2e6"
						dy=".3em">Image cap</text>
				</svg>
				<div class="card-body">
					<h5 class="card-title">Card title</h5>
					<p class="card-text">This is a short card.</p>
				</div>
			</div>
		</div>
		<div class="col mb-4">
			<div class="card h-100">
				<svg class="bd-placeholder-img card-img-top" width="100%" height="180"
					xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img"
					aria-label="Placeholder: Image cap">
					<title>Placeholder</title>
					<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%" fill="#dee2e6"
						dy=".3em">Image cap</text>
				</svg>
				<div class="card-body">
					<h5 class="card-title">Card title</h5>
					<p class="card-text">This is a longer card with supporting text below as a natural lead-in to
						additional content.</p>
				</div>
			</div>
		</div>
		<div class="col mb-4">
			<div class="card h-100">
				<svg class="bd-placeholder-img card-img-top" width="100%" height="180"
					xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img"
					aria-label="Placeholder: Image cap">
					<title>Placeholder</title>
					<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%" fill="#dee2e6"
						dy=".3em">Image cap</text>
				</svg>
				<div class="card-body">
					<h5 class="card-title">Card title</h5>
					<p class="card-text">This is a longer card with supporting text below as a natural lead-in to
						additional content. This content is a little bit longer.</p>
				</div>
			</div>
		</div>
	</div>
	<div class="card text-center mb-4">
		<div class="card-header">
			Featured
		</div>
		<div class="card-body">
			<h5 class="card-title">Special title treatment</h5>
			<p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
			<a href="#" class="btn btn-primary">Go somewhere</a>
		</div>
		<div class="card-footer text-muted">
			2 days ago
		</div>
	</div>
	<div class="row row-cols-1 row-cols-md-2">
		<div class="col mb-4">
			<div class="card mb-3">
				<div class="row no-gutters">
					<div class="col-md-4">
						<svg class="bd-placeholder-img" width="100%" height="250" xmlns="http://www.w3.org/2000/svg"
							preserveAspectRatio="xMidYMid slice" focusable="false" role="img"
							aria-label="Placeholder: Image">
							<title>Placeholder</title>
							<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%" fill="#dee2e6"
								dy=".3em">Image</text>
						</svg>
					</div>
					<div class="col-md-8">
						<div class="card-body">
							<h5 class="card-title">Card title</h5>
							<p class="card-text">This is a wider card with supporting text below as a natural lead-in to
								additional content. This content is a little bit longer.</p>
							<p class="card-text"><small class="text-muted">Last updated 3 mins ago</small></p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="col mb-4">
			<div class="card mb-3">
				<div class="row no-gutters">
					<div class="col-md-4">
						<svg class="bd-placeholder-img" width="100%" height="250" xmlns="http://www.w3.org/2000/svg"
							preserveAspectRatio="xMidYMid slice" focusable="false" role="img"
							aria-label="Placeholder: Image">
							<title>Placeholder</title>
							<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%" fill="#dee2e6"
								dy=".3em">Image</text>
						</svg>
					</div>
					<div class="col-md-8">
						<div class="card-body">
							<h5 class="card-title">Card title</h5>
							<p class="card-text">This is a wider card with supporting text below as a natural lead-in to
								additional content. This content is a little bit longer.</p>
							<p class="card-text"><small class="text-muted">Last updated 3 mins ago</small></p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div id="carouselExampleControls" class="carousel slide" data-ride="carousel">
		<div class="carousel-inner">
			<div class="carousel-item active">
				<div class="row row-cols-2 row-cols-md-4">
					<div class="col mb-4">
						<div class="card h-100">
							<svg class="bd-placeholder-img card-img-top" width="100%" height="180"
								xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice"
								focusable="false" role="img" aria-label="Placeholder: Image cap">
								<title>Placeholder</title>
								<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%"
									fill="#dee2e6" dy=".3em">Image cap</text>
							</svg>
							<div class="card-body">
								<h5 class="card-title">Card title</h5>
								<p class="card-text">This is a longer card with supporting text below as a natural
									lead-in to additional content. This content is a little bit longer.</p>
							</div>
						</div>
					</div>
					<div class="col mb-4">
						<div class="card h-100">
							<svg class="bd-placeholder-img card-img-top" width="100%" height="180"
								xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice"
								focusable="false" role="img" aria-label="Placeholder: Image cap">
								<title>Placeholder</title>
								<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%"
									fill="#dee2e6" dy=".3em">Image cap</text>
							</svg>
							<div class="card-body">
								<h5 class="card-title">Card title</h5>
								<p class="card-text">This is a short card.</p>
							</div>
						</div>
					</div>
					<div class="col mb-4">
						<div class="card h-100">
							<svg class="bd-placeholder-img card-img-top" width="100%" height="180"
								xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice"
								focusable="false" role="img" aria-label="Placeholder: Image cap">
								<title>Placeholder</title>
								<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%"
									fill="#dee2e6" dy=".3em">Image cap</text>
							</svg>
							<div class="card-body">
								<h5 class="card-title">Card title</h5>
								<p class="card-text">This is a longer card with supporting text below as a natural
									lead-in to additional content.</p>
							</div>
						</div>
					</div>
					<div class="col mb-4">
						<div class="card h-100">
							<svg class="bd-placeholder-img card-img-top" width="100%" height="180"
								xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice"
								focusable="false" role="img" aria-label="Placeholder: Image cap">
								<title>Placeholder</title>
								<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%"
									fill="#dee2e6" dy=".3em">Image cap</text>
							</svg>
							<div class="card-body">
								<h5 class="card-title">Card title</h5>
								<p class="card-text">This is a longer card with supporting text below as a natural
									lead-in to additional content. This content is a little bit longer.</p>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="carousel-item">
				<div class="row row-cols-2 row-cols-md-4">
					<div class="col mb-4">
						<div class="card h-100">
							<svg class="bd-placeholder-img card-img-top" width="100%" height="180"
								xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice"
								focusable="false" role="img" aria-label="Placeholder: Image cap">
								<title>Placeholder</title>
								<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%"
									fill="#dee2e6" dy=".3em">Image cap</text>
							</svg>
							<div class="card-body">
								<h5 class="card-title">Card title</h5>
								<p class="card-text">This is a longer card with supporting text below as a natural
									lead-in to additional content. This content is a little bit longer.</p>
							</div>
						</div>
					</div>
					<div class="col mb-4">
						<div class="card h-100">
							<svg class="bd-placeholder-img card-img-top" width="100%" height="180"
								xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice"
								focusable="false" role="img" aria-label="Placeholder: Image cap">
								<title>Placeholder</title>
								<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%"
									fill="#dee2e6" dy=".3em">Image cap</text>
							</svg>
							<div class="card-body">
								<h5 class="card-title">Card title</h5>
								<p class="card-text">This is a short card.</p>
							</div>
						</div>
					</div>
					<div class="col mb-4">
						<div class="card h-100">
							<svg class="bd-placeholder-img card-img-top" width="100%" height="180"
								xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice"
								focusable="false" role="img" aria-label="Placeholder: Image cap">
								<title>Placeholder</title>
								<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%"
									fill="#dee2e6" dy=".3em">Image cap</text>
							</svg>
							<div class="card-body">
								<h5 class="card-title">Card title</h5>
								<p class="card-text">This is a longer card with supporting text below as a natural
									lead-in to additional content.</p>
							</div>
						</div>
					</div>
					<div class="col mb-4">
						<div class="card h-100">
							<svg class="bd-placeholder-img card-img-top" width="100%" height="180"
								xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice"
								focusable="false" role="img" aria-label="Placeholder: Image cap">
								<title>Placeholder</title>
								<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%"
									fill="#dee2e6" dy=".3em">Image cap</text>
							</svg>
							<div class="card-body">
								<h5 class="card-title">Card title</h5>
								<p class="card-text">This is a longer card with supporting text below as a natural
									lead-in to additional content. This content is a little bit longer.</p>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="carousel-item">
				<div class="row row-cols-2 row-cols-md-4">
					<div class="col mb-4">
						<div class="card h-100">
							<svg class="bd-placeholder-img card-img-top" width="100%" height="180"
								xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice"
								focusable="false" role="img" aria-label="Placeholder: Image cap">
								<title>Placeholder</title>
								<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%"
									fill="#dee2e6" dy=".3em">Image cap</text>
							</svg>
							<div class="card-body">
								<h5 class="card-title">Card title</h5>
								<p class="card-text">This is a longer card with supporting text below as a natural
									lead-in to additional content. This content is a little bit longer.</p>
							</div>
						</div>
					</div>
					<div class="col mb-4">
						<div class="card h-100">
							<svg class="bd-placeholder-img card-img-top" width="100%" height="180"
								xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice"
								focusable="false" role="img" aria-label="Placeholder: Image cap">
								<title>Placeholder</title>
								<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%"
									fill="#dee2e6" dy=".3em">Image cap</text>
							</svg>
							<div class="card-body">
								<h5 class="card-title">Card title</h5>
								<p class="card-text">This is a short card.</p>
							</div>
						</div>
					</div>
					<div class="col mb-4">
						<div class="card h-100">
							<svg class="bd-placeholder-img card-img-top" width="100%" height="180"
								xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice"
								focusable="false" role="img" aria-label="Placeholder: Image cap">
								<title>Placeholder</title>
								<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%"
									fill="#dee2e6" dy=".3em">Image cap</text>
							</svg>
							<div class="card-body">
								<h5 class="card-title">Card title</h5>
								<p class="card-text">This is a longer card with supporting text below as a natural
									lead-in to additional content.</p>
							</div>
						</div>
					</div>
					<div class="col mb-4">
						<div class="card h-100">
							<svg class="bd-placeholder-img card-img-top" width="100%" height="180"
								xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice"
								focusable="false" role="img" aria-label="Placeholder: Image cap">
								<title>Placeholder</title>
								<rect width="100%" height="100%" fill="#868e96"></rect><text x="50%" y="50%"
									fill="#dee2e6" dy=".3em">Image cap</text>
							</svg>
							<div class="card-body">
								<h5 class="card-title">Card title</h5>
								<p class="card-text">This is a longer card with supporting text below as a natural
									lead-in to additional content. This content is a little bit longer.</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<a class="carousel-control-prev" href="#carouselExampleControls" role="button" data-slide="prev">
			<span class="carousel-control-prev-icon" aria-hidden="true"></span>
			<span class="sr-only">Previous</span>
		</a>
		<a class="carousel-control-next" href="#carouselExampleControls" role="button" data-slide="next">
			<span class="carousel-control-next-icon" aria-hidden="true"></span>
			<span class="sr-only">Next</span>
		</a>
	</div>
</div>
<?php get_footer(); ?>
