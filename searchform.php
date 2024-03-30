<?php
/**
 * WordPress search form.
 *
 * @package Lerm https://lerm.net
 * @date    2016-07-14
 * @since  1.0
 */
$class = wp_is_mobile() ? 'p-3' : '';
$value = is_search() ? get_search_query() : ''
?>
<form role="search" method="get" id="searchform" class="search-form mb-0 <?php echo esc_attr( $class ); ?>
" action="<?php echo esc_url( home_url( '/' ) ); ?>" required >
		<input type="text" class="form-control" name="s" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr__( 'Search…', 'lerm' ); ?>">
</form>
