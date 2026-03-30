<?php // phpcs:disable WordPress.Files.FileName
/**
 * Theme options configuration.
 *
 * Sections are organised as:
 *   Appearance  ─ Identity · Layout · Colors · Typography · Custom CSS
 *   Content     ─ Blog · Post · Page · Sidebar · Carousel
 *   System      ─ Optimization · Mailing · SEO · CDN
 *   Community   ─ Social · User Center · Advertisement
 *   Tools       ─ Backup
 *
 * @package Lerm
 */

if ( ! class_exists( 'CSF' ) ) {
	return;
}

$prefix    = 'lerm_theme_options';
$imagepath = LERM_URI . 'assets/img/';

// ─────────────────────────────────────────────────────────────────────────────
// Panel
// ─────────────────────────────────────────────────────────────────────────────
CSF::createOptions(
	$prefix,
	array(
		'menu_title'      => __( 'Theme Options', 'lerm' ),
		'menu_slug'       => 'lerm_options',
		'framework_title' => 'Lerm <small>Theme Options</small>',
		'footer_credit'   => sprintf(
			// translators: %s: link to Lerm website.
			__( 'Thank you for creating with <a href="%s" target="_blank">Lerm</a>', 'lerm' ),
			'https://www.hanost.com/'
		),
	)
);

// ═════════════════════════════════════════════════════════════════════════════
// GROUP: Appearance
// ═════════════════════════════════════════════════════════════════════════════
CSF::createSection(
	$prefix,
	array(
		'id'    => 'appearance',
		'title' => __( 'Appearance', 'lerm' ),
		'icon'  => 'fas fa-paint-brush',
	)
);

// ── Appearance › Identity ────────────────────────────────────────────────────
CSF::createSection(
	$prefix,
	array(
		'parent' => 'appearance',
		'title'  => __( 'Identity', 'lerm' ),
		'icon'   => 'fas fa-id-card',
		'fields' => array(

			array(
				'id'       => 'large_logo',
				'type'     => 'media',
				'title'    => __( 'Desktop logo', 'lerm' ),
				'subtitle' => __( 'Shown in the desktop header.', 'lerm' ),
				'library'  => 'image',
				'url'      => false,
			),
			array(
				'id'       => 'mobile_logo',
				'type'     => 'media',
				'title'    => __( 'Mobile logo', 'lerm' ),
				'subtitle' => __( 'Shown on small screens. Falls back to desktop logo if empty.', 'lerm' ),
				'library'  => 'image',
				'url'      => false,
			),

			array(
				'type'    => 'subheading',
				'content' => __( 'Site identity', 'lerm' ),
			),
			array(
				'id'         => 'blogname',
				'type'       => 'text',
				'title'      => __( 'Site title', 'lerm' ),
				'subtitle'   => __( 'Overrides the WordPress site title in the theme.', 'lerm' ),
				'attributes' => array(
					'placeholder' => get_bloginfo( 'name', 'display' ),
				),
			),
			array(
				'id'         => 'blogdesc',
				'type'       => 'text',
				'title'      => __( 'Site tagline', 'lerm' ),
				'subtitle'   => __( 'Overrides the WordPress tagline in the theme.', 'lerm' ),
				'attributes' => array(
					'placeholder' => get_bloginfo( 'description', 'display' ),
				),
			),

			array(
				'type'    => 'subheading',
				'content' => __( 'Footer', 'lerm' ),
			),
			array(
				'id'          => 'footer_menus',
				'type'        => 'select',
				'title'       => __( 'Footer menu', 'lerm' ),
				'subtitle'    => __( 'Navigation menu displayed in the footer area.', 'lerm' ),
				'placeholder' => __( 'Select a menu', 'lerm' ),
				'options'     => 'menus',
			),
			array(
				'id'         => 'icp_num',
				'type'       => 'text',
				'title'      => __( 'ICP registration number', 'lerm' ),
				'subtitle'   => __( 'Chinese ICP filing number shown in the footer (e.g. 京ICP备12345678号).', 'lerm' ),
				'attributes' => array(
					'placeholder' => get_option( 'zh_cn_l10n_icp_num' ),
				),
			),
			array(
				'id'            => 'copyright',
				'type'          => 'wp_editor',
				'title'         => __( 'Footer custom text', 'lerm' ),
				'subtitle'      => __( 'Additional content rendered in the footer (HTML allowed).', 'lerm' ),
				'height'        => '80px',
				'media_buttons' => false,
				'quicktags'     => false,
				'tinymce'       => false,
			),
		),
	)
);

// ── Appearance › Layout ──────────────────────────────────────────────────────
CSF::createSection(
	$prefix,
	array(
		'parent' => 'appearance',
		'title'  => __( 'Layout', 'lerm' ),
		'icon'   => 'fas fa-columns',
		'fields' => array(

			array(
				'type'    => 'subheading',
				'content' => __( 'Container style', 'lerm' ),
			),
			array(
				'id'       => 'layout_style',
				'type'     => 'button_set',
				'title'    => __( 'Container style', 'lerm' ),
				'subtitle' => __( 'Wide — full browser width; Boxed — fixed centered box; Separate — content and sidebar float with gaps.', 'lerm' ),
				'options'  => array(
					'wide-layout'     => __( 'Wide', 'lerm' ),
					'boxed-layout'    => __( 'Boxed', 'lerm' ),
					'separate-layout' => __( 'Separate', 'lerm' ),
				),
				'default'  => 'separate-layout',
			),
			array(
				'id'          => 'site_width',
				'type'        => 'spinner',
				'title'       => __( 'Container width (px)', 'lerm' ),
				'subtitle'    => __( 'Maximum width of the main content container. Default: 1140.', 'lerm' ),
				'dependency'  => array( 'layout_style', '!=', 'boxed-layout' ),
				'min'         => 600,
				'max'         => 4096,
				'step'        => 1,
				'output'      => array( '.wide-layout .container', '.separate-layout .container' ),
				'output_mode' => 'max-width',
				'default'     => 1140,
			),
			array(
				'id'          => 'boxed_width',
				'type'        => 'spinner',
				'title'       => __( 'Box width (px)', 'lerm' ),
				'subtitle'    => __( 'Width of the centered page box in Boxed mode. Default: 1140.', 'lerm' ),
				'dependency'  => array( 'layout_style', '==', 'boxed-layout' ),
				'min'         => 600,
				'max'         => 4096,
				'step'        => 1,
				'output'      => array( '.boxed-layout #page' ),
				'output_mode' => 'width',
				'default'     => 1140,
			),
			array(
				'id'          => 'outside_bg_color',
				'type'        => 'color',
				'title'       => __( 'Box outer background', 'lerm' ),
				'subtitle'    => __( 'Background color of the area outside the box in Boxed mode.', 'lerm' ),
				'dependency'  => array( 'layout_style', '==', 'boxed-layout' ),
				'default'     => '#ebebeb',
				'output_mode' => 'background-color',
				'output'      => array( '.boxed-layout' ),
			),
			array(
				'id'          => 'inner_bg_color',
				'type'        => 'color',
				'title'       => __( 'Box inner background', 'lerm' ),
				'subtitle'    => __( 'Background color of the page box itself in Boxed mode.', 'lerm' ),
				'dependency'  => array( 'layout_style', '==', 'boxed-layout' ),
				'default'     => '#ffffff',
				'output_mode' => 'background-color',
				'output'      => array( '.boxed-layout #page' ),
			),

			array(
				'type'    => 'subheading',
				'content' => __( 'Sidebar layout', 'lerm' ),
			),
			array(
				'id'       => 'global_layout',
				'type'     => 'image_select',
				'title'    => __( 'Default sidebar position', 'lerm' ),
				'subtitle' => __( 'Full-width, narrow full-width, sidebar on left, or sidebar on right. Can be overridden per-post.', 'lerm' ),
				'options'  => array(
					'layout-1c'        => $imagepath . '1c.png',
					'layout-1c-narrow' => $imagepath . '1c-narrow.png',
					'layout-2c-l'      => $imagepath . '2c-l.png',
					'layout-2c-r'      => $imagepath . '2c-r.png',
				),
				'radio'    => true,
				'default'  => 'layout-2c-r',
			),
			array(
				'id'         => 'affix',
				'type'       => 'switcher',
				'dependency' => array( 'global_layout', 'any', 'layout-2c-l, layout-2c-r' ),
				'title'      => __( 'Sticky sidebar', 'lerm' ),
				'desc'       => __( 'Keep the sidebar visible when the user scrolls past it.', 'lerm' ),
			),
			array(
				'id'          => 'content_width',
				'type'        => 'spinner',
				'title'       => __( 'Content column width (%)', 'lerm' ),
				'subtitle'    => __( 'Width of the main content column when a sidebar is present. Default: 66.67.', 'lerm' ),
				'min'         => 10,
				'max'         => 90,
				'step'        => 1,
				'output_mode' => 'width',
				'default'     => 66.6666666667,
			),
			array(
				'id'          => 'sidebar_width',
				'type'        => 'spinner',
				'title'       => __( 'Sidebar column width (%)', 'lerm' ),
				'subtitle'    => __( 'Width of the sidebar column. Default: 33.33.', 'lerm' ),
				'min'         => 10,
				'max'         => 90,
				'step'        => 1,
				'output_mode' => 'width',
				'default'     => 33.3333333333,
			),

			array(
				'type'    => 'subheading',
				'content' => __( 'Navbar', 'lerm' ),
			),
			array(
				'id'      => 'navbar_align',
				'type'    => 'button_set',
				'title'   => __( 'Navigation alignment', 'lerm' ),
				'options' => array(
					'justify-content-md-start'  => __( 'Left', 'lerm' ),
					'justify-content-md-center' => __( 'Center', 'lerm' ),
					'justify-content-md-end'    => __( 'Right', 'lerm' ),
				),
				'default' => 'justify-content-md-end',
			),
			array(
				'id'      => 'navbar_search',
				'type'    => 'switcher',
				'title'   => __( 'Show search in navbar', 'lerm' ),
				'default' => false,
			),
		),
	)
);

// ── Appearance › Colors ──────────────────────────────────────────────────────
CSF::createSection(
	$prefix,
	array(
		'parent' => 'appearance',
		'title'  => __( 'Colors', 'lerm' ),
		'icon'   => 'fas fa-palette',
		'fields' => array(

			// ── Brand ────────────────────────────────────────────────────────
			array(
				'type'    => 'subheading',
				'content' => __( 'Brand colors', 'lerm' ),
			),
			array(
				'id'       => 'primary_color',
				'type'     => 'link_color',
				'title'    => __( 'Primary color', 'lerm' ),
				'subtitle' => __( 'Used for buttons, badges, and interactive accents.', 'lerm' ),
				'default'  => array(
					'color'  => '#0084ba',
					'hover'  => '#0063aa',
					'active' => '#0063aa',
					'focus'  => '#0063aa',
				),
			),
			array(
				'id'       => 'link_color',
				'type'     => 'link_color',
				'title'    => __( 'Link color', 'lerm' ),
				'subtitle' => __( 'Color of all anchor tags in body content.', 'lerm' ),
				'active'   => true,
				'focus'    => true,
				'default'  => array(
					'color'  => '#0084ba',
					'hover'  => '#0063aa',
					'active' => '#0063aa',
					'focus'  => '#0063aa',
				),
				'output'   => array( 'a' ),
			),

			// ── Page background ───────────────────────────────────────────────
			array(
				'type'    => 'subheading',
				'content' => __( 'Page background', 'lerm' ),
			),
			array(
				'id'                   => 'body_background',
				'type'                 => 'background',
				'title'                => __( 'Body background', 'lerm' ),
				'subtitle'             => __( 'Page background color or image.', 'lerm' ),
				'background_image_url' => false,
				'default'              => array(
					'background-color' => '#ffffff',
				),
				'output'               => 'body',
			),
			array(
				'id'                   => 'content_background',
				'type'                 => 'background',
				'title'                => __( 'Card / content background', 'lerm' ),
				'subtitle'             => __( 'Background color of post and page content cards.', 'lerm' ),
				'background_image_url' => false,
				'default'              => array(
					'background-color' => '#ffffff',
				),
				'output'               => array( '.card' ),
			),

			// ── Header ────────────────────────────────────────────────────────
			array(
				'type'    => 'subheading',
				'content' => __( 'Header & navigation', 'lerm' ),
			),
			array(
				'id'          => 'header_bg_color',
				'type'        => 'color',
				'title'       => __( 'Header background', 'lerm' ),
				'subtitle'    => __( 'Background of the site header, dropdown menus, and off-canvas panel.', 'lerm' ),
				'default'     => '#ffffff',
				'output_mode' => 'background-color',
				'output'      => array( '.site-header', '.dropdown-menu', '.offcanvas' ),
			),
			array(
				'id'       => 'site_header_border',
				'type'     => 'border',
				'title'    => __( 'Header border', 'lerm' ),
				'subtitle' => __( 'Border drawn around the site header. Typically only the bottom is visible.', 'lerm' ),
				'default'  => array(
					'top'    => '0',
					'bottom' => '1',
					'left'   => '0',
					'right'  => '0',
					'style'  => 'solid',
					'color'  => '#82828244',
				),
				'output'   => '.site-header',
			),
			array(
				'id'               => 'navbar_link_color',
				'type'             => 'link_color',
				'title'            => __( 'Nav link color', 'lerm' ),
				'subtitle'         => __( 'Color of navigation links and dropdown items.', 'lerm' ),
				'output_important' => true,
				'default'          => array(
					'color' => '#828282',
					'hover' => '#0084ba',
				),
				'output'           => array( '.navbar-nav .nav-link', '.dropdown-item', '.navbar-btn' ),
			),
			array(
				'id'               => 'navbar_active_color',
				'type'             => 'color_pair',
				'title'            => __( 'Nav active item color', 'lerm' ),
				'subtitle'         => __( 'Text and background color of the currently active nav item.', 'lerm' ),
				'output_important' => true,
				'default'          => array(
					'color'            => '#0084ba',
					'background_color' => '#ffffff',
				),
				'output'           => array(
					'.navbar-nav .nav-link.active',
					'.navbar-nav .show > .nav-link',
					'.dropdown-item.active',
					'.dropdown-item:active',
				),
			),
			array(
				'id'          => 'navbar_item_padding',
				'type'        => 'spacing',
				'title'       => __( 'Nav item padding', 'lerm' ),
				'subtitle'    => __( 'Vertical padding applied to each navigation link.', 'lerm' ),
				'units'       => array( 'rem', 'em' ),
				'output_mode' => 'padding',
				'left'        => false,
				'right'       => false,
				'default'     => array(
					'top'    => '1.5',
					'bottom' => '1.5',
					'unit'   => 'rem',
				),
				'output'      => array( '.nav-link' ),
			),

			// ── Widget headers ────────────────────────────────────────────────
			array(
				'type'    => 'subheading',
				'content' => __( 'Widget headers', 'lerm' ),
			),
			array(
				'id'           => 'widget_header_color',
				'type'         => 'color_pair',
				'title'        => __( 'Widget header color', 'lerm' ),
				'subtitle'     => __( 'Text, background, and border color of widget / card header bars.', 'lerm' ),
				'border_color' => true,
				'default'      => array(
					'color'            => '',
					'background_color' => '',
					'border_color'     => '',
				),
				'output'       => array( '.card-header', '.navigation .current', '.comment-pager .current' ),
			),

			// ── Footer ────────────────────────────────────────────────────────
			array(
				'type'    => 'subheading',
				'content' => __( 'Footer', 'lerm' ),
			),
			array(
				'id'       => 'footer_widget_color',
				'type'     => 'color_pair',
				'title'    => __( 'Footer widgets area', 'lerm' ),
				'subtitle' => __( 'Text and background color of the footer widgets zone.', 'lerm' ),
				'default'  => array(
					'color'            => '#dddddd',
					'background_color' => '#333333',
				),
				'output'   => array( '.footer' ),
			),
			array(
				'id'       => 'footer_bar_color',
				'type'     => 'color_pair',
				'title'    => __( 'Footer bar (copyright strip)', 'lerm' ),
				'subtitle' => __( 'Text and background color of the bottom copyright bar.', 'lerm' ),
				'default'  => array(
					'color'            => '#dddddd',
					'background_color' => '#555555',
				),
				'output'   => array( '.colophon' ),
			),

			// ── Buttons ───────────────────────────────────────────────────────
			array(
				'type'    => 'subheading',
				'content' => __( 'Buttons', 'lerm' ),
			),
			array(
				'id'               => 'btn_primary',
				'type'             => 'color_pair',
				'title'            => __( 'Primary button (default state)', 'lerm' ),
				'subtitle'         => __( 'Text, background, and border color of primary buttons at rest.', 'lerm' ),
				'border_color'     => true,
				'output_important' => true,
				'default'          => array(
					'color'            => '#0084ba',
					'background_color' => '',
					'border_color'     => '#0084ba',
				),
				'output'           => array(
					'.btn-custom',
					'a[id="cancel-comment-reply-link"]',
				),
			),
			array(
				'id'               => 'btn_primary_hover',
				'type'             => 'color_pair',
				'title'            => __( 'Primary button (hover state)', 'lerm' ),
				'subtitle'         => __( 'Text and background color of primary buttons on hover.', 'lerm' ),
				'border_color'     => true,
				'output_important' => true,
				'default'          => array(
					'color'            => '#ffffff',
					'background_color' => '#0084ba',
					'border_color'     => '#0084ba',
				),
				'output'           => array(
					'.btn-custom:hover',
					'a[id="cancel-comment-reply-link"]:hover',
				),
			),
		),
	)
);

// ── Appearance › Typography ──────────────────────────────────────────────────
CSF::createSection(
	$prefix,
	array(
		'parent' => 'appearance',
		'title'  => __( 'Typography', 'lerm' ),
		'icon'   => 'fas fa-font',
		'fields' => array(

			array(
				'id'             => 'body_typography',
				'type'           => 'typography',
				'title'          => __( 'Body text', 'lerm' ),
				'subtitle'       => __( 'Base font applied to all body text.', 'lerm' ),
				'text_transform' => false,
				'unit'           => 'rem',
				'default'        => array(
					'font-weight' => '400',
					'color'       => '#5d6777',
					'font-size'   => '.875',
					'unit'        => 'rem',
				),
				'output'         => 'body',
			),
			array(
				'id'             => 'menu_typography',
				'type'           => 'typography',
				'title'          => __( 'Navigation font', 'lerm' ),
				'subtitle'       => __( 'Font applied to the top navigation bar.', 'lerm' ),
				'font_family'    => '',
				'text_transform' => false,
				'unit'           => 'rem',
				'default'        => array(
					'font-weight' => '400',
					'color'       => '#5d6777',
					'font-size'   => '.875',
					'unit'        => 'rem',
					'line-height' => '1.5',
				),
				'output'         => '.navbar',
			),
		),
	)
);

// ── Appearance › Custom CSS ──────────────────────────────────────────────────
CSF::createSection(
	$prefix,
	array(
		'parent' => 'appearance',
		'title'  => __( 'Custom CSS', 'lerm' ),
		'icon'   => 'fas fa-code',
		'fields' => array(
			array(
				'id'       => 'custom_css',
				'type'     => 'code_editor',
				'title'    => __( 'Custom CSS', 'lerm' ),
				'subtitle' => __( 'Additional CSS code that overrides the default theme styles.', 'lerm' ),
				'settings' => array(
					'theme' => 'mbo',
					'mode'  => 'css',
				),
			),

		),
	)
);

// ═════════════════════════════════════════════════════════════════════════════
// GROUP: Content
// ═════════════════════════════════════════════════════════════════════════════
CSF::createSection(
	$prefix,
	array(
		'id'    => 'content',
		'title' => __( 'Content', 'lerm' ),
		'icon'  => 'fas fa-file-alt',
	)
);

// ── Content › Blog ───────────────────────────────────────────────────────────
CSF::createSection(
	$prefix,
	array(
		'parent' => 'content',
		'title'  => __( 'Blog (archive)', 'lerm' ),
		'icon'   => 'fas fa-list',
		'fields' => array(

			array(
				'id'      => 'summary_or_full',
				'type'    => 'radio',
				'title'   => __( 'Post display mode', 'lerm' ),
				'desc'    => __( 'Full — show the entire post body on the archive page. Summary — show the excerpt only.', 'lerm' ),
				'options' => array(
					'content_full'    => __( 'Full content', 'lerm' ),
					'content_summary' => __( 'Excerpt only', 'lerm' ),
				),
				'default' => 'content_summary',
			),
			array(
				'id'      => 'excerpt_length',
				'type'    => 'slider',
				'title'   => __( 'Excerpt length (words)', 'lerm' ),
				'desc'    => __( 'Number of words shown in the auto-generated excerpt.', 'lerm' ),
				'min'     => 0,
				'max'     => 300,
				'step'    => 5,
				'default' => 95,
			),
			array(
				'id'      => 'show_thumbnail',
				'type'    => 'switcher',
				'title'   => __( 'Show post thumbnails', 'lerm' ),
				'default' => true,
			),
			array(
				'id'    => 'thumbnail_gallery',
				'type'  => 'gallery',
				'title' => __( 'Default thumbnail pool', 'lerm' ),
				'desc'  => __( 'Images used as fallback thumbnails for posts without a featured image.', 'lerm' ),
			),
			array(
				'id'      => 'cat_exclude',
				'type'    => 'checkbox',
				'title'   => __( 'Exclude categories', 'lerm' ),
				'desc'    => __( 'Posts in these categories will not appear on the blog index.', 'lerm' ),
				'options' => 'categories',
			),
			array(
				'id'      => 'load_more',
				'type'    => 'switcher',
				'title'   => __( 'Ajax "Load more" button', 'lerm' ),
				'desc'    => __( 'Replace standard pagination with an Ajax-powered load-more button.', 'lerm' ),
				'default' => false,
			),
			array(
				'id'      => 'loading_animate',
				'type'    => 'switcher',
				'title'   => __( 'Post card entrance animation', 'lerm' ),
				'default' => false,
			),
			array(
				'id'      => 'summary_meta',
				'type'    => 'sorter',
				'title'   => __( 'Archive post meta items', 'lerm' ),
				'desc'    => __( 'Drag items between Enabled and Disabled to control which meta fields appear on archive cards.', 'lerm' ),
				'default' => array(
					'enabled'  => array(
						'categories' => __( 'Category', 'lerm' ),
						'read'       => __( 'Reading time', 'lerm' ),
					),
					'disabled' => array(
						'author'       => __( 'Author', 'lerm' ),
						'responses'    => __( 'Comments', 'lerm' ),
						'publish_date' => __( 'Publish date', 'lerm' ),
						'format'       => __( 'Format', 'lerm' ),
					),
				),
			),
		),
	)
);

// ── Content › Post ───────────────────────────────────────────────────────────
CSF::createSection(
	$prefix,
	array(
		'parent' => 'content',
		'title'  => __( 'Post (single)', 'lerm' ),
		'icon'   => 'fas fa-newspaper',
		'fields' => array(

			array(
				'id'      => 'post_navigation',
				'type'    => 'switcher',
				'title'   => __( 'Previous / next post navigation', 'lerm' ),
				'default' => true,
			),
			array(
				'id'      => 'disable_pingback',
				'type'    => 'switcher',
				'title'   => __( 'Disable pingbacks', 'lerm' ),
				'default' => false,
			),
			array(
				'id'    => 'enable_code_highlight',
				'type'  => 'switcher',
				'title' => __( 'Syntax highlighting', 'lerm' ),
				'desc'  => __( 'Enable Prism.js code highlighting for code blocks inside posts.', 'lerm' ),
			),
			array(
				'id'    => 'author_bio',
				'type'  => 'switcher',
				'title' => __( 'Author bio box', 'lerm' ),
				'desc'  => __( 'Show the author biography card below the post content.', 'lerm' ),
			),

			array(
				'type'    => 'subheading',
				'content' => __( 'Related posts', 'lerm' ),
			),
			array(
				'id'    => 'related_posts',
				'type'  => 'switcher',
				'title' => __( 'Show related posts', 'lerm' ),
			),
			array(
				'id'         => 'related_number',
				'type'       => 'spinner',
				'dependency' => array( 'related_posts', '==', 'true' ),
				'title'      => __( 'Number of related posts', 'lerm' ),
				'default'    => 5,
			),

			array(
				'type'    => 'subheading',
				'content' => __( 'Post meta fields', 'lerm' ),
			),
			array(
				'id'      => 'single_top',
				'type'    => 'sorter',
				'title'   => __( 'Meta shown above content', 'lerm' ),
				'desc'    => __( 'Drag to control which meta items appear in the post header.', 'lerm' ),
				'default' => array(
					'enabled'  => array(
						'publish_date' => __( 'Publish date', 'lerm' ),
						'categories'   => __( 'Category', 'lerm' ),
						'read'         => __( 'Reading time', 'lerm' ),
						'responses'    => __( 'Comments', 'lerm' ),
					),
					'disabled' => array(
						'format' => __( 'Format', 'lerm' ),
						'author' => __( 'Author', 'lerm' ),
					),
				),
			),
			array(
				'id'      => 'single_bottom',
				'type'    => 'sorter',
				'title'   => __( 'Meta shown below content', 'lerm' ),
				'desc'    => __( 'Drag to control which meta items appear in the post footer.', 'lerm' ),
				'default' => array(
					'enabled'  => array(
						'publish_date' => __( 'Publish date', 'lerm' ),
						'categories'   => __( 'Category', 'lerm' ),
					),
					'disabled' => array(
						'format'    => __( 'Format', 'lerm' ),
						'author'    => __( 'Author', 'lerm' ),
						'read'      => __( 'Reading time', 'lerm' ),
						'responses' => __( 'Comments', 'lerm' ),
					),
				),
			),
		),
	)
);

// ── Content › Page ───────────────────────────────────────────────────────────
CSF::createSection(
	$prefix,
	array(
		'parent' => 'content',
		'title'  => __( 'Page', 'lerm' ),
		'icon'   => 'fas fa-file',
		'fields' => array(
			array(
				'id'      => 'search_filter',
				'type'    => 'switcher',
				'title'   => __( 'Exclude pages from search results', 'lerm' ),
				'desc'    => __( 'When enabled, static pages are hidden from the WordPress search results.', 'lerm' ),
				'default' => true,
			),
		),
	)
);

// ── Content › Sidebar ────────────────────────────────────────────────────────
CSF::createSection(
	$prefix,
	array(
		'parent' => 'content',
		'title'  => __( 'Sidebar', 'lerm' ),
		'icon'   => 'fas fa-columns',
		'fields' => array(

			array(
				'id'     => 'register_sidebars',
				'type'   => 'group',
				'title'  => __( 'Custom sidebars', 'lerm' ),
				'desc'   => __( 'Register additional sidebars. Once saved, they appear in Appearance → Widgets.', 'lerm' ),
				'fields' => array(
					array(
						'id'    => 'sidebar_title',
						'type'  => 'text',
						'title' => __( 'Sidebar name', 'lerm' ),
					),
				),
			),

			array(
				'type'    => 'subheading',
				'content' => __( 'Sidebar assignments', 'lerm' ),
			),
			array(
				'id'          => 'single_sidebar_select',
				'type'        => 'select',
				'title'       => __( 'Single post sidebar', 'lerm' ),
				'placeholder' => __( 'Select a sidebar', 'lerm' ),
				'options'     => 'sidebars',
			),
			array(
				'id'          => 'blog_sidebar_select',
				'type'        => 'select',
				'title'       => __( 'Blog archive sidebar', 'lerm' ),
				'placeholder' => __( 'Select a sidebar', 'lerm' ),
				'options'     => 'sidebars',
			),
			array(
				'id'          => 'front_page_sidebar',
				'type'        => 'select',
				'title'       => __( 'Front page sidebar', 'lerm' ),
				'placeholder' => __( 'Select a sidebar', 'lerm' ),
				'options'     => 'sidebars',
			),
			array(
				'id'          => 'page_sidebar',
				'type'        => 'select',
				'title'       => __( 'Static page sidebar', 'lerm' ),
				'placeholder' => __( 'Select a sidebar', 'lerm' ),
				'options'     => 'sidebars',
			),

			array(
				'type'    => 'subheading',
				'content' => __( 'Widget settings', 'lerm' ),
			),
			array(
				'id'      => 'comment_excerpt_length',
				'type'    => 'slider',
				'title'   => __( 'Latest comments widget excerpt length', 'lerm' ),
				'min'     => 0,
				'max'     => 300,
				'step'    => 5,
				'default' => 95,
			),
		),
	)
);

// ── Content › Carousel ───────────────────────────────────────────────────────
CSF::createSection(
	$prefix,
	array(
		'parent' => 'content',
		'title'  => __( 'Carousel / Slider', 'lerm' ),
		'icon'   => 'fas fa-images',
		'fields' => array(

			array(
				'id'    => 'slide_enable',
				'type'  => 'switcher',
				'title' => __( 'Enable homepage slider', 'lerm' ),
			),
			array(
				'id'         => 'slide_position',
				'type'       => 'image_select',
				'title'      => __( 'Slider position', 'lerm' ),
				'dependency' => array( 'slide_enable', '==', 'true' ),
				'options'    => array(
					'under_navbar'  => $imagepath . 'main_width.png',
					'under_primary' => $imagepath . 'primary_width.png',
					'full_width'    => $imagepath . 'full_width.png',
				),
			),
			array(
				'id'         => 'slide_indicators',
				'type'       => 'switcher',
				'dependency' => array( 'slide_enable', '==', 'true' ),
				'title'      => __( 'Show slide indicators (dots)', 'lerm' ),
			),
			array(
				'id'         => 'slide_control',
				'type'       => 'switcher',
				'dependency' => array( 'slide_enable', '==', 'true' ),
				'title'      => __( 'Show prev / next arrows', 'lerm' ),
			),
			array(
				'id'                     => 'slide_images',
				'type'                   => 'group',
				'dependency'             => array( 'slide_enable', '==', 'true' ),
				'title'                  => __( 'Slides', 'lerm' ),
				'button_title'           => __( 'Add slide', 'lerm' ),
				'accordion_title_number' => true,
				'accordion_title_auto'   => false,
				'fields'                 => array(
					array(
						'id'    => 'image',
						'type'  => 'media',
						'title' => __( 'Slide image', 'lerm' ),
						'url'   => false,
					),
					array(
						'id'    => 'title',
						'type'  => 'text',
						'title' => __( 'Caption title', 'lerm' ),
					),
					array(
						'id'    => 'url',
						'type'  => 'text',
						'title' => __( 'Link URL', 'lerm' ),
					),
					array(
						'id'    => 'description',
						'type'  => 'textarea',
						'title' => __( 'Caption text', 'lerm' ),
					),
				),
			),
		),
	)
);

// ═════════════════════════════════════════════════════════════════════════════
// GROUP: System
// ═════════════════════════════════════════════════════════════════════════════
CSF::createSection(
	$prefix,
	array(
		'id'    => 'system',
		'title' => __( 'System', 'lerm' ),
		'icon'  => 'fas fa-server',
	)
);

// ── System › Optimization ────────────────────────────────────────────────────
CSF::createSection(
	$prefix,
	array(
		'parent' => 'system',
		'title'  => __( 'Optimization', 'lerm' ),
		'icon'   => 'fas fa-rocket',
		'fields' => array(

			array(
				'type'    => 'subheading',
				'content' => __( 'Asset loading speed', 'lerm' ),
			),
			array(
				'id'      => 'super_admin',
				'type'    => 'switcher',
				'title'   => __( 'Backend asset acceleration', 'lerm' ),
				'desc'    => __( 'Load WordPress dashboard static assets from public mirrors to speed up the admin panel for users in China.', 'lerm' ),
				'default' => false,
			),
			array(
				'id'          => 'super_gravatar',
				'type'        => 'select',
				'title'       => __( 'Gravatar mirror', 'lerm' ),
				'subtitle'    => __( 'Replace the default Gravatar CDN with a faster mirror.', 'lerm' ),
				'placeholder' => __( 'Select a Gravatar mirror', 'lerm' ),
				'options'     => array(
					'disable'                           => __( 'Disabled (use Gravatar directly)', 'lerm' ),
					'https://cdn.sep.cc/avatar/'        => 'SEP',
					'https://cravatar.cn/avatar/'       => 'Cravatar',
					'https://sdn.geekzu.org/avatar/'    => 'Geekzu',
					'https://gravatar.loli.net/avatar/' => 'loli.net',
					'https://weavatar.com/avatar/'      => 'WeAvatar',
				),
				'default'     => 'disable',
			),
			array(
				'id'          => 'super_googleapis',
				'type'        => 'select',
				'title'       => __( 'Google Fonts mirror', 'lerm' ),
				'subtitle'    => __( 'Only needed when your theme loads Google Fonts.', 'lerm' ),
				'placeholder' => __( 'Select a Google Fonts mirror', 'lerm' ),
				'options'     => array(
					'disable' => __( 'Disabled', 'lerm' ),
					'geekzu'  => 'Geekzu',
					'loli'    => 'loli.net',
					'ustc'    => __( 'USTC (University of Science and Technology of China)', 'lerm' ),
				),
				'default'     => 'disable',
			),
			array(
				'id'      => 'lazyload',
				'type'    => 'switcher',
				'title'   => __( 'Lazy-load images', 'lerm' ),
				'desc'    => __( 'Defer off-screen image loading until the user scrolls to them.', 'lerm' ),
				'default' => false,
			),

			array(
				'type'    => 'subheading',
				'content' => __( 'WordPress head cleanup', 'lerm' ),
			),
			array(
				'id'       => 'super_optimize',
				'type'     => 'checkbox',
				'title'    => __( 'Remove unnecessary head tags', 'lerm' ),
				'subtitle' => __( 'Reduce page source size and avoid leaking information via wp_head.', 'lerm' ),
				'options'  => array(
					'rsd_link'                        => __( 'RSD link', 'lerm' ),
					'wlwmanifest_link'                => __( 'Windows Live Writer manifest', 'lerm' ),
					'wp_generator'                    => __( 'WordPress version meta tag', 'lerm' ),
					'remove_ver'                      => __( 'Version query strings on assets', 'lerm' ),
					'start_post_rel_link'             => __( 'Random post rel link', 'lerm' ),
					'index_rel_link'                  => __( 'Index rel link', 'lerm' ),
					'adjacent_posts_rel_link_wp_head' => __( 'Prev/next post rel links', 'lerm' ),
					'parent_post_rel_link'            => __( 'Parent post rel link', 'lerm' ),
					'wp_shortlink_wp_head'            => __( 'Shortlink tag', 'lerm' ),
					'feed_links'                      => __( 'RSS feed links', 'lerm' ),
					'disable_emojis'                  => __( 'Emoji scripts and styles', 'lerm' ),
					'disable_oembed'                  => __( 'oEmbed discovery links', 'lerm' ),
					'remove_rest_api'                 => __( 'REST API link tag', 'lerm' ),
					'disable_rest_api'                => __( 'REST API entirely', 'lerm' ),
					'remove_recent_comments_css'      => __( 'Recent comments widget inline CSS', 'lerm' ),
					'rel_canonical'                   => __( 'rel=canonical tag', 'lerm' ),
					'remove_global_styles_render_svg' => __( 'Global styles SVG filter block', 'lerm' ),
				),
			),
		),
	)
);

// ── System › Mailing ─────────────────────────────────────────────────────────
CSF::createSection(
	$prefix,
	array(
		'parent' => 'system',
		'title'  => __( 'Mailing', 'lerm' ),
		'icon'   => 'fas fa-envelope',
		'fields' => array(

			array(
				'id'    => 'email_notice',
				'type'  => 'switcher',
				'title' => __( 'Comment notification emails', 'lerm' ),
				'label' => __( 'Send an email to the author when a new comment is posted.', 'lerm' ),
			),
			array(
				'id'         => 'smtp_options',
				'type'       => 'fieldset',
				'title'      => __( 'SMTP settings', 'lerm' ),
				'dependency' => array( 'email_notice', '==', 'true' ),
				'fields'     => array(

					array(
						'type'    => 'subheading',
						'content' => __( 'Sender identity', 'lerm' ),
					),
					array(
						'id'    => 'from_email',
						'type'  => 'text',
						'title' => __( 'From address', 'lerm' ),
						'desc'  => __( 'Email address that outgoing messages are sent from.', 'lerm' ),
					),
					array(
						'id'    => 'from_name',
						'type'  => 'text',
						'title' => __( 'From name', 'lerm' ),
						'desc'  => __( 'Display name shown to recipients.', 'lerm' ),
					),

					array(
						'type'    => 'subheading',
						'content' => __( 'SMTP server', 'lerm' ),
					),
					array(
						'id'    => 'smtp_host',
						'type'  => 'text',
						'title' => __( 'SMTP host', 'lerm' ),
					),
					array(
						'id'    => 'smtp_port',
						'type'  => 'number',
						'title' => __( 'SMTP port', 'lerm' ),
					),
					array(
						'id'      => 'ssl_enable',
						'type'    => 'button_set',
						'title'   => __( 'Encryption', 'lerm' ),
						'desc'    => __( 'TLS is recommended for most providers. Only choose SSL if your provider requires it.', 'lerm' ),
						'options' => array(
							''    => __( 'None', 'lerm' ),
							'tls' => 'TLS',
							'ssl' => 'SSL',
						),
						'default' => 'tls',
					),
					array(
						'id'      => 'smtp_auth',
						'type'    => 'radio',
						'title'   => __( 'SMTP authentication', 'lerm' ),
						'options' => array(
							true  => __( 'Use username and password', 'lerm' ),
							false => __( 'No authentication', 'lerm' ),
						),
					),
					array(
						'id'    => 'username',
						'type'  => 'text',
						'title' => __( 'SMTP username', 'lerm' ),
					),
					array(
						'id'         => 'pswd',
						'type'       => 'text',
						'title'      => __( 'SMTP password', 'lerm' ),
						'attributes' => array( 'type' => 'password' ),
					),
				),
			),
		),
	)
);

// ── System › SEO ─────────────────────────────────────────────────────────────
CSF::createSection(
	$prefix,
	array(
		'parent' => 'system',
		'id'     => 'seo',
		'title'  => __( 'SEO', 'lerm' ),
		'icon'   => 'fas fa-search',
	)
);

CSF::createSection(
	$prefix,
	array(
		'parent' => 'seo',
		'title'  => __( 'Meta & titles', 'lerm' ),
		'icon'   => 'fas fa-tags',
		'fields' => array(

			array(
				'id'         => 'keywords',
				'type'       => 'text',
				'title'      => __( 'Default keywords', 'lerm' ),
				'subtitle'   => __( 'Comma-separated keywords written into the <meta name="keywords"> tag on the home page.', 'lerm' ),
				'attributes' => array(
					'style'       => 'width:100%',
					'placeholder' => __( 'e.g. WordPress, theme, blog', 'lerm' ),
				),
			),
			array(
				'id'         => 'description',
				'type'       => 'textarea',
				'title'      => __( 'Default description', 'lerm' ),
				'subtitle'   => __( 'Written into the <meta name="description"> tag on the home page.', 'lerm' ),
				'attributes' => array(
					'placeholder' => __( 'A brief description of your site.', 'lerm' ),
				),
			),
			array(
				'id'      => 'title_sep',
				'type'    => 'button_set',
				'title'   => __( 'Title separator', 'lerm' ),
				'desc'    => __( 'Character placed between the page title and the site name. Example: My Post | My Site.', 'lerm' ),
				'options' => array(
					'-'        => '-',
					'&ndash;'  => '&ndash;',
					'&mdash;'  => '&mdash;',
					':'        => ':',
					'&middot;' => '&middot;',
					'&bull;'   => '&bull;',
					'*'        => '*',
					'&#8902;'  => '&#8902;',
					'|'        => '|',
					'~'        => '~',
					'&laquo;'  => '&laquo;',
					'&raquo;'  => '&raquo;',
					'&gt;'     => '&gt;',
				),
				'default' => '|',
			),
			array(
				'id'          => 'title_structure',
				'type'        => 'select',
				'title'       => __( 'Home page title order', 'lerm' ),
				'subtitle'    => __( 'Drag items to reorder the parts of the <title> tag on the front page.', 'lerm' ),
				'chosen'      => true,
				'multiple'    => true,
				'sortable'    => true,
				'placeholder' => __( 'Select parts', 'lerm' ),
				'options'     => array(
					'title'      => __( 'Site title', 'lerm' ),
					'separator'  => __( 'Separator', 'lerm' ),
					'tagline'    => __( 'Site tagline', 'lerm' ),
					'post_title' => __( 'Post title', 'lerm' ),
					'page_title' => __( 'Page title', 'lerm' ),
				),
				'default'     => array( 'title', 'separator', 'tagline' ),
			),
			array(
				'id'          => 'post_title_structure',
				'type'        => 'select',
				'title'       => __( 'Post title order', 'lerm' ),
				'chosen'      => true,
				'multiple'    => true,
				'sortable'    => true,
				'placeholder' => __( 'Select parts', 'lerm' ),
				'options'     => array(
					'title'      => __( 'Site title', 'lerm' ),
					'separator'  => __( 'Separator', 'lerm' ),
					'tagline'    => __( 'Site tagline', 'lerm' ),
					'post_title' => __( 'Post title', 'lerm' ),
					'page_title' => __( 'Page title', 'lerm' ),
				),
				'default'     => array( 'post_title', 'separator', 'title' ),
			),
			array(
				'id'          => 'page_title_structure',
				'type'        => 'select',
				'title'       => __( 'Page title order', 'lerm' ),
				'chosen'      => true,
				'multiple'    => true,
				'sortable'    => true,
				'placeholder' => __( 'Select parts', 'lerm' ),
				'options'     => array(
					'title'      => __( 'Site title', 'lerm' ),
					'separator'  => __( 'Separator', 'lerm' ),
					'tagline'    => __( 'Site tagline', 'lerm' ),
					'post_title' => __( 'Post title', 'lerm' ),
					'page_title' => __( 'Page title', 'lerm' ),
				),
				'default'     => array( 'page_title', 'separator', 'title' ),
			),
			array(
				'id'      => 'html_slug',
				'type'    => 'switcher',
				'title'   => __( 'Append .html to page URLs', 'lerm' ),
				'desc'    => __( 'After changing this, re-save the Permalink settings (Settings → Permalinks).', 'lerm' ),
				'default' => false,
			),
		),
	)
);

CSF::createSection(
	$prefix,
	array(
		'parent' => 'seo',
		'title'  => __( 'Sitemap', 'lerm' ),
		'icon'   => 'fas fa-sitemap',
		'fields' => array(

			array(
				'id'      => 'sitemap_enable',
				'type'    => 'switcher',
				'title'   => __( 'Enable WordPress sitemap', 'lerm' ),
				'default' => true,
			),
			array(
				'id'         => 'exclude_post_types',
				'type'       => 'checkbox',
				'dependency' => array( 'sitemap_enable', '==', 'true' ),
				'inline'     => true,
				'title'      => __( 'Exclude content types', 'lerm' ),
				'desc'       => __( 'Selected types will be omitted from the sitemap.', 'lerm' ),
				'options'    => array(
					'page'        => __( 'Pages', 'lerm' ),
					'post'        => __( 'Posts', 'lerm' ),
					'category'    => __( 'Categories', 'lerm' ),
					'post_tag'    => __( 'Tags', 'lerm' ),
					'post_format' => __( 'Formats', 'lerm' ),
					'users'       => __( 'Authors', 'lerm' ),
				),
			),
			array(
				'id'         => 'exclude_categories',
				'type'       => 'checkbox',
				'dependency' => array(
					array( 'sitemap_enable', '==', 'true' ),
					array( 'exclude_post_types', '!=', 'category' ),
				),
				'title'      => __( 'Exclude specific categories', 'lerm' ),
				'options'    => 'categories',
			),
			array(
				'id'         => 'exclude_tags',
				'type'       => 'checkbox',
				'dependency' => array(
					array( 'sitemap_enable', '==', 'true' ),
					array( 'exclude_post_types', '!=', 'post_tag' ),
				),
				'title'      => __( 'Exclude specific tags', 'lerm' ),
				'options'    => 'tags',
			),
			array(
				'id'         => 'exclude_page',
				'type'       => 'checkbox',
				'dependency' => array(
					array( 'sitemap_enable', '==', 'true' ),
					array( 'exclude_post_types', '!=', 'page' ),
				),
				'title'      => __( 'Exclude specific pages', 'lerm' ),
				'options'    => 'pages',
			),
			array(
				'id'         => 'exclude_post',
				'type'       => 'checkbox',
				'dependency' => array(
					array( 'sitemap_enable', '==', 'true' ),
					array( 'exclude_post_types', '!=', 'post' ),
				),
				'title'      => __( 'Exclude specific posts', 'lerm' ),
				'options'    => 'posts',
			),
		),
	)
);

CSF::createSection(
	$prefix,
	array(
		'parent' => 'seo',
		'title'  => __( 'Breadcrumb', 'lerm' ),
		'icon'   => 'fas fa-bread-slice',
		'fields' => array(

			array(
				'id'      => 'breadcrumb_container',
				'type'    => 'button_set',
				'title'   => __( 'Wrapper element', 'lerm' ),
				'desc'    => __( 'HTML element that wraps the breadcrumb trail.', 'lerm' ),
				'options' => array(
					'nav' => 'nav',
					'div' => 'div',
				),
				'default' => 'nav',
			),
			array(
				'id'      => 'breadcrumb_list_tag',
				'type'    => 'button_set',
				'title'   => __( 'List element', 'lerm' ),
				'options' => array(
					'ol'  => 'ol',
					'ul'  => 'ul',
					'div' => 'div',
				),
				'default' => 'ol',
			),
			array(
				'id'      => 'breadcrumb_item_tag',
				'type'    => 'button_set',
				'title'   => __( 'Item element', 'lerm' ),
				'options' => array(
					'li'   => 'li',
					'span' => 'span',
				),
				'default' => 'li',
			),
			array(
				'id'      => 'breadcrumb_separator',
				'type'    => 'text',
				'title'   => __( 'Separator', 'lerm' ),
				'desc'    => __( 'Character placed between breadcrumb items, e.g. / or >.', 'lerm' ),
				'default' => '/',
			),
			array(
				'id'    => 'breadcrumb_before',
				'type'  => 'text',
				'title' => __( 'Before text', 'lerm' ),
				'desc'  => __( 'String output immediately before the breadcrumb trail.', 'lerm' ),
			),
			array(
				'id'    => 'breadcrumb_after',
				'type'  => 'text',
				'title' => __( 'After text', 'lerm' ),
				'desc'  => __( 'String output immediately after the breadcrumb trail.', 'lerm' ),
			),
			array(
				'id'      => 'breadcrumb_front_show',
				'type'    => 'switcher',
				'title'   => __( 'Show on front page', 'lerm' ),
				'default' => false,
			),
			array(
				'id'      => 'breadcrumb_show_title',
				'type'    => 'switcher',
				'title'   => __( 'Include current page title', 'lerm' ),
				'desc'    => __( 'Show the current page/post title as the last (non-linked) item.', 'lerm' ),
				'default' => true,
			),
		),
	)
);

CSF::createSection(
	$prefix,
	array(
		'parent' => 'seo',
		'title'  => __( 'Analytics & submission', 'lerm' ),
		'icon'   => 'fas fa-chart-bar',
		'fields' => array(

			array(
				'id'       => 'baidu_tongji',
				'type'     => 'code_editor',
				'title'    => __( 'Baidu Analytics code', 'lerm' ),
				'subtitle' => __( 'Paste the full Baidu Tongji script tag here. Output before </head>.', 'lerm' ),
				'sanitize' => false,
				'settings' => array( 'mode' => 'htmlmixed' ),
			),
			array(
				'id'      => 'baidu_submit',
				'type'    => 'switcher',
				'title'   => __( 'Baidu URL auto-submission', 'lerm' ),
				'desc'    => __( 'Automatically push new post URLs to Baidu Search.', 'lerm' ),
				'default' => false,
			),
			array(
				'id'         => 'submit_url',
				'type'       => 'text',
				'dependency' => array( 'baidu_submit', '==', 'true' ),
				'title'      => __( 'Push API endpoint URL', 'lerm' ),
			),
			array(
				'id'         => 'submit_token',
				'type'       => 'text',
				'dependency' => array( 'baidu_submit', '==', 'true' ),
				'title'      => __( 'Push API token', 'lerm' ),
			),
		),
	)
);

// ── System › CDN ─────────────────────────────────────────────────────────────
CSF::createSection(
	$prefix,
	array(
		'parent' => 'system',
		'title'  => __( 'CDN', 'lerm' ),
		'icon'   => 'fas fa-globe',
		'fields' => array(

			array(
				'id'      => 'enable_cdn',
				'type'    => 'switcher',
				'title'   => __( 'Enable CDN URL rewriting', 'lerm' ),
				'desc'    => __( 'Rewrites static asset URLs to point to your CDN origin.', 'lerm' ),
				'default' => false,
			),
			array(
				'id'         => 'new_url',
				'type'       => 'text',
				'dependency' => array( 'enable_cdn', '==', 'true' ),
				'title'      => __( 'CDN URL', 'lerm' ),
				'subtitle'   => __( 'Your CDN origin without a trailing slash. e.g. https://cdn.example.com', 'lerm' ),
				'attributes' => array(
					'placeholder' => get_bloginfo( 'url', 'display' ),
				),
				'default'    => get_bloginfo( 'url', 'display' ),
			),
			array(
				'id'         => 'off_new_url',
				'type'       => 'text',
				'dependency' => array( 'enable_cdn', '==', 'true' ),
				'title'      => __( 'Relative URL prefix', 'lerm' ),
				'subtitle'   => __( 'Prefix for relative URLs such as /wp-content/…. Usually the same as the CDN URL.', 'lerm' ),
				'attributes' => array(
					'placeholder' => get_bloginfo( 'url', 'display' ),
				),
				'default'    => get_bloginfo( 'url', 'display' ),
			),
			array(
				'id'         => 'include_dir',
				'type'       => 'text',
				'dependency' => array( 'enable_cdn', '==', 'true' ),
				'title'      => __( 'Included directories', 'lerm' ),
				'subtitle'   => __( 'Comma-separated directory prefixes to rewrite. Default: wp-content, wp-includes.', 'lerm' ),
				'default'    => 'wp-content, wp-includes',
			),
			array(
				'id'         => 'exclude_if_substring',
				'type'       => 'text',
				'dependency' => array( 'enable_cdn', '==', 'true' ),
				'title'      => __( 'Excluded substrings', 'lerm' ),
				'subtitle'   => __( 'Skip rewriting when the URL contains any of these substrings. Default: .php.', 'lerm' ),
				'default'    => '.php',
			),
		),
	)
);

// ═════════════════════════════════════════════════════════════════════════════
// GROUP: Community
// ═════════════════════════════════════════════════════════════════════════════
CSF::createSection(
	$prefix,
	array(
		'id'    => 'community',
		'title' => __( 'Community', 'lerm' ),
		'icon'  => 'fas fa-users',
	)
);

// ── Community › Social ───────────────────────────────────────────────────────
CSF::createSection(
	$prefix,
	array(
		'parent' => 'community',
		'title'  => __( 'Social', 'lerm' ),
		'icon'   => 'fas fa-share-alt',
		'fields' => array(

			array(
				'id'                    => 'qrcode_image',
				'type'                  => 'background',
				'title'                 => __( 'WeChat QR code', 'lerm' ),
				'subtitle'              => __( 'Shown when the user hovers over the WeChat social link.', 'lerm' ),
				'background_color'      => false,
				'background_origin'     => false,
				'background_repeat'     => false,
				'background_size'       => false,
				'background_position'   => false,
				'background_attachment' => false,
				'background_image_url'  => false,
				'output'                => 'a[rel~="weixin"]::after',
			),
			array(
				'id'       => 'donate_qrcode',
				'type'     => 'media',
				'title'    => __( 'Donation QR code', 'lerm' ),
				'subtitle' => __( 'Payment QR code displayed on the donation widget.', 'lerm' ),
				'url'      => false,
			),
			array(
				'id'      => 'social_share',
				'type'    => 'checkbox',
				'title'   => __( 'Share buttons', 'lerm' ),
				'desc'    => __( 'Select which social platforms appear as share buttons on single posts.', 'lerm' ),
				'inline'  => true,
				'options' => array(
					'weibo'       => '<i class="fa fa-weibo"></i> Weibo',
					'wechat'      => '<i class="fa fa-wechat"></i> WeChat',
					'qq'          => '<i class="fa fa-qq"></i> QQ',
					'qzone'       => '<i class="fa fa-qzone"></i> Qzone',
					'douban'      => '<i class="fa fa-douban"></i> Douban',
					'linkedin'    => '<i class="fa fa-linkedin"></i> LinkedIn',
					'facebook'    => '<i class="fa fa-facebook"></i> Facebook',
					'twitter'     => '<i class="fa fa-twitter"></i> X / Twitter',
					'google_plus' => '<i class="fa fa-google-plus"></i> Google+',
				),
			),
		),
	)
);

// ── Community › Advertisement ────────────────────────────────────────────────
CSF::createSection(
	$prefix,
	array(
		'parent' => 'community',
		'title'  => __( 'Advertisement', 'lerm' ),
		'icon'   => 'fas fa-ad',
		'fields' => array(

			array(
				'id'      => 'ad_switcher',
				'type'    => 'switcher',
				'title'   => __( 'Show advertisements', 'lerm' ),
				'default' => false,
			),
			array(
				'id'            => 'ad1',
				'type'          => 'wp_editor',
				'dependency'    => array( 'ad_switcher', '==', 'true' ),
				'title'         => __( 'Homepage ad code', 'lerm' ),
				'subtitle'      => __( 'Paste your ad embed code here. Shown on the home page.', 'lerm' ),
				'height'        => '80px',
				'media_buttons' => false,
				'quicktags'     => false,
				'tinymce'       => false,
			),
		),
	)
);

// ── Community › User Center ──────────────────────────────────────────────────
CSF::createSection(
	$prefix,
	array(
		'parent' => 'community',
		'title'  => __( 'User center', 'lerm' ),
		'icon'   => 'fas fa-user-circle',
		'fields' => array(

			array(
				'type'    => 'subheading',
				'content' => __( 'Frontend login', 'lerm' ),
			),
			array(
				'id'    => 'frontend_login',
				'type'  => 'switcher',
				'title' => __( 'Enable frontend login', 'lerm' ),
			),
			array(
				'id'          => 'frontend_login_page',
				'dependency'  => array( 'frontend_login', '==', 'true' ),
				'type'        => 'select',
				'title'       => __( 'Login page', 'lerm' ),
				'subtitle'    => __( 'The page that displays the frontend login form.', 'lerm' ),
				'placeholder' => __( 'Select a page', 'lerm' ),
				'options'     => 'pages',
				'query_args'  => array( 'posts_per_page' => -1 ),
			),
			array(
				'id'         => 'menu_login_item',
				'dependency' => array( 'frontend_login', '==', 'true' ),
				'type'       => 'switcher',
				'title'      => __( 'Add login / avatar item to navigation', 'lerm' ),
			),
			array(
				'id'         => 'login_redirect_url',
				'type'       => 'switcher',
				'title'      => __( 'Redirect to homepage after login', 'lerm' ),
				'dependency' => array( 'frontend_login', '==', 'true' ),
			),
			array(
				'id'         => 'logout_redirect_url',
				'type'       => 'switcher',
				'title'      => __( 'Redirect to homepage after logout', 'lerm' ),
				'dependency' => array( 'frontend_login', '==', 'true' ),
			),

			array(
				'type'    => 'subheading',
				'content' => __( 'Registration', 'lerm' ),
			),
			array(
				'id'         => 'frontend_regist',
				'dependency' => array( 'frontend_login', '==', 'true' ),
				'type'       => 'switcher',
				'title'      => __( 'Allow frontend registration', 'lerm' ),
			),
			array(
				'id'         => 'users_can_register',
				'type'       => 'switcher',
				'title'      => __( 'Open registration to everyone', 'lerm' ),
				'dependency' => array( 'frontend_regist', '==', 'true' ),
			),
			array(
				'id'         => 'default_role',
				'type'       => 'select',
				'title'      => __( 'Default user role', 'lerm' ),
				'subtitle'   => __( 'Role assigned to newly registered users.', 'lerm' ),
				'options'    => wp_roles()->get_names(),
				'dependency' => array( 'frontend_regist', '==', 'true' ),
			),
			array(
				'id'         => 'default_login_page',
				'type'       => 'switcher',
				'title'      => __( 'Disable default WordPress login page (wp-login.php)', 'lerm' ),
				'dependency' => array( 'frontend_regist', '==', 'true' ),
			),

			array(
				'type'    => 'subheading',
				'content' => __( 'Profile & account features', 'lerm' ),
			),
			array(
				'id'         => 'front_user_center',
				'dependency' => array( 'frontend_login', '==', 'true' ),
				'type'       => 'switcher',
				'title'      => __( 'Enable frontend profile / account center', 'lerm' ),
			),
			array(
				'id'          => 'frontend_user_center_page',
				'dependency'  => array( 'front_user_center', '==', 'true' ),
				'type'        => 'select',
				'title'       => __( 'Account center page', 'lerm' ),
				'placeholder' => __( 'Select a page', 'lerm' ),
				'options'     => 'pages',
				'query_args'  => array( 'posts_per_page' => -1 ),
			),
			array(
				'id'         => 'frontend_profile',
				'type'       => 'switcher',
				'title'      => __( 'Enable profile editing', 'lerm' ),
				'dependency' => array( 'frontend_regist', '==', 'true' ),
			),
			array(
				'id'         => 'favorite_post',
				'type'       => 'switcher',
				'title'      => __( 'Enable post favorites', 'lerm' ),
				'dependency' => array( 'frontend_regist', '==', 'true' ),
			),
			array(
				'id'         => 'my_posts',
				'type'       => 'switcher',
				'title'      => __( 'Enable "My Posts" section', 'lerm' ),
				'dependency' => array( 'frontend_regist', '==', 'true' ),
			),
		),
	)
);

// ═════════════════════════════════════════════════════════════════════════════
// Tools › Backup
// ═════════════════════════════════════════════════════════════════════════════
CSF::createSection(
	$prefix,
	array(
		'title'  => __( 'Backup', 'lerm' ),
		'icon'   => 'fas fa-shield-alt',
		'fields' => array(
			array(
				'type' => 'backup',
			),
		),
	)
);
