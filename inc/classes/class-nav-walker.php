<?php
/**
 * A custom WordPress nav walker class to implement the Bootstrap 5 navigation style in a custom theme using the WordPress built in menu manager.
 *
 * @package Lerm\Inc
 * @author Lerm https://www.hanost.com
 * @since  Lerm 4.0.0
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Lerm\Inc;

use Walker_Nav_Menu;

// Check if Class Exists.
if ( ! class_exists( 'Nav_Walker' ) ) :
	class Nav_Walker extends Walker_Nav_menu {
		// 开始子菜单
		public function start_lvl( &$output, $depth = 0, $args = array() ) {
			$indent = str_repeat( "\t", $depth );
			// 为子菜单添加 class
			$output .= "\n$indent<ul class=\"dropdown-menu\">\n";
		}

		// 开始菜单项
		public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
			$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

			$li_attributes = '';
			$class_names   = '';

			$classes = empty( $item->classes ) ? array() : (array) $item->classes;

			// 为菜单项添加 menu-item-[ID] class
			$classes[] = 'nav-item';
			$classes[] = 'menu-item-' . $item->ID;

			// 为当前菜单项添加 active class
			$classes[] = ( $item->current || $item->current_item_ancestor ) ? 'active' : '';

			// 为菜单项添加 dropdown class
			$classes[] = ( $args->has_children ) ? 'dropdown' : '';

			$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args ) );
			$class_names = ' class="' . esc_attr( $class_names ) . '"';

			$id = apply_filters( 'nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args );
			$id = ! empty( $id ) ? ' id="' . esc_attr( $id ) . '"' : '';

			$output .= $indent . '<li' . $id . $class_names . $li_attributes . '>';

			$attributes  = ! empty( $item->attr_title ) ? ' title="' . esc_attr( $item->attr_title ) . '"' : '';
			$attributes .= ! empty( $item->target ) ? ' target="' . esc_attr( $item->target ) . '"' : '';
			$attributes .= ! empty( $item->xfn ) ? ' rel="' . esc_attr( $item->xfn ) . '"' : '';
			$attributes .= ! empty( $item->url ) ? ' href="' . esc_attr( $item->url ) . '"' : 'href="#"';
			$attributes .= $depth > 0 ? ' class="dropdown-item"' : '';
			$attributes .= ( $args->has_children ) ? 'role="button" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"' : ' class="nav-link"';

			$item_output  = $args->before;
			$item_output .= '<a ' . $attributes . '>';
			$item_output .= $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;
			$item_output .= '</a>';
			$item_output .= $args->after;

			$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
		}

		public function display_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ) {
			if ( ! $element ) {
				return;
			}

			$id_field = $this->db_fields['id'];

			if ( is_object( $args[0] ) ) {
				// 添加 has_children 属性
				$args[0]->has_children = ! empty( $children_elements[ $element->$id_field ] );
			}

			parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
		}
	}
endif;
