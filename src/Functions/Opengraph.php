<?php // phpcs:disable WordPress.Files.FileName
/**
 * Improved Open Graph Tags
 *
 * Adds robust Open Graph meta tags for better sharing previews.
 * This is a hardened, more maintainable variant of the classic Jetpack implementation
 * adjusted for the Lerm theme (namespaced string domain 'lerm').
 *
 * @link http://ogp.me/
 *
 * NOTE: This file intentionally keeps the original function names for compatibility
 * with themes/plugins that may call them directly. Internal behavior is improved.
 */

if ( ! function_exists( 'jetpack_og_tags' ) ) {
	/**
	 * Output Open Graph meta tags in the document head.
	 */
	function jetpack_og_tags() {
		// Prefer the modern filter name; keep backward compatibility with older filter.
		$open_graph_enabled = apply_filters( 'jetpack_enable_open_graph', true );
		if ( false === $open_graph_enabled ) {
			return;
		}

		// Backwards compatibility for legacy filter (deprecated).
		if ( false === apply_filters( 'jetpack_enable_opengraph', true ) ) {
			_deprecated_function( 'jetpack_enable_opengraph', '2.0.3', 'jetpack_enable_open_graph' );
			return;
		}

		// Temporarily remove widont filter if present; remember if we removed it.
		$widont_removed = false;
		if ( has_filter( 'the_title', 'widont' ) ) {
			$widont_removed = remove_filter( 'the_title', 'widont' );
		}

		$og_output = "\n<!-- Jetpack Open Graph Tags -->\n";
		$tags      = array();

		// Image size defaults, filterable
		$image_width        = absint( apply_filters( 'jetpack_open_graph_image_width', 200 ) );
		$image_height       = absint( apply_filters( 'jetpack_open_graph_image_height', 200 ) );
		$description_length = absint( apply_filters( 'jetpack_open_graph_description_length', 197 ) );

		if ( is_home() || is_front_page() ) {
			$site_type              = apply_filters( 'jetpack_open_graph_site_type', 'website' );
			$tags['og:type']        = $site_type;
			$tags['og:title']       = get_bloginfo( 'name' );
			$tags['og:description'] = get_bloginfo( 'description' );

			$front_page_id = get_option( 'page_for_posts' );
			if ( 'page' === get_option( 'show_on_front' ) && $front_page_id && is_home() ) {
				$tags['og:url'] = get_permalink( $front_page_id );
			} else {
				$tags['og:url'] = home_url( '/' );
			}

			// Allow site owners to associate FB admins via a filtered option
			$facebook_admins = apply_filters( 'jetpack_facebook_admins', array() );
			if ( ! empty( $facebook_admins ) ) {
				$tags['fb:admins'] = $facebook_admins;
			}
		} elseif ( is_author() ) {
			$author = get_queried_object();
			if ( $author ) {
				$tags['og:type']            = 'profile';
				$tags['og:title']           = wp_kses_post( $author->display_name );
				$tags['og:url']             = ! empty( $author->user_url ) ? esc_url_raw( $author->user_url ) : get_author_posts_url( $author->ID );
				$tags['og:description']     = wp_kses_post( $author->description );
				$tags['profile:first_name'] = get_the_author_meta( 'first_name', $author->ID );
				$tags['profile:last_name']  = get_the_author_meta( 'last_name', $author->ID );
			}
		} elseif ( is_singular() ) {
			global $post;
			$data = $post;
			if ( $data ) {
				$tags['og:type'] = 'article';

				// Title
				if ( empty( $data->post_title ) ) {
					$tags['og:title'] = ' ';
				} else {
					$tags['og:title'] = wp_strip_all_tags( apply_filters( 'the_title', $data->post_title, $data->ID ) );
				}

				$tags['og:url'] = get_permalink( $data->ID );

				// Description: prefer excerpt, then before more tag, then fallback filter
				if ( ! post_password_required() ) {
					if ( ! empty( $data->post_excerpt ) ) {
						$desc = preg_replace( '@https?://[\S]+@', '', strip_shortcodes( wp_strip_all_tags( $data->post_excerpt ) ) );
					} else {
						$exploded = explode( '<!--more-->', $data->post_content );
						$desc     = wp_trim_words( preg_replace( '@https?://[\S]+@', '', strip_shortcodes( wp_strip_all_tags( $exploded[0] ) ) ) );
					}

					if ( empty( $desc ) ) {
						$tags['og:description'] = apply_filters( 'jetpack_open_graph_fallback_description', __( 'Visit the post for more.', 'lerm' ), $data );
					} else {
						$tags['og:description'] = trim( convert_chars( wptexturize( $desc ) ) );
					}
				}

				$tags['article:published_time'] = gmdate( 'c', strtotime( $data->post_date_gmt ) );
				$tags['article:modified_time']  = gmdate( 'c', strtotime( $data->post_modified_gmt ) );

				if ( post_type_supports( get_post_type( $data ), 'author' ) && isset( $data->post_author ) ) {
					$publicize_facebook_user = get_post_meta( $data->ID, '_publicize_facebook_user', true );
					if ( ! empty( $publicize_facebook_user ) ) {
						$tags['article:author'] = esc_url_raw( $publicize_facebook_user );
					}
				}
			}
		}

		// Allow plugins/themes to modify the base tags
		$tags = apply_filters( 'jetpack_open_graph_base_tags', $tags, compact( 'image_width', 'image_height' ) );

		// Re-enable widont if we removed it earlier
		if ( $widont_removed ) {
			add_filter( 'the_title', 'widont' );
		}

		// If there are no tags and filter says return, bail.
		if ( empty( $tags ) && apply_filters( 'jetpack_open_graph_return_if_empty', true ) ) {
			return;
		}

		$tags['og:site_name'] = get_bloginfo( 'name' );

		// Build image block
		if ( ! post_password_required() ) {
			$image_info = jetpack_og_get_image( $image_width, $image_height );
			if ( ! empty( $image_info['src'] ) ) {
				$tags['og:image'] = esc_url_raw( $image_info['src'] );
				if ( ! empty( $image_info['width'] ) ) {
					$tags['og:image:width'] = (int) $image_info['width'];
				}
				if ( ! empty( $image_info['height'] ) ) {
					$tags['og:image:height'] = (int) $image_info['height'];
				}
				if ( ! empty( $image_info['alt_text'] ) ) {
					$tags['og:image:alt'] = wp_kses_post( $image_info['alt_text'] );
				}
			}
		}

		if ( empty( $tags['og:title'] ) ) {
			$tags['og:title'] = __( '(no title)', 'lerm' );
		}

		// Shorten description if too long
		if ( isset( $tags['og:description'] ) ) {
			$tags['og:description'] = mb_strlen( $tags['og:description'] ) > $description_length ? mb_substr( $tags['og:description'], 0, $description_length ) . '…' : $tags['og:description'];
		}

		// Final filter for tags prior to output
		$tags = apply_filters( 'jetpack_open_graph_tags', $tags, compact( 'image_width', 'image_height' ) );

		// Support secure_url mapping per-image
		$secure = isset( $tags['og:image:secure_url'] ) ? $tags['og:image:secure_url'] : '';
		unset( $tags['og:image:secure_url'] );
		$secure_image_num = 0;

		foreach ( (array) $tags as $tag_property => $tag_content ) {
			$tag_content = (array) $tag_content;
			$tag_content = array_unique( $tag_content );

			foreach ( $tag_content as $single ) {
				if ( empty( $single ) ) {
					continue;
				}

				$og_tag     = sprintf( '<meta property="%s" content="%s" />', esc_attr( $tag_property ), esc_attr( $single ) );
				$og_output .= apply_filters( 'jetpack_open_graph_output', $og_tag );
				$og_output .= "\n";

				if ( 'og:image' === $tag_property ) {
					if ( is_array( $secure ) && ! empty( $secure[ $secure_image_num ] ) ) {
						$og_tag     = sprintf( '<meta property="og:image:secure_url" content="%s" />', esc_url( $secure[ $secure_image_num ] ) );
						$og_output .= apply_filters( 'jetpack_open_graph_output', $og_tag );
						$og_output .= "\n";
					} elseif ( ! is_array( $secure ) && ! empty( $secure ) ) {
						$og_tag     = sprintf( '<meta property="og:image:secure_url" content="%s" />', esc_url( $secure ) );
						$og_output .= apply_filters( 'jetpack_open_graph_output', $og_tag );
						$og_output .= "\n";
					}
					++$secure_image_num;
				}
			}
		}

		$og_output .= "\n<!-- End Jetpack Open Graph Tags -->\n";

		// Print output. The individual tag values were escaped above.
		echo $og_output; // phpcs:ignore WordPress.Security.EscapeOutput -- tags already escaped above.
	}
}


if ( ! function_exists( 'jetpack_og_get_image' ) ) {
	/**
	 * Returns an image to be used as Open Graph image. Always returns an array with at least 'src'.
	 *
	 * @param int $width  Minimum width.
	 * @param int $height Minimum height.
	 * @return array{src:string,width?:int,height?:int,alt_text?:string}
	 */
	function jetpack_og_get_image( $width = 200, $height = 200 ) {
		$width  = absint( $width );
		$height = absint( $height );
		$image  = array();

		// 1) If singular and attachment image
		if ( is_singular() && ! is_home() ) {
			if ( is_attachment( get_the_ID() ) && 'image' === substr( get_post_mime_type(), 0, 5 ) ) {
				$image['src'] = wp_get_attachment_url( get_the_ID() );
			}

			// 2) Use Jetpack_PostImages if available
			if ( empty( $image ) && class_exists( 'Jetpack_PostImages' ) ) {
				$post_images = Jetpack_PostImages::get_images(
					get_the_ID(),
					array(
						'width'  => $width,
						'height' => $height,
					)
				);
				if ( $post_images && ! is_wp_error( $post_images ) ) {
					foreach ( (array) $post_images as $post_image ) {
						if ( ! empty( $post_image['src'] ) ) {
							$image['src'] = $post_image['src'];
							if ( isset( $post_image['src_width'], $post_image['src_height'] ) ) {
								$image['width']  = (int) $post_image['src_width'];
								$image['height'] = (int) $post_image['src_height'];
							}
							if ( ! empty( $post_image['alt_text'] ) ) {
								$image['alt_text'] = wp_strip_all_tags( $post_image['alt_text'] );
							}
							break; // first acceptable image
						}
					}
				}
			}
		}

		// 3) Blavatar fallback
		if ( empty( $image ) && function_exists( 'blavatar_domain' ) && function_exists( 'blavatar_exists' ) ) {
			$blavatar_domain = blavatar_domain( home_url() );
			if ( blavatar_exists( $blavatar_domain ) ) {
				$image['src']    = blavatar_url( $blavatar_domain, 'img', $width, false, true );
				$image['width']  = $width;
				$image['height'] = $height;
			}
		}

		// 4) Site logo (Jetpack)
		if ( empty( $image ) && function_exists( 'jetpack_has_site_logo' ) && jetpack_has_site_logo() ) {
			$image_id = jetpack_get_site_logo( 'id' );
			$logo     = wp_get_attachment_image_src( $image_id, 'full' );
			if ( isset( $logo[0], $logo[1], $logo[2] ) && _jetpack_og_get_image_validate_size( $logo[1], $logo[2], $width, $height ) ) {
				$image['src']    = $logo[0];
				$image['width']  = (int) $logo[1];
				$image['height'] = (int) $logo[2];
			}
		}

		// 5) Core site icon
		if ( empty( $image ) && function_exists( 'has_site_icon' ) && has_site_icon() ) {
			$image_id = get_option( 'site_icon' );
			$icon     = wp_get_attachment_image_src( $image_id, 'full' );
			if ( isset( $icon[0], $icon[1], $icon[2] ) && _jetpack_og_get_image_validate_size( $icon[1], $icon[2], $width, $height ) ) {
				$image['src']    = $icon[0];
				$image['width']  = (int) $icon[1];
				$image['height'] = (int) $icon[2];
			}
		}

		// Final fallback image (filterable)
		if ( empty( $image ) ) {
			$image['src'] = apply_filters( 'jetpack_open_graph_image_default', 'https://s0.wp.com/i/blank.jpg' );
		}

		return $image;
	}
}

if ( ! function_exists( '_jetpack_og_get_image_validate_size' ) ) {
	function _jetpack_og_get_image_validate_size( $width, $height, $req_width, $req_height ) {
		if ( ! $width || ! $height ) {
			return false;
		}

		return ( (int) $width >= (int) $req_width ) && ( (int) $height >= (int) $req_height );
	}
}

if ( ! function_exists( 'jetpack_og_get_image_gravatar' ) ) {
	function jetpack_og_get_image_gravatar( $email, $width ) {
		return get_avatar_url( $email, array( 'size' => absint( $width ) ) );
	}
}

// End of improved Open Graph tags file
