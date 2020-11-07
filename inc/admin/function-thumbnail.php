<?php
/**
 * @author Lerm https://www.hanost.com
 * @since  2.0
 *
 * show thumbnails on posts list page
 * if has feature image, show the thumbnail, else show the first image, if has no image, show default
 */
function lerm_thumb_nail( $args = array() ) {
	$thumbnail = new Lerm_Thumbnail( $args );
	return $thumbnail;
}
class Lerm_Thumbnail {

	public $image = array();


	public function __construct( $args = array() ) {
		$defaults = array(
			'show_feature'     => true,
			'show_first_image' => true,
			'show_first_image' => true,
			'classes'          => '',
			'height'           => '',
			'width'            => '',
		);
		// Parse the arguments with the deaults.
		$this->args = apply_filters( 'breadcrumb_trail_args', wp_parse_args( $args, $defaults ) );

		//call set function
		if ( true === $this->args['show_feature'] ) {
			$this->set_thumbnail();
		}
		$this->post_image();
	}
	public function set_thumbnail() {
		$height     = $this->args['height'];
		$width      = $this->args['width'];
		$classes    = $this->args['classes'];
		$src        = $this->get_image();
		$thumbnail  = '<figure class="thumbnail-wrap m-0 mr-md-3 card-img-top">';
		$thumbnail .= sprintf( '<a href="%1$s" title="%2$s" rel="bookmark"><img class="thumbnail h-100 %3$s " src="%4$s" height="%5$s" width="%6$s" alt="%2$s"></a>', get_permalink(), get_the_title(), $classes, $src, $height, $width );
		$thumbnail .= '</figure>';
		echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}


	public function get_image() {
		global $post;
		if ( has_post_thumbnail() ) {
			$src = get_the_post_thumbnail_url();
		} else {
			$content = $post->post_content;
			preg_match_all( '/<img [^>]*src=["|\']([^"|\']+)/i', $content, $matches, PREG_PATTERN_ORDER );
			// var_dump( $matches );
			$n = count( $matches[1] );
			if ( $n > 0 ) {
				$src = $matches[1][0];
			} else {
				$src = LERM_URI . 'assets/img/random/' . wp_rand( 1, 10 ) . '.jpg';
			}
		}
		return $src;
	}
	protected function feature_image() {
		global $post;
		if ( has_post_thumbnail() ) {
			$thumbnial = get_the_post_thumbnail();
			var_dump( $thumbnial );
		}

	}
	protected function post_image() {
		global $post;
		ob_start();
		ob_end_clean();
		$content = $post->post_content;
		preg_match_all( '/<img [^>]*src=["|\']([^"|\']+)/i', $content, $matches, PREG_PATTERN_ORDER );
		$srcs = $matches[1];

	}
	protected function lib_image() {
		# code...
	}

	public function dimensions() {

	}
}

function lerm_post_thumbnail() {
	global $post;
	if ( has_post_thumbnail() ) {
		$src = get_the_post_thumbnail_url();
	} else {
		$content = $post->post_content;
		preg_match_all( '/<img [^>]*src=["|\']([^"|\']+)/i', $content, $matches, PREG_PATTERN_ORDER );
		$n = count( $matches[1] );
		if ( $n > 0 ) {
			$src = $matches[1][0];
		} else {
			$src = LERM_URI . 'assets/img/random/' . wp_rand( 1, 10 ) . '.jpg';
		}
	}
		return $src;
}























































// lightbox function
function image_with_a_tag( $content ) {
	$regex = "/<a([^<>]*)><img([^<>]*)src=['\"]([^<>'\"]*)\.(bmp|gif|jpeg|jpg|png)([^<>'\"]*)['\"]([^<>]*)><\/a>/sim";

	return preg_replace_callback( $regex, 'image_replace_callback', $content );
}

function image_without_a_tag( $content ) {
	$regex = "/<img([^<>]*)src=['\"]([^<>'\"]*)\.(bmp|gif|jpeg|jpg|png)([^<>'\"]*)['\"]([^<>]*)>/sim";

	return preg_replace_callback( $regex, 'image_replace_callback', $content );
}

function image_replace_callback( $matches ) {
	if ( stripos( $matches[0], 'href' ) ) {
		$a_open  = '';
		$a_close = '';
		$img     = '<img' . $matches[2] . 'src="' . $matches[3] . '.' . $matches[4] . $matches[5] . '"' . $matches[6] . ' >';
	} else {
		$a_open  = '<a href="' . $matches[2] . '.' . $matches[3] . '" data-toggle="lightbox" data-gallery="entry-gallery">';
		$a_close = '</a>';
		$img     = $matches[0];
	}
	return $a_open . $img . $a_close;
}
add_filter( 'the_content', 'image_with_a_tag', 2 );
add_filter( 'the_content', 'image_without_a_tag', 2 );


function sunset_get_attachment() {
	global $post;
	$output = '';
	if ( has_post_thumbnail() ) :
		$output = wp_get_attachment_url( get_post_thumbnail_id( get_the_ID() ) );
	else :
		$args        = array(
			'post_type'      => 'attachment',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'post_parent'    => $post->ID,
		);
		$attachments = get_posts( $args );
		// var_dump($attachments);
		if ( $attachments ) :
			foreach ( $attachments as $attachment ) :
				$output = wp_get_attachment_url( $attachment->ID );
			endforeach;
		endif;

		wp_reset_postdata();

	endif;

	return $output;
}

if ( ! function_exists( 'twentythirteen_the_attached_image' ) ) :
	/**
	 * Print the attached image with a link to the next attached image.
	 *
	 * @since Twenty Thirteen 1.0
	 */
	function twentythirteen_the_attached_image() {
		/**
		 * Filter the image attachment size to use.
		 *
		 * @since Twenty thirteen 1.0
		 *
		 * @param array $size {
		 *     @type int The attachment height in pixels.
		 *     @type int The attachment width in pixels.
		 * }
		 */
		$attachment_size     = apply_filters( 'twentythirteen_attachment_size', array( 724, 724 ) );
		$next_attachment_url = wp_get_attachment_url();
		$post                = get_post();

		/*
		 * Grab the IDs of all the image attachments in a gallery so we can get the URL
		 * of the next adjacent image in a gallery, or the first image (if we're
		 * looking at the last image in a gallery), or, in a gallery of one, just the
		 * link to that image file.
		 */
		$attachment_ids = get_posts(
			array(
				'post_parent' => $post->post_parent,
				'fields'      => 'ids',
				'numberposts' => -1,
				'post_status' => 'inherit',
				'post_type'   => 'attachment',
				//'post_mime_type' => 'image',
				'order'       => 'ASC',
				'orderby'     => 'menu_order ID',
			)
		);

		// If there is more than 1 attachment in a gallery...
		if ( count( $attachment_ids ) > 1 ) {
			foreach ( $attachment_ids as $idx => $attachment_id ) {
				if ( $attachment_id == $post->ID ) {
					$next_id = $attachment_ids[ ( $idx + 1 ) % count( $attachment_ids ) ];
					break;
				}
			}

			// get the URL of the next image attachment...
			if ( $next_id ) {
				$next_attachment_url = get_attachment_link( $next_id );
			} else {
				// or get the URL of the first image attachment.
				$next_attachment_url = get_attachment_link( reset( $attachment_ids ) );
			}
		}

		printf(
			'<a href="%1$s" title="%2$s" rel="attachment">%3$s</a>',
			esc_url( $next_attachment_url ),
			the_title_attribute( array( 'echo' => false ) ),
			wp_get_attachment_image( $post->ID, $attachment_size )
		);
	}
endif;
