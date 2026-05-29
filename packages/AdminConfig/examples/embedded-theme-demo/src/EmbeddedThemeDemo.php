<?php
/**
 * Embedded theme demo schemas.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Examples;

use Lerm\AdminConfig\WordPress\Runtime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EmbeddedThemeDemo {

	public static function register( Runtime $runtime ): void {
		self::register_data_sources( $runtime );

		if ( ! $runtime->has( 'acme-theme-style-kit' ) ) {
			$runtime->register( self::options_schema() );
		}

		if ( ! $runtime->has( 'acme-theme-hero-metabox' ) ) {
			$runtime->register( self::metabox_schema() );
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function options_schema(): array {
		return array(
			'id'        => 'acme-theme-style-kit',
			'title'     => __( 'Theme Style Kit', 'lerm-admin-config' ),
			'container' => array(
				'type' => 'options_page',
			),
			'store'     => array(
				'type' => 'option',
				'key'  => 'acme_theme_style_kit',
			),
			'menu'      => array(
				'parent_slug' => 'themes.php',
				'page_title'  => __( 'Theme Style Kit', 'lerm-admin-config' ),
				'menu_title'  => __( 'Theme Style Kit', 'lerm-admin-config' ),
				'capability'  => 'edit_theme_options',
			),
			'view'      => array(
				'eyebrow'     => __( 'Embedded Mode', 'lerm-admin-config' ),
				'title'       => __( 'Theme Style Kit', 'lerm-admin-config' ),
				'description' => __( 'A theme-owned example showing advanced fields and reusable storage through the embedded runtime.', 'lerm-admin-config' ),
				'debug'       => true,
			),
			'sections'  => array(
				'brand' => array(
					'title'       => __( 'Brand', 'lerm-admin-config' ),
					'description' => __( 'Global style tokens compiled from one schema.', 'lerm-admin-config' ),
					'fields'      => array(
						array(
							'id'             => 'headline_typography',
							'type'           => 'typography',
							'label'          => __( 'Headline Typography', 'lerm-admin-config' ),
							'description'    => __( 'Controls used by hero and archive headings.', 'lerm-admin-config' ),
							'style'          => true,
							'letter_spacing' => true,
							'transform'      => true,
							'default'        => array(
								'font-family'    => 'Inter, system-ui, sans-serif',
								'font-weight'    => '700',
								'font-style'     => 'normal',
								'font-size'      => '3',
								'unit'           => 'rem',
								'line-height'    => '1.05',
								'letter-spacing' => '-0.02',
								'text-transform' => 'none',
								'color'          => '#0f172a',
							),
						),
						array(
							'id'          => 'feature_icon',
							'type'        => 'icon',
							'label'       => __( 'Feature Icon', 'lerm-admin-config' ),
							'description' => __( 'Shared icon used by theme promo blocks.', 'lerm-admin-config' ),
							'choices'     => array(
								'dashicons-lightbulb'    => __( 'Idea', 'lerm-admin-config' ),
								'dashicons-admin-customizer' => __( 'Customizer', 'lerm-admin-config' ),
								'dashicons-format-image' => __( 'Gallery', 'lerm-admin-config' ),
								'dashicons-chart-bar'    => __( 'Analytics', 'lerm-admin-config' ),
								'dashicons-star-filled'  => __( 'Featured', 'lerm-admin-config' ),
							),
							'default'     => 'dashicons-admin-customizer',
						),
						array(
							'id'          => 'surface_tabs',
							'type'        => 'tabbed',
							'label'       => __( 'Surface Recipes', 'lerm-admin-config' ),
							'description' => __( 'Different card recipes kept in one option payload.', 'lerm-admin-config' ),
							'default_tab' => 'default',
							'items'       => array(
								array(
									'id'          => 'default',
									'title'       => __( 'Default Surface', 'lerm-admin-config' ),
									'description' => __( 'Base card colors and copy.', 'lerm-admin-config' ),
									'fields'      => array(
										array(
											'id'      => 'title',
											'type'    => 'text',
											'label'   => __( 'Title', 'lerm-admin-config' ),
											'default' => __( 'Clean editorial card', 'lerm-admin-config' ),
										),
										array(
											'id'      => 'body',
											'type'    => 'textarea',
											'label'   => __( 'Body', 'lerm-admin-config' ),
											'default' => __( 'Use for standard archive highlights and feature callouts.', 'lerm-admin-config' ),
										),
										array(
											'id'      => 'accent',
											'type'    => 'color',
											'label'   => __( 'Accent', 'lerm-admin-config' ),
											'default' => '#14b8a6',
										),
									),
								),
								array(
									'id'          => 'featured',
									'title'       => __( 'Featured Surface', 'lerm-admin-config' ),
									'description' => __( 'Higher contrast variant for promoted content.', 'lerm-admin-config' ),
									'fields'      => array(
										array(
											'id'      => 'title',
											'type'    => 'text',
											'label'   => __( 'Title', 'lerm-admin-config' ),
											'default' => __( 'Promoted story card', 'lerm-admin-config' ),
										),
										array(
											'id'      => 'body',
											'type'    => 'textarea',
											'label'   => __( 'Body', 'lerm-admin-config' ),
											'default' => __( 'Use on the homepage and category headers when content needs extra emphasis.', 'lerm-admin-config' ),
										),
										array(
											'id'      => 'accent',
											'type'    => 'color',
											'label'   => __( 'Accent', 'lerm-admin-config' ),
											'default' => '#f97316',
										),
									),
								),
							),
							'default'     => array(
								'default'  => array(
									'title'  => __( 'Clean editorial card', 'lerm-admin-config' ),
									'body'   => __( 'Use for standard archive highlights and feature callouts.', 'lerm-admin-config' ),
									'accent' => '#14b8a6',
								),
								'featured' => array(
									'title'  => __( 'Promoted story card', 'lerm-admin-config' ),
									'body'   => __( 'Use on the homepage and category headers when content needs extra emphasis.', 'lerm-admin-config' ),
									'accent' => '#f97316',
								),
							),
						),
					),
				),
				'hero'  => array(
					'title'       => __( 'Hero Content', 'lerm-admin-config' ),
					'description' => __( 'Panel-based content blocks for the homepage hero.', 'lerm-admin-config' ),
					'fields'      => array(
						array(
							'id'                => 'featured_story_pack',
							'type'              => 'ajax_select',
							'source'            => 'theme_story_packs',
							'label'             => __( 'Featured Story Pack', 'lerm-admin-config' ),
							'description'       => __( 'Search a curated list of story packs through the same embedded AJAX data-source layer.', 'lerm-admin-config' ),
							'placeholder'       => __( 'Search story packs...', 'lerm-admin-config' ),
							'min_search_length' => 1,
							'per_page'          => 4,
							'default'           => 'editorial-weekender',
						),
						array(
							'id'          => 'hero_accordion',
							'type'        => 'accordion',
							'label'       => __( 'Hero Panels', 'lerm-admin-config' ),
							'description' => __( 'Structured hero copy panels stored in the same theme option.', 'lerm-admin-config' ),
							'items'       => array(
								array(
									'id'          => 'intro',
									'title'       => __( 'Intro', 'lerm-admin-config' ),
									'description' => __( 'Primary hero copy.', 'lerm-admin-config' ),
									'open'        => true,
									'fields'      => array(
										array(
											'id'      => 'eyebrow',
											'type'    => 'text',
											'label'   => __( 'Eyebrow', 'lerm-admin-config' ),
											'default' => __( 'Featured layout', 'lerm-admin-config' ),
										),
										array(
											'id'      => 'headline',
											'type'    => 'text',
											'label'   => __( 'Headline', 'lerm-admin-config' ),
											'default' => __( 'Build your wp-admin settings from schema', 'lerm-admin-config' ),
										),
										array(
											'id'      => 'summary',
											'type'    => 'textarea',
											'label'   => __( 'Summary', 'lerm-admin-config' ),
											'default' => __( 'This embedded example lives in a theme, but uses the same runtime, field registry, and store adapters as the standalone plugin mode.', 'lerm-admin-config' ),
										),
									),
								),
								array(
									'id'          => 'actions',
									'title'       => __( 'Actions', 'lerm-admin-config' ),
									'description' => __( 'CTA labels and emphasis.', 'lerm-admin-config' ),
									'fields'      => array(
										array(
											'id'      => 'primary_label',
											'type'    => 'text',
											'label'   => __( 'Primary Label', 'lerm-admin-config' ),
											'default' => __( 'Explore stories', 'lerm-admin-config' ),
										),
										array(
											'id'      => 'secondary_label',
											'type'    => 'text',
											'label'   => __( 'Secondary Label', 'lerm-admin-config' ),
											'default' => __( 'Open archive', 'lerm-admin-config' ),
										),
										array(
											'id'      => 'highlight',
											'type'    => 'switcher',
											'label'   => __( 'Highlight buttons', 'lerm-admin-config' ),
											'default' => 1,
										),
									),
								),
							),
							'default'     => array(
								'intro'   => array(
									'eyebrow'  => __( 'Featured layout', 'lerm-admin-config' ),
									'headline' => __( 'Build your wp-admin settings from schema', 'lerm-admin-config' ),
									'summary'  => __( 'This embedded example lives in a theme, but uses the same runtime, field registry, and store adapters as the standalone plugin mode.', 'lerm-admin-config' ),
								),
								'actions' => array(
									'primary_label'   => __( 'Explore stories', 'lerm-admin-config' ),
									'secondary_label' => __( 'Open archive', 'lerm-admin-config' ),
									'highlight'       => 1,
								),
							),
						),
					),
				),
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function metabox_schema(): array {
		return array(
			'id'        => 'acme-theme-hero-metabox',
			'title'     => __( 'Hero Overrides', 'lerm-admin-config' ),
			'container' => array(
				'type'       => 'metabox',
				'title'      => __( 'Hero Overrides', 'lerm-admin-config' ),
				'post_types' => array( 'page', 'post' ),
				'context'    => 'side',
				'priority'   => 'default',
				'capability' => 'edit_post',
			),
			'store'     => array(
				'type' => 'post_meta',
				'key'  => '_acme_theme_hero_overrides',
			),
			'sections'  => array(
				'hero' => array(
					'title'       => __( 'Hero', 'lerm-admin-config' ),
					'description' => __( 'Per-post overrides powered by the same embedded runtime.', 'lerm-admin-config' ),
					'fields'      => array(
						array(
							'id'      => 'hero_badge',
							'type'    => 'text',
							'label'   => __( 'Hero Badge', 'lerm-admin-config' ),
							'default' => '',
						),
						array(
							'id'      => 'hero_icon',
							'type'    => 'icon',
							'label'   => __( 'Hero Icon', 'lerm-admin-config' ),
							'choices' => array(
								'dashicons-format-aside' => __( 'Aside', 'lerm-admin-config' ),
								'dashicons-format-image' => __( 'Image', 'lerm-admin-config' ),
								'dashicons-format-video' => __( 'Video', 'lerm-admin-config' ),
								'dashicons-megaphone'    => __( 'Announcement', 'lerm-admin-config' ),
							),
							'default' => 'dashicons-format-aside',
						),
						array(
							'id'      => 'hero_accent',
							'type'    => 'color',
							'label'   => __( 'Hero Accent', 'lerm-admin-config' ),
							'default' => '#0ea5e9',
						),
					),
				),
			),
		);
	}

	private static function register_data_sources( Runtime $runtime ): void {
		if ( $runtime->has_data_source( 'theme_story_packs' ) ) {
			return;
		}

		$runtime->register_data_source(
			'theme_story_packs',
			static function ( array $args = array() ): array {
				$catalog  = EmbeddedThemeDemo::story_pack_items();
				$search   = strtolower( trim( (string) ( $args['search'] ?? '' ) ) );
				$page     = max( 1, (int) ( $args['page'] ?? 1 ) );
				$per_page = max( 1, (int) ( $args['per_page'] ?? 5 ) );

				$filtered = array_values(
					array_filter(
						$catalog,
						static function ( array $item ) use ( $search ): bool {
							if ( '' === $search ) {
								return true;
							}

							$haystack = strtolower( trim( (string) ( $item['label'] ?? '' ) . ' ' . (string) ( $item['value'] ?? '' ) ) );

							return str_contains( $haystack, $search );
						}
					)
				);

				$offset = ( $page - 1 ) * $per_page;

				return array(
					'items' => array_slice( $filtered, $offset, $per_page ),
					'more'  => count( $filtered ) > $offset + $per_page,
				);
			}
		);
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	private static function story_pack_items(): array {
		return array(
			array(
				'value' => 'editorial-weekender',
				'label' => __( 'Editorial Weekender', 'lerm-admin-config' ),
			),
			array(
				'value' => 'makers-notebook',
				'label' => __( 'Makers Notebook', 'lerm-admin-config' ),
			),
			array(
				'value' => 'culture-briefing',
				'label' => __( 'Culture Briefing', 'lerm-admin-config' ),
			),
			array(
				'value' => 'field-report',
				'label' => __( 'Field Report', 'lerm-admin-config' ),
			),
			array(
				'value' => 'sound-diary',
				'label' => __( 'Sound Diary', 'lerm-admin-config' ),
			),
			array(
				'value' => 'city-notes',
				'label' => __( 'City Notes', 'lerm-admin-config' ),
			),
		);
	}
}
