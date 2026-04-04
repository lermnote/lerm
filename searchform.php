<?php
/**
 * WordPress search form.
 *
 * @package Lerm
 * @since  1.0
 */

$template_options = lerm_get_template_options();
$class            = wp_is_mobile() ? 'p-3' : '';
$value            = is_search() ? get_search_query() : '';
$placeholder      = ! empty( $template_options['search_placeholder'] )
	? (string) $template_options['search_placeholder']
	: __( 'Search…', 'lerm' );
$input_id         = wp_unique_id( 'search-form-input-' );
?>
<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="search-form position-relative mb-0 <?php echo esc_attr( $class ); ?>" novalidate>
	<label class="visually-hidden" for="<?php echo esc_attr( $input_id ); ?>"><?php esc_html_e( 'Search', 'lerm' ); ?></label>
	<input
		id="<?php echo esc_attr( $input_id ); ?>"
		type="text"
		class="form-control form-control-sm"
		name="s"
		value="<?php echo esc_attr( $value ); ?>"
		placeholder="<?php echo esc_attr( $placeholder ); ?>"
		autocomplete="off"
		required
	>
	<div class="invalid-feedback">
		<?php esc_html_e( 'Please enter a search keyword.', 'lerm' ); ?>
	</div>
	<div class="js-live-search-results list-group position-absolute start-0 top-100 w-100 shadow-sm mt-2 d-none" aria-live="polite" style="z-index:1060;max-height:420px;overflow-y:auto;background:white"></div>
</form>
