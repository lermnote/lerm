<?php
/**
 * Posts thumbnail handle.
 *
 * @package Lerm/Inc
 */

namespace Lerm\Inc;

use Lerm\Inc\Traits\Hooker;
use Lerm\Inc\Traits\Singleton;

class Thumbnail extends Theme_Abstract {
	use Hooker, Singleton;

	/**
	 * $default parse to arg;
	 *
	 * @var array
	 */
	public $args = array();

	/**
	 * Collection of posts thumbnails.
	 *
	 * @var array
	 */
	public $thumbnails = array();

	public function __construct( $args = array() ) {
		$this->hooks();
		$defauls = array(
			'post_id' => get_the_ID(),

		);
		$this->args = apply_filters( 'get_the_image_args', wp_parse_args( $args, $defauls ) );
	}

	protected function handle() {}

	protected function hooks() {
		$this->filter( 'post_thumbnail_html', 'get_default_thumbnail', 1, 5 );
	}

	public function get_default_thumbnail( $html, $post_id, $post_thumbnail_id, $size, $attr ) {

		if ( $this->feature_image() ) {
			$thumbnail_id = $this->feature_image();
		} elseif ( $this->post_images() ) {
			$thumbnail_id = $this->post_images();
		} else {
			$thumbnail_id = $this->thumbnail_gallery();
		}

		if ( '' === $html ) {
			if ( intval( $thumbnail_id ) > 0 ) {
				$html = wp_get_attachment_image( intval( $thumbnail_id ), $size, false, $attr );
			}
		}

		return $html;
	}

	/**
	 *
	 */
	protected function feature_image() {
		return has_post_thumbnail() ? get_post_thumbnail_id() : '';
	}

	/**
	 * Hanlde post images
	 *
	 * @return string attachment_url_to_postid( $matches[1][0] ) first post image id
	 */
	protected function post_images() {
		global $post;
		$post_content = get_post_field( 'post_content', $this->args['post_id'] );
		preg_match_all( '/<img[^>]*src=[\"|\']([^>\"\'\s]+).*alt\=[\"|\']([^>\"\']+).*?[\/]?>/i', $post_content, $matches, PREG_PATTERN_ORDER );
		return $matches[1] ? attachment_url_to_postid( $matches[1][0] ) : '';
	}

	/**
	 * Default thumbnial if show thumbnail on post list page,but nethever feature image,nor post images
	 *
	 * @return string $thumbnail_gallery[ $rand_key ] image id
	 */
	protected function thumbnail_gallery() {
		if ( lerm_options( 'thumbnail_gallery' ) ) {
			$thumbnail_gallery = explode( ',', lerm_options( 'thumbnail_gallery' ) );
			$rand_key          = array_rand( $thumbnail_gallery );
			return $thumbnail_gallery[ $rand_key ];
		}
	}
}
