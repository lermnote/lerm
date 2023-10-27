<?php

if ( class_exists( 'CSF' ) ) {
	// Set a unique slug-like ID
	$prefix = 'lerm_theme_options';

	// If using image radio buttons, define a directory path
	$imagepath = LERM_URI . 'assets/img/';

	// Create options
	CSF::createOptions(
		$prefix,
		array(
			'menu_title'      => __( 'Theme Options', 'lerm' ),
			'menu_slug'       => 'lerm_options',
			'framework_title' => __( 'Theme Options <small>by O\'conner</small>', 'lerm' ),
			'footer_credit'   => __( 'Thank you for creating with <a href="https://www.hanost.com/" target="_blank">Lerm</a>', 'lerm' ),
		)
	);

	// ----------------------------------------
	// Color Schemes
	// ----------------------------------------
	CSF::createSection(
		$prefix,
		array(
			'id'    => 'general_options',
			'title' => __( 'General options', 'lerm' ),
			'icon'  => 'fab fa-first-order',
		)
	);
	CSF::createSection(
		$prefix,
		array(
			'parent' => 'general_options',
			'title'  => __( 'Basic', 'lerm' ),
			'icon'   => 'fa fa-tachometer',
			'fields' => array(
				array(
					'type'    => 'heading',
					'content' => __( 'Basic setting', 'lerm' ),
				),
				array(
					'id'        => 'large_logo',
					'type'      => 'media',
					'add_title' => 'Add Logo',
					'title'     => __( 'Logo large', 'lerm' ),
					'label'     => __( 'Logo Blink', 'lerm' ),
					'library'   => 'image',
					'url'       => false,
				),
				array(
					'id'        => 'mobile_logo',
					'type'      => 'media',
					'add_title' => 'Add Logo',
					'title'     => __( 'Logo mobile', 'lerm' ),
					'label'     => __( 'Logo mobile', 'lerm' ),
					'url'       => false,
				),
				array(
					'id'         => 'blogname',
					'type'       => 'text',
					'title'      => __( 'Website Title', 'lerm' ),
					'attributes' => array(
						'placeholder' => get_bloginfo( 'name', 'display' ),
					),
				),
				array(
					'id'         => 'blogdesc',
					'type'       => 'text',
					'title'      => __( 'Website description', 'lerm' ),
					'attributes' => array(
						'placeholder' => get_bloginfo( 'description', 'display' ),
					),
				),
				array(
					'id'      => 'narbar_align',
					'type'    => 'button_set',
					'title'   => __( 'Navbar align', 'lerm' ),
					'options' => array(
						'justify-content-md-start'  => 'Left',
						'justify-content-md-center' => 'Center',
						'justify-content-md-end'    => 'Right',
					),
					'default' => 'justify-content-md-end',
				),
				array(
					'id'      => 'narbar_search',
					'type'    => 'switcher',
					'title'   => __( 'Navbar search form', 'lerm' ),
					'options' => array(
						'true'  => __( 'Show navbar search form', 'lerm' ),
						'false' => __( 'Do NOT show navbar search form', 'lerm' ),
					),
					'default' => false,
				),

				array(
					'id'         => 'icp_num',
					'type'       => 'text',
					'title'      => __( 'Website ICP Number', 'lerm' ),
					'attributes' => array(
						'placeholder' => get_option( 'zh_cn_l10n_icp_num' ),
					),
				),
				array(
					'id'          => 'footer_menus',
					'type'        => 'select',
					'title'       => 'Select a menus show on footer',
					'placeholder' => 'Select a menu',
					'options'     => 'menus',
				),
				array(
					'id'            => 'copyright',
					'type'          => 'wp_editor',
					'title'         => __( 'Othor Information', 'lerm' ),
					'after'         => __( 'This other information will appear on the footer.', 'lerm' ),
					'height'        => '80px',
					'media_buttons' => false,
					'quicktags'     => false,
					'tinymce'       => false,
				),
			),
		)
	);
	CSF::createSection(
		$prefix,
		array(
			'parent' => 'general_options',
			'title'  => __( 'Color', 'lerm' ),
			'icon'   => 'fa fa-tachometer',
			'fields' => array(
				array(
					'type'    => 'heading',
					'content' => __( 'Color and style', 'lerm' ),
				),

				array(
					'id'      => 'main_color',
					'type'    => 'link_color',
					'title'   => __( '_Primary Color', 'lerm' ),
					'default' => array(
						'color'  => '#0084ba',
						'hover'  => '#0063aa',
						'active' => '#0063aa',
						'focus'  => '#0063aa',
					),
				),
				array(
					'id'      => 'link_color',
					'type'    => 'link_color',
					'title'   => __( 'Links Color', 'lerm' ),
					'active'  => true,
					'focus'   => true,
					'default' => array(
						'color'  => '#0084ba',
						'hover'  => '#0063aa',
						'active' => '#0063aa',
						'focus'  => '#0063aa',
					),
					'output'  => array( 'a' ),
				),
				array(
					'id'                   => 'body_background',
					'type'                 => 'background',
					'title'                => __( 'Body Background', 'lerm' ),
					'subtitle'             => __( 'Default body background color', 'lerm' ),
					'background_image_url' => false,
					'default'              => array(
						'background-color'      => '#fff',
						'background-position'   => '',
						'background-repeat'     => '',
						'background-attachment' => '',
						'background-size'       => '',
					),

					'output'               => 'body',
				),
				array(
					'id'                   => 'content_background',
					'type'                 => 'background',
					'title'                => __( 'Content Background', 'lerm' ),
					'subtitle'             => __( 'single content background color', 'lerm' ),
					'background_image_url' => false,
					'default'              => array(
						'background-color'      => '#fff',
						'background-position'   => '',
						'background-repeat'     => '',
						'background-attachment' => '',
						'background-size'       => '',
					),
					'output'               => array(
						'.card',
					),
				),

				array(
					'type'    => 'heading',
					'content' => __( 'General setting', 'lerm' ),
				),
				array(
					'id'          => 'header_bg_color',
					'type'        => 'color',
					'title'       => __( 'Site header background color', 'lerm' ),
					'subtitle'    => __( 'site header and navbar background color', 'lerm' ),
					'default'     => '#fff',
					'output_mode' => 'background-color',
					'output'      => array(
						'.site-header',
						'.dropdown-menu',
						'.offcanvas',
					),
				),
				array(
					'id'             => 'body_typography',
					'type'           => 'typography',
					'title'          => 'Site Typography',
					'preview'        => 'always',
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
					'id'           => 'title_wrap',
					'type'         => 'color_pair',
					'title'        => __( 'Widget header color', 'lerm' ),
					'border_color' => true,
					'default'      => array(
						'color'            => '',
						'background_color' => '',
						'border_color'     => '',
					),
					'output'       => array( '.card-header, .navigation .current', '.comment-pager .current' ),
				),
				array(
					'type'    => 'heading',
					'content' => __( 'Header color schemes', 'lerm' ),
				),

				array(
					'id'             => 'menu_typography',
					'type'           => 'typography',
					'title'          => 'Menu typography',
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
				array(
					'id'               => 'navbar_link_color',
					'type'             => 'link_color',
					'title'            => __( 'Menu links color', 'lerm' ),
					'output_important' => true,
					'default'          => array(
						'color' => '#828282',
						'hover' => '#0084ba',
					),
					'output'           => array( '.navbar-nav .nav-link', '.dropdown-item', '.navbar-btn' ),
				),
				array(
					'id'               => 'header_color',
					'type'             => 'color_pair',
					'title'            => __( 'Menu items active color', 'lerm' ),
					'output_important' => true,
					'default'          => array(
						'color'            => '#0084ba',
						'background_color' => '#fff',
					),
					'output'           => array( '.navbar-nav .nav-link.active', '.navbar-nav .show > .nav-link', '.dropdown-item.active', '.dropdown-item:active' ),
				),
				array(
					'id'          => 'navbar_item_spacing',
					'type'        => 'spacing',
					'title'       => 'Menu items padding',
					'units'       => array( 'rem', 'em' ),
					'output_mode' => 'padding',
					'default'     => array(
						'top'    => '1.5',
						'bottom' => '1.5',
						'unit'   => 'rem',
					),
					'left'        => false,
					'right'       => false,
					'output'      => array( '.nav-link' ),
				),
				array(
					'id'      => 'site_header_border',
					'type'    => 'border',
					'title'   => 'Site header border',
					'default' => array(
						'top'    => '0',
						'bottom' => '1',
						'left'   => '0',
						'right'  => '0',
						'style'  => 'solid',
						'color'  => '#82828244',
					),
					'output'  => '.site-header',
				),
				array(
					'type'    => 'heading',
					'content' => __( 'Footer color schemes', 'lerm' ),
				),
				array(
					'id'      => 'colophon_style',
					'type'    => 'color_pair',
					'title'   => __( 'Footer copyright color', 'lerm' ),
					'default' => array(
						'color'            => '#ddd',
						'background_color' => '#555',
					),
					'output'  => array( '.colophon' ),
				),
				array(
					'id'      => 'footer_widget_color',
					'type'    => 'color_pair',
					'title'   => __( 'Footer widgets color', 'lerm' ),
					'default' => array(
						'color'            => '#ddd',
						'background_color' => '#333',
					),
					'output'  => array( '.footer' ),
				),
				array(
					'type'    => 'heading',
					'content' => __( 'Buttons Color', 'lerm' ),
				),

				array(
					'id'               => 'custom_button',
					'type'             => 'color_pair',
					'border_color'     => true,
					'output_important' => true,
					'default'          => array(
						'color'            => '#0084ba',
						'background_color' => '',
						'border_color'     => '#0084ba',
					),
					'title'            => __( 'Primary Buttons Color', 'lerm' ),
					'output'           => array(
						'.btn-custom',
						// '.comment-reply-link',
						'a[id="cancel-comment-reply-link"]',
						// '.tag-cloud-link',
					),
				),
				array(
					'id'               => 'custom_button_hover',
					'type'             => 'color_pair',
					'border_color'     => true,
					'output_important' => true,
					'default'          => array(
						'color'            => '#fff',
						'background_color' => '#0084ba',
					),
					'title'            => __( 'Primary Buttons Hover Color', 'lerm' ),
					'output'           => array(
						'.btn-custom:hover',
						'a[id="cancel-comment-reply-link"]:hover',
						// '.tag-cloud-link:hover',
					),
				),
				array(
					'id'           => 'like_button',
					'type'         => 'color_pair',
					'title'        => __( 'Like Button Color', 'lerm' ),
					'border_color' => true,
					'default'      => array(
						'color'            => '#fff',
						'background_color' => '#c82333',
					),
					'output'       => '.like-button,.like-button:hover',
				),
				array(
					'type'    => 'heading',
					'content' => __( 'Custom Style', 'lerm' ),
				),
				array(
					'id'       => 'custom_css',
					'type'     => 'code_editor',
					'title'    => __( 'Custom CSS', 'lerm' ),
					'before'   => '<p class="csf-text-muted"> Add your own CSS code here to customize the look and layout of the site</p>',
					'settings' => array(
						'theme' => 'mbo',
						'mode'  => 'css',
					),
				),
			),
		)
	);
	CSF::createSection(
		$prefix,
		array(
			'parent' => 'general_options',
			'title'  => __( 'Layout', 'lerm' ),
			'icon'   => 'fa fa-tachometer',
			'fields' => array(
				array(
					'type'    => 'heading',
					'content' => __( 'General layout', 'lerm' ),
				),
				array(
					'id'       => 'layout_style',
					'type'     => 'button_set',
					'title'    => __( 'Layout style', 'lerm' ),
					'subtitle' => __( 'Select site layout style', 'lerm' ),
					'options'  => array(
						'wide-layout'     => __( 'Wide', 'lerm' ),
						'boxed-layout'    => __( 'Boxed', 'lerm' ),
						'separate-layout' => __( 'Separate', 'lerm' ),
					),
					'default'  => 'separate-layout',
				),
				array(
					'id'       => 'global_layout',
					'type'     => 'image_select',
					'title'    => __( 'Main Layout', 'lerm' ),
					'subtitle' => __( 'Default site layout——sidebar select and position determin', 'lerm' ),
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
					'title'      => __( 'Sidebar Affix', 'lerm' ),
					'desc'       => __( 'Sidebar Affix', 'lerm' ),
				),
				array(
					'id'          => 'boxed_width',
					'type'        => 'spinner',
					'title'       => __( 'Boxed Width (px)', 'lerm' ),
					'subtitle'    => __( 'Main site container width', 'lerm' ),
					'dependency'  => array( 'layout_style', '==', 'boxed-layout' ),
					'min'         => 0,
					'max'         => 4096,
					'step'        => 1,
					'unit'        => 'px',
					'output'      => array( '.boxed-layout #page' ),
					'output_mode' => 'width',
					'default'     => 1140,
				),
				array(
					'id'          => 'site_width',
					'type'        => 'spinner',
					'title'       => __( 'Main Container Width (px)', 'lerm' ),
					'subtitle'    => __( 'Main site container width|default:1140|max:4096', 'lerm' ),
					'dependency'  => array( 'layout_style', '!=', 'boxed-layout' ),
					'min'         => 0,
					'max'         => 4096,
					'step'        => 1,
					'output'      => array(
						'.wide-layout .container',
						'.separate-layout .container',
					),
					'output_mode' => 'width',
				),

				array(
					'id'          => 'content_width',
					'type'        => 'spinner',
					'title'       => __( 'Content Width (%)', 'lerm' ),
					'subtitle'    => __( 'Content area width|default:66.6666666667', 'lerm' ),
					'min'         => 0,
					'max'         => 100,
					'step'        => 1,
					'output_mode' => 'width',
					'default'     => 66.6666666667,
				),
				array(
					'id'          => 'sidebar_width',
					'type'        => 'spinner',
					'title'       => __( 'Sidebar Width (%)', 'lerm' ),
					'subtitle'    => __( 'Sidebar area width|default:33.3333333333', 'lerm' ),
					'min'         => 0,
					'max'         => 100,
					'step'        => 1,
					'output_mode' => 'width',
					'default'     => 33.3333333333,
				),
				array(
					'id'          => 'outside_bg_color',
					'type'        => 'color',
					'title'       => __( 'Outside background', 'lerm' ),
					'subtitle'    => __( 'Outside background color', 'lerm' ),
					'default'     => '#ebebeb',
					'output_mode' => 'background-color',
					'output'      => array(
						'.boxed-layout',
					),
				),
				array(
					'id'          => 'inner_bg_color',
					'type'        => 'color',
					'title'       => __( 'Inner background', 'lerm' ),
					'subtitle'    => __( 'Inner background color', 'lerm' ),
					'default'     => '#fff',
					'output_mode' => 'background-color',
					'output'      => array(
						'.boxed-layout #page',
					),
				),
			),
		)
	);
	// ----------------------------------------
	// an  option section for acceleration and optimization
	// ----------------------------------------
	CSF::createSection(
		$prefix,
		array(
			'id'    => 'optimization',
			'title' => __( 'Optimization', 'lerm' ),
			'icon'  => 'fa fa-rocket',
		)
	);
	CSF::createSection(
		$prefix,
		array(
			'parent' => 'optimization',
			'title'  => __( 'Acceleration', 'lerm' ),
			'icon'   => 'fa fa-star',
			'fields' => array(
				array(
					'type'    => 'heading',
					'content' => __( 'WordPress Acceleration', 'lerm' ),
				),
				array(
					'id'      => 'super_admin',
					'type'    => 'switcher',
					'title'   => __( 'Backend Acceleration', 'lerm' ),
					'desc'    => __( '将WordPress核心所依赖的静态文件切换为公共资源，此选项极大的加快管理后台访问速度 ', 'lerm' ),
					'default' => false,
				),
				array(
					'id'          => 'super_gravatar',
					'type'        => 'select',
					'title'       => __( 'Gravatar Acceleration', 'lerm' ),
					'placeholder' => __( 'Select a gravatar acceleration services', 'lerm' ),
					'desc'        => __( '提高Gravatar头像的加载速度', 'lerm' ),
					'options'     => array(
						'disable'                        => __( 'Disable', 'lerm' ),
						'https://cdn.sep.cc/avatar/'     => __( 'Sep 加速服务', 'lerm' ),
						'https://cravatar.cn/avatar/'    => __( 'Cravatar 加速服务', 'lerm' ),
						'https://sdn.geekzu.org/avatar/' => __( 'Geekzu 加速服务', 'lerm' ),
						'https://gravatar.loli.net/avatar/' => __( 'Loli 加速服务', 'lerm' ),
						'https://weavatar.com/avatar/'      => __( 'WeAvatar 加速服务', 'lerm' ),
					),
					'default'     => 'disable',
				),
				array(
					'id'          => 'super_googleapis',
					'type'        => 'select',
					'title'       => __( 'Googlefont acceleration', 'lerm' ),
					'placeholder' => __( 'Select a goolge services acceleration', 'lerm' ),
					'desc'        => __( 'Please enable this option only if Googlefonts are included to avoid unnecessary performance loss,', 'lerm' ),
					'options'     => array(
						'disable' => __( 'Disable', 'lerm' ),
						'geekzu'  => __( 'Geekzu 加速服务', 'lerm' ),
						'loli'    => __( 'Loli 加速服务', 'lerm' ),
						'ustc'    => __( 'USTC 加速服务', 'lerm' ),
					),
					'default'     => 'disable',
				),

			),
		)
	);
	CSF::createSection(
		$prefix,
		array(
			'parent' => 'optimization',
			'title'  => __( 'Optimize', 'lerm' ),
			'icon'   => 'fa fa-star',
			'fields' => array(
				array(
					'type'    => 'heading',
					'content' => __( 'WordPress Optimize', 'lerm' ),
				),
				array(
					'id'       => 'super_optimize',
					'type'     => 'checkbox',
					'title'    => __( 'WordPress optimization', 'lerm' ),
					'subtitle' => __( 'Cleanup wp_head unneccessary and unsecure codes.', 'lerm' ),
					'options'  => array(
						'rsd_link'                        => __( 'Remove RSD link', 'lerm' ),
						'wlwmanifest_link'                => __( 'Remove Windows live writer', 'lerm' ),
						'wp_generator'                    => __( 'Remove WordPress version', 'lerm' ),
						'remove_ver'                      => __( 'Remove version in styles and scripts', 'lerm' ),
						'start_post_rel_link'             => __( 'Remove random post link', 'lerm' ),
						'index_rel_link'                  => __( 'Remove link to index page', 'lerm' ),
						'adjacent_posts_rel_link_wp_head' => __( 'Remove the next and previous post links', 'lerm' ),
						'parent_post_rel_link'            => __( 'Remove parent post link', 'lerm' ),
						'wp_shortlink_wp_head'            => __( 'Remove wp head shortlink', 'lerm' ),
						'feed_links'                      => __( 'Remove rss feed links', 'lerm' ),
						'disable_emojis'                  => __( 'Remove WordPress emojis', 'lerm' ),
						'disable_oembed'                  => __( 'Disable WordPress embed', 'lerm' ),
						'remove_rest_api'                 => __( 'Remove rest api links in head', 'lerm' ),
						'disable_rest_api'                => __( 'Disable rest api', 'lerm' ),
						'remove_recent_comments_css'      => __( 'Remove recent comments widget styles', 'lerm' ),
						'rel_canonical'                   => __( 'Remove rel canonical', 'lerm' ),
						'remove_global_styles_render_svg' => __( 'Remove wp global styles render svg filters', 'lerm' ),
					),
				),
			),
		)
	);
	// ----------------------------------------
	// an  option section for Email -- done
	// ----------------------------------------
	CSF::createSection(
		$prefix,
		array(
			'title'  => __( 'Mailing', 'lerm' ),
			'icon'   => 'fa fa-envelope-o',
			'fields' => array(
				array(
					'id'    => 'email_notice',
					'type'  => 'switcher',
					'title' => __( 'Email Notification', 'lerm' ),
					'label' => __( 'Enable notification email for comments', 'lerm' ),
				),
				array(
					'id'         => 'smtp_options',
					'type'       => 'fieldset',
					'dependency' => array( 'email_notice', '==', 'true' ),
					'fields'     => array(
						array(
							'type'    => 'heading',
							'content' => __( 'From Mail', 'lerm' ),
						),
						array(
							'id'    => 'from_email',
							'type'  => 'text',
							'title' => __( 'From Email', 'lerm' ),
						),
						array(
							'id'    => 'from_name',
							'type'  => 'text',
							'title' => __( 'From Name', 'lerm' ),
						),
						array(
							'type'    => 'heading',
							'content' => __( 'SMTP Options', 'lerm' ),
						),
						array(
							'id'    => 'smtp_host',
							'type'  => 'text',
							'title' => __( 'SMTP Host', 'lerm' ),
						),
						array(
							'id'    => 'smtp_port',
							'type'  => 'text',
							'title' => __( 'SMTP Port', 'lerm' ),
						),
						array(
							'id'    => 'ssl_enable',
							'type'  => 'switcher',
							'title' => __( 'SSL Encryption', 'lerm' ),
						),
						array(
							'id'      => 'smtp_auth',
							'type'    => 'radio',
							'title'   => __( 'SMTP Authentication', 'lerm' ),
							'label'   => __( 'If Your Email open the smtp authentication', 'lerm' ),
							'options' => array(
								'true'  => __( 'Use SMTP authentication', 'lerm' ),
								'false' => __( 'Do NOT use SMTP authentication', 'lerm' ),
							),
						),
						array(
							'id'    => 'username',
							'type'  => 'text',
							'title' => __( 'Username', 'lerm' ),
						),
						array(
							'id'         => 'pswd',
							'type'       => 'text',
							'title'      => __( 'Password', 'lerm' ),
							'attributes' => array(
								'type' => 'password',
							),
						),
					),
				),
			),
		)
	);
		// ----------------------------------------
	// an  option section for seo-- done
	// ----------------------------------------
	CSF::createSection(
		$prefix,
		array(
			'id'    => 'seo_optimize',
			'title' => __( 'SEO optimize', 'lerm' ),
			'icon'  => 'fas fa-gavel',
		)
	);
	CSF::createSection(
		$prefix,
		array(
			'parent' => 'seo_optimize',
			'icon'   => 'fa fa-flask',
			'title'  => __( 'TDK', 'lerm' ),
			'fields' => array(
				array(
					'id'         => 'keywords',
					'type'       => 'text',
					'title'      => __( 'Keywords', 'lerm' ),
					'desc'       => __( 'Separate with commas', 'lerm' ),
					'attributes' => array(
						'style'       => 'width: 100%',
						'placeholder' => __( 'eg: WordPress,Theme...', 'lerm' ),
					),
				),
				array(
					'id'         => 'description',
					'type'       => 'textarea',
					'title'      => __( 'Description', 'lerm' ),
					'attributes' => array(
						'placeholder' => __( 'Description', 'lerm' ),
					),
				),
				array(
					'id'      => 'title_sep',
					'type'    => 'button_set',
					'title'   => __( 'Title separator', 'lerm' ),
					'desc'    => __( 'Select a title separator of your Website. Default "|", eg: My Website|Just another WordPress site', 'lerm' ),
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
					'default' => '-',
				),
				array(
					'id'      => 'html_slug',
					'type'    => 'switcher',
					'title'   => __( 'HTML Slug', 'lerm' ),
					'desc'    => __( 'Shows .html slug for pages (please RE-SAVE the permalink options after changed slug)', 'lerm' ),
					'default' => false,
				),
				array(
					'id'      => 'lazyload',
					'type'    => 'switcher',
					'title'   => __( 'Images Lazyload', 'lerm' ),
					'desc'    => __( 'Images Lazyload', 'lerm' ),
					'default' => false,
				),
				array(
					'id'      => 'baidu_submit',
					'type'    => 'switcher',
					'title'   => __( 'Baidu Submit', 'lerm' ),
					'default' => false,
				),
				array(
					'id'         => 'submit_url',
					'type'       => 'text',
					'dependency' => array( 'baidu_submit', '==', 'true' ),
					'title'      => __( 'Submit URL', 'lerm' ),
				),
				array(
					'id'         => 'submit_token',
					'type'       => 'text',
					'dependency' => array( 'baidu_submit', '==', 'true' ),
					'title'      => __( 'Submit Token', 'lerm' ),
				),
				array(
					'id'       => 'baidu_tongji',
					'type'     => 'code_editor',
					'title'    => __( 'Baidu Tongji', 'lerm' ),
					'after'    => __( 'Baidu tongji code in before head tag', 'lerm' ),
					'sanitize' => false,
				),

				array(
					'id'          => 'title_structure',
					'type'        => 'select',
					'title'       => __( 'Home title structure', 'lerm' ),
					'chosen'      => true,
					'multiple'    => true,
					'sortable'    => true,
					'placeholder' => 'Select an option',
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
					'title'       => __( 'Post title structure', 'lerm' ),
					'chosen'      => true,
					'multiple'    => true,
					'sortable'    => true,
					'placeholder' => 'Select an option',
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
					'id'          => 'page_title_structure',
					'type'        => 'select',
					'title'       => __( 'Page title structure', 'lerm' ),
					'chosen'      => true,
					'multiple'    => true,
					'sortable'    => true,
					'placeholder' => 'Select an option',
					'options'     => array(
						'title'      => __( 'Site title', 'lerm' ),
						'separator'  => __( 'Separator', 'lerm' ),
						'tagline'    => __( 'Site tagline', 'lerm' ),
						'post_title' => __( 'Post title', 'lerm' ),
						'page_title' => __( 'Page title', 'lerm' ),
					),
					'default'     => array( 'page_title', 'separator', 'title' ),
				),

			),
		)
	);
	CSF::createSection(
		$prefix,
		array(
			'parent' => 'seo_optimize',
			'icon'   => 'fa fa-flask',
			'title'  => __( 'Sitemap', 'lerm' ),
			'fields' => array(
				array(
					'type'    => 'heading',
					'content' => __( 'WordPress Sitemap', 'lerm' ),
				),
				array(
					'id'      => 'sitemap_enable',
					'type'    => 'switcher',
					'title'   => __( 'Use WordPress sitemap', 'lerm' ),
					'desc'    => __( 'Use WordPress sitemap', 'lerm' ),
					'default' => true,
				),
				array(
					'id'         => 'exclude_post_types',
					'type'       => 'checkbox',
					'dependency' => array( 'sitemap_enable', '==', 'true' ),
					'inline'     => true,
					'title'      => __( 'Post Types', 'lerm' ),
					'desc'       => __( 'Here you can select post types that you do not want to include in your sitemap.', 'lerm' ),
					'options'    => array(
						'page'        => __( 'Pages', 'lerm' ),
						'post'        => __( 'Posts', 'lerm' ),
						'category'    => __( 'Categories', 'lerm' ),
						'post_tag'    => __( 'Tags', 'lerm' ),
						'post_format' => __( 'Formats', 'lerm' ),
						'users'       => __( 'Users', 'lerm' ),
					),
				),
				array(
					'id'         => 'exclude_categories',
					'type'       => 'checkbox',
					'dependency' => array(
						array( 'sitemap_enable', '==', 'true' ),
						array( 'exclude_post_types', '!=', 'category' ),
					),
					'title'      => __( 'Categories', 'lerm' ),
					'desc'       => __( 'Here you can select post types that you do not want to include in your sitemap.', 'lerm' ),
					'options'    => 'categories',
				),
				array(
					'id'         => 'exclude_tags',
					'type'       => 'checkbox',
					'dependency' => array(
						array( 'sitemap_enable', '==', 'true' ),
						array( 'exclude_post_types', '!=', 'post_tag' ),
					),
					'title'      => __( 'Tags', 'lerm' ),
					'desc'       => __( 'Here you can select post types that you do not want to include in your sitemap.', 'lerm' ),
					'options'    => 'tags',
				),
				array(
					'id'         => 'exclude_users',
					'type'       => 'checkbox',
					'dependency' => array(
						array( 'sitemap_enable', '==', 'true' ),
						array( 'exclude_post_types', '!=', 'user' ),
					),
					'title'      => __( 'Users', 'lerm' ),
					'desc'       => __( 'Here you can select post types that you do not want to include in your sitemap.', 'lerm' ),
					'options'    => 'users',
				),
				array(
					'id'         => 'exclude_page',
					'type'       => 'checkbox',
					'dependency' => array(
						array( 'sitemap_enable', '==', 'true' ),
						array( 'exclude_post_types', '!=', 'page' ),
					),
					'title'      => __( 'Pages', 'lerm' ),
					'desc'       => __( 'Here you can select pages that you do not want to include in your sitemap.', 'lerm' ),
					'options'    => 'pages',
				),
				array(
					'id'         => 'exclude_post',
					'type'       => 'checkbox',
					'dependency' => array(
						array( 'sitemap_enable', '==', 'true' ),
						array( 'exclude_post_types', '!=', 'post' ),
					),
					'title'      => __( 'Posts', 'lerm' ),
					'desc'       => __( 'Here you can select posts that you do not want to include in your sitemap.', 'lerm' ),
					'options'    => 'posts',
				),
			),
		)
	);
	CSF::createSection(
		$prefix,
		array(
			'parent' => 'seo_optimize',
			'icon'   => 'fa fa-flask',
			'title'  => __( 'Breadcrumb', 'lerm' ),
			'fields' => array(
				array(
					'id'      => 'breadcrumb_container',
					'type'    => 'button_set',
					'title'   => __( 'Container', 'lerm' ),
					'desc'    => __( 'Container HTML element. nav|div', 'lerm' ),
					'options' => array(
						'nav' => __( 'nav', 'lerm' ),
						'div' => __( 'div', 'lerm' ),
					),
					'default' => 'nav',
				),
				array(
					'id'    => 'breadcrumb_before',
					'type'  => 'text',
					'title' => __( 'Before', 'lerm' ),
					'desc'  => __( 'String to output before breadcrumb menu.', 'lerm' ),
				),
				array(
					'id'    => 'breadcrumb_after',
					'type'  => 'text',
					'title' => __( 'After', 'lerm' ),
					'desc'  => __( 'String to output after breadcrumb menu.', 'lerm' ),
				),
				array(
					'id'      => 'breadcrumb_list_tag',
					'type'    => 'button_set',
					'title'   => __( 'List tag', 'lerm' ),
					'desc'    => __( 'The HTML tag to use for the list wrapper.', 'lerm' ),
					'options' => array(
						'ol'  => __( 'ol', 'lerm' ),
						'ul'  => __( 'ul', 'lerm' ),
						'div' => __( 'div', 'lerm' ),
					),
					'default' => 'ol',
				),
				array(
					'id'      => 'breadcrumb_item_tag',
					'type'    => 'button_set',
					'title'   => __( 'Item tag', 'lerm' ),
					'desc'    => __( 'The HTML tag to use for the list wrapper.', 'lerm' ),
					'options' => array(
						'li'   => __( 'li', 'lerm' ),
						'span' => __( 'span', 'lerm' ),
					),
					'default' => 'li',
				),
				array(
					'id'      => 'breadcrumb_separator',
					'type'    => 'text',
					'title'   => __( 'After', 'lerm' ),
					'desc'    => __( 'Breadcrumb link separator, ex: \'»\',, Alt Code', 'lerm' ),
					'default' => '/',
				),
				array(
					'id'      => 'breadcrumb_front_show',
					'type'    => 'switcher',
					'title'   => __( 'Show on front', 'lerm' ),
					'desc'    => __( 'Whether to show when `is_front_page()`.', 'lerm' ),
					'default' => false,
				),
				array(
					'id'      => 'breadcrumb_show_title',
					'type'    => 'switcher',
					'title'   => __( 'Show title', 'lerm' ),
					'desc'    => __( 'Whether to show the title (last item) in the trail.', 'lerm' ),
					'default' => true,
				),
			),
		)
	);
	// ----------------------------------------
	// a opttion panel for Blog -- done
	// ----------------------------------------
	CSF::createSection(
		$prefix,
		array(
			'icon'   => 'fa fa-desktop',
			'title'  => __( 'Blog Optimize', 'lerm' ),
			'fields' => array(
				array(
					'id'      => 'summary_or_full',
					'type'    => 'radio',
					'title'   => __( 'Show post content on page', 'lerm' ),
					'label'   => __( 'show post content on blog page', 'lerm' ),
					'options' => array(
						'content_full'    => __( 'Full', 'lerm' ),
						'content_summary' => __( 'Summary', 'lerm' ),
					),
					'default' => 'content_summary',
				),
				array(
					'id'      => 'cat-exclude',
					'type'    => 'checkbox',
					'title'   => 'Exclude Category on blog page',
					'options' => 'categories',
				),
				array(
					'id'      => 'show_thumbnail',
					'type'    => 'switcher',
					'title'   => __( 'Show thumbnail on posts list', 'lerm' ),
					'label'   => __( 'Switcher on to show posts thumbnial', 'lerm' ),
					'default' => true,
				),
				array(
					'id'    => 'thumbnail_gallery',
					'type'  => 'gallery',
					'title' => __( 'Post Thumbnail Gallery', 'lerm' ),
				),
				array(
					'id'      => 'load_more',
					'type'    => 'switcher',
					'title'   => __( 'Ajax load more post', 'lerm' ),
					'label'   => __( 'Switcher on to Ajax load more post, off to show pagenation', 'lerm' ),
					'default' => false,
				),
				array(
					'id'      => 'loading-animate',
					'type'    => 'switcher',
					'title'   => __( 'Loading animate', 'lerm' ),
					'label'   => __( 'Switcher on to Enable posts loading animate', 'lerm' ),
					'default' => false,
				),

				array(
					'id'      => 'excerpt_length',
					'type'    => 'slider',
					'title'   => __( 'Post Excerpt length on posts list page', 'lerm' ),
					'min'     => 0,
					'max'     => 300,
					'step'    => 5,
					'default' => 95,
				),
				array(
					'id'      => 'summary_meta',
					'type'    => 'sorter',
					'title'   => __( 'Summary Meta', 'lerm' ),
					'default' => array(
						'enabled'  => array(
							'categories' => 'Category',
							'read'       => 'Read',
						),
						'disabled' => array(
							'author'       => 'Author',
							'comment'      => 'Comment',
							'publish_date' => 'Publish Date',
							'format'       => 'Format',
						),
					),
				),
			),
		)
	);
	// ----------------------------------------
	// a opttion panel for Page -- done
	// ----------------------------------------
	CSF::createSection(
		$prefix,
		array(
			'icon'   => 'fa fa-tasks',
			'title'  => __( 'Page Optimize', 'lerm' ),
			'fields' => array(
				array(
					'id'      => 'search_filter',
					'type'    => 'switcher',
					'title'   => __( 'Search Filter', 'lerm' ),
					'label'   => __( 'Search results exclude all pages', 'lerm' ),
					'default' => true,
				),
			),
		)
	);
	// ----------------------------------------
	// a opttion panel for Post -- done
	// ----------------------------------------
	CSF::createSection(
		$prefix,
		array(
			'icon'   => 'fa fa-newspaper-o',
			'title'  => __( 'Post Optimize', 'lerm' ),
			'fields' => array(
				array(
					'type'    => 'heading',
					'content' => __( 'Single page optimize', 'lerm' ),
				),

				array(
					'id'      => 'disable_pingback',
					'type'    => 'switcher',
					'title'   => __( 'Disable pingback', 'lerm' ),
					'default' => false,
				),
				array(
					'id'      => 'post_navigation',
					'type'    => 'switcher',
					'title'   => __( 'Posts Navigation', 'lerm' ),
					'default' => true,
				),
				array(
					'id'    => 'enable_code_highlight',
					'type'  => 'switcher',
					'title' => __( 'Enable Code Cighlight', 'lerm' ),
					'label' => __( 'If your posts contain some code, then enable it', 'lerm' ),
				),
				array(
					'id'    => 'author_bio',
					'type'  => 'switcher',
					'title' => __( 'Author Biography', 'lerm' ),
					'label' => __( 'Show author biography in sidebar (if have sidebar)', 'lerm' ),
				),
				array(
					'type'    => 'subheading',
					'content' => __( 'Related posts', 'lerm' ),
				),
				array(
					'id'    => 'related_posts',
					'type'  => 'switcher',
					'title' => __( 'Related Posts', 'lerm' ),
					'label' => __( 'Show related posts under single bottom', 'lerm' ),
				),
				array(
					'id'         => 'raleted_number',
					'type'       => 'spinner',
					'dependency' => array( 'related_posts', '==', 'true' ),
					'title'      => __( 'Related posts number', 'lerm' ),
					'default'    => 5,
				),
				array(
					'id'      => 'single_top',
					'type'    => 'sorter',
					'title'   => __( 'Post meta top', 'lerm' ),
					'desc'    => __( 'Show post meta on single top', 'lerm' ),
					'default' => array(
						'enabled'  => array(
							'publish_date' => 'Publish Date',
							'categories'   => 'Category',
							'read'         => 'Read',
							'comment'      => 'Comment',
						),
						'disabled' => array(
							'format' => 'Format',
							'author' => 'Author',
						),
					),
				),
				array(
					'id'      => 'single_bottom',
					'type'    => 'sorter',
					'title'   => __( 'Post meta bottom', 'lerm' ),
					'desc'    => __( 'Show post meta on single bottom', 'lerm' ),
					'default' => array(
						'enabled'  => array(
							'publish_date' => 'Publish Date',
							'categories'   => 'Category',
						),
						'disabled' => array(
							'format'  => 'Format',
							'author'  => 'Author',
							'read'    => 'Read',
							'comment' => 'Comment',
						),
					),
				),
			),
		)
	);
	// ----------------------------------------
	// a opttion panel for sidebar -- done
	// ----------------------------------------
	CSF::createSection(
		$prefix,
		array(
			'icon'   => 'fa fa-weixin',
			'title'  => __( 'Sidebar', 'lerm' ),
			'fields' => array(
				array(
					'type'    => 'heading',
					'content' => __( 'Basic Setting', 'lerm' ),
				),
				array(
					'id'     => 'register_sidebars',
					'type'   => 'group',
					'title'  => __( 'Register Sidebars', 'lerm' ),
					'fields' => array(
						array(
							'id'    => 'sidebar_title',
							'type'  => 'text',
							'title' => __( 'Sidebar Title', 'lerm' ),
						),
					),
				),
				array(
					'id'          => 'single_sidebar_select',
					'type'        => 'select',
					'title'       => __( 'Single sidebar', 'lerm' ),
					'placeholder' => 'Select a sidebar',
					'options'     => 'sidebars',
				),
				array(
					'id'          => 'blog_sidebar_select',
					'type'        => 'select',
					'title'       => __( 'Blog sidebar', 'lerm' ),
					'placeholder' => 'Select a sidebar',
					'options'     => 'sidebars',
				),
				array(
					'id'          => 'front_page_sidebar',
					'type'        => 'select',
					'title'       => __( 'Front page sidebar', 'lerm' ),
					'placeholder' => 'Select a sidebar',
					'options'     => 'sidebars',
				),
				array(
					'id'          => 'page_sidebar',
					'type'        => 'select',
					'title'       => __( 'Page sidebar', 'lerm' ),
					'placeholder' => 'Select a sidebar',
					'options'     => 'sidebars',
				),
				array(
					'type'    => 'heading',
					'content' => __( 'Widget Setting', 'lerm' ),
				),
				array(
					'id'      => 'comment_excerpt_length',
					'type'    => 'slider',
					'title'   => __( 'Latest comment widget excerpt length', 'lerm' ),
					'min'     => 0,
					'max'     => 300,
					'step'    => 5,
					'default' => 95,
				),
			),
		)
	);
	// ----------------------------------------
	// a opttion panel for Social -- done
	// ----------------------------------------
	CSF::createSection(
		$prefix,
		array(
			'icon'   => 'fa fa-weibo',
			'title'  => __( 'Social', 'lerm' ),
			'fields' => array(
				array(
					'id'                    => 'qrcode_image',
					'type'                  => 'background',
					'title'                 => __( 'Weixin Qrcode', 'lerm' ),
					'background_color'      => false,
					'background_origin'     => false,
					'background_repeat'     => false,
					'background_size'       => false,
					'background_position'   => false,
					'background_attachment' => false,
					'background_image_url'  => false,
					'label'                 => __( 'Show Qrcode mouse hover', 'lerm' ),
					'output'                => 'a[rel="weixin"]::after',
				),
				array(
					'id'    => 'donate-qrcode',
					'type'  => 'media',
					'title' => __( 'Donate Qrcode', 'lerm' ),
					'url'   => false,
					'label' => __( 'Show related posts  in sidebar (if have sidebar)', 'lerm' ),
				),
				array(
					'id'      => 'social_share',
					'type'    => 'checkbox',
					'title'   => 'Social share icons',
					'inline'  => true,
					'options' => array(
						'weibo'       => '<i class="fa fa-weibo"></i>',
						'wechat'      => '<i class="fa fa-wechat"></i>',
						'qq'          => '<i class="fa fa-qq"></i>',
						'qzone'       => '<i class="fa fa-qzone"></i>',
						'douban'      => '<i class="fa fa-douban"></i>',
						'linkedin'    => '<i class="fa fa-linkedin"></i>',
						'facebook'    => '<i class="fa fa-facebook"></i>',
						'twitter'     => '<i class="fa fa-twitter"></i>',
						'google_plus' => '<i class="fa fa-google-plus"></i>',
					),
				),
			),
		)
	);
	// ----------------------------------------
	// a opttion panel for Carousel -- done
	// ----------------------------------------
	CSF::createSection(
		$prefix,
		array(
			'icon'   => 'fa fa-camera-retro',
			'title'  => __( 'Carousel Options', 'lerm' ),
			'fields' => array(
				array(
					'id'    => 'slide_enable',
					'type'  => 'switcher',
					'title' => __( 'Enable Slides', 'lerm' ),
					'label' => __( 'Enable to show slides on frontpage', 'lerm' ),
				),
				array(
					'id'         => 'slide_position',
					'type'       => 'image_select',
					'title'      => __( 'Slides Position', 'lerm' ),
					'label'      => __( 'Select Slides Position On frontpage', 'lerm' ),
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
					'title'      => __( 'Slides indicators', 'lerm' ),
					'desc'       => __( 'Enable to show slides indicators', 'lerm' ),
				),
				array(
					'id'         => 'slide_control',
					'type'       => 'switcher',
					'dependency' => array( 'slide_enable', '==', 'true' ),
					'title'      => __( 'Slides control arrows', 'lerm' ),
					'desc'       => __( 'Enable to show slides control arrows', 'lerm' ),
				),
				array(
					'id'                     => 'slide_images',
					'type'                   => 'group',
					'dependency'             => array( 'slide_enable', '==', 'true' ),
					'title'                  => __( 'Slides', 'lerm' ),
					'button_title'           => __( 'Add Slide', 'lerm' ),
					'accordion_title'        => __( 'New Slide', 'lerm' ),
					'accordion_title_number' => true,
					'accordion_title_auto'   => false,
					'fields'                 => array(
						array(
							'id'    => 'image',
							'type'  => 'media',
							'title' => __( 'Slide', 'lerm' ),
							'url'   => false,
						),
						array(
							'id'    => 'title',
							'type'  => 'text',
							'title' => __( 'Title', 'lerm' ),
						),
						array(
							'id'    => 'url',
							'type'  => 'text',
							'title' => __( 'URL', 'lerm' ),
						),
						array(
							'id'    => 'description',
							'type'  => 'textarea',
							'title' => __( 'Description', 'lerm' ),
						),
					),
				),

			),
		)
	);
	// ----------------------------------------
	// a opttion panel for other -- done
	// ----------------------------------------
	CSF::createSection(
		$prefix,
		array(
			'icon'   => 'fa fa-ad',
			'title'  => __( 'AD', 'lerm' ),
			'fields' => array(
				array(
					'id'      => 'ad_switcher',
					'type'    => 'switcher',
					'title'   => __( 'Show ads on Website', 'lerm' ),
					'default' => false,
				),
				array(
					'id'            => 'ad1',
					'type'          => 'wp_editor',
					'dependency'    => array( 'ad_switcher', '==', 'true' ),
					'title'         => __( 'AD codes', 'lerm' ),
					'after'         => __( 'This ad code on home.', 'lerm' ),
					'height'        => '80px',
					'media_buttons' => false,
					'quicktags'     => false,
					'tinymce'       => false,
				),
			),
		)
	);
	// ----------------------------------------
	// a opttion panel for other -- done
	// ----------------------------------------
	CSF::createSection(
		$prefix,
		array(
			'icon'   => 'fa fa-star',
			'title'  => __( 'Simple CDN', 'lerm' ),
			'fields' => array(
				array(
					'id'      => 'enable_cdn',
					'type'    => 'switcher',
					'title'   => __( 'Enable CDN', 'lerm' ),
					'desc'    => __( 'Enable CDN suport', 'lerm' ),
					'default' => false,
				),
				array(
					'id'         => 'site_url',
					'type'       => 'text',
					'dependency' => array( 'enable_cdn', '==', 'true' ),
					'title'      => __( 'Site URL', 'lerm' ),
					'desc'       => __( 'The new URL to be used in place of site url for rewriting. No trailing <code>/</code> please. E.g. <code>http://cdn.lerm.local/wp-includes/js/jquery/jquery-migrate.js</code>', 'lerm' ),
					'attributes' => array(
						'placeholder' => get_bloginfo( 'url', 'display' ),
					),
					'default'    => get_bloginfo( 'url', 'display' ),
				),
				array(
					'id'         => 'off_site_url',
					'type'       => 'text',
					'dependency' => array( 'enable_cdn', '==', 'true' ),
					'title'      => __( 'Off-site URL', 'lerm' ),
					'desc'       => __( 'Check this if you want to have links like <code><em>/</em>wp-content/xyz.png</code> rewritten - i.e. without your blog\'s domain as prefix.', 'lerm' ),
					'attributes' => array(
						'placeholder' => get_bloginfo( 'url', 'display' ),
					),
					'default'    => get_bloginfo( 'url', 'display' ),
				),
				array(
					'id'         => 'include_dir',
					'type'       => 'text',
					'dependency' => array( 'enable_cdn', '==', 'true' ),
					'title'      => __( 'Include DIR', 'lerm' ),
					'desc'       => __( 'Directories to include in static file matching. Use a comma as the delimiter. Default is <code>wp-content, wp-includes</code>, which will be enforced if this field is left empty.', 'lerm' ),
					'default'    => 'wp-content, wp-includes',
				),
				array(
					'id'         => 'exclude_if_substring',
					'type'       => 'text',
					'dependency' => array( 'enable_cdn', '==', 'true' ),
					'title'      => __( 'Exclude if substring', 'lerm' ),
					'desc'       => __( 'Excludes something from being rewritten if one of the above strings is found in the match. Use a comma as the delimiter. E.g. <code>.php, .flv, .do</code>, always include <code>.php</code> (default).', 'lerm' ),
					'default'    => '.php',
				),
			),
		)
	);
	// ----------------------------------------
	// Field: backup
	// ----------------------------------------
	CSF::createSection(
		$prefix,
		array(
			'title'  => 'Backup',
			'icon'   => 'fa fa-shield',
			'fields' => array(
				array(
					'type' => 'backup',
				),
			),
		)
	);
	// ----------------------------------------
	// Field: User Center
	// ----------------------------------------
	CSF::createSection(
		$prefix,
		array(
			'title'  => 'User Center',
			'icon'   => 'fa fa-user',
			'fields' => array(
				array(
					'type'  => 'heading',
					'title' => __( 'Basic Setting', 'lerm' ),
				),
				array(
					'id'    => 'frontend_register',
					'type'  => 'switcher',
					'title' => __( 'Frontend register', 'lerm' ),
				),
				array(
					'id'         => 'frontend_profile',
					'type'       => 'switcher',
					'title'      => __( 'Frontend Profile Page', 'lerm' ),
					'dependency' => array( 'frontend_register', '==', 'true' ),
				),
				array(
					'id'         => 'favorite_post',
					'type'       => 'switcher',
					'title'      => __( 'Favorite Post', 'lerm' ),
					'dependency' => array( 'frontend_register', '==', 'true' ),
				),
				array(
					'id'         => 'my_posts',
					'type'       => 'switcher',
					'title'      => __( 'My Own Posts', 'lerm' ),
					'dependency' => array( 'frontend_register', '==', 'true' ),
				),
				array(
					'id'         => 'favorite_post',
					'type'       => 'switcher',
					'title'      => __( 'Favorite Post', 'lerm' ),
					'dependency' => array( 'frontend_register', '==', 'true' ),
				),
				array(
					'type'       => 'heading',
					'title'      => __( 'Register And Login', 'lerm' ),
					'dependency' => array( 'frontend_register', '==', 'true' ),
				),
				array(
					'id'         => 'login_redirect',
					'type'       => 'switcher',
					'title'      => __( 'Login Redirect to Homepage', 'lerm' ),
					'dependency' => array( 'frontend_register', '==', 'true' ),
				),
				array(
					'id'         => 'default_login_page',
					'type'       => 'switcher',
					'title'      => __( 'Disable Default', 'lerm' ),
					'label'      => __( 'Disable Default WordPress login page', 'lerm' ),
					'dependency' => array( 'frontend_register', '==', 'true' ),
				),
				array(
					'id'         => 'users_can_register',
					'type'       => 'switcher',
					'title'      => __( 'User Can Register', 'lerm' ),
					'label'      => __( 'Disable Default WordPress login page', 'lerm' ),
					'dependency' => array( 'frontend_register', '==', 'true' ),
				),
				array(
					'id'         => 'default_role',
					'type'       => 'select',
					'title'      => __( 'Default Role', 'lerm' ),
					'label'      => __( 'Select default WordPress register roles', 'lerm' ),
					'options'    => $wp_roles->get_names(),
					'dependency' => array( 'frontend_register', '==', 'true' ),
				),
			),
		)
	);
}
