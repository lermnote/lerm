<?php
/**
 * Posts thumbnail handle.
 *
 * @package Lerm/Inc
 */

namespace Lerm\Inc;

class Image
{
    /**
     * $default parse to arg;
     *
     * @var array
     */
    public $args = array();

    /**
     * Image id;
     *
     * @var string
     */
    public $image_id = '';


    /**
     * Construst function initials.
     */
    public function __construct($args = array())
    {
        $defaults = array(
            'post_id'    => get_the_ID(),
            'size'       => 'home-thumb',
            'lazy'       => 'lazy',
            'order'      => array('block', 'scan', 'default'),
            'default'    => array(), // URL in medias 'http://example.com/wp-content/uploads/2016/05/01.jpg'

        );

        $this->args = apply_filters('get_the_image_args', wp_parse_args($args, $defaults));

        // Initialize the image handling process
        $this->set_image_as_thumbnail($this->args['post_id']);
    }

    // instance
	public static function instance( $params = array() ) {
		return new self( $params );
	}

    public function set_image_as_thumbnail($post_id)
    {

        if (has_post_thumbnail() || !$post_id) {
            return;
        }
        

        $this->first_image_in_blocks($post_id);
        $this->get_post_image_id($post_id);
        $this->get_default_image($this->args['default']);

        // Set post thumbnail if image_id is not empty
        if (!empty($this->image_id)) {
            
            set_post_thumbnail($this->args['post_id'], $this->image_id);
        }

    }


    /**
     * function that will return the ID of the first image in a post from a Gutenberg-based post
     *
     * @return int $first_image_blocks['attrs']['id'] first post image id
     */
    protected function first_image_in_blocks($post_id)
    {
        $post   = get_post($post_id);
        $blocks = parse_blocks($post->post_content);

        // Iterate over the blocks
        foreach ($blocks as $block) {
            if ('core/image' === $block[ 'blockName' ]) {
                $this->image_id = isset($block['attrs']['id']) ? $block['attrs']['id'] : null;
                return;
            }
        }
    }

    /**
     * Find and return the first post image from post content.
     *
     * @param int $post_id ID of the post to get the image from
     * @return void
     */
    protected function get_post_image_id($post_id)
    {
        $post_content = get_post_field('post_content', $post_id);

        if (empty($post_content)||!empty($this->image_id)) {
            return;
        }

        $document = new \DOMDocument();
        @$document->loadHTML($post_content);

        $images = $document->getElementsByTagName('img');

        foreach ($images as $image) {
            $src      = $image->getAttribute('src');
            $alt      = $image->getAttribute('alt');
            $image_id = attachment_url_to_postid($src);

            if ($image_id) {
                $this->image_id = $image_id;
                return;
            }

        }
    }

    /**
     * Default thumbnial if show thumbnail on post list page,but nethever feature image,nor post images
     *
     * @return string $thumbnail_gallery[ $rand_key ] image id
     */
    protected function get_default_image($images)
    {

        if (empty($images)||!empty($this->image_id)) {
            return;
        }

        $image_ids = is_array($images) ? $images : explode(',', $images);
        $this->image_id = $image_ids[array_rand($image_ids)];
        var_dump( $this->image_id);
    }

}
