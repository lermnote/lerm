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
		if ( ! $runtime->has( 'acme-demo-settings' ) ) {
			$runtime->register( self::settings_schema() );
		}

		if ( ! $runtime->has( 'acme-demo-comment' ) ) {
			$runtime->register( self::comment_schema() );
		}

		if ( is_multisite() && ! $runtime->has( 'acme-demo-network-settings' ) ) {
			$runtime->register( self::network_schema() );
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function settings_schema(): array {
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
			),
			'sections'  => array(
				'general' => array(
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
					),
				),
				'advanced' => array(
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
								'dashicons-lightbulb'       => __( 'Idea', 'lerm-admin-config-demo' ),
								'dashicons-megaphone'       => __( 'Launch', 'lerm-admin-config-demo' ),
								'dashicons-chart-bar'       => __( 'Analytics', 'lerm-admin-config-demo' ),
								'dashicons-format-image'    => __( 'Visual', 'lerm-admin-config-demo' ),
								'dashicons-star-filled'     => __( 'Featured', 'lerm-admin-config-demo' ),
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
				'tools'   => array(
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
					),
				),
			),
		);
	}
}
