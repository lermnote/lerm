<?php
/**
 * Breadcrumb trail template.
 *
 *  @package Lerm https://lerm.net
 */
use Lerm\View\Breadcrumb;

$template_options = lerm_get_template_options();

Breadcrumb::instance(
	array(
		'container'     => $template_options['breadcrumb_container'],
		'before'        => $template_options['breadcrumb_before'],
		'after'         => $template_options['breadcrumb_after'],
		'list_tag'      => $template_options['breadcrumb_list_tag'],
		'item_tag'      => $template_options['breadcrumb_item_tag'],
		'separator'     => $template_options['breadcrumb_separator'],
		'show_on_front' => $template_options['breadcrumb_front_show'],
		'network'       => false,
		'show_title'    => ! empty( $template_options['breadcrumb_show_title'] ),
		'labels'        => array(),
		'post_taxonomy' => array(),
		'echo'          => true,
	)
);
