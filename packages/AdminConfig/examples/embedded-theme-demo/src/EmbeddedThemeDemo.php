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
			'title'     => __( 'Theme Style Kit', 'lerm' ),
			'container' => array(
				'type' => 'options_page',
			),
			'store'     => array(
				'type' => 'option',
				'key'  => 'acme_theme_style_kit',
			),
			'menu'      => array(
				'parent_slug' => 'themes.php',
				'page_title'  => __( 'Theme Style Kit', 'lerm' ),
				'menu_title'  => __( 'Theme Style Kit', 'lerm' ),
				'capability'  => 'edit_theme_options',
			),
			'view'      => array(
				'eyebrow'     => __( 'Embedded Mode', 'lerm' ),
				'title'       => __( 'Theme Style Kit', 'lerm' ),
				'description' => __( 'A theme-owned example showing advanced fields and reusable storage through the embedded runtime.', 'lerm' ),
				'debug'       => true,
			),
			'sections'  => array(
				'brand' => array(
					'title'       => __( 'Brand', 'lerm' ),
					'description' => __( 'Global style tokens compiled from one schema.', 'lerm' ),
					'fields'      => array(
						array(
							'id'             => 'headline_typography',
							'type'           => 'typography',
							'label'          => __( 'Headline Typography', 'lerm' ),
							'description'    => __( 'Controls used by hero and archive headings.', 'lerm' ),
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
							'label'       => __( 'Feature Icon', 'lerm' ),
							'description' => __( 'Shared icon used by theme promo blocks.', 'lerm' ),
							'choices'     => array(
								'dashicons-lightbulb'         => __( 'Idea', 'lerm' ),
								'dashicons-admin-customizer'  => __( 'Customizer', 'lerm' ),
								'dashicons-format-image'      => __( 'Gallery', 'lerm' ),
								'dashicons-chart-bar'         => __( 'Analytics', 'lerm' ),
								'dashicons-star-filled'       => __( 'Featured', 'lerm' ),
							),
							'default'     => 'dashicons-admin-customizer',
						),
						array(
							'id'          => 'surface_tabs',
							'type'        => 'tabbed',
							'label'       => __( 'Surface Recipes', 'lerm' ),
							'description' => __( 'Different card recipes kept in one option payload.', 'lerm' ),
							'default_tab' => 'default',
							'items'       => array(
								array(
									'id'          => 'default',
									'title'       => __( 'Default Surface', 'lerm' ),
									'description' => __( 'Base card colors and copy.', 'lerm' ),
									'fields'      => array(
										array(
											'id'      => 'title',
											'type'    => 'text',
											'label'   => __( 'Title', 'lerm' ),
											'default' => __( 'Clean editorial card', 'lerm' ),
										),
										array(
											'id'      => 'body',
											'type'    => 'textarea',
											'label'   => __( 'Body', 'lerm' ),
											'default' => __( 'Use for standard archive highlights and feature callouts.', 'lerm' ),
										),
										array(
											'id'      => 'accent',
											'type'    => 'color',
											'label'   => __( 'Accent', 'lerm' ),
											'default' => '#14b8a6',
										),
									),
								),
								array(
									'id'          => 'featured',
									'title'       => __( 'Featured Surface', 'lerm' ),
									'description' => __( 'Higher contrast variant for promoted content.', 'lerm' ),
									'fields'      => array(
										array(
											'id'      => 'title',
											'type'    => 'text',
											'label'   => __( 'Title', 'lerm' ),
											'default' => __( 'Promoted story card', 'lerm' ),
										),
										array(
											'id'      => 'body',
											'type'    => 'textarea',
											'label'   => __( 'Body', 'lerm' ),
											'default' => __( 'Use on the homepage and category headers when content needs extra emphasis.', 'lerm' ),
										),
										array(
											'id'      => 'accent',
											'type'    => 'color',
											'label'   => __( 'Accent', 'lerm' ),
											'default' => '#f97316',
										),
									),
								),
							),
							'default'     => array(
								'default'  => array(
									'title'  => __( 'Clean editorial card', 'lerm' ),
									'body'   => __( 'Use for standard archive highlights and feature callouts.', 'lerm' ),
									'accent' => '#14b8a6',
								),
								'featured' => array(
									'title'  => __( 'Promoted story card', 'lerm' ),
									'body'   => __( 'Use on the homepage and category headers when content needs extra emphasis.', 'lerm' ),
									'accent' => '#f97316',
								),
							),
						),
					),
				),
				'hero'  => array(
					'title'       => __( 'Hero Content', 'lerm' ),
					'description' => __( 'Panel-based content blocks for the homepage hero.', 'lerm' ),
					'fields'      => array(
						array(
							'id'                => 'featured_story_pack',
							'type'              => 'ajax_select',
							'source'            => 'theme_story_packs',
							'label'             => __( 'Featured Story Pack', 'lerm' ),
							'description'       => __( 'Search a curated list of story packs through the same embedded AJAX data-source layer.', 'lerm' ),
							'placeholder'       => __( 'Search story packs...', 'lerm' ),
							'min_search_length' => 1,
							'per_page'          => 4,
							'default'           => 'editorial-weekender',
						),
						array(
							'id'          => 'hero_accordion',
							'type'        => 'accordion',
							'label'       => __( 'Hero Panels', 'lerm' ),
							'description' => __( 'Structured hero copy panels stored in the same theme option.', 'lerm' ),
							'items'       => array(
								array(
									'id'          => 'intro',
									'title'       => __( 'Intro', 'lerm' ),
									'description' => __( 'Primary hero copy.', 'lerm' ),
									'open'        => true,
									'fields'      => array(
										array(
											'id'      => 'eyebrow',
											'type'    => 'text',
											'label'   => __( 'Eyebrow', 'lerm' ),
											'default' => __( 'Featured layout', 'lerm' ),
										),
										array(
											'id'      => 'headline',
											'type'    => 'text',
											'label'   => __( 'Headline', 'lerm' ),
											'default' => __( 'Build your wp-admin settings from schema', 'lerm' ),
										),
										array(
											'id'      => 'summary',
											'type'    => 'textarea',
											'label'   => __( 'Summary', 'lerm' ),
											'default' => __( 'This embedded example lives in a theme, but uses the same runtime, field registry, and store adapters as the standalone plugin mode.', 'lerm' ),
										),
									),
								),
								array(
									'id'          => 'actions',
									'title'       => __( 'Actions', 'lerm' ),
									'description' => __( 'CTA labels and emphasis.', 'lerm' ),
									'fields'      => array(
										array(
											'id'      => 'primary_label',
											'type'    => 'text',
											'label'   => __( 'Primary Label', 'lerm' ),
											'default' => __( 'Explore stories', 'lerm' ),
										),
										array(
											'id'      => 'secondary_label',
											'type'    => 'text',
											'label'   => __( 'Secondary Label', 'lerm' ),
											'default' => __( 'Open archive', 'lerm' ),
										),
										array(
											'id'      => 'highlight',
											'type'    => 'switcher',
											'label'   => __( 'Highlight buttons', 'lerm' ),
											'default' => 1,
										),
									),
								),
							),
							'default'     => array(
								'intro'   => array(
									'eyebrow'  => __( 'Featured layout', 'lerm' ),
									'headline' => __( 'Build your wp-admin settings from schema', 'lerm' ),
									'summary'  => __( 'This embedded example lives in a theme, but uses the same runtime, field registry, and store adapters as the standalone plugin mode.', 'lerm' ),
								),
								'actions' => array(
									'primary_label'   => __( 'Explore stories', 'lerm' ),
									'secondary_label' => __( 'Open archive', 'lerm' ),
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
			'title'     => __( 'Hero Overrides', 'lerm' ),
			'container' => array(
				'type'       => 'metabox',
				'title'      => __( 'Hero Overrides', 'lerm' ),
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
					'title'       => __( 'Hero', 'lerm' ),
					'description' => __( 'Per-post overrides powered by the same embedded runtime.', 'lerm' ),
					'fields'      => array(
						array(
							'id'      => 'hero_badge',
							'type'    => 'text',
							'label'   => __( 'Hero Badge', 'lerm' ),
							'default' => '',
						),
						array(
							'id'      => 'hero_icon',
							'type'    => 'icon',
							'label'   => __( 'Hero Icon', 'lerm' ),
							'choices' => array(
								'dashicons-format-aside' => __( 'Aside', 'lerm' ),
								'dashicons-format-image' => __( 'Image', 'lerm' ),
								'dashicons-format-video' => __( 'Video', 'lerm' ),
								'dashicons-megaphone'    => __( 'Announcement', 'lerm' ),
							),
							'default' => 'dashicons-format-aside',
						),
						array(
							'id'      => 'hero_accent',
							'type'    => 'color',
							'label'   => __( 'Hero Accent', 'lerm' ),
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
				'label' => __( 'Editorial Weekender', 'lerm' ),
			),
			array(
				'value' => 'makers-notebook',
				'label' => __( 'Makers Notebook', 'lerm' ),
			),
			array(
				'value' => 'culture-briefing',
				'label' => __( 'Culture Briefing', 'lerm' ),
			),
			array(
				'value' => 'field-report',
				'label' => __( 'Field Report', 'lerm' ),
			),
			array(
				'value' => 'sound-diary',
				'label' => __( 'Sound Diary', 'lerm' ),
			),
			array(
				'value' => 'city-notes',
				'label' => __( 'City Notes', 'lerm' ),
			),
		);
	}
}
