<?php
/**
 * Breadcrumb trail template.
 *
 *  @package Lerm https://lerm.net
 */
\Lerm\Inc\Core\Breadcrumb::instance(
	array(
		'container'     => lerm_options( 'breadcrumb_container' ),
		'before'        => lerm_options( 'breadcrumb_before' ),
		'after'         => lerm_options( 'breadcrumb_after' ),
		'list_tag'      => lerm_options( 'breadcrumb_list_tag' ),
		'item_tag'      => lerm_options( 'breadcrumb_item_tag' ),
		'separator'     => lerm_options( 'breadcrumb_separator' ),
		'show_on_front' => lerm_options( 'breadcrumb_show_on_front' ),
		'network'       => false,
		'show_title'    => lerm_options( 'breadcrumb_show_title' ) ? true : false,
		'labels'        => array(),
		'post_taxonomy' => array(),
		'echo'          => true,
	)
);
