<?php
/**
 * A unique identifier is defined to store the options in the database and reference them from the theme.
 */
function optionsframework_option_name() {
	// Change this to use your theme slug
	$themename = wp_get_theme();
	$themename = preg_replace("/\W/", "_", strtolower($themename) );

	$optionsframework_settings = get_option( 'optionsframework' );
	$optionsframework_settings['id'] = $themename;
	update_option( 'optionsframework', $optionsframework_settings );
}

/**
 * Defines an array of options that will be used to generate the settings page and be saved in the database.
 * When creating the 'id' fields, make sure to use all lowercase and no spaces.
 *
 * If you are making your theme translatable, you should replace 'lerm'
 * with the actual text domain for your theme.  Read more:
 * http://codex.wordpress.org/Function_Reference/load_theme_textdomain
 */

function optionsframework_options() {
	// Multicheck Defaults
	$multicheck_defaults = array(
		'one' => '1',
		'five' => '1'
	);

	// Background Defaults
	$background_defaults = array(
		'color' => '',
		'image' => '',
		'repeat' => 'repeat',
		'position' => 'top center',
		'attachment'=>'scroll' );

	// Typography Defaults
	$typography_defaults = array(
		'size' => '15px',
		'face' => 'georgia',
		'style' => 'bold',
		'color' => '#bada55' );

	// Typography Options
	$typography_options = array(
		'sizes' => array( '6','12','14','16','20' ),
		'faces' => array( 'Helvetica Neue' => 'Helvetica Neue','Arial' => 'Arial' ),
		'styles' => array( 'normal' => 'Normal','bold' => 'Bold' ),
		'color' => false
	);
	// 将所有分类（categories）加入数组
	  $options_link_cats = array();
		$link_cats = get_terms( 'link_category' );
		foreach ( $link_cats as $link_cat ) {
			$options_link_cats[$link_cat->term_id] = $link_cat->name;
			}

	// If using image radio buttons, define a directory path
	$imagepath =  get_template_directory_uri() . '/img/';

	$options = array();

	$options[] = array(
		'name' => __( '基本设置', 'lerm' ),
		'type' => 'heading'
	);
	$options[] = array(
		'name' => __( 'Logo扫光效果', 'lerm' ),
		'desc' => __( '启用Logo扫光效果', 'lerm' ),
		'id' => 'blink',
		'std' => '0',
		'type' => 'checkbox'
	);
	$options[] = array(
		'name' => __( '标题分隔符', 'lerm' ),
		'desc' => __( '标题分隔符默认为"|",例如：乐朦|标题分隔符', 'lerm' ),
		'id' => 'title_separator',
		'std' => '|',
		'class' => 'mini',
		'type' => 'text'
	);
	$options[] = array(
		'name' => __( '顶部菜单', 'lerm' ),
		'desc' => __( '启用顶部菜单', 'lerm' ),
		'id' => 'topbar',
		'std' => '1',
		'type' => 'checkbox'
	);
	$options[] = array(
		'name' => __( '首页公告', 'lerm' ),
		'desc' => __( '启用首页公告', 'lerm' ),
		'id' => 'crumb-notice',
		'std' => '0',
		'type' => 'checkbox'
	);
	$options[] = array(
		'name' => __( '公告滚动', 'lerm' ),
		'desc' => __( '启用首页公告滚动', 'lerm' ),
		'id' => 'notice-scroll',
		'std' => '0',
		'type' => 'checkbox'
	);
	$options[] = array(
		'name' => __( '', 'lerm' ),
		'desc' => __( '公告指向链接：默认为首页', 'lerm' ),
		'id' => 'crumb-notice-link',
		'std' => site_url(),
		'type' => 'text'
	);
	$options[] = array(
		'name' => __( '', 'lerm' ),
		'desc' => __( '显示公告内容', 'lerm' ),
		'id' => 'crumb-notice-content',
		'placeholder' => '网站公告内容',
		'type' => 'textarea'
	);
	$options[] = array(
		'name' => __( '侧边栏', 'lerm' ),
		'desc' => __( '启用侧边栏跟随滚滚动', 'lerm' ),
		'id' => 'affix',
		'std' => '0',
		'type' => 'checkbox'
	);
	$options[] = array(
		'name' => "整体布局",
		'desc' => "3种布局供选择，点击选择你喜欢的布局方式。",
		'id' => "layout",
		'std' => "right-side",
		'type' => "images",
		'options' => array(
			'single' => $imagepath . '1col.png',
			'left-side' => $imagepath . '2cl.png',
			'right-side' => $imagepath . '2cr.png'
		)
	);
	if ( $options_link_cats ) {
	$options[] = array(
		'name' => __('选择链接分类目录', 'lerm'),
		'desc' => __('选择首页链接分类', 'lerm'),
		'id' => 'link_category',
		'type' => 'select',
		'options' => $options_link_cats);
	}
	$options[] = array(
		'name' => __( '备案号', 'lerm' ),
		'desc' => __( '在网站底部增加备案号', 'lerm' ),
		'id' => 'icp',
		'std' => '',
		'type' => 'text'
	);
	$options[] = array(
		'name' => __( '', 'lerm' ),
		'desc' => __( '网站底部内容', 'lerm' ),
		'id' => 'sitemap',
		'placeholder' => '网站底部内容',
		'type' => 'textarea'
	);
//slide
	$options[] = array(
		'name' => __( '图片轮播', 'lerm' ),
		'type' => 'heading'
	);
	$options[] = array(
		'name' => __( '首页幻灯片', 'lerm' ),
		'desc' => __( '启用首页幻灯片', 'lerm' ),
		'id' => 'slide-on',
		'std' => '0',
		'type' => 'checkbox'
	);
	for($i=0; $i<5; $i++){
		$options[] = array(
			'name' => __('图片-'.($i+1), 'lerm'),
			'desc' => __('预览图像将以全尺寸上传。', 'lerm'),
			'id' => 'slide'.$i,
			'type' => 'upload');
			$options[] = array(
				'desc' => __( '链接文字', 'lerm' ),
				'id' => 'slide_name'.$i,
				'std' => '',
				'type' => 'text'
			);
		$options[] = array(
			'desc' => __( '链接', 'lerm' ),
			'id' => 'slide_url'.$i,
			'std' => '',
			'type' => 'text'
		);
	}
	$options[] = array(
		'name' => __( '文章页面', 'lerm' ),
		'type' => 'heading'
	);
		$options[] = array(
			'name' => __( '文章导航', 'lerm' ),
			'desc' => __( '默认启用文章导航', 'lerm' ),
			'id' => 'post_navigation',
			'std' => '1',
			'type' => 'checkbox'
		);
	//SEO设置
	$options[] = array(
		'name' => __( 'SEO设置', 'lerm' ),
		'type' => 'heading'
	);
	$options[] = array(
		'name' => __( '页面伪静态', 'lerm' ),
		'desc' => __( '启用页面伪静态，.html', 'lerm' ),
		'id' => 'html_page',
		'std' => '0',
		'type' => 'checkbox'
	);
	$options[] = array(
		'name' => __( '关键词内链', 'lerm' ),
		'desc' => __( '启用关键词内链', 'lerm' ),
		'id' => 'post_tag_link',
		'std' => '1',
		'type' => 'checkbox'
	);
	$options[] = array(
		'name' => __( '', 'lerm' ),
		'desc' => __( '一个关键字少于多少不替换，默认为1', 'lerm' ),
		'id' => 'match_num_from',
		'std' => '1',
		'class'=>'mini',
		'type' => 'text'
	);
	$options[] = array(
		'name' => __( '', 'lerm' ),
		'desc' => __( '一个关键字最多替换，默认为1', 'lerm' ),
		'id' => 'match_num_to',
		'std' => '1',
		'class'=>'mini',
		'type' => 'text'
	);
	$options[] = array(
		'name' => __( '关键词(Keyword)', 'lerm' ),
		'desc' => __( '输入你的网站关键字，以英文逗号分开,一般不超过100个字符', 'lerm' ),
		'id' => 'keywords',
		'std' => '例如:WordPress,theme等',
		'type' => 'text'
	);
	$options[] = array(
		'name' => __( '网站描述(Description)', 'lerm' ),
		'desc' => __( '输入你的网站描述，一般不超过200个字符', 'lerm' ),
		'id' => 'description',
		'placeholder' => '网站描述',
		'type' => 'textarea'
	);
	$options[] = array(
		'name' => __( '百度主动推送', 'lerm' ),
		'desc' => __( '启用主动推送', 'lerm' ),
		'id' => 'sitemap_submit',
		'std' => '0',
		'type' => 'checkbox'
	);
	$options[] = array(
		'name' => __( 'token值', 'lerm' ),
		'desc' => __( '在百度站长平台获取主动推送token值，比如：http://data.zz.baidu.com/urls?site=域名&token=一组字符, ', 'lerm' ),
		'id' => 'sitemap_token',
		'std' => '',
		'type' => 'text'
	);


	$options[] = array(
		'name' => __( '邮件', 'lerm' ),
		'type' => 'heading'
	);
	$options[] = array(
		'name' => __( '邮件通知', 'lerm' ),
		'desc' => __( '启用邮件通知', 'lerm' ),
		'id' => 'mail',
		'std' => '0',
		'type' => 'checkbox'
	);
	$options[] = array(
		'name' => __( '邮件服务器', 'lerm' ),
		'desc' => __( '输入邮件服务器', 'lerm' ),
		'id' => 'host',
		'std' => '',
		'type' => 'text'
	);
	$options[] = array(
		'name' => __( '端口号', 'lerm' ),
		'desc' => __( '输入邮件服务端口号', 'lerm' ),
		'id' => 'port',
		'std' => '25',
		'type' => 'text'
	);
	$options[] = array(
		'name' => __( '用户邮箱', 'lerm' ),
		'desc' => __( '此邮箱必须和发件邮箱一致', 'lerm' ),
		'id' => 'username',
		'std' => '',
		'type' => 'text'
	);
	$options[] = array(
		'name' => __( '发件邮箱', 'lerm' ),
		'desc' => __( '输入邮件发件邮箱', 'lerm' ),
		'id' => 'from',
		'std' => '',
		'type' => 'text'
	);
	$options[] = array(
		'name' => __( '邮箱密码', 'lerm' ),
		'desc' => __( '输入发件邮箱密码', 'lerm' ),
		'id' => 'password',
		'std' => '',
		'type' => 'password'
	);
	$options[] = array(
		'name' => __( '发件人昵称', 'lerm' ),
		'desc' => __( '输入发件人昵称', 'lerm' ),
		'id' => 'fromname',
		'std' => '',
		'type' => 'text'
	);
	$options[] = array(
		'name' => __( '社会化', 'lerm' ),
		'type' => 'heading'
	);
	$options[] = array(
		'name' => __( '微博', 'lerm' ),
		'desc' => __( '请输入微博地址', 'lerm' ),
		'id' => 'weibo',
		'std' => '',
		'type' => 'text'
	);
	$options[] = array(
		'name' => __( 'Github', 'lerm' ),
		'desc' => __( 'Github地址', 'lerm' ),
		'id' => 'github',
		'std' => '',
		'type' => 'text'
	);
	$options[] = array(
		'name' => __( '微信二维码', 'lerm' ),
		'desc' => __( '请输入微信二维码路径', 'lerm' ),
		'id' => 'qrcode',
		'std' => '',
		'type' => 'text'
	);
	$options[] = array(
		'name' => __( 'Feed', 'lerm' ),
		'desc' => __( '请输入feed路径', 'lerm' ),
		'id' => 'rss',
		'std' => '',
		'type' => 'text'
	);
	$options[] = array(
		'name' => __( '其他', 'lerm' ),
		'type' => 'heading'
	);
	$options[] = array(
		'name' => __( '搜索', 'lerm' ),
		'desc' => __( '搜索结果排除所有页面', 'lerm' ),
		'id' => 'search_filter',
		'std' => '1',
		'type' => 'checkbox'
	);


	return $options;
};
