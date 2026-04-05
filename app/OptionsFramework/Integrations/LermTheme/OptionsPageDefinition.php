<?php // phpcs:disable WordPress.Files.FileName
/**
 * Lerm theme options page definition for the reusable options framework.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\OptionsFramework\Integrations\LermTheme;

use Lerm\OptionsFramework\Support\PageSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OptionsPageDefinition {

	public const OPTION_NAME = 'lerm_theme_options';

	/**
	 * Full options framework page definition for this theme.
	 *
	 * @return array<string, mixed>
	 */
	public static function definition(): array {
		return array(
			'id'          => 'lerm-theme-settings',
			'option_name' => self::OPTION_NAME,
			'menu'        => array(
				'parent_slug' => 'themes.php',
				'page_title'  => __( 'Lerm Settings', 'lerm' ),
				'menu_title'  => __( 'Lerm Settings', 'lerm' ),
				'capability'  => 'manage_options',
			),
			'view'        => array(
				'eyebrow'      => __( 'Native admin', 'lerm' ),
				'title'        => __( 'Lerm Settings', 'lerm' ),
				'description'  => __( 'The first production page running on the new Lerm Options Framework MVP.', 'lerm' ),
				'legacy_panel' => array(
					'title'        => __( 'Legacy panel', 'lerm' ),
					'description'  => __( 'Any section that has not been migrated yet still lives in the old Codestar screen.', 'lerm' ),
					'button_label' => __( 'Open legacy panel', 'lerm' ),
					'url'          => admin_url( 'admin.php?page=lerm_options' ),
				),
			),
			'sections'    => self::sections(),
		);
	}

	/**
	 * Return settings sections and field definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function sections(): array {
		return array(
			'header'              => self::header_section(),
			'footer'              => self::footer_section(),
			'appearance_advanced' => self::appearance_advanced_section(),
			'content_posts'       => self::content_posts_section(),
			'system'              => self::system_section(),
			'search'              => self::search_section(),
			'comments'            => self::comments_section(),
			'account'             => self::account_section(),
			'seo'                 => self::seo_section(),
		);
	}

	/**
	 * Flatten the schema to defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return PageSchema::defaults( self::definition() );
	}

	/**
	 * Return every field keyed numerically.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function fields(): array {
		return PageSchema::fields( self::definition() );
	}

	/**
	 * Return a section definition.
	 *
	 * @param string $section_id Section ID.
	 * @return array<string, mixed>|null
	 */
	public static function section( string $section_id ): ?array {
		return PageSchema::section( self::definition(), $section_id );
	}

	/**
	 * Return a field definition by ID.
	 *
	 * @param string $field_id Field ID.
	 * @return array<string, mixed>|null
	 */
	public static function field( string $field_id ): ?array {
		return PageSchema::field( self::definition(), $field_id );
	}

	/**
	 * Resolve field choices.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return array<string, string>
	 */
	public static function choices( array $field ): array {
		return PageSchema::choices( $field );
	}

	/**
	 * Return menu choices for select fields.
	 *
	 * @return array<string, string>
	 */
	public static function menu_choices(): array {
		$choices = array(
			'0' => __( 'No footer menu', 'lerm' ),
		);

		foreach ( wp_get_nav_menus() as $menu ) {
			$choices[ (string) $menu->term_id ] = $menu->name;
		}

		return $choices;
	}

	/**
	 * Return page choices for page selectors.
	 *
	 * @return array<string, string>
	 */
	public static function page_choices(): array {
		$choices = array(
			'0' => __( 'No page selected', 'lerm' ),
		);

		foreach ( get_pages( array( 'sort_column' => 'post_title' ) ) as $page ) {
			$choices[ (string) $page->ID ] = $page->post_title;
		}

		return $choices;
	}

	/**
	 * Return role choices for account settings.
	 *
	 * @return array<string, string>
	 */
	public static function role_choices(): array {
		$roles = wp_roles();

		return is_object( $roles ) ? array_map( 'strval', $roles->get_names() ) : array();
	}

	/**
	 * Header section.
	 *
	 * @return array<string, mixed>
	 */
	private static function header_section(): array {
		return array(
			'title'       => __( 'Header', 'lerm' ),
			'description' => __( 'Branding, navigation, and header behaviour settings.', 'lerm' ),
			'fields'      => array(
				array(
					'id'          => 'large_logo',
					'type'        => 'media',
					'label'       => __( 'Desktop logo', 'lerm' ),
					'description' => __( 'Shown in the desktop header.', 'lerm' ),
					'group'       => __( 'Branding', 'lerm' ),
					'default'     => array(),
					'button_text' => __( 'Choose desktop logo', 'lerm' ),
				),
				array(
					'id'          => 'mobile_logo',
					'type'        => 'media',
					'label'       => __( 'Mobile logo', 'lerm' ),
					'description' => __( 'Shown on small screens. Falls back to the desktop logo if empty.', 'lerm' ),
					'group'       => __( 'Branding', 'lerm' ),
					'default'     => array(),
					'button_text' => __( 'Choose mobile logo', 'lerm' ),
				),
				array(
					'id'          => 'blogname',
					'type'        => 'text',
					'label'       => __( 'Site title override', 'lerm' ),
					'description' => __( 'Overrides the WordPress site title inside the theme.', 'lerm' ),
					'group'       => __( 'Branding', 'lerm' ),
					'default'     => '',
					'placeholder' => get_bloginfo( 'name', 'display' ),
				),
				array(
					'id'          => 'blogdesc',
					'type'        => 'text',
					'label'       => __( 'Site tagline override', 'lerm' ),
					'description' => __( 'Overrides the WordPress tagline inside the theme.', 'lerm' ),
					'group'       => __( 'Branding', 'lerm' ),
					'default'     => '',
					'placeholder' => get_bloginfo( 'description', 'display' ),
				),
				array(
					'id'          => 'header_bg_color',
					'type'        => 'color',
					'label'       => __( 'Header background', 'lerm' ),
					'description' => __( 'Background color for the site header, dropdown menus, and off-canvas panel.', 'lerm' ),
					'group'       => __( 'Navigation', 'lerm' ),
					'default'     => '#ffffff',
				),
				array(
					'id'          => 'navbar_align',
					'type'        => 'select',
					'label'       => __( 'Navigation alignment', 'lerm' ),
					'description' => __( 'Controls how the main navigation is aligned on desktop.', 'lerm' ),
					'group'       => __( 'Navigation', 'lerm' ),
					'default'     => 'justify-content-md-end',
					'choices'     => array(
						'justify-content-md-start'  => __( 'Left', 'lerm' ),
						'justify-content-md-center' => __( 'Center', 'lerm' ),
						'justify-content-md-end'    => __( 'Right', 'lerm' ),
					),
				),
				array(
					'id'          => 'navbar_search',
					'type'        => 'switcher',
					'label'       => __( 'Show search in navbar', 'lerm' ),
					'description' => __( 'Displays a search trigger in the desktop and mobile navigation.', 'lerm' ),
					'group'       => __( 'Navigation', 'lerm' ),
					'default'     => false,
				),
				array(
					'id'          => 'sticky_header',
					'type'        => 'switcher',
					'label'       => __( 'Sticky header', 'lerm' ),
					'description' => __( 'Keep the header fixed at the top while the visitor scrolls.', 'lerm' ),
					'group'       => __( 'Behaviour', 'lerm' ),
					'default'     => false,
				),
				array(
					'id'               => 'sticky_header_shrink',
					'type'             => 'switcher',
					'label'            => __( 'Shrink sticky header', 'lerm' ),
					'description'      => __( 'Reduce header height after the page starts scrolling.', 'lerm' ),
					'group'            => __( 'Behaviour', 'lerm' ),
					'default'          => false,
					'dependency_field' => 'sticky_header',
					'dependency_value' => '1',
				),
				array(
					'id'          => 'transparent_header',
					'type'        => 'switcher',
					'label'       => __( 'Transparent header on hero', 'lerm' ),
					'description' => __( 'Allows the header to become transparent when a hero or slider sits directly below it.', 'lerm' ),
					'group'       => __( 'Behaviour', 'lerm' ),
					'default'     => false,
				),
			),
		);
	}

	/**
	 * Footer section.
	 *
	 * @return array<string, mixed>
	 */
	private static function footer_section(): array {
		return array(
			'title'       => __( 'Footer', 'lerm' ),
			'description' => __( 'Footer content and menu settings.', 'lerm' ),
			'fields'      => array(
				array(
					'id'          => 'footer_menus',
					'type'        => 'select',
					'label'       => __( 'Footer menu', 'lerm' ),
					'description' => __( 'Navigation menu displayed in the footer area.', 'lerm' ),
					'group'       => __( 'Footer content', 'lerm' ),
					'default'     => 0,
					'choices'     => array( self::class, 'menu_choices' ),
					'cast'        => 'int',
				),
				array(
					'id'          => 'icp_num',
					'type'        => 'text',
					'label'       => __( 'ICP registration number', 'lerm' ),
					'description' => __( 'Chinese ICP filing number shown in the footer.', 'lerm' ),
					'group'       => __( 'Footer content', 'lerm' ),
					'default'     => '',
					'placeholder' => (string) get_option( 'zh_cn_l10n_icp_num' ),
				),
				array(
					'id'          => 'copyright',
					'type'        => 'wp_editor',
					'label'       => __( 'Footer custom text', 'lerm' ),
					'description' => __( 'Additional content rendered in the footer. Basic HTML is allowed.', 'lerm' ),
					'group'       => __( 'Footer content', 'lerm' ),
					'default'     => '',
					'editor_args' => array(
						'teeny'         => true,
						'textarea_rows' => 6,
						'media_buttons' => false,
					),
				),
			),
		);
	}

	/**
	 * Appearance advanced section.
	 *
	 * @return array<string, mixed>
	 */
	private static function appearance_advanced_section(): array {
		return array(
			'title'       => __( 'Appearance+', 'lerm' ),
			'description' => __( 'Advanced styling, dark mode, and utility UI elements.', 'lerm' ),
			'fields'      => array(
				array(
					'id'          => 'custom_css',
					'type'        => 'code_editor',
					'label'       => __( 'Custom CSS', 'lerm' ),
					'description' => __( 'CSS entered here is output in a style tag in the page head.', 'lerm' ),
					'group'       => __( 'Custom CSS', 'lerm' ),
					'default'     => '',
					'rows'        => 12,
				),
				array(
					'id'          => 'dark_mode_enable',
					'type'        => 'switcher',
					'label'       => __( 'Enable dark mode support', 'lerm' ),
					'description' => __( 'Adds a visitor-facing toggle between light and dark themes.', 'lerm' ),
					'group'       => __( 'Dark mode', 'lerm' ),
					'default'     => false,
				),
				array(
					'id'               => 'dark_mode_default',
					'type'             => 'button_set',
					'label'            => __( 'Default color scheme', 'lerm' ),
					'description'      => __( 'Choose whether visitors start in light, dark, or system-following mode.', 'lerm' ),
					'group'            => __( 'Dark mode', 'lerm' ),
					'default'          => 'system',
					'choices'          => array(
						'light'  => __( 'Light', 'lerm' ),
						'dark'   => __( 'Dark', 'lerm' ),
						'system' => __( 'Follow system', 'lerm' ),
					),
					'dependency_field' => 'dark_mode_enable',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'dark_mode_toggle_position',
					'type'             => 'button_set',
					'label'            => __( 'Toggle button position', 'lerm' ),
					'description'      => __( 'Choose whether the dark-mode toggle lives in the navbar or as a floating sidebar button.', 'lerm' ),
					'group'            => __( 'Dark mode', 'lerm' ),
					'default'          => 'navbar',
					'choices'          => array(
						'navbar'  => __( 'In navbar', 'lerm' ),
						'sidebar' => __( 'Floating button', 'lerm' ),
					),
					'dependency_field' => 'dark_mode_enable',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'dark_bg_body',
					'type'             => 'color',
					'label'            => __( 'Body background', 'lerm' ),
					'description'      => __( 'Page-level background color in dark mode.', 'lerm' ),
					'group'            => __( 'Dark palette', 'lerm' ),
					'default'          => '#1a1b1e',
					'dependency_field' => 'dark_mode_enable',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'dark_bg_card',
					'type'             => 'color',
					'label'            => __( 'Card background', 'lerm' ),
					'description'      => __( 'Background color for cards and content surfaces in dark mode.', 'lerm' ),
					'group'            => __( 'Dark palette', 'lerm' ),
					'default'          => '#25262b',
					'dependency_field' => 'dark_mode_enable',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'dark_bg_header',
					'type'             => 'color',
					'label'            => __( 'Header background', 'lerm' ),
					'group'            => __( 'Dark palette', 'lerm' ),
					'default'          => '#1f2023',
					'dependency_field' => 'dark_mode_enable',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'dark_text',
					'type'             => 'color',
					'label'            => __( 'Body text color', 'lerm' ),
					'group'            => __( 'Dark palette', 'lerm' ),
					'default'          => '#c1c2c5',
					'dependency_field' => 'dark_mode_enable',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'dark_text_muted',
					'type'             => 'color',
					'label'            => __( 'Muted text color', 'lerm' ),
					'description'      => __( 'Used for secondary metadata, captions, and placeholders in dark mode.', 'lerm' ),
					'group'            => __( 'Dark palette', 'lerm' ),
					'default'          => '#909296',
					'dependency_field' => 'dark_mode_enable',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'dark_border',
					'type'             => 'color',
					'label'            => __( 'Border color', 'lerm' ),
					'group'            => __( 'Dark palette', 'lerm' ),
					'default'          => '#373a40',
					'dependency_field' => 'dark_mode_enable',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'dark_footer_bg',
					'type'             => 'color',
					'label'            => __( 'Footer widgets background', 'lerm' ),
					'group'            => __( 'Dark palette', 'lerm' ),
					'default'          => '#141517',
					'dependency_field' => 'dark_mode_enable',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'dark_bar_bg',
					'type'             => 'color',
					'label'            => __( 'Footer bar background', 'lerm' ),
					'group'            => __( 'Dark palette', 'lerm' ),
					'default'          => '#101113',
					'dependency_field' => 'dark_mode_enable',
					'dependency_value' => '1',
				),
				array(
					'id'          => 'reading_progress',
					'type'        => 'switcher',
					'label'       => __( 'Show reading progress bar', 'lerm' ),
					'description' => __( 'Displays a slim reading-progress bar at the very top of single posts.', 'lerm' ),
					'group'       => __( 'Reader UI', 'lerm' ),
					'default'     => false,
				),
				array(
					'id'               => 'reading_progress_color',
					'type'             => 'color',
					'label'            => __( 'Progress bar color', 'lerm' ),
					'group'            => __( 'Reader UI', 'lerm' ),
					'default'          => '#0084ba',
					'dependency_field' => 'reading_progress',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'reading_progress_height',
					'type'             => 'number',
					'label'            => __( 'Progress bar height (px)', 'lerm' ),
					'group'            => __( 'Reader UI', 'lerm' ),
					'default'          => 3,
					'min'              => 1,
					'max'              => 10,
					'step'             => 1,
					'dependency_field' => 'reading_progress',
					'dependency_value' => '1',
				),
				array(
					'id'          => 'back_to_top',
					'type'        => 'switcher',
					'label'       => __( 'Show back-to-top button', 'lerm' ),
					'group'       => __( 'Utility buttons', 'lerm' ),
					'default'     => true,
				),
				array(
					'id'               => 'back_to_top_threshold',
					'type'             => 'number',
					'label'            => __( 'Back-to-top threshold (px)', 'lerm' ),
					'description'      => __( 'The button appears after the visitor scrolls this many pixels.', 'lerm' ),
					'group'            => __( 'Utility buttons', 'lerm' ),
					'default'          => 400,
					'min'              => 100,
					'max'              => 2000,
					'step'             => 50,
					'dependency_field' => 'back_to_top',
					'dependency_value' => '1',
				),
				array(
					'id'          => 'qq_chat_enable',
					'type'        => 'switcher',
					'label'       => __( 'Show QQ live chat button', 'lerm' ),
					'group'       => __( 'Utility buttons', 'lerm' ),
					'default'     => false,
				),
				array(
					'id'               => 'qq_chat_number',
					'type'             => 'text',
					'label'            => __( 'QQ number', 'lerm' ),
					'description'      => __( 'Opens a QQ chat window when the floating button is clicked.', 'lerm' ),
					'group'            => __( 'Utility buttons', 'lerm' ),
					'default'          => '',
					'placeholder'      => '825641026',
					'dependency_field' => 'qq_chat_enable',
					'dependency_value' => '1',
				),
			),
		);
	}

	/**
	 * Content posts section.
	 *
	 * @return array<string, mixed>
	 */
	private static function content_posts_section(): array {
		return array(
			'title'       => __( 'Content+', 'lerm' ),
			'description' => __( 'Archive presentation and single-post feature controls.', 'lerm' ),
			'fields'      => array(
				array(
					'id'          => 'summary_or_full',
					'type'        => 'radio',
					'label'       => __( 'Archive post display mode', 'lerm' ),
					'description' => __( 'Choose whether archive pages render full post bodies or excerpts.', 'lerm' ),
					'group'       => __( 'Archive cards', 'lerm' ),
					'default'     => 'content_summary',
					'choices'     => array(
						'content_full'    => __( 'Full content', 'lerm' ),
						'content_summary' => __( 'Excerpt only', 'lerm' ),
					),
				),
				array(
					'id'          => 'excerpt_length',
					'type'        => 'number',
					'label'       => __( 'Excerpt length (words)', 'lerm' ),
					'description' => __( 'Number of words shown in automatically generated excerpts.', 'lerm' ),
					'group'       => __( 'Archive cards', 'lerm' ),
					'default'     => 95,
					'min'         => 0,
					'max'         => 300,
					'step'        => 5,
				),
				array(
					'id'          => 'show_thumbnail',
					'type'        => 'switcher',
					'label'       => __( 'Show archive thumbnails', 'lerm' ),
					'description' => __( 'Displays featured or fallback thumbnails on archive cards.', 'lerm' ),
					'group'       => __( 'Archive cards', 'lerm' ),
					'default'     => true,
				),
				array(
					'id'          => 'thumbnail_gallery',
					'type'        => 'gallery',
					'label'       => __( 'Fallback thumbnail pool', 'lerm' ),
					'description' => __( 'Images used when a post does not have a featured image.', 'lerm' ),
					'group'       => __( 'Archive cards', 'lerm' ),
					'default'     => array(),
				),
				array(
					'id'          => 'load_more',
					'type'        => 'switcher',
					'label'       => __( 'Ajax "Load more" pagination', 'lerm' ),
					'description' => __( 'Replaces standard archive pagination with a load-more button.', 'lerm' ),
					'group'       => __( 'Archive cards', 'lerm' ),
					'default'     => false,
				),
				array(
					'id'          => 'loading_animate',
					'type'        => 'switcher',
					'label'       => __( 'Archive card entrance animation', 'lerm' ),
					'group'       => __( 'Archive cards', 'lerm' ),
					'default'     => false,
				),
				array(
					'id'          => 'summary_meta',
					'type'        => 'sorter',
					'label'       => __( 'Archive meta items', 'lerm' ),
					'description' => __( 'Drag to set order, then tick the items that should appear below archive cards.', 'lerm' ),
					'group'       => __( 'Archive cards', 'lerm' ),
					'default'     => self::summary_meta_default(),
					'choices'     => array( self::class, 'post_meta_choices' ),
				),
				array(
					'id'          => 'post_navigation',
					'type'        => 'switcher',
					'label'       => __( 'Previous / next post navigation', 'lerm' ),
					'group'       => __( 'Single post', 'lerm' ),
					'default'     => true,
				),
				array(
					'id'          => 'disable_pingback',
					'type'        => 'switcher',
					'label'       => __( 'Disable pingbacks', 'lerm' ),
					'group'       => __( 'Single post', 'lerm' ),
					'default'     => false,
				),
				array(
					'id'          => 'enable_code_highlight',
					'type'        => 'switcher',
					'label'       => __( 'Syntax highlighting', 'lerm' ),
					'description' => __( 'Enable Prism.js highlighting for code blocks inside posts.', 'lerm' ),
					'group'       => __( 'Single post', 'lerm' ),
					'default'     => false,
				),
				array(
					'id'          => 'author_bio',
					'type'        => 'switcher',
					'label'       => __( 'Author bio box', 'lerm' ),
					'description' => __( 'Show the author biography card below single posts.', 'lerm' ),
					'group'       => __( 'Single post', 'lerm' ),
					'default'     => false,
				),
				array(
					'id'          => 'related_posts',
					'type'        => 'switcher',
					'label'       => __( 'Show related posts', 'lerm' ),
					'group'       => __( 'Single post', 'lerm' ),
					'default'     => false,
				),
				array(
					'id'               => 'related_number',
					'type'             => 'number',
					'label'            => __( 'Related posts count', 'lerm' ),
					'group'            => __( 'Single post', 'lerm' ),
					'default'          => 5,
					'min'              => 1,
					'max'              => 12,
					'step'             => 1,
					'dependency_field' => 'related_posts',
					'dependency_value' => '1',
				),
				array(
					'id'          => 'single_top',
					'type'        => 'sorter',
					'label'       => __( 'Header meta items', 'lerm' ),
					'description' => __( 'Controls the post meta shown above the article body.', 'lerm' ),
					'group'       => __( 'Single post meta', 'lerm' ),
					'default'     => self::single_top_default(),
					'choices'     => array( self::class, 'post_meta_choices' ),
				),
				array(
					'id'          => 'single_bottom',
					'type'        => 'sorter',
					'label'       => __( 'Footer meta items', 'lerm' ),
					'description' => __( 'Controls the post meta shown below the article body.', 'lerm' ),
					'group'       => __( 'Single post meta', 'lerm' ),
					'default'     => self::single_bottom_default(),
					'choices'     => array( self::class, 'post_meta_choices' ),
				),
				array(
					'id'          => 'toc_enable',
					'type'        => 'switcher',
					'label'       => __( 'Enable table of contents', 'lerm' ),
					'description' => __( 'Auto-generate a TOC from H2/H3 headings inside post content.', 'lerm' ),
					'group'       => __( 'Table of contents', 'lerm' ),
					'default'     => false,
				),
				array(
					'id'               => 'toc_min_headings',
					'type'             => 'number',
					'label'            => __( 'Minimum headings required', 'lerm' ),
					'description'      => __( 'The TOC stays hidden when a post has fewer headings than this value.', 'lerm' ),
					'group'            => __( 'Table of contents', 'lerm' ),
					'default'          => 3,
					'min'              => 1,
					'max'              => 20,
					'step'             => 1,
					'dependency_field' => 'toc_enable',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'toc_position',
					'type'             => 'button_set',
					'label'            => __( 'TOC position', 'lerm' ),
					'group'            => __( 'Table of contents', 'lerm' ),
					'default'          => 'before_content',
					'choices'          => array(
						'before_content' => __( 'Above content', 'lerm' ),
						'sidebar'        => __( 'In sidebar', 'lerm' ),
						'floating'       => __( 'Floating panel', 'lerm' ),
					),
					'dependency_field' => 'toc_enable',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'toc_collapsed',
					'type'             => 'switcher',
					'label'            => __( 'Collapse TOC by default', 'lerm' ),
					'group'            => __( 'Table of contents', 'lerm' ),
					'default'          => false,
					'dependency_field' => 'toc_enable',
					'dependency_value' => '1',
				),
				array(
					'id'          => 'post_likes_enable',
					'type'        => 'switcher',
					'label'       => __( 'Show post like button', 'lerm' ),
					'group'       => __( 'Engagement', 'lerm' ),
					'default'     => true,
				),
				array(
					'id'          => 'comment_likes_enable',
					'type'        => 'switcher',
					'label'       => __( 'Show comment like button', 'lerm' ),
					'group'       => __( 'Engagement', 'lerm' ),
					'default'     => true,
				),
				array(
					'id'          => 'post_views_enable',
					'type'        => 'switcher',
					'label'       => __( 'Show post view count', 'lerm' ),
					'group'       => __( 'Engagement', 'lerm' ),
					'default'     => true,
				),
				array(
					'id'               => 'views_unique_only',
					'type'             => 'switcher',
					'label'            => __( 'Count unique visitors only', 'lerm' ),
					'description'      => __( 'Avoids inflating view counts with repeated reloads from the same browser session.', 'lerm' ),
					'group'            => __( 'Engagement', 'lerm' ),
					'default'          => true,
					'dependency_field' => 'post_views_enable',
					'dependency_value' => '1',
				),
				array(
					'id'          => 'share_position',
					'type'        => 'button_set',
					'label'       => __( 'Share button position', 'lerm' ),
					'group'       => __( 'Sharing & copyright', 'lerm' ),
					'default'     => 'bottom',
					'choices'     => array(
						'top'    => __( 'Above content', 'lerm' ),
						'bottom' => __( 'Below content', 'lerm' ),
						'both'   => __( 'Both', 'lerm' ),
						'none'   => __( 'Hidden', 'lerm' ),
					),
				),
				array(
					'id'          => 'share_show_count',
					'type'        => 'switcher',
					'label'       => __( 'Show share count', 'lerm' ),
					'group'       => __( 'Sharing & copyright', 'lerm' ),
					'default'     => false,
				),
				array(
					'id'          => 'post_copyright_enable',
					'type'        => 'switcher',
					'label'       => __( 'Show copyright / repost notice', 'lerm' ),
					'description' => __( 'Displays a copyright reminder and permalink block beneath single posts.', 'lerm' ),
					'group'       => __( 'Sharing & copyright', 'lerm' ),
					'default'     => true,
				),
				array(
					'id'               => 'post_copyright_text',
					'type'             => 'textarea',
					'label'            => __( 'Custom copyright text', 'lerm' ),
					'description'      => __( 'Overrides the default copyright message. Leave empty to use the theme default.', 'lerm' ),
					'group'            => __( 'Sharing & copyright', 'lerm' ),
					'default'          => '',
					'rows'             => 4,
					'placeholder'      => __( 'This article was published by ... Please credit the original source when reposting.', 'lerm' ),
					'dependency_field' => 'post_copyright_enable',
					'dependency_value' => '1',
				),
			),
		);
	}

	/**
	 * System section.
	 *
	 * @return array<string, mixed>
	 */
	private static function system_section(): array {
		return array(
			'title'       => __( 'System', 'lerm' ),
			'description' => __( 'Runtime optimization, delivery mirrors, and custom head/footer code.', 'lerm' ),
			'fields'      => array(
				array(
					'id'          => 'super_admin',
					'type'        => 'switcher',
					'label'       => __( 'Backend asset acceleration', 'lerm' ),
					'description' => __( 'Load WordPress dashboard static assets from a public mirror for faster admin performance in China.', 'lerm' ),
					'group'       => __( 'Asset mirrors', 'lerm' ),
					'default'     => false,
				),
				array(
					'id'          => 'super_gravatar',
					'type'        => 'select',
					'label'       => __( 'Gravatar mirror', 'lerm' ),
					'description' => __( 'Replace the default avatar CDN with a faster mirror.', 'lerm' ),
					'group'       => __( 'Asset mirrors', 'lerm' ),
					'default'     => 'disable',
					'choices'     => array( self::class, 'gravatar_choices' ),
				),
				array(
					'id'          => 'super_googleapis',
					'type'        => 'select',
					'label'       => __( 'Google Fonts mirror', 'lerm' ),
					'description' => __( 'Only needed when the theme is loading Google-hosted font assets.', 'lerm' ),
					'group'       => __( 'Asset mirrors', 'lerm' ),
					'default'     => 'disable',
					'choices'     => array( self::class, 'google_mirror_choices' ),
				),
				array(
					'id'          => 'lazyload',
					'type'        => 'switcher',
					'label'       => __( 'Lazy-load images', 'lerm' ),
					'description' => __( 'Defer off-screen image loading until the visitor scrolls near them.', 'lerm' ),
					'group'       => __( 'Frontend delivery', 'lerm' ),
					'default'     => false,
				),
				array(
					'id'          => 'super_optimize',
					'type'        => 'checkbox_list',
					'label'       => __( 'WordPress head cleanup', 'lerm' ),
					'description' => __( 'Remove optional tags and features from wp_head and related outputs.', 'lerm' ),
					'group'       => __( 'Frontend delivery', 'lerm' ),
					'default'     => array(),
					'choices'     => array( self::class, 'optimize_flags_choices' ),
				),
				array(
					'id'          => 'head_scripts',
					'type'        => 'code_editor',
					'label'       => __( 'Before </head>', 'lerm' ),
					'description' => __( 'Code injected into the head of every page: analytics, verification tags, custom fonts, and similar snippets.', 'lerm' ),
					'group'       => __( 'Custom injection', 'lerm' ),
					'default'     => '',
					'rows'        => 10,
				),
				array(
					'id'          => 'footer_scripts',
					'type'        => 'code_editor',
					'label'       => __( 'Before </body>', 'lerm' ),
					'description' => __( 'Code injected just before the closing body tag: chat widgets, deferred scripts, and similar snippets.', 'lerm' ),
					'group'       => __( 'Custom injection', 'lerm' ),
					'default'     => '',
					'rows'        => 10,
				),
			),
		);
	}

	/**
	 * Search section.
	 *
	 * @return array<string, mixed>
	 */
	private static function search_section(): array {
		return array(
			'title'       => __( 'Search', 'lerm' ),
			'description' => __( 'Instant search dropdown behaviour and search box copy.', 'lerm' ),
			'fields'      => array(
				array(
					'id'          => 'search_results_per_page',
					'type'        => 'number',
					'label'       => __( 'Instant search results count', 'lerm' ),
					'description' => __( 'Maximum number of items shown in the instant search dropdown.', 'lerm' ),
					'group'       => __( 'Instant search', 'lerm' ),
					'default'     => 5,
					'min'         => 1,
					'max'         => 20,
					'step'        => 1,
				),
				array(
					'id'          => 'search_placeholder',
					'type'        => 'text',
					'label'       => __( 'Search box placeholder', 'lerm' ),
					'description' => __( 'Placeholder shown in the search form and instant search field.', 'lerm' ),
					'group'       => __( 'Instant search', 'lerm' ),
					'default'     => '',
					'placeholder' => __( 'Search...', 'lerm' ),
				),
			),
		);
	}

	/**
	 * Comments section.
	 *
	 * @return array<string, mixed>
	 */
	private static function comments_section(): array {
		return array(
			'title'       => __( 'Comments', 'lerm' ),
			'description' => __( 'Global comment behaviour, form rules, and avatar presentation.', 'lerm' ),
			'fields'      => array(
				array(
					'id'          => 'comments_enable',
					'type'        => 'switcher',
					'label'       => __( 'Enable comments globally', 'lerm' ),
					'description' => __( 'When disabled, comments are closed everywhere regardless of per-post settings.', 'lerm' ),
					'group'       => __( 'Comment behaviour', 'lerm' ),
					'default'     => true,
				),
				array(
					'id'               => 'comments_require_login',
					'type'             => 'switcher',
					'label'            => __( 'Require login to comment', 'lerm' ),
					'description'      => __( 'Visitors must sign in before they can submit comments.', 'lerm' ),
					'group'            => __( 'Comment behaviour', 'lerm' ),
					'default'          => false,
					'dependency_field' => 'comments_enable',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'comment_moderation',
					'type'             => 'switcher',
					'label'            => __( 'Hold comments for moderation', 'lerm' ),
					'description'      => __( 'All new comments must be approved before showing publicly.', 'lerm' ),
					'group'            => __( 'Comment behaviour', 'lerm' ),
					'default'          => false,
					'dependency_field' => 'comments_enable',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'comments_per_page',
					'type'             => 'number',
					'label'            => __( 'Comments per page', 'lerm' ),
					'description'      => __( 'How many comments to display before pagination kicks in.', 'lerm' ),
					'group'            => __( 'Comment behaviour', 'lerm' ),
					'default'          => 20,
					'min'              => 5,
					'max'              => 100,
					'step'             => 5,
					'dependency_field' => 'comments_enable',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'comment_nesting_depth',
					'type'             => 'number',
					'label'            => __( 'Maximum nesting depth', 'lerm' ),
					'description'      => __( 'How many threaded reply levels are allowed.', 'lerm' ),
					'group'            => __( 'Comment behaviour', 'lerm' ),
					'default'          => 3,
					'min'              => 1,
					'max'              => 10,
					'step'             => 1,
					'dependency_field' => 'comments_enable',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'comment_form_fields',
					'type'             => 'checkbox_list',
					'label'            => __( 'Required form fields', 'lerm' ),
					'description'      => __( 'Choose which identity fields are required in the comment form.', 'lerm' ),
					'group'            => __( 'Comment form', 'lerm' ),
					'default'          => array( 'name', 'email' ),
					'choices'          => array(
						'name'    => __( 'Name', 'lerm' ),
						'email'   => __( 'Email', 'lerm' ),
						'website' => __( 'Website', 'lerm' ),
					),
					'dependency_field' => 'comments_enable',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'comment_placeholder',
					'type'             => 'text',
					'label'            => __( 'Comment textarea placeholder', 'lerm' ),
					'description'      => __( 'Prompt shown in the comment textarea before the visitor types.', 'lerm' ),
					'group'            => __( 'Comment form', 'lerm' ),
					'default'          => '',
					'placeholder'      => __( 'Leave a comment...', 'lerm' ),
					'dependency_field' => 'comments_enable',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'comment_min_length',
					'type'             => 'number',
					'label'            => __( 'Minimum comment length', 'lerm' ),
					'description'      => __( 'Set to 0 to disable the minimum length check.', 'lerm' ),
					'group'            => __( 'Comment form', 'lerm' ),
					'default'          => 10,
					'min'              => 0,
					'max'              => 500,
					'step'             => 1,
					'dependency_field' => 'comments_enable',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'comment_max_length',
					'type'             => 'number',
					'label'            => __( 'Maximum comment length', 'lerm' ),
					'description'      => __( 'Set to 0 to remove the maximum length limit.', 'lerm' ),
					'group'            => __( 'Comment form', 'lerm' ),
					'default'          => 2000,
					'min'              => 0,
					'max'              => 10000,
					'step'             => 100,
					'dependency_field' => 'comments_enable',
					'dependency_value' => '1',
				),
				array(
					'id'          => 'comment_avatar_size',
					'type'        => 'number',
					'label'       => __( 'Comment avatar size', 'lerm' ),
					'description' => __( 'Avatar size in pixels for the comment list.', 'lerm' ),
					'group'       => __( 'Avatar', 'lerm' ),
					'default'     => 48,
					'min'         => 24,
					'max'         => 128,
					'step'        => 4,
				),
				array(
					'id'          => 'comment_show_cravatar_tip',
					'type'        => 'switcher',
					'label'       => __( 'Show Cravatar tip', 'lerm' ),
					'description' => __( 'Display a hint encouraging users without an avatar to set one on Cravatar.cn.', 'lerm' ),
					'group'       => __( 'Avatar', 'lerm' ),
					'default'     => true,
				),
			),
		);
	}

	/**
	 * Account section.
	 *
	 * @return array<string, mixed>
	 */
	private static function account_section(): array {
		return array(
			'title'       => __( 'Account', 'lerm' ),
			'description' => __( 'Frontend login, registration, and account center settings.', 'lerm' ),
			'fields'      => array(
				array(
					'id'          => 'frontend_login',
					'type'        => 'switcher',
					'label'       => __( 'Enable frontend login', 'lerm' ),
					'description' => __( 'Turns on the theme frontend login and password reset flow.', 'lerm' ),
					'group'       => __( 'Frontend login', 'lerm' ),
					'default'     => false,
				),
				array(
					'id'               => 'frontend_login_page',
					'type'             => 'select',
					'label'            => __( 'Login page', 'lerm' ),
					'description'      => __( 'Page that renders the frontend login template.', 'lerm' ),
					'group'            => __( 'Frontend login', 'lerm' ),
					'default'          => 0,
					'choices'          => array( self::class, 'page_choices' ),
					'cast'             => 'int',
					'dependency_field' => 'frontend_login',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'menu_login_item',
					'type'             => 'switcher',
					'label'            => __( 'Add login/avatar item to navigation', 'lerm' ),
					'description'      => __( 'Shows a login or account entry in the main navigation.', 'lerm' ),
					'group'            => __( 'Frontend login', 'lerm' ),
					'default'          => false,
					'dependency_field' => 'frontend_login',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'login_redirect_url',
					'type'             => 'switcher',
					'label'            => __( 'Redirect to homepage after login', 'lerm' ),
					'description'      => __( 'If disabled, successful login goes to the configured account page.', 'lerm' ),
					'group'            => __( 'Frontend login', 'lerm' ),
					'default'          => false,
					'dependency_field' => 'frontend_login',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'logout_redirect_url',
					'type'             => 'switcher',
					'label'            => __( 'Redirect to homepage after logout', 'lerm' ),
					'description'      => __( 'If disabled, logout returns visitors to the frontend login page.', 'lerm' ),
					'group'            => __( 'Frontend login', 'lerm' ),
					'default'          => false,
					'dependency_field' => 'frontend_login',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'frontend_regist',
					'type'             => 'switcher',
					'label'            => __( 'Allow frontend registration', 'lerm' ),
					'description'      => __( 'Shows registration in the frontend auth flow.', 'lerm' ),
					'group'            => __( 'Registration', 'lerm' ),
					'default'          => false,
					'dependency_field' => 'frontend_login',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'users_can_register',
					'type'             => 'switcher',
					'label'            => __( 'Open registration to everyone', 'lerm' ),
					'description'      => __( 'Maps to the WordPress "Anyone can register" behaviour through the theme auth layer.', 'lerm' ),
					'group'            => __( 'Registration', 'lerm' ),
					'default'          => false,
					'dependency_field' => 'frontend_regist',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'default_role',
					'type'             => 'select',
					'label'            => __( 'Default user role', 'lerm' ),
					'description'      => __( 'Role assigned to newly registered users.', 'lerm' ),
					'group'            => __( 'Registration', 'lerm' ),
					'default'          => get_option( 'default_role', 'subscriber' ),
					'choices'          => array( self::class, 'role_choices' ),
					'dependency_field' => 'frontend_regist',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'default_login_page',
					'type'             => 'switcher',
					'label'            => __( 'Disable wp-login.php', 'lerm' ),
					'description'      => __( 'Redirect most direct visits to wp-login.php into the frontend auth page.', 'lerm' ),
					'group'            => __( 'Registration', 'lerm' ),
					'default'          => false,
					'dependency_field' => 'frontend_regist',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'front_user_center',
					'type'             => 'switcher',
					'label'            => __( 'Enable frontend account center', 'lerm' ),
					'description'      => __( 'Turns on the frontend user center experience.', 'lerm' ),
					'group'            => __( 'Account center', 'lerm' ),
					'default'          => false,
					'dependency_field' => 'frontend_login',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'frontend_user_center_page',
					'type'             => 'select',
					'label'            => __( 'Account center page', 'lerm' ),
					'description'      => __( 'Page that renders the frontend account center template.', 'lerm' ),
					'group'            => __( 'Account center', 'lerm' ),
					'default'          => 0,
					'choices'          => array( self::class, 'page_choices' ),
					'cast'             => 'int',
					'dependency_field' => 'front_user_center',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'frontend_profile',
					'type'             => 'switcher',
					'label'            => __( 'Enable profile editing', 'lerm' ),
					'description'      => __( 'Shows frontend profile editing inside the account center.', 'lerm' ),
					'group'            => __( 'Account center', 'lerm' ),
					'default'          => false,
					'dependency_field' => 'frontend_regist',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'favorite_post',
					'type'             => 'switcher',
					'label'            => __( 'Enable post favorites', 'lerm' ),
					'description'      => __( 'Reserved switch for favorites features in the account center.', 'lerm' ),
					'group'            => __( 'Account center', 'lerm' ),
					'default'          => false,
					'dependency_field' => 'frontend_regist',
					'dependency_value' => '1',
				),
				array(
					'id'               => 'my_posts',
					'type'             => 'switcher',
					'label'            => __( 'Enable "My Posts" section', 'lerm' ),
					'description'      => __( 'Reserved switch for author-owned content features in the account center.', 'lerm' ),
					'group'            => __( 'Account center', 'lerm' ),
					'default'          => false,
					'dependency_field' => 'frontend_regist',
					'dependency_value' => '1',
				),
			),
		);
	}

	/**
	 * SEO section.
	 *
	 * @return array<string, mixed>
	 */
	private static function seo_section(): array {
		return array(
			'title'       => __( 'SEO', 'lerm' ),
			'description' => __( 'Title and URL behaviour, plus Baidu submission settings.', 'lerm' ),
			'fields'      => array(
				array(
					'id'          => 'title_sep',
					'type'        => 'select',
					'label'       => __( 'Title separator', 'lerm' ),
					'description' => __( 'Character placed between the page title and the site name.', 'lerm' ),
					'group'       => __( 'Meta output', 'lerm' ),
					'default'     => '-',
					'choices'     => array(
						'-'       => '-',
						'&ndash;' => '&ndash;',
						'&mdash;' => '&mdash;',
						':'       => ':',
						'|'       => '|',
					),
				),
				array(
					'id'          => 'html_slug',
					'type'        => 'switcher',
					'label'       => __( 'Append .html to page URLs', 'lerm' ),
					'description' => __( 'After changing this, re-save the permalink settings in WordPress.', 'lerm' ),
					'group'       => __( 'Meta output', 'lerm' ),
					'default'     => false,
				),
				array(
					'id'          => 'sitemap_enable',
					'type'        => 'switcher',
					'label'       => __( 'Enable XML sitemap', 'lerm' ),
					'description' => __( 'Outputs the theme sitemap endpoint for search engines.', 'lerm' ),
					'group'       => __( 'Meta output', 'lerm' ),
					'default'     => false,
				),
				array(
					'id'          => 'baidu_submit',
					'type'        => 'switcher',
					'label'       => __( 'Enable Baidu URL auto-submission', 'lerm' ),
					'description' => __( 'Automatically push new post URLs to Baidu Search.', 'lerm' ),
					'group'       => __( 'Baidu push', 'lerm' ),
					'default'     => false,
				),
				array(
					'id'               => 'submit_url',
					'type'             => 'url',
					'label'            => __( 'Push API endpoint URL', 'lerm' ),
					'description'      => __( 'The full Baidu push API endpoint URL.', 'lerm' ),
					'group'            => __( 'Baidu push', 'lerm' ),
					'default'          => '',
					'dependency_field' => 'baidu_submit',
					'dependency_value' => '1',
					'placeholder'      => 'https://data.zz.baidu.com/urls?site=example.com&token=xxxx',
				),
				array(
					'id'               => 'submit_token',
					'type'             => 'text',
					'label'            => __( 'Push API token', 'lerm' ),
					'description'      => __( 'Token used when Baidu push is enabled.', 'lerm' ),
					'group'            => __( 'Baidu push', 'lerm' ),
					'default'          => '',
					'dependency_field' => 'baidu_submit',
					'dependency_value' => '1',
				),
			),
		);
	}

	/**
	 * Return optimization flag choices.
	 *
	 * @return array<string, string>
	 */
	public static function optimize_flags_choices(): array {
		return array(
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
		);
	}

	/**
	 * Return post meta item choices for sorter fields.
	 *
	 * @return array<string, string>
	 */
	public static function post_meta_choices(): array {
		return array(
			'publish_date' => __( 'Publish date', 'lerm' ),
			'categories'   => __( 'Category', 'lerm' ),
			'read'         => __( 'Reading time', 'lerm' ),
			'responses'    => __( 'Comments', 'lerm' ),
			'format'       => __( 'Format', 'lerm' ),
			'author'       => __( 'Author', 'lerm' ),
		);
	}

	/**
	 * Return gravatar mirror choices.
	 *
	 * @return array<string, string>
	 */
	public static function gravatar_choices(): array {
		return array(
			'disable'                           => __( 'Disabled (use Gravatar directly)', 'lerm' ),
			'https://cdn.sep.cc/avatar/'        => 'SEP',
			'https://cravatar.cn/avatar/'       => 'Cravatar',
			'https://sdn.geekzu.org/avatar/'    => 'Geekzu',
			'https://gravatar.loli.net/avatar/' => 'loli.net',
			'https://weavatar.com/avatar/'      => 'WeAvatar',
		);
	}

	/**
	 * Return Google-hosted asset mirror choices.
	 *
	 * @return array<string, string>
	 */
	public static function google_mirror_choices(): array {
		return array(
			'disable' => __( 'Disabled', 'lerm' ),
			'geekzu'  => 'Geekzu',
			'loli'    => 'loli.net',
			'ustc'    => __( 'USTC (University of Science and Technology of China)', 'lerm' ),
		);
	}

	/**
	 * Default archive meta sorter state.
	 *
	 * @return array<string, array<string, string>>
	 */
	private static function summary_meta_default(): array {
		$choices = self::post_meta_choices();

		return array(
			'enabled'  => array(
				'categories' => $choices['categories'],
				'read'       => $choices['read'],
			),
			'disabled' => array(
				'author'       => $choices['author'],
				'responses'    => $choices['responses'],
				'publish_date' => $choices['publish_date'],
				'format'       => $choices['format'],
			),
		);
	}

	/**
	 * Default single-post header meta sorter state.
	 *
	 * @return array<string, array<string, string>>
	 */
	private static function single_top_default(): array {
		$choices = self::post_meta_choices();

		return array(
			'enabled'  => array(
				'publish_date' => $choices['publish_date'],
				'categories'   => $choices['categories'],
				'read'         => $choices['read'],
				'responses'    => $choices['responses'],
			),
			'disabled' => array(
				'format' => $choices['format'],
				'author' => $choices['author'],
			),
		);
	}

	/**
	 * Default single-post footer meta sorter state.
	 *
	 * @return array<string, array<string, string>>
	 */
	private static function single_bottom_default(): array {
		$choices = self::post_meta_choices();

		return array(
			'enabled'  => array(
				'publish_date' => $choices['publish_date'],
				'categories'   => $choices['categories'],
			),
			'disabled' => array(
				'format'    => $choices['format'],
				'author'    => $choices['author'],
				'read'      => $choices['read'],
				'responses' => $choices['responses'],
			),
		);
	}
}
