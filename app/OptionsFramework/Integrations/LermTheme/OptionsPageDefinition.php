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

	private const PAGE_ID = 'lerm-theme-settings';

	/**
	 * Ordered section IDs loaded from config files.
	 *
	 * @var array<int, string>
	 */
	private const SECTION_ORDER = array(
		'header',
		'footer',
		'appearance_layout',
		'appearance_colors',
		'appearance_typography',
		'appearance_advanced',
		'content_posts',
		'content_pages',
		'content_sidebar',
		'content_carousel',
		'content_404',
		'system',
		'system_mailing',
		'system_cdn',
		'search',
		'comments',
		'account',
		'seo',
		'community_social',
		'community_profiles',
		'community_ads',
		'tools_backup',
	);

	/**
	 * Cached full page definition.
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $definition_cache = null;

	/**
	 * Cached section definitions.
	 *
	 * @var array<string, array<string, mixed>>|null
	 */
	private static ?array $sections_cache = null;

	/**
	 * Full options framework page definition for this theme.
	 *
	 * @return array<string, mixed>
	 */
	public static function definition(): array {
		if ( null !== self::$definition_cache ) {
			return self::$definition_cache;
		}

		$definition                = self::load_config( 'page.php' );
		$definition['id']          = $definition['id'] ?? self::PAGE_ID;
		$definition['option_name'] = $definition['option_name'] ?? self::OPTION_NAME;
		$definition['sections']    = self::sections();
		self::$definition_cache    = $definition;

		return self::$definition_cache;
	}

	/**
	 * Return settings sections and field definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function sections(): array {
		if ( null !== self::$sections_cache ) {
			return self::$sections_cache;
		}

		$sections = array();

		foreach ( self::SECTION_ORDER as $section_id ) {
			$section = self::load_config( 'sections/' . $section_id . '.php' );

			if ( ! empty( $section ) ) {
				$sections[ $section_id ] = $section;
			}
		}

		self::$sections_cache = $sections;

		return self::$sections_cache;
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
	 * Return published pages without a placeholder option.
	 *
	 * @return array<string, string>
	 */
	public static function page_checkbox_choices(): array {
		$choices = array();

		foreach ( get_pages( array( 'sort_column' => 'post_title' ) ) as $page ) {
			$choices[ (string) $page->ID ] = $page->post_title;
		}

		return $choices;
	}

	/**
	 * Return published posts for checkbox selectors.
	 *
	 * @return array<string, string>
	 */
	public static function post_choices(): array {
		$choices = array();
		$posts   = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'numberposts'    => 200,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'suppress_filters' => false,
			)
		);

		foreach ( $posts as $post ) {
			$choices[ (string) $post->ID ] = $post->post_title;
		}

		return $choices;
	}

	/**
	 * Return categories for checkbox selectors.
	 *
	 * @return array<string, string>
	 */
	public static function category_choices(): array {
		$choices = array();
		$terms   = get_categories(
			array(
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		foreach ( $terms as $term ) {
			$choices[ (string) $term->term_id ] = $term->name;
		}

		return $choices;
	}

	/**
	 * Return tags for checkbox selectors.
	 *
	 * @return array<string, string>
	 */
	public static function tag_choices(): array {
		$choices = array();
		$terms   = get_tags(
			array(
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		foreach ( $terms as $term ) {
			$choices[ (string) $term->term_id ] = $term->name;
		}

		return $choices;
	}

	/**
	 * Return sidebar choices including custom registered sidebars.
	 *
	 * @return array<string, string>
	 */
	public static function sidebar_choices(): array {
		global $wp_registered_sidebars;

		$choices = array(
			'home-sidebar'   => __( 'Home Sidebar', 'lerm' ),
			'footer-sidebar' => __( 'Footer Sidebar', 'lerm' ),
		);

		if ( is_array( $wp_registered_sidebars ) ) {
			foreach ( $wp_registered_sidebars as $sidebar ) {
				if ( empty( $sidebar['id'] ) || empty( $sidebar['name'] ) ) {
					continue;
				}

				$choices[ (string) $sidebar['id'] ] = (string) $sidebar['name'];
			}
		}

		$raw = get_option( self::OPTION_NAME, array() );
		$raw = is_array( $raw ) ? $raw : array();

		foreach ( (array) ( $raw['register_sidebars'] ?? array() ) as $sidebar ) {
			if ( ! is_array( $sidebar ) ) {
				continue;
			}

			$title = trim( (string) ( $sidebar['sidebar_title'] ?? '' ) );

			if ( '' === $title ) {
				continue;
			}

			$choices[ sanitize_title( $title ) . '-sidebar' ] = $title;
		}

		asort( $choices );

		return $choices;
	}

	/**
	 * Return title structure component choices.
	 *
	 * @return array<string, string>
	 */
	public static function title_structure_choices(): array {
		return array(
			'title'      => __( 'Site title', 'lerm' ),
			'separator'  => __( 'Separator', 'lerm' ),
			'tagline'    => __( 'Site tagline', 'lerm' ),
			'post_title' => __( 'Post title', 'lerm' ),
			'page_title' => __( 'Page title', 'lerm' ),
		);
	}

	/**
	 * Return XML sitemap entity choices.
	 *
	 * @return array<string, string>
	 */
	public static function sitemap_type_choices(): array {
		return array(
			'page'     => __( 'Pages', 'lerm' ),
			'post'     => __( 'Posts', 'lerm' ),
			'category' => __( 'Categories', 'lerm' ),
			'post_tag' => __( 'Tags', 'lerm' ),
			'format'   => __( 'Formats', 'lerm' ),
			'users'    => __( 'Authors', 'lerm' ),
		);
	}

	/**
	 * Return supported share platform choices.
	 *
	 * @return array<string, string>
	 */
	public static function social_share_choices(): array {
		return array(
			'weibo'       => 'Weibo',
			'wechat'      => 'WeChat',
			'qq'          => 'QQ',
			'qzone'       => 'Qzone',
			'douban'      => 'Douban',
			'linkedin'    => 'LinkedIn',
			'facebook'    => 'Facebook',
			'twitter'     => 'X / Twitter',
			'google_plus' => 'Google+',
		);
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
	public static function summary_meta_default(): array {
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
	public static function single_top_default(): array {
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
	public static function single_bottom_default(): array {
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

	/**
	 * Load one config file.
	 *
	 * @param string $relative_path Relative path inside the integration config directory.
	 * @return array<string, mixed>
	 */
	private static function load_config( string $relative_path ): array {
		$path = self::config_dir() . '/' . ltrim( $relative_path, '/' );

		if ( ! file_exists( $path ) ) {
			return array();
		}

		$config = require $path;

		return is_array( $config ) ? $config : array();
	}

	/**
	 * Base config directory.
	 */
	private static function config_dir(): string {
		return __DIR__ . '/config';
	}
}
