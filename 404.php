<?php
/**
 * The template for displaying the 404 page.
 *
 * @package Lerm
 */

get_header();

use function Lerm\Support\lerm_breadcrumb;
$template_options = lerm_get_template_options();

$page_title  = ! empty( $template_options['404_title'] )
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
$custom_img_id  = $template_options['404_image_id'] ?? 0;
$custom_img_url = $custom_img_id ? wp_get_attachment_image_url( (int) $custom_img_id, 'large' ) : '';
$default_img    = LERM_URI . 'assets/img/notfound.gif';
$img_url        = $custom_img_url ? $custom_img_url : $default_img;
?>

<?php lerm_breadcrumb(); ?>
<div class="text-center py-5">
	<img src="<?php echo esc_url( $img_url ); ?>"
		alt="<?php echo esc_attr( $page_title ); ?>"
		class="img-fluid mb-4"
		style="max-height:300px;">
	<h1 class="display-5 fw-bold mb-3"><?php echo esc_html( $page_title ); ?></h1>
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

<?php
get_footer();
