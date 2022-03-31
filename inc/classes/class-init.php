<?php
/**
 * Theme initial class
 *
 * @package Lerm\inc
 */

namespace Lerm\Inc;

use WP_Error;

use Lerm\Inc\Traits\Singleton;
use Lerm\Inc\Traits\Hooker;

class Init {
	use Singleton, hooker;

	public $init = array();

	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_optimize_', wp_parse_args( $params, self::$args ) );
		$this->hooks();
	}
	protected function hooks() {
		$this->filter( 'document_title_separator', 'title_separator', 15, 1 );
		$this->filter( 'frontpage_template', 'front_page_template', 15, 1 );
		$this->filter( 'wp_tag_cloud', 'tag_cloud', 10, 1 );
		$this->filters( array( 'nav_menu_css_class', 'nav_menu_item_id', 'page_css_class' ), 'remove_css_attributes', 100, 1 );
		$this->filter( 'pre_option_link_manager_enabled', '__return_true' );
	}

	/**
	 * Clean up menu attributes.
	 *
	 * @param array $ver
	 * @return $ver
	 */
	public function remove_css_attributes( $var ) {
		return is_array( $var ) ? array_intersect( $var, array( 'active', 'dropdown', 'open', 'show' ) ) : '';
	}

	/**
	 * Use front-page.php when Front page displays is set to a static page.
	 *
	 * @param string $template front-page.php.
	 *
	 * @return string The template to be used: blank if is_home() is true (defaults to index.php), else $template.
	 */
	public static function front_page_template( $template ) {
		return is_home() ? '' : $template;
	}

	/**
	 * Add custom class item to replay links.
	 *
	 * @param string $class
	 * @return void
	 */
	public static function replace_reply_link_class( $class ) {
		return str_replace( 'class=\'', 'class=\'btn btn-sm btn-custom ', $class );
	}

	/**
	 * Custom tags cloud args.
	 *
	 * @param array $args
	 * @return string|string[] Tag cloud as a string or an array, depending on 'format' argument.
	 */
	public static function tag_cloud( $args ) {
		$args = array(
			'largest'  => 22,
			'smallest' => 8,
			'unit'     => 'pt',
			'number'   => 22,
			'orderby'  => 'count',
			'order'    => 'DESC',
		);
		$tags = get_tags();

		$return = wp_generate_tag_cloud( $tags, $args );
		return $return;
	}
}
