<?php

if ( is_admin() || in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) ) ) {
	return;
}

$simple_lazyload_is_strict_lazyload = false;

function simple_lazyload_lazyload() {
	add_action( 'template_redirect', 'simple_lazyload_obstart' );

	function simple_lazyload_obstart() {
		ob_start( 'simple_lazyload_obend' );
	}

	function simple_lazyload_obend( $content ) {
		return simple_lazyload_content_filter_lazyload( $content );
	}
	
	function simple_lazyload_content_filter_lazyload( $content ) {
		$skip_lazyload = apply_filters( 'simple_lazyload_skip_lazyload', false );

		// don't lazyload for feeds, previews
		if ( $skip_lazyload || is_feed() || is_preview() ) {
			return $content;
		}

		global $simple_lazyload_is_strict_lazyload;

		$regexp = '/<img([^<>]*)\.(bmp|gif|jpeg|jpg|png)([^<>]*)>/i';

		$content = preg_replace_callback(
			$regexp,
			'simple_lazyload_lazyimg_str_handler',
			$content
		);

		return $content;
	}
	function simple_lazyload_lazyimg_str_handler( $matches ) {
		global $simple_lazyload_is_strict_lazyload;

		$lazyimg_str = $matches[0];

		// no need to use lazy load
		if ( stripos( $lazyimg_str, 'src=' ) === false ) {
			return $lazyimg_str;
		}
		if ( ( stripos( $lazyimg_str, 'skip_lazyload' ) !== false ) || ( stripos( $lazyimg_str, 'custom-logo' ) !== false ) || ( stripos( $lazyimg_str, 'slider' ) !== false ) || ( stripos( $lazyimg_str, 'avatar' ) !== false ) || ( stripos( $lazyimg_str, 'qrcode' ) !== false ) ) {
			return $lazyimg_str;
		}

		// if (preg_match("/width=/i", $lazyimg_str)
		// || preg_match("/width:/i", $lazyimg_str)
		// || preg_match("/height=/i", $lazyimg_str)
		// || preg_match("/height:/i", $lazyimg_str)) {
		// $alt_image_src = get_template_directory_uri () . '/img/grey.gif';
		// } else {
		// if (preg_match("/\/smilies\//i", $lazyimg_str)
		// || preg_match("/\/smiles\//i", $lazyimg_str)
		// || preg_match("/\/avatar\//i", $lazyimg_str)
		// || preg_match("/\/avatars\//i", $lazyimg_str)
		// )
		// {
		// $alt_image_src = get_template_directory_uri () . '/img/grey.gif';
		// } else {
		// $alt_image_src = get_template_directory_uri () .'/img/grey.gif';
		// }
		// }

		if ( stripos( $lazyimg_str, 'class=' ) === false ) {
			$lazyimg_str = preg_replace(
				'/<img(.*)>/i',
				'<img class="lazyimg"$1>',
				$lazyimg_str
			);
		} else {
			$lazyimg_str = preg_replace(
				"/<img(.*)class=['\"]([\w\-\s]*)['\"](.*)>/i",
				'<img$1class="$2 lazyimg"$3>',
				$lazyimg_str
			);
		}

		if ( stripos( $lazyimg_str, 'srcset=' ) ) {
			if ( ! stripos( $lazyimg_str, 'data-srcset=' ) ) {
				$regexp      = "/<img([^<>]*)srcset=['\"]([^<>'\"]*)['\"]([^<>]*)>/i";
				$replace     = '<img$1srcset="data:image/gif;base64,R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-srcset="$2"$3>';
				$lazyimg_str = preg_replace(
					$regexp,
					$replace,
					$lazyimg_str
				);
				$lazyimg_str = str_ireplace( 'lazyimg', 'responsively-lazy', $lazyimg_str );
			}
			$lazyimg_str = str_ireplace( 'lazyimg', '', $lazyimg_str );
		} else {
				$regexp    = "/<img([^<>]*)src=['\"]([^<>'\"]*)\.(bmp|gif|jpeg|jpg|png)([^<>'\"]*)['\"]([^<>]*)>/i";
				$replace   = '<img$1src="' . $alt_image_src . '" file="$2.$3$4"$5><noscript>' . $matches[0] . '</noscript>';
			  $lazyimg_str = preg_replace( $regexp, $replace, $lazyimg_str );
		}

		return $lazyimg_str;
	}

	// add_action('wp_head', 'simple_lazyload_footer_lazyload', 11);
	add_action( 'wp_footer', 'simple_lazyload_footer_lazyload', 11 );
	function simple_lazyload_footer_lazyload() {
		$loading_icon = get_template_directory() . '/img/loading.gif';
		$loading_icon = apply_filters( 'simple_lazyload_loading_icon', $loading_icon );
		print( '
<!-- Simple Lazyload css and js -->
<style type="text/css">
	.lazyimg {
		opacity: 0.1;
		filter: alpha(opacity=10);
		background: url(' . $loading_icon . ') no-repeat center center;
	}
</style>
<noscript>
<style type="text/css">
	.lazyimg{display:none;}
</style>
</noscript>

<script type="text/javascript">
	Array.prototype.S = String.fromCharCode(2);
	Array.prototype.in_array = function(e) {
		var r = new RegExp(this.S + e + this.S);
		return (r.test(this.S + this.join(this.S) + this.S));
	};

	Array.prototype.pull = function(content) {
		for (var i = 0, n = 0; i < this.length; i++) {
		  if (this[i] != content) {
		    this[n++] = this[i];
		  }
		}
		this.length -= 1;
	};

  jQuery(function($) {
	  $(document).bind("lazyimgs", function() {
		  if (!window._lazyimgs) {
		    window._lazyimgs = $("img.lazyimg");
		  } else {
		    var _lazyimgs_new = $("img.lazyimg:not([lazyloadindexed=1])");
		    if (_lazyimgs_new.length > 0) {
		      window._lazyimgs = $(window._lazyimgs.toArray().concat(_lazyimgs_new.toArray()));
		    }
		  }
		  window._lazyimgs.attr("lazyloadindexed", 1);
		});
		$(document).trigger("lazyimgs");
		if (_lazyimgs.length == 0) {
		  return;
		}
		var toload_inds = [];
		var loaded_inds = [];
		var failed_inds = [];
		var failed_count = {};
		var lazyload = function() {
		  if (loaded_inds.length == _lazyimgs.length) {
		    return;
		  }
		  var threshold = 200;
		  _lazyimgs.each(function(i) {
		    _self = $(this);
		    if (_self.attr("lazyloadpass") === undefined && _self.attr("file") &&(!_self.attr("src") || (_self.attr("src") && _self.attr("file") != _self.attr("src")))) {
		      if ((_self.offset().top) < ($(window).height() + $(document).scrollTop() + threshold) &&
		          (_self.offset().left) < ($(window).width() + $(document).scrollLeft() + threshold) &&
		          (_self.offset().top) > ($(document).scrollTop() - threshold) &&
		          (_self.offset().left) > ($(document).scrollLeft() - threshold)
		      ) {
		        if (toload_inds.in_array(i)) {
		          return;
		        }
		        toload_inds.push(i);
		        if (failed_count["count" + i] === undefined) {
		          failed_count["count" + i] = 0;
		        }
		        _self.css("opacity", 1);
		        $("<img ind=\"" + i + "\"/>").bind("load", function() {
		          var ind = $(this).attr("ind");
		          if (loaded_inds.in_array(ind)) {
		            return;
		          }
		          loaded_inds.push(ind);
		          var _img = _lazyimgs.eq(ind);
		          _img.attr("src", _img.attr("file")).css("background-image", "none").attr("lazyloadpass", "1");
		        }).bind("error", function() {
		          var ind = $(this).attr("ind");
		          if (!failed_inds.in_array(ind)) {
		            failed_inds.push(ind);
		          }
		          failed_count["count" + ind]++;
		          if (failed_count["count" + ind] < 2) {
		            toload_inds.pull(ind);
		          }
		        })
						.attr("src", _self.attr("file"));
		      }
		    }
		 });
		}
		lazyload();
		var ins;
		$(window).scroll(function() {
		  clearTimeout(ins);
		 ins = setTimeout(lazyload, 100);
		});
		$(window).resize(function() {
		  clearTimeout(ins);
		  ins = setTimeout(lazyload, 100);
		});
	});

	jQuery(function($) {
		var calc_image_height = function(_img) {
		  var width = _img.attr("width");
		  var height = _img.attr("height");
		  if (!(width && height && width >= 300)) return;
		  var now_width = _img.width();
		  var now_height = parseInt(height * (now_width / width));
		  _img.css("height", now_height);
		}
		var fix_images_height = function() {
		  _lazyimgs.each(function() {
		  calc_image_height($(this));
		  });
		}
		fix_images_height();
		$(window).resize(fix_images_height);
	});
</script>
<!-- Simple Lazyload css and js END -->
' );
	}
}
simple_lazyload_lazyload();
