<?php
/**
 * Theme initial class
 *
 * @package Lerm\inc
 */

namespace Lerm\Inc;

use WP_Error;

use Lerm\Inc\Traits\Singleton;

class Init extends Theme_Abstract {
	use Singleton;

	protected $init = [];


	protected function __construct() {
		$this->hooks();
	}
	protected function handle() {

		$filters = [];

	}

	protected function hooks() {
		add_action( 'init', [ $this, 'unscure' ] );
		add_action( 'init', [ $this, 'disable_emojis' ] );
		$this->filter( 'document_title_separator', 'title_separator', 15, 1 );
		$this->filter( 'frontpage_template', 'front_page_template', 15, 1 );
		$this->filter( 'comment_reply_link', 'replace_reply_link_class' );
		$this->filter( 'widget_tag_cloud_args', 'tag_cloud_args' );
		$this->filters( [ 'script_loader_src', 'style_loader_src' ], 'remove_ver', 15, 1 );
		$this->link_manager();
		$this->remove_recent_comments_css();
		$this->disable_rest_api();
	}

	/**
	 * Clean up wp_head() from unused or unsecure stuff
	 *
	 * @return void
	 */
	public function unscure() {
		remove_action( 'wp_head', 'wp_generator' );// version info
		remove_action( 'wp_head', 'rsd_link' );// offline edit
		remove_action( 'wp_head', 'wlwmanifest_link' );// offline edit
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );// context url
		remove_action( 'wp_head', 'feed_links', 2 );// comment feed
		remove_action( 'wp_head', 'feed_links_extra', 3 );// comment feed
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );// shot link
		remove_action( 'wp_head', 'rel_canonical' );
		remove_action( 'wp_head', 'wp_resource_hints', 2 );// s.w.org
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		add_filter( 'the_generator', '__return_false' );
	}

	/**
	 * Disable WordPress emojis.
	 *
	 * @return void
	 */
	public function disable_emojis() {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'emoji_svg_url', '__return_false' );
		$this->filter( 'tiny_mce_plugins', 'disable_emojis_tinymce' );
	}
	/**
	 * Disable emojis
	 *
	 * @return array
	 */
	public function disable_emojis_tinymce( $plugins ) {
		return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
	}

	/**
	 * Remove style and script version of urls.
	 *
	 * @param string $url
	 * @return $url
	 */
	public function remove_ver( $url ) {
		return $url ? remove_query_arg( 'ver', $url ) : false;
	}

	/**
	 * Enable link manager on wp-admin page.
	 *
	 * @return void
	 */
	protected function link_manager() {
		add_filter( 'pre_option_link_manager_enabled', '__return_true' );
	}

	/**
	 * Use front-page.php when Front page displays is set to a static page.
	 *
	 * @param string $template front-page.php.
	 *
	 * @return string The template to be used: blank if is_home() is true (defaults to index.php), else $template.
	 */
	public function front_page_template( $template ) {
		return is_home() ? '' : $template;
	}

	/**
	 * Set title separatpr of site.
	 *
	 * @param string $sep
	 * @return string The site title separator.
	 */
	public function title_separator( $sep ) {
		return lerm_options( 'title_sep' ) ? str_replace( '-', lerm_options( 'title_sep' ), $sep ) : $sep;
	}

	/**
	 * Add custom class item to replay links.
	 *
	 * @param string $class
	 * @return void
	 */
	public function replace_reply_link_class( $class ) {
		return str_replace( 'class=\'', 'class=\'btn btn-sm btn-custom ', $class );
	}

	/**
	 * Remove the default styles that are packaged with the Recent Comments widget.
	 *
	 * @return void
	 */
	public function remove_recent_comments_css() {
		add_filter( 'show_recent_comments_widget_style', '__return_false' );
	}

	/**
	 * Disable rest api.
	 *
	 * @return void
	 */
	public function disable_rest_api() {
		remove_action( 'xml_rsd_apis', 'rest_output_rsd' );
		remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
		remove_action( 'template_redirect', 'rest_output_linkheader', 11 );
		//$this->filter( 'rest_authentication_errors', 'rest_authorization_error' );
	}

	/**
	 * Retrun an error when REST API is accessed.
	 *
	 * @return WP_Error
	 */
	public function rest_authorization_error() {
		return new WP_Error(
			'rest_forbidden',
			__( 'REST API frobidden.sss', 'lerm' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}

	/**
	 * Custmm tags cloud args.
	 *
	 * @param array $args
	 * @return array Tag cloud args.
	 */
	public function tag_cloud_args( $args ) {
		return array_merge(
			$args,
			[
				'largest'  => 1.382,
				'smallest' => 0.618,
				'unit'     => 'em',
				'number'   => 22,
				'orderby'  => 'count',
				'order'    => 'DESC',
			]
		);
	}
}
