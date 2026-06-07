<?php
/**
 * Demo schemas for the Admin Config example plugin.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Examples;

use Lerm\AdminConfig\WordPress\Runtime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SchemaDemoPlugin {

	public static function register( Runtime $runtime ): void {
		DemoExtensions::register( $runtime );

		if ( ! $runtime->has( 'acme-demo-settings' ) ) {
			$runtime->register( self::settings_schema( $runtime ) );
		}

		if ( ! $runtime->has( 'acme-demo-post-metabox' ) ) {
			$runtime->register( self::post_metabox_schema() );
		}

		if ( ! $runtime->has( 'acme-demo-comment' ) ) {
			$runtime->register( self::comment_schema() );
		}

		if ( ! $runtime->has( 'acme-demo-profile' ) ) {
			$runtime->register( self::profile_schema( $runtime ) );
		}

		if ( ! $runtime->has( 'acme-demo-taxonomy' ) ) {
			$runtime->register( self::taxonomy_schema( $runtime ) );
		}

		if ( ! $runtime->has( 'acme-demo-block-editor-panel' ) ) {
			$runtime->register( self::block_editor_panel_schema() );
		}

		if ( is_multisite() && ! $runtime->has( 'acme-demo-network-settings' ) ) {
			$runtime->register( self::network_schema() );
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function settings_schema( Runtime $runtime ): array {
		return array(
			'id'        => 'acme-demo-settings',
			'title'     => __( 'Admin Config Demo', 'lerm-admin-config-demo' ),
			'container' => array(
				'type' => 'options_page',
			),
			'store'     => array(
				'type' => 'option',
				'key'  => 'acme_demo_settings',
			),
			'menu'      => array(
				'parent_slug' => 'options-general.php',
				'page_title'  => __( 'Admin Config Demo', 'lerm-admin-config-demo' ),
				'menu_title'  => __( 'Admin Config Demo', 'lerm-admin-config-demo' ),
				'capability'  => 'manage_options',
			),
			'view'      => array(
				'eyebrow'     => __( 'Demo plugin', 'lerm-admin-config-demo' ),
				'title'       => __( 'Admin Config Demo', 'lerm-admin-config-demo' ),
				'description' => __( 'A reference plugin showing the same schema runtime driving different WordPress admin surfaces.', 'lerm-admin-config-demo' ),
				'debug'       => true,
			),
			'sections'  => array(
				'general'    => array(
					'title'       => __( 'General', 'lerm-admin-config-demo' ),
					'description' => __( 'Simple site-level options rendered by the shared runtime.', 'lerm-admin-config-demo' ),
					'fields'      => array(
						array(
							'id'          => 'feature_enabled',
							'type'        => 'switcher',
							'label'       => __( 'Enable demo feature', 'lerm-admin-config-demo' ),
							'description' => __( 'Turns the demo feature on or off.', 'lerm-admin-config-demo' ),
							'default'     => 1,
						),
						array(
							'id'          => 'accent_color',
							'type'        => 'color',
							'label'       => __( 'Accent color', 'lerm-admin-config-demo' ),
							'description' => __( 'Used by the demo front-end integration.', 'lerm-admin-config-demo' ),
							'default'     => '#2271b1',
						),
						array(
							'id'          => 'layout_style',
							'type'        => 'button_set',
							'label'       => __( 'Layout style', 'lerm-admin-config-demo' ),
							'description' => __( 'A simple choice field to demonstrate typed schema options.', 'lerm-admin-config-demo' ),
							'choices'     => array(
								'compact' => __( 'Compact', 'lerm-admin-config-demo' ),
								'card'    => __( 'Card', 'lerm-admin-config-demo' ),
								'split'   => __( 'Split', 'lerm-admin-config-demo' ),
							),
							'default'     => 'compact',
						),
						array(
							'id'          => 'tone_preset',
							'type'        => 'select',
							'label'       => __( 'Tone preset', 'lerm-admin-config-demo' ),
							'description' => __( 'Choices resolved from a runtime data source before schema registration.', 'lerm-admin-config-demo' ),
							'choices'     => $runtime->resolve_data_source( 'tone_presets', array( 'experimental' => true ) ),
							'default'     => 'calm',
						),
					),
				),
				'extensions' => array(
					'title'       => __( 'Extension API', 'lerm-admin-config-demo' ),
					'description' => __( 'This section is wired through the public runtime extension methods.', 'lerm-admin-config-demo' ),
					'fields'      => array(
						array(
							'id'          => 'release_slug',
							'type'        => 'slug_text',
							'label'       => __( 'Release slug', 'lerm-admin-config-demo' ),
							'description' => __( 'Custom field type registered by the demo plugin. Values are sanitized into slugs and validated for length.', 'lerm-admin-config-demo' ),
							'default'     => 'spring-launch',
							'placeholder' => 'spring-launch',
						),
						array(
							'id'                => 'featured_campaign',
							'type'              => 'ajax_select',
							'source'            => 'campaign_library',
							'label'             => __( 'Featured Campaign', 'lerm-admin-config-demo' ),
							'description'       => __( 'Search the shared campaign library through the AJAX-backed data-source transport.', 'lerm-admin-config-demo' ),
							'placeholder'       => __( 'Search campaigns...', 'lerm-admin-config-demo' ),
							'min_search_length' => 1,
							'per_page'          => 4,
							'default'           => 'spring-launch',
						),
						array(
							'id'                => 'supporting_campaigns',
							'type'              => 'ajax_select',
							'source'            => 'campaign_library',
							'multiple'          => true,
							'label'             => __( 'Supporting Campaigns', 'lerm-admin-config-demo' ),
							'description'       => __( 'Multi-select hydration and paging use the same runtime resolver and transport layer.', 'lerm-admin-config-demo' ),
							'placeholder'       => __( 'Search campaigns...', 'lerm-admin-config-demo' ),
							'min_search_length' => 1,
							'per_page'          => 4,
							'default'           => array( 'creator-series', 'audio-week' ),
						),
					),
				),
				'advanced'   => array(
					'title'       => __( 'Advanced Fields', 'lerm-admin-config-demo' ),
					'description' => __( 'Typography, icon selection, accordion panels, and tabbed content all come from the same schema runtime.', 'lerm-admin-config-demo' ),
					'fields'      => array(
						array(
							'id'             => 'brand_typography',
							'type'           => 'typography',
							'label'          => __( 'Brand Typography', 'lerm-admin-config-demo' ),
							'description'    => __( 'Shared heading style values compiled from PHP defaults and rendered through the advanced module.', 'lerm-admin-config-demo' ),
							'style'          => true,
							'letter_spacing' => true,
							'align'          => true,
							'default'        => array(
								'font-family'    => 'Inter, system-ui, sans-serif',
								'font-weight'    => '700',
								'font-style'     => 'normal',
								'font-size'      => '2.25',
								'unit'           => 'rem',
								'line-height'    => '1.15',
								'letter-spacing' => '0',
								'text-align'     => 'left',
								'color'          => '#0f172a',
							),
						),
						array(
							'id'          => 'feature_icon',
							'type'        => 'icon',
							'label'       => __( 'Feature Icon', 'lerm-admin-config-demo' ),
							'description' => __( 'A curated Dashicons picker rendered by the advanced fields module.', 'lerm-admin-config-demo' ),
							'choices'     => array(
								'dashicons-lightbulb'    => __( 'Idea', 'lerm-admin-config-demo' ),
								'dashicons-megaphone'    => __( 'Launch', 'lerm-admin-config-demo' ),
								'dashicons-chart-bar'    => __( 'Analytics', 'lerm-admin-config-demo' ),
								'dashicons-format-image' => __( 'Visual', 'lerm-admin-config-demo' ),
								'dashicons-star-filled'  => __( 'Featured', 'lerm-admin-config-demo' ),
								'dashicons-admin-site-alt3' => __( 'Site', 'lerm-admin-config-demo' ),
							),
							'default'     => 'dashicons-lightbulb',
						),
						array(
							'id'          => 'launch_accordion',
							'type'        => 'accordion',
							'label'       => __( 'Launch Accordion', 'lerm-admin-config-demo' ),
							'description' => __( 'Panel-based fields for messaging and CTA tuning.', 'lerm-admin-config-demo' ),
							'items'       => array(
								array(
									'id'          => 'intro',
									'title'       => __( 'Intro Panel', 'lerm-admin-config-demo' ),
									'description' => __( 'Copy used by the hero intro block.', 'lerm-admin-config-demo' ),
									'open'        => true,
									'fields'      => array(
										array(
											'id'      => 'eyebrow',
											'type'    => 'text',
											'label'   => __( 'Eyebrow', 'lerm-admin-config-demo' ),
											'default' => __( 'New release', 'lerm-admin-config-demo' ),
										),
										array(
											'id'      => 'headline',
											'type'    => 'text',
											'label'   => __( 'Headline', 'lerm-admin-config-demo' ),
											'default' => __( 'Schema-driven admin screens', 'lerm-admin-config-demo' ),
										),
										array(
											'id'      => 'summary',
											'type'    => 'textarea',
											'label'   => __( 'Summary', 'lerm-admin-config-demo' ),
											'default' => __( 'Use one PHP schema to drive defaults, UI, dependencies, and storage across multiple wp-admin surfaces.', 'lerm-admin-config-demo' ),
										),
									),
								),
								array(
									'id'          => 'cta',
									'title'       => __( 'CTA Panel', 'lerm-admin-config-demo' ),
									'description' => __( 'Controls used by the action area.', 'lerm-admin-config-demo' ),
									'fields'      => array(
										array(
											'id'      => 'button_label',
											'type'    => 'text',
											'label'   => __( 'Button Label', 'lerm-admin-config-demo' ),
											'default' => __( 'Try the demo', 'lerm-admin-config-demo' ),
										),
										array(
											'id'      => 'button_style',
											'type'    => 'button_set',
											'label'   => __( 'Button Style', 'lerm-admin-config-demo' ),
											'choices' => array(
												'filled'  => __( 'Filled', 'lerm-admin-config-demo' ),
												'outline' => __( 'Outline', 'lerm-admin-config-demo' ),
												'ghost'   => __( 'Ghost', 'lerm-admin-config-demo' ),
											),
											'default' => 'filled',
										),
										array(
											'id'      => 'accent',
											'type'    => 'color',
											'label'   => __( 'Accent', 'lerm-admin-config-demo' ),
											'default' => '#0ea5e9',
										),
									),
								),
							),
							'default'     => array(
								'intro' => array(
									'eyebrow'  => __( 'New release', 'lerm-admin-config-demo' ),
									'headline' => __( 'Schema-driven admin screens', 'lerm-admin-config-demo' ),
									'summary'  => __( 'Use one PHP schema to drive defaults, UI, dependencies, and storage across multiple wp-admin surfaces.', 'lerm-admin-config-demo' ),
								),
								'cta'   => array(
									'button_label' => __( 'Try the demo', 'lerm-admin-config-demo' ),
									'button_style' => 'filled',
									'accent'       => '#0ea5e9',
								),
							),
						),
						array(
							'id'          => 'card_tabs',
							'type'        => 'tabbed',
							'label'       => __( 'Card Tabs', 'lerm-admin-config-demo' ),
							'description' => __( 'Tabbed panels for alternate content recipes in the same settings payload.', 'lerm-admin-config-demo' ),
							'default_tab' => 'primary',
							'items'       => array(
								array(
									'id'          => 'primary',
									'title'       => __( 'Primary Card', 'lerm-admin-config-demo' ),
									'description' => __( 'Main marketing card copy.', 'lerm-admin-config-demo' ),
									'fields'      => array(
										array(
											'id'      => 'title',
											'type'    => 'text',
											'label'   => __( 'Title', 'lerm-admin-config-demo' ),
											'default' => __( 'Fast setup', 'lerm-admin-config-demo' ),
										),
										array(
											'id'      => 'body',
											'type'    => 'textarea',
											'label'   => __( 'Body', 'lerm-admin-config-demo' ),
											'default' => __( 'Plugin authors can install the package directly and start registering schemas immediately.', 'lerm-admin-config-demo' ),
										),
									),
								),
								array(
									'id'          => 'secondary',
									'title'       => __( 'Secondary Card', 'lerm-admin-config-demo' ),
									'description' => __( 'Theme-bundled embedded mode copy.', 'lerm-admin-config-demo' ),
									'fields'      => array(
										array(
											'id'      => 'title',
											'type'    => 'text',
											'label'   => __( 'Title', 'lerm-admin-config-demo' ),
											'default' => __( 'Embedded runtime', 'lerm-admin-config-demo' ),
										),
										array(
											'id'      => 'body',
											'type'    => 'textarea',
											'label'   => __( 'Body', 'lerm-admin-config-demo' ),
											'default' => __( 'Theme authors can ship the same runtime inside a package or Composer dependency without exposing framework internals.', 'lerm-admin-config-demo' ),
										),
									),
								),
							),
							'default'     => array(
								'primary'   => array(
									'title' => __( 'Fast setup', 'lerm-admin-config-demo' ),
									'body'  => __( 'Plugin authors can install the package directly and start registering schemas immediately.', 'lerm-admin-config-demo' ),
								),
								'secondary' => array(
									'title' => __( 'Embedded runtime', 'lerm-admin-config-demo' ),
									'body'  => __( 'Theme authors can ship the same runtime inside a package or Composer dependency without exposing framework internals.', 'lerm-admin-config-demo' ),
								),
							),
						),
					),
				),
				'tools'      => array(
					'title'       => __( 'Tools', 'lerm-admin-config-demo' ),
					'description' => __( 'Shared backup/import controls also work for non-theme plugin settings.', 'lerm-admin-config-demo' ),
					'fields'      => array(
						array(
							'id'          => 'backup',
							'type'        => 'backup_tools',
							'label'       => __( 'Backup', 'lerm-admin-config-demo' ),
							'description' => __( 'Export or import the demo plugin settings payload.', 'lerm-admin-config-demo' ),
							'save'        => false,
						),
					),
				),
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function post_metabox_schema(): array {
		return array(
			'id'        => 'acme-demo-post-metabox',
			'title'     => __( 'Entry Display Overrides', 'lerm-admin-config-demo' ),
			'container' => array(
				'type'       => 'metabox',
				'title'      => __( 'Entry Display Overrides', 'lerm-admin-config-demo' ),
				'post_types' => array( 'post', 'page' ),
				'context'    => 'side',
				'priority'   => 'default',
				'capability' => 'edit_post',
			),
			'store'     => array(
				'type' => 'post_meta',
				'key'  => '_acme_demo_entry_settings',
			),
			'sections'  => array(
				'display' => array(
					'title'       => __( 'Display', 'lerm-admin-config-demo' ),
					'description' => __( 'Per-entry overrides stored through the shared post meta adapter.', 'lerm-admin-config-demo' ),
					'fields'      => array(
						array(
							'id'          => 'featured_entry',
							'type'        => 'switcher',
							'label'       => __( 'Feature this entry', 'lerm-admin-config-demo' ),
							'description' => __( 'A simple post-meta flag handled by the metabox container.', 'lerm-admin-config-demo' ),
							'default'     => 0,
						),
						array(
							'id'          => 'entry_slug',
							'type'        => 'slug_text',
							'label'       => __( 'Entry slug', 'lerm-admin-config-demo' ),
							'description' => __( 'A root-level custom text field used by the block editor panel validation flow.', 'lerm-admin-config-demo' ),
							'default'     => 'featured-entry',
							'placeholder' => 'featured-entry',
						),
						array(
							'id'          => 'entry_layout',
							'type'        => 'select',
							'label'       => __( 'Entry layout', 'lerm-admin-config-demo' ),
							'description' => __( 'A simple select field rendered by the block editor panel.', 'lerm-admin-config-demo' ),
							'choices'     => array(
								'compact' => __( 'Compact', 'lerm-admin-config-demo' ),
								'feature' => __( 'Feature', 'lerm-admin-config-demo' ),
								'wide'    => __( 'Wide', 'lerm-admin-config-demo' ),
							),
							'default'     => 'compact',
						),
						array(
							'id'          => 'entry_format',
							'type'        => 'radio',
							'label'       => __( 'Entry format', 'lerm-admin-config-demo' ),
							'description' => __( 'A simple radio field rendered by the block editor panel.', 'lerm-admin-config-demo' ),
							'choices'     => array(
								'standard'  => __( 'Standard', 'lerm-admin-config-demo' ),
								'editorial' => __( 'Editorial', 'lerm-admin-config-demo' ),
								'alert'     => __( 'Alert', 'lerm-admin-config-demo' ),
							),
							'default'     => 'standard',
						),
						array(
							'id'          => 'entry_emphasis',
							'type'        => 'button_set',
							'label'       => __( 'Entry emphasis', 'lerm-admin-config-demo' ),
							'description' => __( 'A compact button-set choice field rendered by the block editor panel.', 'lerm-admin-config-demo' ),
							'choices'     => array(
								'normal'    => __( 'Normal', 'lerm-admin-config-demo' ),
								'spotlight' => __( 'Spotlight', 'lerm-admin-config-demo' ),
								'quiet'     => __( 'Quiet', 'lerm-admin-config-demo' ),
							),
							'default'     => 'normal',
						),
						array(
							'id'          => 'entry_accent',
							'type'        => 'color',
							'label'       => __( 'Entry accent', 'lerm-admin-config-demo' ),
							'description' => __( 'A color value rendered by the block editor panel.', 'lerm-admin-config-demo' ),
							'default'     => '#2271b1',
						),
						array(
							'id'          => 'entry_review_date',
							'type'        => 'date',
							'label'       => __( 'Entry review date', 'lerm-admin-config-demo' ),
							'description' => __( 'A date value rendered by the block editor panel.', 'lerm-admin-config-demo' ),
							'default'     => '2026-04-26',
						),
						array(
							'id'          => 'entry_priority',
							'type'        => 'slider',
							'label'       => __( 'Entry priority', 'lerm-admin-config-demo' ),
							'description' => __( 'A range value rendered by the block editor panel.', 'lerm-admin-config-demo' ),
							'min'         => 1,
							'max'         => 5,
							'step'        => 1,
							'default'     => 3,
						),
						array(
							'id'          => 'entry_score',
							'type'        => 'spinner',
							'label'       => __( 'Entry score', 'lerm-admin-config-demo' ),
							'description' => __( 'A numeric spinner value rendered by the block editor panel.', 'lerm-admin-config-demo' ),
							'min'         => 0,
							'max'         => 10,
							'step'        => 1,
							'default'     => 2,
						),
						array(
							'id'          => 'entry_channels',
							'type'        => 'checkbox_list',
							'label'       => __( 'Entry channels', 'lerm-admin-config-demo' ),
							'description' => __( 'Multi-value choices rendered by the block editor panel.', 'lerm-admin-config-demo' ),
							'choices'     => array(
								'homepage'   => __( 'Homepage', 'lerm-admin-config-demo' ),
								'newsletter' => __( 'Newsletter', 'lerm-admin-config-demo' ),
								'rss'        => __( 'RSS', 'lerm-admin-config-demo' ),
							),
							'default'     => array( 'homepage' ),
						),
						array(
							'id'          => 'entry_upload',
							'type'        => 'upload',
							'label'       => __( 'Entry upload', 'lerm-admin-config-demo' ),
							'description' => __( 'A URL-based upload field rendered by the block editor panel media picker.', 'lerm-admin-config-demo' ),
							'library'     => 'image',
							'button_text' => __( 'Choose uploaded file', 'lerm-admin-config-demo' ),
							'remove_text' => __( 'Remove file', 'lerm-admin-config-demo' ),
							'default'     => '',
						),
						array(
							'id'          => 'entry_media',
							'type'        => 'media',
							'label'       => __( 'Entry media', 'lerm-admin-config-demo' ),
							'description' => __( 'A single attachment field rendered by the block editor panel media picker.', 'lerm-admin-config-demo' ),
							'button_text' => __( 'Choose image', 'lerm-admin-config-demo' ),
							'remove_text' => __( 'Remove image', 'lerm-admin-config-demo' ),
							'default'     => array(),
						),
						array(
							'id'          => 'entry_gallery',
							'type'        => 'gallery',
							'label'       => __( 'Entry gallery', 'lerm-admin-config-demo' ),
							'description' => __( 'An ordered attachment list rendered by the block editor panel media picker.', 'lerm-admin-config-demo' ),
							'button_text' => __( 'Choose gallery images', 'lerm-admin-config-demo' ),
							'remove_text' => __( 'Clear gallery', 'lerm-admin-config-demo' ),
							'default'     => array(),
						),
						array(
							'id'          => 'entry_dimensions',
							'type'        => 'dimensions',
							'label'       => __( 'Entry card size', 'lerm-admin-config-demo' ),
							'description' => __( 'A nested dimensions object rendered by the block editor panel.', 'lerm-admin-config-demo' ),
							'units'       => array( 'px', '%', 'rem' ),
							'default'     => array(
								'width'  => '320',
								'height' => '180',
								'unit'   => 'px',
							),
						),
						array(
							'id'          => 'entry_spacing',
							'type'        => 'spacing',
							'label'       => __( 'Entry card spacing', 'lerm-admin-config-demo' ),
							'description' => __( 'A nested spacing object rendered by the block editor panel.', 'lerm-admin-config-demo' ),
							'units'       => array( 'px', 'rem' ),
							'default'     => array(
								'top'    => '8',
								'right'  => '12',
								'bottom' => '8',
								'left'   => '12',
								'unit'   => 'px',
							),
						),
						array(
							'id'          => 'entry_border',
							'type'        => 'border',
							'label'       => __( 'Entry card border', 'lerm-admin-config-demo' ),
							'description' => __( 'A composite border object rendered by the block editor panel.', 'lerm-admin-config-demo' ),
							'default'     => array(
								'top'    => '1',
								'right'  => '1',
								'bottom' => '1',
								'left'   => '1',
								'style'  => 'solid',
								'color'  => '#2271b1',
							),
						),
						array(
							'id'          => 'entry_link_colors',
							'type'        => 'link_color',
							'label'       => __( 'Entry link colors', 'lerm-admin-config-demo' ),
							'description' => __( 'Normal and hover link colors rendered by the block editor panel.', 'lerm-admin-config-demo' ),
							'default'     => array(
								'color' => '#2271b1',
								'hover' => '#135e96',
							),
						),
						array(
							'id'             => 'entry_typography',
							'type'           => 'typography',
							'label'          => __( 'Entry headline typography', 'lerm-admin-config-demo' ),
							'description'    => __( 'A typography object rendered by the block editor panel.', 'lerm-admin-config-demo' ),
							'style'          => true,
							'letter_spacing' => true,
							'align'          => true,
							'units'          => array( 'px', 'rem' ),
							'default'        => array(
								'font-family'    => 'Inter, system-ui, sans-serif',
								'font-weight'    => '700',
								'font-style'     => 'normal',
								'font-size'      => '2',
								'unit'           => 'rem',
								'line-height'    => '1.2',
								'letter-spacing' => '0',
								'text-align'     => 'left',
								'color'          => '#2271b1',
							),
						),
						array(
							'id'                           => 'entry_background',
							'type'                         => 'background',
							'label'                        => __( 'Entry background', 'lerm-admin-config-demo' ),
							'description'                  => __( 'A background object rendered by the block editor panel.', 'lerm-admin-config-demo' ),
							'background_gradient'          => true,
							'background_origin'            => true,
							'background_clip'              => true,
							'background_blend_mode'        => true,
							'background_image_button_text' => __( 'Choose background image', 'lerm-admin-config-demo' ),
							'default'                      => array(
								'background-color'      => '#f8fafc',
								'background-gradient-color' => '#e0f2fe',
								'background-gradient-direction' => 'to right',
								'background-image'      => array(),
								'background-position'   => 'center center',
								'background-repeat'     => 'no-repeat',
								'background-attachment' => 'scroll',
								'background-size'       => 'cover',
								'background-origin'     => 'padding-box',
								'background-clip'       => 'border-box',
								'background-blend-mode' => 'normal',
							),
						),
						array(
							'id'          => 'entry_palette',
							'type'        => 'palette',
							'label'       => __( 'Entry palette', 'lerm-admin-config-demo' ),
							'description' => __( 'A palette selector rendered by the block editor panel.', 'lerm-admin-config-demo' ),
							'choices'     => array(
								'cool' => array( '#0f172a', '#38bdf8', '#e0f2fe' ),
								'warm' => array( '#7c2d12', '#fb923c', '#fed7aa' ),
								'mono' => array( '#111827', '#6b7280', '#f9fafb' ),
							),
							'default'     => 'cool',
						),
						array(
							'id'          => 'entry_image_style',
							'type'        => 'image_select',
							'label'       => __( 'Entry image style', 'lerm-admin-config-demo' ),
							'description' => __( 'An image-backed choice selector rendered by the block editor panel.', 'lerm-admin-config-demo' ),
							'choices'     => array(
								'cover'  => 'https://example.test/admin-config-cover.png',
								'split'  => 'https://example.test/admin-config-split.png',
								'poster' => 'https://example.test/admin-config-poster.png',
							),
							'default'     => 'cover',
						),
						array(
							'id'                => 'entry_campaign',
							'type'              => 'ajax_select',
							'source'            => 'campaign_library',
							'label'             => __( 'Entry campaign', 'lerm-admin-config-demo' ),
							'description'       => __( 'An async campaign selector rendered through the block editor panel REST data-source endpoint.', 'lerm-admin-config-demo' ),
							'placeholder'       => __( 'Search campaigns...', 'lerm-admin-config-demo' ),
							'search_label'      => __( 'Search entry campaign', 'lerm-admin-config-demo' ),
							'min_search_length' => 1,
							'per_page'          => 4,
							'default'           => 'spring-launch',
						),
						array(
							'id'          => 'entry_links',
							'type'        => 'group',
							'label'       => __( 'Entry links', 'lerm-admin-config-demo' ),
							'description' => __( 'A repeatable group rendered by the block editor panel.', 'lerm-admin-config-demo' ),
							'fields'      => array(
								array(
									'id'      => 'label',
									'type'    => 'text',
									'label'   => __( 'Link label', 'lerm-admin-config-demo' ),
									'default' => __( 'Read more', 'lerm-admin-config-demo' ),
								),
								array(
									'id'      => 'url',
									'type'    => 'url',
									'label'   => __( 'Link URL', 'lerm-admin-config-demo' ),
									'default' => 'https://example.test/read-more',
								),
							),
							'default'     => array(
								array(
									'label' => __( 'Read more', 'lerm-admin-config-demo' ),
									'url'   => 'https://example.test/read-more',
								),
							),
						),
						array(
							'id'          => 'entry_icon',
							'type'        => 'icon',
							'label'       => __( 'Entry icon', 'lerm-admin-config-demo' ),
							'description' => __( 'A post-specific icon override rendered from the advanced field registry.', 'lerm-admin-config-demo' ),
							'choices'     => array(
								'dashicons-format-aside'   => __( 'Aside', 'lerm-admin-config-demo' ),
								'dashicons-star-filled'    => __( 'Featured', 'lerm-admin-config-demo' ),
								'dashicons-megaphone'      => __( 'Announcement', 'lerm-admin-config-demo' ),
								'dashicons-admin-site-alt' => __( 'Site', 'lerm-admin-config-demo' ),
							),
							'default'     => 'dashicons-format-aside',
						),
						array(
							'id'          => 'entry_badge',
							'type'        => 'fieldset',
							'label'       => __( 'Entry badge', 'lerm-admin-config-demo' ),
							'description' => __( 'Nested post-meta fields used to exercise fieldset rendering and validation inside the metabox flow.', 'lerm-admin-config-demo' ),
							'fields'      => array(
								array(
									'id'      => 'label',
									'type'    => 'text',
									'label'   => __( 'Label', 'lerm-admin-config-demo' ),
									'default' => __( 'Featured', 'lerm-admin-config-demo' ),
								),
								array(
									'id'          => 'slug',
									'type'        => 'slug_text',
									'label'       => __( 'Badge slug', 'lerm-admin-config-demo' ),
									'description' => __( 'Nested validation should still block the metabox save if this value is too short.', 'lerm-admin-config-demo' ),
									'default'     => 'featured-entry',
								),
							),
							'default'     => array(
								'label' => __( 'Featured', 'lerm-admin-config-demo' ),
								'slug'  => 'featured-entry',
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
	private static function block_editor_panel_schema(): array {
		return array(
			'id'        => 'acme-demo-block-editor-panel',
			'title'     => __( 'Demo Block Editor Panel', 'lerm-admin-config-demo' ),
			'container' => array(
				'type'       => 'block_editor_panel',
				'title'      => __( 'Demo Panel', 'lerm-admin-config-demo' ),
				'post_types' => array( 'post', 'page' ),
				'capability' => 'edit_post',
			),
			'store'     => array(
				'type' => 'post_meta',
				'key'  => '_acme_demo_block_panel_settings',
			),
			'sections'  => array(
				'settings' => array(
					'title'       => __( 'Panel Settings', 'lerm-admin-config-demo' ),
					'description' => __( 'A standalone block editor panel driven by the block_editor_panel container type. No classic metabox is registered — this schema only appears in the Gutenberg sidebar.', 'lerm-admin-config-demo' ),
					'fields'      => array(
						array(
							'id'          => 'panel_featured',
							'type'        => 'switcher',
							'label'       => __( 'Featured entry', 'lerm-admin-config-demo' ),
							'description' => __( 'Toggles the featured flag through the block editor panel.', 'lerm-admin-config-demo' ),
							'default'     => 0,
						),
						array(
							'id'          => 'panel_accent',
							'type'        => 'color',
							'label'       => __( 'Accent color', 'lerm-admin-config-demo' ),
							'description' => __( 'A color picker rendered inside the block editor panel.', 'lerm-admin-config-demo' ),
							'default'     => '#2271b1',
						),
						array(
							'id'          => 'panel_layout',
							'type'        => 'select',
							'label'       => __( 'Layout', 'lerm-admin-config-demo' ),
							'description' => __( 'A select control rendered inside the block editor panel.', 'lerm-admin-config-demo' ),
							'choices'     => array(
								'default' => __( 'Default', 'lerm-admin-config-demo' ),
								'wide'    => __( 'Wide', 'lerm-admin-config-demo' ),
								'full'    => __( 'Full width', 'lerm-admin-config-demo' ),
							),
							'default'     => 'default',
						),
					),
				),
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function comment_schema(): array {
		return array(
			'id'        => 'acme-demo-comment',
			'title'     => __( 'Comment Insight', 'lerm-admin-config-demo' ),
			'container' => array(
				'type'     => 'comment',
				'title'    => __( 'Comment Insight', 'lerm-admin-config-demo' ),
				'context'  => 'normal',
				'priority' => 'default',
			),
			'store'     => array(
				'type' => 'comment_meta',
				'key'  => 'acme_demo_comment_settings',
			),
			'sections'  => array(
				'moderation' => array(
					'title'       => __( 'Moderation', 'lerm-admin-config-demo' ),
					'description' => __( 'Admin-only metadata attached to the current comment.', 'lerm-admin-config-demo' ),
					'fields'      => array(
						array(
							'id'          => 'featured_reply',
							'type'        => 'switcher',
							'label'       => __( 'Featured reply', 'lerm-admin-config-demo' ),
							'description' => __( 'Highlights the comment in downstream presentation logic.', 'lerm-admin-config-demo' ),
							'default'     => 0,
						),
						array(
							'id'          => 'sentiment',
							'type'        => 'radio',
							'label'       => __( 'Sentiment', 'lerm-admin-config-demo' ),
							'description' => __( 'A simple moderation classification.', 'lerm-admin-config-demo' ),
							'choices'     => array(
								'neutral'  => __( 'Neutral', 'lerm-admin-config-demo' ),
								'positive' => __( 'Positive', 'lerm-admin-config-demo' ),
								'negative' => __( 'Negative', 'lerm-admin-config-demo' ),
							),
							'default'     => 'neutral',
						),
						array(
							'id'          => 'staff_note',
							'type'        => 'textarea',
							'label'       => __( 'Staff note', 'lerm-admin-config-demo' ),
							'description' => __( 'Internal note saved into comment meta.', 'lerm-admin-config-demo' ),
							'default'     => '',
						),
						array(
							'id'          => 'review_badge',
							'type'        => 'fieldset',
							'label'       => __( 'Review badge', 'lerm-admin-config-demo' ),
							'description' => __( 'Nested comment-meta fields that reuse the same schema compiler and validator pipeline.', 'lerm-admin-config-demo' ),
							'fields'      => array(
								array(
									'id'      => 'label',
									'type'    => 'text',
									'label'   => __( 'Label', 'lerm-admin-config-demo' ),
									'default' => __( 'Staff pick', 'lerm-admin-config-demo' ),
								),
								array(
									'id'          => 'slug',
									'type'        => 'slug_text',
									'label'       => __( 'Badge slug', 'lerm-admin-config-demo' ),
									'description' => __( 'Use an invalid short slug to verify nested validation on the comment screen.', 'lerm-admin-config-demo' ),
									'default'     => 'staff-pick',
								),
							),
							'default'     => array(
								'label' => __( 'Staff pick', 'lerm-admin-config-demo' ),
								'slug'  => 'staff-pick',
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
	private static function profile_schema( Runtime $runtime ): array {
		return array(
			'id'        => 'acme-demo-profile',
			'title'     => __( 'Profile Demo Settings', 'lerm-admin-config-demo' ),
			'container' => array(
				'type'       => 'profile',
				'title'      => __( 'Profile Demo Settings', 'lerm-admin-config-demo' ),
				'capability' => 'edit_user',
			),
			'store'     => array(
				'type' => 'user_meta',
				'key'  => 'acme_demo_profile_settings',
			),
			'sections'  => array(
				'preferences' => array(
					'title'       => __( 'Preferences', 'lerm-admin-config-demo' ),
					'description' => __( 'User-level settings stored through the same compiled schema pipeline.', 'lerm-admin-config-demo' ),
					'fields'      => array(
						array(
							'id'          => 'public_badge',
							'type'        => 'switcher',
							'label'       => __( 'Show public badge', 'lerm-admin-config-demo' ),
							'description' => __( 'Example user-meta switch stored by the profile container.', 'lerm-admin-config-demo' ),
							'default'     => 0,
						),
						array(
							'id'          => 'profile_slug',
							'type'        => 'slug_text',
							'label'       => __( 'Profile slug', 'lerm-admin-config-demo' ),
							'description' => __( 'Same custom field type reused inside a different container.', 'lerm-admin-config-demo' ),
							'default'     => 'team-member',
						),
						array(
							'id'          => 'profile_tone',
							'type'        => 'select',
							'label'       => __( 'Profile tone', 'lerm-admin-config-demo' ),
							'description' => __( 'Select choices resolved through the demo data source registry.', 'lerm-admin-config-demo' ),
							'choices'     => $runtime->resolve_data_source( 'tone_presets' ),
							'default'     => 'clean',
						),
						array(
							'id'          => 'profile_badge',
							'type'        => 'fieldset',
							'label'       => __( 'Profile badge', 'lerm-admin-config-demo' ),
							'description' => __( 'Nested user-meta fields saved through the same profile container.', 'lerm-admin-config-demo' ),
							'fields'      => array(
								array(
									'id'      => 'label',
									'type'    => 'text',
									'label'   => __( 'Label', 'lerm-admin-config-demo' ),
									'default' => __( 'Core team', 'lerm-admin-config-demo' ),
								),
								array(
									'id'          => 'slug',
									'type'        => 'slug_text',
									'label'       => __( 'Badge slug', 'lerm-admin-config-demo' ),
									'description' => __( 'Nested validation should block user-meta writes until the slug is fixed.', 'lerm-admin-config-demo' ),
									'default'     => 'core-team',
								),
							),
							'default'     => array(
								'label' => __( 'Core team', 'lerm-admin-config-demo' ),
								'slug'  => 'core-team',
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
	private static function taxonomy_schema( Runtime $runtime ): array {
		return array(
			'id'        => 'acme-demo-taxonomy',
			'title'     => __( 'Category Demo Settings', 'lerm-admin-config-demo' ),
			'container' => array(
				'type'       => 'taxonomy',
				'taxonomy'   => array( 'category' ),
				'capability' => 'manage_categories',
			),
			'store'     => array(
				'type' => 'term_meta',
				'key'  => 'acme_demo_category_settings',
			),
			'sections'  => array(
				'presentation' => array(
					'title'       => __( 'Presentation', 'lerm-admin-config-demo' ),
					'description' => __( 'Taxonomy term meta rendered on category create and edit screens.', 'lerm-admin-config-demo' ),
					'fields'      => array(
						array(
							'id'          => 'featured_category',
							'type'        => 'switcher',
							'label'       => __( 'Featured category', 'lerm-admin-config-demo' ),
							'description' => __( 'Marks a category as featured in downstream templates.', 'lerm-admin-config-demo' ),
							'default'     => 0,
						),
						array(
							'id'          => 'category_slug',
							'type'        => 'slug_text',
							'label'       => __( 'Custom category slug', 'lerm-admin-config-demo' ),
							'description' => __( 'Demonstrates custom field type reuse in taxonomy term meta.', 'lerm-admin-config-demo' ),
							'default'     => 'featured-category',
						),
						array(
							'id'          => 'category_tone',
							'type'        => 'select',
							'label'       => __( 'Category tone', 'lerm-admin-config-demo' ),
							'description' => __( 'Resolved from the runtime data-source registry.', 'lerm-admin-config-demo' ),
							'choices'     => $runtime->resolve_data_source( 'tone_presets' ),
							'default'     => 'bold',
						),
						array(
							'id'          => 'category_badge',
							'type'        => 'fieldset',
							'label'       => __( 'Category badge', 'lerm-admin-config-demo' ),
							'description' => __( 'Nested term-meta fields that show the same validation and error replay on taxonomy forms.', 'lerm-admin-config-demo' ),
							'fields'      => array(
								array(
									'id'      => 'label',
									'type'    => 'text',
									'label'   => __( 'Label', 'lerm-admin-config-demo' ),
									'default' => __( 'Featured', 'lerm-admin-config-demo' ),
								),
								array(
									'id'          => 'slug',
									'type'        => 'slug_text',
									'label'       => __( 'Badge slug', 'lerm-admin-config-demo' ),
									'description' => __( 'Use a short slug to confirm nested validation on category create and edit screens.', 'lerm-admin-config-demo' ),
									'default'     => 'featured-category',
								),
								array(
									'id'      => 'icon',
									'type'    => 'icon',
									'label'   => __( 'Badge icon', 'lerm-admin-config-demo' ),
									'choices' => array(
										'dashicons-tag'  => __( 'Tag', 'lerm-admin-config-demo' ),
										'dashicons-category' => __( 'Category', 'lerm-admin-config-demo' ),
										'dashicons-star-filled' => __( 'Featured', 'lerm-admin-config-demo' ),
										'dashicons-flag' => __( 'Flag', 'lerm-admin-config-demo' ),
									),
									'default' => 'dashicons-category',
								),
							),
							'default'     => array(
								'label' => __( 'Featured', 'lerm-admin-config-demo' ),
								'slug'  => 'featured-category',
								'icon'  => 'dashicons-category',
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
	private static function network_schema(): array {
		return array(
			'id'        => 'acme-demo-network-settings',
			'title'     => __( 'Network Demo Settings', 'lerm-admin-config-demo' ),
			'container' => array(
				'type' => 'network_options_page',
			),
			'store'     => array(
				'type' => 'network_option',
				'key'  => 'acme_demo_network_settings',
			),
			'menu'      => array(
				'parent_slug' => 'settings.php',
				'page_title'  => __( 'Network Demo Settings', 'lerm-admin-config-demo' ),
				'menu_title'  => __( 'Admin Config Demo', 'lerm-admin-config-demo' ),
				'capability'  => 'manage_network_options',
			),
			'view'      => array(
				'eyebrow'     => __( 'Multisite', 'lerm-admin-config-demo' ),
				'title'       => __( 'Network Demo Settings', 'lerm-admin-config-demo' ),
				'description' => __( 'Network-scoped settings stored through the same schema/store/container pipeline.', 'lerm-admin-config-demo' ),
			),
			'sections'  => array(
				'general' => array(
					'title'       => __( 'General', 'lerm-admin-config-demo' ),
					'description' => __( 'Network defaults propagated to sites using this plugin.', 'lerm-admin-config-demo' ),
					'fields'      => array(
						array(
							'id'          => 'shared_presets',
							'type'        => 'switcher',
							'label'       => __( 'Enable shared presets', 'lerm-admin-config-demo' ),
							'description' => __( 'Allows sites to consume centrally managed presets.', 'lerm-admin-config-demo' ),
							'default'     => 1,
						),
						array(
							'id'          => 'template_endpoint',
							'type'        => 'url',
							'label'       => __( 'Template endpoint', 'lerm-admin-config-demo' ),
							'description' => __( 'Example remote URL for a shared template feed.', 'lerm-admin-config-demo' ),
							'default'     => 'https://example.com/templates.json',
						),
						array(
							'id'          => 'shared_library',
							'type'        => 'fieldset',
							'label'       => __( 'Shared library', 'lerm-admin-config-demo' ),
							'description' => __( 'Nested network defaults stored in the same site-option payload.', 'lerm-admin-config-demo' ),
							'fields'      => array(
								array(
									'id'          => 'feed_slug',
									'type'        => 'slug_text',
									'label'       => __( 'Feed slug', 'lerm-admin-config-demo' ),
									'description' => __( 'Use this field to verify nested validation on the multisite network screen.', 'lerm-admin-config-demo' ),
									'default'     => 'shared-library',
								),
								array(
									'id'      => 'landing_path',
									'type'    => 'text',
									'label'   => __( 'Landing path', 'lerm-admin-config-demo' ),
									'default' => '/library',
								),
							),
							'default'     => array(
								'feed_slug'    => 'shared-library',
								'landing_path' => '/library',
							),
						),
					),
				),
			),
		);
	}
}
