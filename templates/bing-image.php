<?php
/**
 * Template Name: Bing Image
 *
* @package Lerm https://lerm.net
 * @date    2016-10-26
 * @since lerm 2.0
 */
get_header();
$img_link   = 'https://www.bing.com/HPImageArchive.aspx?format=js&idx=0&n=2&mkt=';
$resolution = '1920x1080';
// language options
$langs = array(
	'en-US',
	'zh-CN',
	'ja-JP',
	'en-AU',
	'en-UK',
	'de-DE',
	'en-NZ',
);
// parameters
foreach ( $langs as $lang ) {
	$url     = $img_link . $lang;
	$request = wp_remote_get( $url );
	$data    = wp_remote_retrieve_body( $request );
	$json    = json_decode( trim( $data ), true );
	if ( $json ) {
		$images = $json['images'];
		foreach ( $images as $image ) {
			$urlbase   = $image['urlbase'];
			$image_url = 'https://www.bing.com' . $urlbase . '_' . $resolution . '.jpg';
			$copyright = $image['copyright'];
		}
	}
}
$row_class    = ( 'layout-1c-narrow' === lerm_site_layout() ) ? 'justify-content-md-center' : '';
$colunm_class = ( wp_is_mobile() || 'layout-1c' === lerm_site_layout() ) ? 'col-md-12' : 'col-lg-8';
?>
<main role="main" class="container"><!--.container-->
	<?php get_template_part( 'template-parts/breadcrumb' ); ?>
	<div class="row <?php echo esc_attr( $row_class ); ?> "><!--.row-->
		<div class="<?php echo esc_attr( $colunm_class ); ?> px-1 px-md-0" ><!--.col-md-12 .col-lg-8-->

	<div class="site-main card">
		<?php
		if ( have_posts() ) :
			while ( have_posts() ) :
				the_post();
				?>
				<figure class="figure">
					<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php esc_attr( $copyright ); ?>" class="figure-img img-fluid rounded">
					<figcaption  class="figure-caption text-center">
						<?php echo esc_html( $copyright ); ?>
					</figcaption>
				</figure>
				<?php
				if ( comments_open() || get_comments_number() ) :
					comments_template();
				endif;
			endwhile;
			?>
		<?php endif; ?>
	</div>
</div>
<?php get_sidebar(); ?>
		</div><!--.row-->
</main><!--.container-->
<?php
get_footer();
