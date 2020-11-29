<?php
/**
 * Posts thumbnail handle.
 *
 * @package Lerm/Inc
 */

namespace Lerm\Inc;

class Image {

	/**
	 * $default parse to arg;
	 *
	 * @var array
	 */
	public $args = array();

	/**
	 * Image html;
	 *
	 * @var string
	 */
	public $image = '';

	/**
	 * Image attributes arguments array filled by the class
	 *
	 * @var array
	 */
	public $image_attr = array();

	/**
	 * Collection of posts thumbnails.
	 *
	 * @var array
	 */
	public $thumbnails = array();

	public function __construct( $args = array() ) {
		$this->hooks();
		$defauls = array(
			'post_id'    => get_the_ID(),
			'size'       => 'home-thumb',
			'lazy'       => 'lazy',
			'order'      => array( 'featured', 'attachment', 'scan', 'default' ),

			//define the method of get image
			'featured'   => '',
			'attachment' => '',
			'scan'       => '',
			'default'    => array(), // URL in medias 'http://example.com/wp-content/uploads/2016/05/01.jpg'

			//
			'before'     => '',
			'after'      => '',
		);
		$this->args = apply_filters( 'get_the_image_args', wp_parse_args( $args, $defauls ) );
		if ( empty( $this->args['post_id'] ) ) {
			return;
		}
		$this->find_image();
	}

	protected function handle() {}

	protected function hooks() {}

	public function get_image() {
		if ( empty( $this->image ) ) {
			return;
		}
		$image_html = apply_filters( 'get_the_image', $this->image );
		echo ( ! empty( $image_html ) ) ? $this->args['before'] . $image_html . $this->args['after'] : $image_html; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	protected function find_image() {

		foreach ( $this->args['order'] as  $method ) {
			if ( ! empty( $this->image ) ) {
				break;
			}

			if ( 'featured' === $method ) {
				$this->get_feature_image();
			} elseif ( 'scan' === $method ) {
				$this->get_post_image();
			} elseif ( 'default' === $method && ! empty( $this->args['default'] ) ) {
				$this->get_default_image();
			}
		}
	}

	/**
	 * Gets the featured image
	 *
	 * @return void
	 */
	protected function get_feature_image() {
		$post_thumbnail_id = get_post_thumbnail_id( $this->args['post_id'] );

		if ( empty( $post_thumbnail_id ) ) {
			return;
		}

		$this->args['size'] = apply_filters( 'post_thumbnail_size', $this->args['size'] );
		$this->get_attachment_image( $post_thumbnail_id );
	}

	/**
	 * Find post images, and return first post image.
	 *
	 * @return string attachment_url_to_postid( $matches[1][0] ) first post image id
	 */
	protected function get_post_image() {
		$post_content = get_post_field( 'post_content', $this->args['post_id'] );

		preg_match_all( '/<img[^>]*src=[\"|\']([^>\"\'\s]+).*alt\=[\"|\']([^>\"\']+).*?[\/]?>/i', $post_content, $matches, PREG_PATTERN_ORDER );

		if ( isset( $matches ) && ! empty( $matches[1][0] ) ) {
			$attachment_id = attachment_url_to_postid( $matches[1][0] );
			$this->get_attachment_image( $attachment_id );
		}
	}

	/**
	 * Default thumbnial if show thumbnail on post list page,but nethever feature image,nor post images
	 *
	 * @return string $thumbnail_gallery[ $rand_key ] image id
	 */
	protected function get_default_image() {
		$images = $this->args['default'];

		if ( empty( $images ) ) {
			return;
		}

		if ( ! is_array( $images ) ) {
			$image_ids = explode( ',', $images );
		}

		$image_id = $image_ids[ array_rand( $image_ids ) ];

		$this->get_attachment_image( $image_id );
	}

	/**
	 * Retireve attachment image html.
	 *
	 * @param int $attachment_id
	 * @return string $html
	 */
	private function get_attachment_image( $attachment_id ) {
		$html  = '';
		$attr  = '';
		$image = wp_get_attachment_image_src( $attachment_id, $this->args['size'] );
		if ( $image ) {
			$size_class = $this->args['size'];

			if ( is_array( $size_class ) ) {
				$size_class = join( 'x', $size_class );
			}

			$attachment   = get_post( $attachment_id );
			$default_attr = array(
				'class'   => "attachment-$size_class",
				'alt'     => trim( wp_strip_all_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) ), // Use Alt field first
				'loading' => $this->args['lazy'],
			);
			if ( empty( $default_attr['alt'] ) ) {
				$default_attr['alt'] = trim( wp_strip_all_tags( $attachment->post_excerpt ) );
			} // If not, Use the Caption

			if ( empty( $default_attr['alt'] ) ) {
				$default_attr['alt'] = trim( wp_strip_all_tags( $attachment->post_title ) );
			} // Finally, use the title

			$attr = wp_parse_args( $attr, $default_attr );
			$html = wp_get_attachment_image( intval( $attachment_id ), $this->args['size'], false, $attr );

			$this->image = $html;
		}
		return $html;
	}
}