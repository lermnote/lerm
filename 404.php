<?php
/**
 * The template for displaying the 404 page.
 *
 * @package Lerm
 */

get_header();

$template_options = lerm_get_template_options();

$title       = ! empty( $template_options['404_title'] )
	? $template_options['404_title']
	: __( '404 Not Found', 'lerm' );
$message     = ! empty( $template_options['404_message'] )
	? $template_options['404_message']
	: __( 'Sorry, the page you are looking for could not be found.', 'lerm' );
$btn_text    = ! empty( $template_options['404_button_text'] )
	? $template_options['404_button_text']
	: __( 'Back to home', 'lerm' );
$btn_url     = ! empty( $template_options['404_button_url'] )
	? $template_options['404_button_url']
	: home_url( '/' );
$show_search = ! isset( $template_options['404_show_search'] ) || ! empty( $template_options['404_show_search'] );

// Custom illustration
$custom_img_id  = $template_options['404_image']['id'] ?? 0;
$custom_img_url = $custom_img_id ? wp_get_attachment_image_url( (int) $custom_img_id, 'large' ) : '';
$default_img    = LERM_URI . 'assets/img/notfound.gif';
$img_url        = $custom_img_url ?: $default_img;
?>
<main role="main" class="container"><!--.container-->
	<?php get_template_part( 'template-parts/components/breadcrumb' ); ?>
	<div class="text-center py-5">
		<img src="<?php echo esc_url( $img_url ); ?>"
			alt="<?php echo esc_attr( $title ); ?>"
			class="img-fluid mb-4"
			style="max-height:300px;">
		<h1 class="display-5 fw-bold mb-3"><?php echo esc_html( $title ); ?></h1>
		<p class="text-muted mb-4"><?php echo esc_html( $message ); ?></p>
		<?php if ( $show_search ) : ?>
			<div class="mx-auto mb-4" style="max-width:480px;">
				<?php get_search_form(); ?>
			</div>
		<?php endif; ?>
		<a href="<?php echo esc_url( $btn_url ); ?>" class="btn btn-custom">
			<?php echo esc_html( $btn_text ); ?>
		</a>
	</div>
</main><!--.container-->
<?php
get_footer();