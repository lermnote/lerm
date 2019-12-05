<?php
/**
 * 添加菜单
 */
class Lerm_Walker_Nav_Menu extends Walker_Nav_Menu {

	// add classes to ul sub-menus
	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		// depth dependent classes
		$indent = ( $depth > 0 ? str_repeat( "\t", $depth ) : '' );
		// code indent
		$display_depth = ( $depth + 1 ); // because it counts the first submenu as 0
		// build html
		$output .= "\n" . $indent . '<div class="dropdown-menu m-0 border-0">' . "\n";
	}
	public function end_lvl( &$output, $depth = 0, $args = array() ) {
	}
	// add main/sub classes to li's and links
	public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		global $wp_query;
		$indent = ( $depth > 0 ? str_repeat( "\t", $depth ) : '' ); // code indent
		// passed classes
		$classes     = empty( $item->classes ) ? array() : (array) $item->classes;
		$class_names = implode( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item ) );

		// build html
		if ( strcasecmp( $depth, 1 ) == 0 ) {
			$output .= $indent;
		} else {
			$output .= $indent . '<li class="nav-item ' . $class_names . '">';
		}
		// link attributes
		$attributes  = ! empty( $item->attr_title ) ? ' title="' . esc_attr( $item->attr_title ) . '"' : '';
		$attributes .= ! empty( $item->target ) ? ' target="' . esc_attr( $item->target ) . '"' : '';
		$attributes .= ! empty( $item->xfn ) ? ' rel="' . esc_attr( $item->xfn ) . '"' : '';
		$attributes .= ! empty( $item->url ) ? ' href="' . esc_attr( $item->url ) . '"' : '';
		$attributes .= ! empty( $item->data_toggle ) ? ' data-toggle="' . esc_attr( $item->data_toggle ) . '"' : '';
		if ( strcasecmp( $depth, 1 ) == 0 ) {
			$attributes .= 'class="dropdown-item ';
			$attributes .= ! empty( $item->a_class ) ? esc_attr( $item->a_class ) : '';
			$attributes .= '"';
		} else {
			$attributes .= ' class="nav-link ';
			$attributes .= ! empty( $item->a_class ) ? esc_attr( $item->a_class ) : '';
			$attributes .= '"';
		}
		$item_output = sprintf(
			'%1$s<a%2$s>%3$s%4$s%5$s</a>',
			$args->before,
			$attributes,
			$args->link_before,
			apply_filters( 'the_title', $item->title, $item->ID ),
			$args->link_after,
			$args->after
		);
		// build html
		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}
	public function end_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
	}
}

add_filter( 'wp_nav_menu_objects', 'add_menu_parent_class' );
function add_menu_parent_class( $items ) {
	$parents = array();
	foreach ( $items as $item ) {
		if ( $item->menu_item_parent && $item->menu_item_parent > 0 ) {
			$parents[] = $item->menu_item_parent;
		}
	}
	foreach ( $items as $item ) {

		if ( in_array( $item->ID, $parents ) ) {
			$item->classes[]   = 'dropdown';
			$item->title       = $item->title;
			$item->a_class     = 'dropdown-toggle';
			$item->data_toggle = 'dropdown';
		}
	}
	return $items;
}

function lerm_current_menu_class( $classes ) {
	if ( in_array( 'current-menu-item', $classes, true ) || in_array( 'current-menu-ancestor', $classes, true ) ) {
		$classes[] = 'active';
	}
	return $classes;
}
add_filter( 'nav_menu_css_class', 'lerm_current_menu_class' );


// 清理多余css id
function lerm_remove_css_attributes( $var ) {
	return is_array( $var ) ? array_intersect( $var, array( 'active', 'dropdown', 'open', 'show' ) ) : '';
}
// add_filter('nav_menu_css_class', 'lerm_remove_css_attributes', 100, 1);
// add_filter('nav_menu_item_id', 'lerm_remove_css_attributes', 100, 1);
// add_filter( 'page_css_class', 'lerm_remove_css_attributes', 100, 1 );
