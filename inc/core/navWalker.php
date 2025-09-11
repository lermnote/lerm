<?php // phpcs:disable WordPress.Files.FileName
/**
 * A custom WordPress nav walker class to implement the Bootstrap 5 navigation style in a custom theme using the WordPress built-in menu manager.
 *
 * @package Lerm\Inc
 * @author Lerm https://www.hanost.com
 * @since  Lerm 4.0.0
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Lerm\Inc\Core;

use Walker_Nav_Menu;

if ( ! class_exists( 'NavWalker' ) ) :
	class NavWalker extends Walker_Nav_menu {
		private $has_schema = false;

		public function __construct() {
			if ( ! has_filter( 'wp_nav_menu_args', array( $this, 'add_schema_to_navbar_ul' ) ) ) {
				add_filter( 'wp_nav_menu_args', array( $this, 'add_schema_to_navbar_ul' ) );
			}
		}
		public function start_lvl( &$output, $depth = 0, $args = null ) {
			$indent = str_repeat( "\t", $depth );

			// Add class for sub-menu.
			$output .= "\n$indent<ul class=\"dropdown-menu\">\n";
		}

		// Start menu item.
		public function start_el( &$output, $data_object, $depth = 0, $args = null, $id = 0 ) {
			// Restores the more descriptive, specific name for use within this method.
			$item = $data_object;

			$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

			$classes   = empty( $item->classes ) ? array() : (array) $item->classes;
			$classes[] = 'menu-item-' . $item->ID;
			$classes[] = 'nav-item';// Add class for menu item.

			// Add dropdown class for menu item with children.
			$classes[] = ( $args->has_children ) ? 'dropdown' : '';

			$class_names = implode( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );

			$id = apply_filters( 'nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth );

			$li_atts          = array();
			$li_atts['id']    = ! empty( $id ) ? $id : '';
			$li_atts['class'] = ! empty( $class_names ) ? $class_names : '';

			$li_atts       = apply_filters( 'nav_menu_item_attributes', $li_atts, $item, $args, $depth );
			$li_attributes = $this->build_atts( $li_atts );

			$output .= $indent . '<li' . $li_attributes . '>';

			$atts           = array();
			$atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
			$atts['target'] = ! empty( $item->target ) ? $item->target : '';
			$atts['rel']    = ! empty( $item->xfn ) ? $item->xfn : '';
			$atts['href']   = ! empty( $item->url ) ? $item->url : '#';

			$atts['aria-current'] = $item->current ? 'page' : '';

			$atts['class'] = $item->current ? 'nav-link active' : 'nav-link';

			if ( $args->has_children ) {
				$atts['role']           = 'button';
				$atts['data-bs-toggle'] = 'dropdown';
				$atts['aria-expanded']  = 'false';
				$atts['class']          = $item->current_item_parent ? 'nav-link dropdown-toggle active' : 'nav-link dropdown-toggle';
			}
			if ( $depth > 0 ) {
				$atts['class'] = $item->current ? 'dropdown-item active' : 'dropdown-item';
			}

			$atts       = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args );
			$attributes = $this->build_atts( $atts );

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
				// Add has_children attribute.
				$args[0]->has_children = ! empty( $children_elements[ $element->$id_field ] );
			}

			parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
		}
		public function add_schema_to_navbar_ul( $args ) {
			if ( isset( $args['items_wrap'] ) ) {
				$wrap = $args['items_wrap'];
				if ( strpos( $wrap, 'SiteNavigationElement' ) === false ) {
					$args['items_wrap'] = preg_replace( '/(>).*>?\%3\$s/', ' itemscope itemtype="http://www.schema.org/SiteNavigationElement"$0', $wrap );
				}
			}
			return $args;
		}
		/**
		 * Builds a string of HTML attributes from an array of key/value pairs.
		 * Empty values are ignored.
		 *
		 * @since 6.3.0
		 *
		 * @param  array $atts Optional. An array of HTML attribute key/value pairs. Default empty array.
		 * @return string A string of HTML attributes.
		 */
		protected function build_atts( $atts = array() ) {
			$attribute_string = '';
			foreach ( $atts as $attr => $value ) {
				if ( false !== $value && '' !== $value && is_scalar( $value ) ) {
					$value             = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
					$attribute_string .= ' ' . $attr . '="' . $value . '"';
				}
			}
			return $attribute_string;
		}
	}
endif;
