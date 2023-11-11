<?php
/**
 * Posts thumbnail handle.
 *
 * @package Lerm/Inc
 */

namespace Lerm\Inc;

use Lerm\Inc\Traits\Singleton;

class Image
{
    use Singleton;
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
    public $image_id = '';

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

    /**
     * Construst function initials.
     */
    public function __construct($args = array())
    {
        $defaults = array(
            'post_id'    => get_the_ID(),
            'size'       => 'home-thumb',
            'lazy'       => 'lazy',
            'order'      => array('featured', 'attachment', 'block', 'scan', 'default'),

            // define the method of get image
            'featured'   => '',
            'attachment' => '',
            'scan'       => '',
            'default'    => array(), // URL in medias 'http://example.com/wp-content/uploads/2016/05/01.jpg'

            'before' => '',
            'after'      => '',
            'class'      => '',

            'echo'       => false,
        );

        $this->args = apply_filters('get_the_image_args', wp_parse_args($args, $defaults));

        // set image as thumbnail
        set_post_thumbnail($this->args[ 'post_id' ], $this->image_id);

    }

    protected function _set_image_as_thumbnail($post_id)
    {

        if (has_post_thumbnail() || !$post_id) {
            return;
        }

        if (in_array('block', $this->args[ 'order' ], true) && empty($this->image_id)) {
            //blocks
            $this->image_id = $this->first_image_in_blocks($post_id);

        }

        if (in_array('scan', $this->args[ 'order' ], true) && empty($this->image_id)) {
            // Get the ID of the first image block
            $this->image_id = $this->get_post_image_id($post_id);

        }

        if (in_array('default', $this->args[ 'order' ], true) && !empty($this->args[ 'default' ]) && empty($this->image_id)) {
            //default image id
            $this->image_id = $this->get_default_image($this->args[ 'default' ]);

        }
    }

    /**
     * Gets the featured image
     *
     * @param int $post_id The ID of the post
     * @return void
     */
    protected function _get_featured_image($post_id)
    {
        if (!$post_id || !has_post_thumbnail($post_id)) {
            return;
        }

        $post_thumbnail_id = get_post_thumbnail_id($post_id);

        if (!$post_thumbnail_id) {
            return;
        }

    }

    /**
     * function that will return the ID of the first image in a post from a Gutenberg-based post
     *
     * @return int $first_image_blocks['attrs']['id'] first post image id
     */
    protected function _first_image_in_blocks($post_id)
    {
        $post   = get_post($post_id);
        $blocks = parse_blocks($post->post_content);

        // Iterate over the blocks
        foreach ($blocks as $block) {
            if ('core/image' === $block[ 'blockName' ]) {
                return isset($block[ 'attrs' ][ 'id' ]) ? $block[ 'attrs' ][ 'id' ] : null;
            }

            return null;
        }
    }

    /**
     * Find and return the first post image from post content.
     *
     * @param int $post_id ID of the post to get the image from
     * @return void
     */
    protected function _get_post_image_id($post_id)
    {
        $post_content = get_post_field('post_content', $post_id);

        if (empty($post_content)) {
            return;
        }

        $document = new \DOMDocument();
        @$document->loadHTML($post_content);

        $images = $document->getElementsByTagName('img');

        foreach ($images as $image) {
            $src      = $image->getAttribute('src');
            $alt      = $image->getAttribute('alt');
            $image_id = attachment_url_to_postid($src);

            return $image_id;

        }
    }

    /**
     * Default thumbnial if show thumbnail on post list page,but nethever feature image,nor post images
     *
     * @return string $thumbnail_gallery[ $rand_key ] image id
     */
    protected function _get_default_image($images)
    {

        if (empty($images)) {
            return;
        }

        if (!is_array($images)) {
            $image_ids = explode(',', $images);
        }

        $image_id = $image_ids[ array_rand($image_ids) ];
        return $image_id;

    }

}
