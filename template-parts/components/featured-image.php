<?php

/**
 * Displays the featured image
 *
 * @package Lerm https://lerm.net
 */
use Lerm\Helpers\Image;
$image = new Image(
	array(
		'post_id' => get_the_ID(),
		'size'    => 'home-thumb',
		'lazy'    => 'lazy',
		'order'   => array( 'featured', 'block', 'scan', 'default' ),
		'default' => lerm_options( 'thumbnail_gallery' ),
	)
);
if ( empty( $image ) ) {
	return;
}
?>
<figure class="figure w-100 m-0" style="max-height:115px; overflow:hidden">
	<?php
	// echo $image->generate_image_html();// phpcs:ignore WordPress.Security.EscapeOutput -- Reason: has been escaped.
	/**
	 * 生成 HTML（支持响应式 srcset，允许外部覆盖 class/attr）
	 *
	 * @param array $result ['id'=>int|null,'src'=>string|null] —— 来自 get_image()
	 * @param array $args   可选：'size' (string), 'lazy' (loading), 'classes'|'class' (string|array), 'alt' (string)
	 * @param array $extra_attr 额外属性，会合并并覆盖默认 attr（键值对）
	 * @return string HTML
	 */
	// public static function generate_image_html( array $result, array $args = array(), array $extra_attr = array() ): string
	?>
</figure><!-- .featured -->
