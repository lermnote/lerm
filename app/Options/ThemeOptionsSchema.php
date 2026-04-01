<?php // phpcs:disable WordPress.Files.FileName
/**
 * Theme options schema for the native admin UI.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ThemeOptionsSchema {

	public const OPTION_NAME = 'lerm_theme_options';

	/**
	 * Return settings sections and field definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function sections(): array {
		return array(
			'header'   => array(
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
			),
			'footer'   => array(
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
			),
			'search'   => array(
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
			),
			'comments' => array(
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
			),
			'account'  => array(
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
						'description'      => __( 'Maps to the WordPress “Anyone can register” behaviour through the theme auth layer.', 'lerm' ),
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
						'description'      => __( 'Redirects most direct visits to wp-login.php into the frontend auth page.', 'lerm' ),
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
						'label'            => __( 'Enable “My Posts” section', 'lerm' ),
						'description'      => __( 'Reserved switch for author-owned content features in the account center.', 'lerm' ),
						'group'            => __( 'Account center', 'lerm' ),
						'default'          => false,
						'dependency_field' => 'frontend_regist',
						'dependency_value' => '1',
					),
				),
			),
			'seo'      => array(
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
			),
		);
	}

	/**
	 * Flatten the schema to defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		$defaults = array();

		foreach ( self::fields() as $field ) {
			$defaults[ $field['id'] ] = $field['default'] ?? '';
		}

		return $defaults;
	}

	/**
	 * Return every field keyed numerically.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function fields(): array {
		$fields = array();

		foreach ( self::sections() as $section ) {
			foreach ( $section['fields'] as $field ) {
				$fields[] = $field;
			}
		}

		return $fields;
	}

	/**
	 * Return a section definition.
	 *
	 * @param string $section_id Section ID.
	 * @return array<string, mixed>|null
	 */
	public static function section( string $section_id ): ?array {
		$sections = self::sections();

		return $sections[ $section_id ] ?? null;
	}

	/**
	 * Return a field definition by ID.
	 *
	 * @param string $field_id Field ID.
	 * @return array<string, mixed>|null
	 */
	public static function field( string $field_id ): ?array {
		foreach ( self::fields() as $field ) {
			if ( $field['id'] === $field_id ) {
				return $field;
			}
		}

		return null;
	}

	/**
	 * Resolve field choices.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return array<string, string>
	 */
	public static function choices( array $field ): array {
		$choices = $field['choices'] ?? array();

		if ( is_callable( $choices ) ) {
			$choices = call_user_func( $choices );
		}

		if ( ! is_array( $choices ) ) {
			return array();
		}

		return array_map( 'strval', $choices );
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
}
