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
	// Basic section
	// ----------------------------------------
	CSF::createSection(
		$prefix,
		array(
			'title'  => __( 'Basic Settings', 'lerm' ),
			'icon'   => 'fa fa-rocket',
			'fields' => array(
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
					'id'      => 'title_sep',
					'type'    => 'radio',
					'title'   => __( 'Title Separator', 'lerm' ),
					'inline'  => true,
					'options' => array(
						'|' => '|',
						'-' => '-',
					),
					'default' => '|',
					'after'   => __( 'Select the Title Separator of your Website. Default "|", eg: My Website|Just another WordPress site', 'lerm' ),
				),

				array(
					'id'      => 'site_width',
					'type'    => 'dimensions',
					'title'   => __( 'Site Width', 'lerm' ),
					'default' => array(
						'width' => '1140',
					),
					'units'   => array( 'px' ),
					'height'  => false,
				),
				array(
					'id'      => 'global_layout',
					'type'    => 'image_select',
					'title'   => __( 'Layout', 'lerm' ),
					'options' => array(
						'layout-1c'        => $imagepath . '1c.png',
						'layout-1c-narrow' => $imagepath . '1c-narrow.png',
						'layout-2c-l'      => $imagepath . '2c-l.png',
						'layout-2c-r'      => $imagepath . '2c-r.png',
					),
					'radio'   => true,
					'default' => 'layout-2c-r',
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
					'id'    => 'affix',
					'type'  => 'switcher',
					'title' => __( 'Sidebar Affix', 'lerm' ),
					'label' => __( 'Sidebar Affix', 'lerm' ),
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
	// ----------------------------------------
	// Color Schemes
	// ----------------------------------------
	CSF::createSection(
		$prefix,
		array(
			'title'  => __( 'Color Schemes', 'lerm' ),
			'icon'   => 'fa fa-tachometer',
			'fields' => array(
				array(
					'type'    => 'heading',
					'content' => __( 'Global Color Schemes', 'lerm' ),
				),
				array(
					'id'      => 'link_color',
					'type'    => 'link_color',
					'title'   => __( 'Global Links Color', 'lerm' ),
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
					'title'                => 'Body Background',
					'background_image_url' => false,
					'default'              => array(
						'background-color'      => '#ebebeb',
						'background-position'   => '',
						'background-repeat'     => '',
						'background-attachment' => '',
						'background-size'       => '',
					),
					'output'               => 'body',
				),

				array(
					'id'             => 'body_typography',
					'type'           => 'typography',
					'title'          => 'Site Typography',
					'font_family'    => '',
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
					'id'           => 'content_background',
					'type'         => 'color_pair',
					'title'        => __( 'Content Color', 'lerm' ),
					'border_color' => true,
					'default'      => array(
						'color'            => '',
						'background_color' => '#fff',
						'border_color'     => '',
					),
					'output'       => array(
						// '#related',
						'.widget',
						// '.summary',
						// '.entry',
						'img.avatar',
						'.author-info',
						'.page-numbers',
						'.comment-respond',
						'.post-navigation',
						'.comments-list',
						'.card',
					),
				),
				array(
					'id'           => 'title_wrap',
					'type'         => 'color_pair',
					'title'        => __( 'Widget Header Color', 'lerm' ),
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
					'content' => __( 'Footer Color Schemes', 'lerm' ),
				),
				array(
					'id'          => 'header_bg_color',
					'type'        => 'color',
					'title'       => __( 'Header Background Color', 'lerm' ),
					'default'     => '#fff',
					'output_mode' => 'background-color',
					'output'      => array( '.site-header', '.dropdown-menu' ),
				),

				array(
					'id'               => 'navbar_link_color',
					'type'             => 'link_color',
					'title'            => __( 'Menu Links Color', 'lerm' ),
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
					'title'            => __( 'Menu Items Active Color', 'lerm' ),
					'output_important' => true,
					'default'          => array(
						'color'            => '#0084ba',
						'background_color' => '#fff',
					),
					'output'           => array( '.navbar-nav .active > .nav-link', '.navbar-nav .show > .nav-link' ),
				),
				array(
					'id'          => 'navbar_item_spacing',
					'type'        => 'spacing',
					'title'       => 'Menu Items Padding',
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
					'title'   => 'Site Header Border',
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
					'content' => __( 'Footer Color Schemes', 'lerm' ),
				),
				array(
					'id'      => 'colophon_style',
					'type'    => 'color_pair',
					'title'   => __( 'Footer Copyright Color', 'lerm' ),
					'default' => array(
						'color'            => '#ddd',
						'background_color' => '#555',
					),
					'output'  => array( '.colophon' ),
				),
				array(
					'id'      => 'footer_widget_color',
					'type'    => 'color_pair',
					'title'   => __( 'Footer Widgets Color', 'lerm' ),
					'default' => array(
						'color'            => '#ddd',
						'background_color' => '#333',
					),
					'output'  => array( '.footer-widget' ),
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
						'.comment-reply-link',
						'a[id="cancel-comment-reply-link"]',
						'.tag-cloud-link',
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
						'.tag-cloud-link:hover',
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
					'id'         => 'mail_options',
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
					),
				),
				array(
					'id'         => 'smtp_options',
					'type'       => 'fieldset',
					'dependency' => array( 'email_notice', '==', 'true' ),
					'fields'     => array(
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
							'id'    => 'ssl_switcher',
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
	CSF::createSection(
		$prefix,
		array(
			'icon'   => 'fa fa-flask',
			'title'  => __( 'SEO Optimize', 'lerm' ),
			'fields' => array(

				array(
					'id'         => 'keywords',
					'type'       => 'text',
					'title'      => __( 'Keywords', 'lerm' ),
					'label'      => __( 'Separate with commas', 'lerm' ),
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
					'id'      => 'html_slug',
					'type'    => 'switcher',
					'title'   => __( 'HTML Slug', 'lerm' ),
					'label'   => __( 'Shows .html slug for pages (please RE-SAVE the permalink options after changed slug)', 'lerm' ),
					'default' => false,
				),
				array(
					'id'      => 'lazyload',
					'type'    => 'switcher',
					'title'   => __( 'Images Lazyload', 'lerm' ),
					'label'   => __( 'Images Lazyload', 'lerm' ),
					'default' => true,
				),
				array(
					'id'      => 'sitemap_submit',
					'type'    => 'switcher',
					'title'   => __( 'Baidu Submit', 'lerm' ),
					'default' => false,
				),
				array(
					'id'         => 'submit_token',
					'type'       => 'text',
					'dependency' => array( 'sitemap_submit', '==', 'true' ),
					'title'      => __( 'Submit Token', 'lerm' ),
				),
				array(
					'id'            => 'baidu_tongji',
					'type'          => 'wp_editor',
					'title'         => __( 'Baidu Tongji', 'lerm' ),
					'after'         => __( 'baidu tongji code in before head tag', 'lerm' ),
					'height'        => '80px',
					'media_buttons' => false,
					'quicktags'     => false,
					'tinymce'       => true,
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
					'id'         => 'cdn_jquery',
					'type'       => 'text',
					'title'      => __( 'jQuery CDN', 'lerm' ),
					'label'      => __( 'jQuery CDN', 'lerm' ),
					'attributes' => array(
						'placeholder' => 'http://',
					),
				),
				array(
					'id'         => 'replace_avatar',
					'type'       => 'text',
					'title'      => __( 'Replace Avatar', 'lerm' ),
					'attributes' => array(
						'placeholder' => 'http://',
					),
				),
				array(
					'id'      => 'avatar_cache',
					'type'    => 'switcher',
					'title'   => __( 'Avatar Cache', 'lerm' ),
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
					'id'      => 'disable_embeds',
					'type'    => 'switcher',
					'title'   => __( 'Disable Embeds', 'lerm' ),
					'default' => false,
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
					'title'   => __( 'Post Meta', 'lerm' ),
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
					'id'      => 'footer_sidebars_count',
					'type'    => 'spinner',
					'title'   => __( 'Footer sidebars count', 'lerm' ),
					'default' => 0,
					'min'     => 0,
					'max'     => 4,
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
					'id'    => 'slide_switcher',
					'type'  => 'switcher',
					'title' => __( 'Enable Slides', 'lerm' ),
					'label' => __( 'Enable to show slides on frontpage', 'lerm' ),
				),
				array(
					'id'         => 'slide_position',
					'type'       => 'radio',
					'title'      => __( 'Slides Position', 'lerm' ),
					'label'      => __( 'Select Slides Position On frontpage', 'lerm' ),
					'dependency' => array( 'slide_switcher', '==', 'true' ),
					'options'    => array(
						'under_navbar'     => __( 'Under navbar', 'lerm' ),
						'above_entry_list' => __( 'Above entry list', 'lerm' ),
						'full_width'       => __( 'Under navbar(Full width)', 'lerm' ),
					),
				),
				array(
					'id'         => 'slide_indicators',
					'type'       => 'switcher',
					'title'      => __( 'Slides indicators', 'lerm' ),
					'label'      => __( 'Enable to show slides indicators', 'lerm' ),
					'dependency' => array( 'slide_switcher', '==', 'true' ),
				),
				array(
					'id'         => 'slide_control',
					'type'       => 'switcher',
					'title'      => __( 'Slides control arrows', 'lerm' ),
					'label'      => __( 'Enable to show slides control arrows', 'lerm' ),
					'dependency' => array( 'slide_switcher', '==', 'true' ),
				),
				array(
					'id'                     => 'lerm_slides',
					'type'                   => 'group',
					'dependency'             => array( 'slide_switcher', '==', 'true' ),
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
			'title'  => __( 'Other', 'lerm' ),
			'fields' => array(
				array(
					'id'      => 'disable_rest_api',
					'type'    => 'switcher',
					'title'   => __( 'Disable Rest API', 'lerm' ),
					'label'   => __( 'If use GutenBerg editor, must enable rest_api', 'lerm' ),
					'default' => false,
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
