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


	public function __construct( $args = array() ) {
		$defaults = array(
			'show'    => true,
			'classes' => '',
			'height'  => '',
			'width'   => '',
		);
		// Parse the arguments with the deaults.
		$this->args = apply_filters( 'breadcrumb_trail_args', wp_parse_args( $args, $defaults ) );

		//call set function
		if ( true === $this->args['show'] ) {
			$this->set_thumbnail();
		}
	}
	public function get_image() {
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

	public function set_thumbnail() {
		$height     = $this->args['height'];
		$width      = $this->args['width'];
		$classes    = $this->args['classes'];
		$src        = $this->get_image();
		$thumbnail  = '<figure class="thumbnail-wrap m-0">';
		$thumbnail .= sprintf( '<a href="%1$s" title="%2$s" rel="bookmark"><img class="thumbnail img-fluid %3$s" src="%4$s" height="%5$s" width="%6$s" alt="%2$s"></a>', get_permalink(), get_the_title(), $classes, $src, $height, $width );
		$thumbnail .= '</figure>';
		echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function dimensions() {

	}
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
