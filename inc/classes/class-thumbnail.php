<?php
/**
 * Posts thumbnail handle.
 *
 * @package Lerm/Inc
 */

namespace Lerm\Inc;

use Lerm\Inc\Traits\Hooker;
use Lerm\Inc\Traits\Singleton;

class Thumbnail extends Theme_Abstract
{
    use Hooker, Singleton;

    /**
     * $default parse to arg;
     *
     * @var array
     */
    public $args = array();
    
    
    /**
     * Image arguments array filled by the class
     *
     * @var array
     */
    public $attr = array();

    /**
     * Collection of posts thumbnails.
     *
     * @var array
     */
    public $thumbnails = array();

    public function __construct($args = array())
    {
        $this->hooks();
        $defauls = array(
            'post_id' => get_the_ID(),
            'size'    => 'thumbnail',
            'lazy'    => false,

        );
        $this->args = apply_filters('get_the_image_args', wp_parse_args($args, $defauls));
    }

    protected function handle()
    {
    }

    protected function hooks()
    {
        $this->filter('post_thumbnail_html', 'get_default_thumbnail', 1, 5);
    }

    public function get_default_thumbnail($html, $post_id, $post_thumbnail_id, $size, $attr)
    {
        if ($this->feature_image()) {
            $thumbnail_id = $this->feature_image();
        } elseif ($this->post_images()) {
            $thumbnail_id = $this->post_images();
        } else {
            $thumbnail_id = $this->thumbnail_gallery();
        }

        if ('' === $html) {
            if (intval($thumbnail_id) > 0) {
                $html = wp_get_attachment_image(intval($thumbnail_id), $size, false, $attr);
            }
        }

        return $html;
    }

    /**
     * Gets the featured image
	 * 
	 * @return void
     */
    protected function get_feature_image()
    {
        $post_thumbnail_id = get_post_thumbnail_id($this->args['post_id']);
        if (empty($post_thumbnail_id)) {
            return;
        }
        $this->args['size'] = apply_filters('post_thumbnail_size', $this->args['size']);
        $this->get_attachment_image($post_thumbnail_id);
    }

    /**
     * Find post images, and return first post image.
     *
     * @return string attachment_url_to_postid( $matches[1][0] ) first post image id
     */
    protected function get_post_image()
    {
        // global $post;
        $post_content = get_post_field('post_content', $this->args['post_id']);
        preg_match_all('/<img[^>]*src=[\"|\']([^>\"\'\s]+).*alt\=[\"|\']([^>\"\']+).*?[\/]?>/i', $post_content, $matches, PREG_PATTERN_ORDER);
        if (isset($matches) && !empty($matches[1][0])) {
            $attachment_id = attachment_url_to_postid($matches[1][0]);
        }
        return $this->get_attachment_image($attachment_id);
    }

    /**
     * Default thumbnial if show thumbnail on post list page,but nethever feature image,nor post images
     *
     * @return string $thumbnail_gallery[ $rand_key ] image id
     */
    protected function thumbnail_gallery()
    {
        if (lerm_options('thumbnail_gallery')) {
            $thumbnail_gallery = explode(',', lerm_options('thumbnail_gallery'));
            $rand_key          = array_rand($thumbnail_gallery);
            return $thumbnail_gallery[ $rand_key ];
        }
    }

    private function get_attachment_image($attachment_id)
    {
        $html = '';
        $attr = '';
        $image = wp_get_attachment_image_src($attachment_id, $this->args['size']);
        if ($image) {
            $attachment = get_post($attachment_id);
            $default_attr = array(
                'class' => "attachment-$this->args['size']",
                'alt'   => trim(strip_tags(get_post_meta($attachment_id, '_wp_attachment_image_alt', true))), // Use Alt field first
                'lazy'  => $this->args['lazy'],
            );
            if (empty($default_attr['alt'])) {
                $default_attr['alt'] = trim(strip_tags($attachment->post_excerpt));
            } // If not, Use the Caption
            if (empty($default_attr['alt'])) {
                $default_attr['alt'] = trim(strip_tags($attachment->post_title));
            } // Finally, use the title
            $attr = wp_parse_args($attr, $default_attr);
            
            $html = wp_get_attachment_image(intval($attachment_id), $this->args['size'], false, $attr);
        }
        return $html;
    }
}
