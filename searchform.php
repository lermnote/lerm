<?php
/**
* WordPress search form.
*
* @authors lerm http://lerm.net
* @date    2016-07-14
* @since version lerm 1.0
*/
$class = wp_is_mobile() ? 'p-3' : '';
$value = is_search() ? get_search_query() : ''
?>
<form role="search" method="get" class="search-form <?php echo esc_attr( $class ); ?>
" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<input type="text" class="form-control" name="s" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr__( 'Search…', 'lerm' ); ?>">
</form>
