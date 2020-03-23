<?php
/**
 * Class Name: Lerm_Walker_Nav_Menu
 * GitHub URI: https://github.com/dupkey/bs4navwalker
 * Description: A custom WordPress nav walker class for Bootstrap 4 nav menus in a custom theme using the WordPress built in menu manager.
 *  License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */
class Lerm_Walker_Nav_Menu extends Walker_Nav_Menu {

	/**
	 * Starts the list before the elements are added.
	 * @see Walker::start_lvl()
	 * @since 3.0.0
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int $depth Depth of menu item. Used for padding.
	 * @param array $args An array of arguments.
	 * @see wp_nav_menu()
	 */
	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		$indent        = str_repeat( "\t", $depth );
		$display_depth = ( $depth + 1 );
		$output       .= "\n" . $indent . '<ul class="dropdown-menu m-0 border-0">' . "\n";
	}

	/**
	 * Ends the list of after the elements are added.
	 * @see Walker::end_lvl()
	 * @since 3.0.0
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int $depth Depth of menu item. Used for padding.
	 * @param array $args An array of arguments.
	 * @see wp_nav_menu()
	 */
	public function end_lvl( &$output, $depth = 0, $args = array() ) {
		$indent  = str_repeat( "\t", $depth );
		$output .= $indent . '</ul>' . "\n";
	}

	/**
	 * Start the element output.
	 * @see Walker::start_el()
	 * @since 3.0.0
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $item Menu item data object.
	 * @param int $depth Depth of menu item. Used for padding.
	 * @param array $args An array of arguments.
	 * @see wp_nav_menu()
	 * @param int $id Current item ID.
	 */
	public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		$indent = ( $depth > 0 ? str_repeat( "\t", $depth ) : '' );

		$classes   = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;

		/**
		* Filter the CSS class(es) applied to a menu item's list item element.
		* @since 3.0.0
		* @since 4.1.0 The `$depth` parameter was added.
		* @param array $classes The CSS classes that are applied to the menu item's `<li'.>` element.
		* @param object $item The current menu item.
		* @param array $args An array of {@see wp_nav_menu()} arguments.
		* @param int $depth Depth of menu item. Used for padding.
		*/
		$class_names  = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
		$class_names .= ' nav-item';

		if ( in_array( 'menu-item-has-children', $classes, true ) ) {
			$class_names .= ' dropdown';
		}
		if ( in_array( 'current-menu-item', $classes, true ) || in_array( 'current-menu-item', $classes, true ) || in_array( 'current-menu-ancestor', $classes, true ) ) {
			$class_names .= ' active';
		}
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

		$id = apply_filters( 'nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth );
		$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

		$output .= $indent . '<li' . $id . $class_names . '>';

		$atts                  = array();
		$atts['title']         = ! empty( $item->attr_title ) ? $item->attr_title : '';
		$atts['target']        = ! empty( $item->target ) ? $item->target : '';
		$atts['data-toggle'] = ! empty( $item->data_toggle ) ? esc_attr( $item->data_toggle ) : '';
		if ( '_blank' === $item->target && empty( $item->xfn ) ) {
			$atts['rel'] = 'noopener noreferrer';
		} else {
			$atts['rel'] = $item->xfn;
		}
		$atts['href']           = ! empty( $item->url ) ? $item->url : '';
		$atts['aria-current'] = $item->current ? 'page' : '';

		if ( 0 === $depth ) {
			$atts['class']  = 'nav-link ';
			$atts['class'] .= ! empty( $item->a_class ) ? esc_attr( $item->a_class ) : '';
		}
		if ( 0 === $depth && in_array( 'menu-item-has-children', $classes, true ) ) {
			$atts['data - toggle'] = 'dropdown';
			$atts['class']        .= ' dropdown-toggle ';
		}
		if ( $depth > 0 ) {
			$manual        = array_values( $classes )[0] . ' dropdown-item';
			$atts['class'] = $manual;
		}

		$atts       = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );
		$attributes = '';
		foreach ( $atts as $attr => $value ) {
			if ( ! empty( $value ) ) {
				$value       = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
				$attributes .= ' ' . $attr . ' = "' . $value . '"';
			}
		}
		$title = apply_filters( 'the_title', $item->title, $item->ID );

		$title = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );

		$item_output  = $args->before;
		$item_output .= '<a' . $attributes . '>';
		$item_output .= $args->link_before . $title . $args->link_after;
		$item_output .= ' </a>';
		$item_output .= $args->after;

		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}
	public function end_el( &$output, $item, $depth = 0, $args = array() ) {
		if ( 0 === $depth ) {
			$output .= '</li>' . "\n";
		}
	}
}

// 清理多余css id
function lerm_remove_css_attributes( $var ) {
	return is_array( $var ) ? array_intersect( $var, array( 'active', 'dropdown', 'open', 'show' ) ) : '';
}
add_filter( 'nav_menu_css_class', 'lerm_remove_css_attributes', 100, 1 );
add_filter( 'nav_menu_item_id', 'lerm_remove_css_attributes', 100, 1 );
add_filter( 'page_css_class', 'lerm_remove_css_attributes', 100, 1 );
