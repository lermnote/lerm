<?php // phpcs:disable WordPress.Files.FileName
/**
 * Posts thumbnail handle.
 *
 * @package Lerm/Inc
 */
namespace Lerm\Inc\Misc;

use Lerm\Inc\Traits\Singleton;

class Image {

	use singleton;

	public $attachment_id = '';

	private $image_args = array();

	/**
	 * $default parse to arg;
	 *
	 * @var array
	 */
	protected $args = array(
		'post_id'           => '',
		'size'              => 'home-thumb',
		'lazy'              => 'lazy',
		'order'             => array( 'featured', 'block', 'scan', 'default' ),

		// Methods of getting an image (in order). array|string
		'meta_key'          => 'thumbnail',
		'featured'          => true,
		'block'             => true,
		'scan'              => true,
		'default'           => array(),

		// Saving the image.
		'save_as_thumbnail' => false, // Set 'featured image'.
		'save_as_meta_key'  => false, // Save as metadata (string).
		'cache'             => false,  // Cache the image.
	);

	/**
	 * Construst function initials.
	 */
	public function __construct( $params = array() ) {

		$this->args = apply_filters( 'get_the_image_args', wp_parse_args( $params, $this->args ) );

		// Initialize the image handling process
		$this->get_image( $this->args['post_id'] );

	}

	public function get_image( $post_id ) {

		$cached_thumbnail = wp_cache_get( $post_id . '_thumbnail', 'post_thumbnail' );
		if ( $cached_thumbnail ) {
			$this->attachment_id = $cached_thumbnail;
			return;
		}

		foreach ( $this->args['order'] as $order ) {

			if ( ! empty( $this->attachment_id ) ) {
				break;
			}

			if ( 'meta_key' === $order && ! empty( $this->args['meta_key'] ) ) {
				$this->get_meta_key_image( $post_id );

			} elseif ( 'featured' === $order && $this->args['featured'] ) {
				$this->get_featured_image( $post_id );

			} elseif ( 'block' === $order && $this->args['block'] ) {
				$this->get_blocks_image( $post_id );

			} elseif ( 'scan' === $order && $this->args['scan'] ) {
				$this->get_scan_image( $post_id );

			} elseif ( 'default' === $order && ! empty( $this->args['default'] ) ) {
				$this->set_default_image( $this->args['default'] );
			}
		}

		if ( ! empty( $this->attachment_id ) ) {
			if ( ! empty( $this->args['meta_key'] ) && $this->args['save_as_meta_key'] ) {
				$this->meta_key_save( $this->args['post_id'], $this->args['meta_key'], $this->attachment_id );
			}

			if ( $this->args['save_as_thumbnail'] ) {
				$this->set_image_as_thumbnail( $this->args['post_id'], $this->attachment_id );
			}
			if ( $this->args['cache'] ) {
				wp_cache_set( $post_id . '_thumbnail', $this->attachment_id, 'post_thumbnail', HOUR_IN_SECONDS );
			}
		}
	}

	public function get_featured_image( $post_id ) {
		$post_thumbnail_id = get_post_thumbnail_id( $post_id );

		if ( ! empty( $post_thumbnail_id ) ) {

			$this->attachment_id = $post_thumbnail_id;
		}
	}

	/**
	 * function that will return the ID of the first image in a post from a Gutenberg-based post
	 *
	 * @return int $first_image_blocks['attrs']['id'] first post image id
	 */
	public function get_blocks_image( $post_id ) {
		$post_content = apply_filters( 'get_the_image_post_content', get_post_field( 'post_content', $post_id ) );
		$blocks       = parse_blocks( $post_content );

		// Iterate over the blocks
		foreach ( $blocks as $block ) {
			if ( 'core/image' === $block['blockName'] && ! empty( $block['attrs']['id'] ) ) {
				$this->attachment_id = $block['attrs']['id'];
				break;
			}
		}
	}

	public function get_scan_image( $post_id ) {
		$post_content = apply_filters( 'get_the_image_post_content', get_post_field( 'post_content', $post_id ) );

		// Check the content for `id="wp-image-%d"`.
		preg_match( '/id=[\'"]wp-image-([\d]*)[\'"]/i', $post_content, $image_ids );

		// Loop through any found image IDs.
		if ( is_array( $image_ids ) ) {

			foreach ( $image_ids as $image_id ) {
				if ( ! empty( $image_id ) ) {
					$this->attachment_id = $image_id;
					return;
				}
			}
		}

		// Search the post's content for the <img /> tag and get its URL.
		preg_match_all( '|<img.*?src=[\'"](.*?)[\'"].*?>|i', $post_content, $matches );

		// If there is a match for the image, set the image args.
		if ( isset( $matches[1] ) && ! empty( $matches[1] ) ) {
			$this->attachment_id = attachment_url_to_postid( $matches[1][0] );
		}
	}

	/**
	 * Default thumbnial if show thumbnail on post list page,but nethever feature image,nor post images
	 *
	 * @return string $thumbnail_gallery[ $rand_key ] image id
	 */
	public function set_default_image( $image_list ) {

		if ( empty( $image_list ) || ! empty( $this->attachment_id ) ) {
			return;
		}

		$image_ids = is_array( $image_list ) ? $image_list : explode( ',', $image_list );

		$this->attachment_id = $image_ids[ array_rand( $image_ids ) ];
	}
	/**
	 * Gets a image by post meta key.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	private function get_meta_key_image( $post_id ) {

		// If $meta_key is not an array.
		if ( ! is_array( $this->args['meta_key'] ) ) {
			$this->args['meta_key'] = array( $this->args['meta_key'] );
		}

		// Loop through each of the given meta keys.
		foreach ( $this->args['meta_key'] as $meta_key ) {

			// Get the image URL by the current meta key in the loop.
			$image = get_post_meta( $post_id, $meta_key, true );

			// If an image was found, break out of the loop.
			if ( is_numeric( $image ) ) {
				$this->attachment_id = absint( $image );
			} else {
				$this->image_args = array( 'src' => $image );
			}
			break;
		}
	}
	/**
	 * Saves the image source as metadata.  Saving the image as meta is actually quite a bit quicker
	 * if the user doesn't have a persistent caching plugin available.  However, it doesn't play as
	 * nicely with custom image sizes used across multiple themes where one might want to resize images.
	 * This option should be reserved for advanced users only.  Don't use in publicly-distributed
	 * themes.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	private function meta_key_save( $post_id, $meta_key, $attachment_id ) {

		// If the $meta_key_save argument is empty or there is no image $url given, return.
		if ( empty( $post_id ) || empty( $meta_key ) || empty( $attachment_id ) ) {
			return;
		}
		// If $meta_key is not an array.
		if ( ! is_array( $this->args['meta_key'] ) ) {
			$this->args['meta_key'] = array( $this->args['meta_key'] );
		}

		// Loop through each of the given meta keys.
		foreach ( $this->args['meta_key'] as $meta_key ) {

			// Get the image URL by the current meta key in the loop.
			$meta = get_post_meta( $post_id, $meta_key, true );

			// If an image was found, break out of the loop.
			if ( empty( $meta ) ) {
				add_post_meta( $post_id, $meta_key, $attachment_id );
			} elseif ( $meta !== $attachment_id ) {
				// If the current value doesn't match the image id, update it.
				update_post_meta( $post_id, $meta_key, $attachment_id, $meta );
			}
			break;
		}

	}

	/**
	 * Saves the image attachment as the WordPress featured image.  This is useful for setting the
	 * featured image for the post in the case that the user forgot to (win for client work!).  It
	 * should not be used in publicly-distributed themes where you don't know how the user will be
	 * setting up their site.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */

	private function set_image_as_thumbnail( $post_id, $attachment_id ) {

		if ( has_post_thumbnail() ) {
			return;
		}
		// Save the attachment as the 'featured image'.
		if ( ! empty( $this->attachment_id ) && $this->args['save_as_thumbnail'] ) {
			set_post_thumbnail( $post_id, $attachment_id );
		}
	}

	// Generate HTML for the image similar to the_post_thumbnail()
	public function generate_image_html() {
		$attachment_id = absint( $this->attachment_id );
		if ( empty( $attachment_id ) ) {
			return;
		}

		$size    = $this->args['size'];
		$classes = implode( ' ', array( 'attachment-' . $size, 'w-100', 'h-100', 'rounded' ) );
		$attr    = array(
			'alt'     => get_the_title( $attachment_id ),
			'title'   => get_the_title( $attachment_id ),
			'class'   => $classes,
			'loading' => $this->args['lazy'],
		);
		return wp_get_attachment_image( $attachment_id, $size, false, $attr );
	}
}
