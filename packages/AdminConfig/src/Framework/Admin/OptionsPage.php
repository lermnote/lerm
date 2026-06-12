<?php // phpcs:disable WordPress.Files.FileName
/**
 * Generic native options page container.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Admin;

use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\Framework\Contracts\AssetPathResolver;
use Lerm\AdminConfig\Framework\Contracts\AssetResolver;
use Lerm\AdminConfig\Framework\Support\I18nStrings;
use Lerm\AdminConfig\Framework\Support\PackageAssets;
use Lerm\AdminConfig\Framework\Support\PageSchema;
use Lerm\AdminConfig\Framework\Support\ScriptAssetMetadata;
use Lerm\AdminConfig\Registry\FieldModuleRegistry;
use Lerm\AdminConfig\WordPress\Support\ValidationFlash;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OptionsPage {

	use FieldErrorMatcher;

	/**
	 * Page definition.
	 *
	 * @var array<string, mixed>
	 */
	private array $definition;

	private OptionStore $store;

	private FieldTypeRegistry $field_types;

	/**
	 * Settings page hook suffix.
	 */
	private string $page_hook = '';

	private AssetResolver $asset_resolver;
	private ?FieldModuleRegistry $field_modules = null;

	/**
	 * The JS global variable name for this page instance.
	 * Namespaced by page slug to avoid collisions on multi-instance pages.
	 */
	private string $js_global;
	private bool $network_admin = false;

	/**
	 * @var array<string, mixed>
	 */
	private array $render_field_errors = array();

	/**
	 * @var array<int, string>
	 */
	private array $render_path_stack = array();

	/**
	 * Lazily-built field-id → {tab, subsection} map.
	 *
	 * @var array<string, array{tab: string, subsection: string}>|null
	 */
	private ?array $field_section_map_cache = null;

	/**
	 * @var array<string, bool>|null
	 */
	private ?array $dependency_controller_fields = null;

	/**
	 * @param array<string, mixed> $definition Page definition.
	 */
	public function __construct( array $definition, OptionStore $store, FieldTypeRegistry $field_types, AssetResolver $asset_resolver, bool $register_hooks = true, ?FieldModuleRegistry $field_modules = null ) {
		$this->definition     = $definition;
		$this->store          = $store;
		$this->field_types    = $field_types;
		$this->asset_resolver = $asset_resolver;
		$this->field_modules  = $field_modules;
		$menu                 = is_array( $this->definition['menu'] ?? null ) ? $this->definition['menu'] : array();
		$this->network_admin  = ! empty( $menu['network_admin'] );
		// JS global can be overridden per-instance via the definition.
		$this->js_global = isset( $this->definition['js_global'] ) && is_string( $this->definition['js_global'] )
			? $this->definition['js_global']
			: 'lermAdminConfig';

		if ( $register_hooks ) {
			add_action( $this->menu_action(), array( $this, 'register_menu' ) );
			add_action( 'admin_post_' . $this->save_action(), array( $this, 'handle_save' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		}
	}

	/**
	 * Register the page under its configured parent.
	 */
	public function register_menu(): void {
		$menu = is_array( $this->definition['menu'] ?? null ) ? $this->definition['menu'] : array();

		$this->page_hook = (string) add_submenu_page(
			(string) ( $menu['parent_slug'] ?? 'themes.php' ),
			(string) ( $menu['page_title'] ?? __( 'Admin Config', 'lerm-admin-config' ) ),
			(string) ( $menu['menu_title'] ?? __( 'Admin Config', 'lerm-admin-config' ) ),
			$this->capability(),
			$this->page_slug(),
			array( $this, 'render_page' )
		);
	}

	/**
	 * Handle a non-JS save request.
	 */
	public function handle_save(): void {
		if ( ! current_user_can( $this->capability() ) ) {
			wp_die( esc_html__( 'You are not allowed to manage these settings.', 'lerm-admin-config' ) );
		}

		$tab        = $this->posted_tab();
		$subsection = $this->posted_subsection();

		check_admin_referer( $this->nonce_action( $tab ) );

		$submitted = isset( $_POST[ $this->option_name() ] ) && is_array( $_POST[ $this->option_name() ] )
			? wp_unslash( $_POST[ $this->option_name() ] )
			: array();

		$success             = $this->store->import_all( $submitted );
		$status              = 'success';
		$redirect_tab        = $tab;
		$redirect_subsection = $subsection;

		if ( $this->store->has_validation_errors() ) {
			$error_target = $this->first_validation_target( $this->store->validation_errors() );

			$status              = 'validation_error';
			$redirect_tab        = $error_target['tab'];
			$redirect_subsection = $error_target['subsection'];
			ValidationFlash::store(
				'options_page',
				$this->schema_id(),
				$this->flash_resource_key(),
				array(
					'tab'        => $redirect_tab,
					'subsection' => $redirect_subsection,
					'global'     => true,
					'message'    => __( 'Please review the highlighted fields before saving again.', 'lerm-admin-config' ),
					'errors'     => $this->store->validation_errors(),
					'submitted'  => $submitted,
				)
			);
		} elseif ( ! $success ) {
			$status = 'error';
			ValidationFlash::store(
				'options_page',
				$this->schema_id(),
				$this->flash_resource_key(),
				array(
					'tab'        => $redirect_tab,
					'subsection' => $redirect_subsection,
					'global'     => true,
					'message'    => __( 'Unable to save these settings right now.', 'lerm-admin-config' ),
					'errors'     => array(),
					'submitted'  => $submitted,
				)
			);
		} else {
			ValidationFlash::clear( 'options_page', $this->schema_id(), $this->flash_resource_key() );
		}

		$redirect_url = add_query_arg(
			array(
				'page'                     => $this->page_slug(),
				'tab'                      => $redirect_tab,
				'lerm_admin_config_status' => $status,
			),
			$this->admin_parent_url()
		);

		if ( '' !== $redirect_subsection ) {
			$redirect_url = add_query_arg( 'subsection', $redirect_subsection, $redirect_url );
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Enqueue page assets.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( $hook_suffix !== $this->page_hook ) {
			return;
		}

		$this->enqueue_support_assets( $this->page_slug() );
	}

	/**
	 * Enqueue the shared field UI assets for non-page containers.
	 */
	public function enqueue_support_assets( string $handle_suffix = '' ): void {
		$code_editor_settings = wp_enqueue_code_editor(
			array(
				'type' => 'text/html',
			)
		);

		wp_enqueue_media();
		wp_enqueue_style( 'wp-codemirror' );
		wp_enqueue_script( 'wp-theme-plugin-editor' );
		// Asset handles are namespaced by page slug so two framework instances
		// on the same admin screen don't enqueue the same handle twice.
		$suffix     = '' !== $handle_suffix ? sanitize_key( $handle_suffix ) : $this->page_slug();
		$css_handle = 'lerm-admin-config-' . $suffix;
		$js_handle  = 'lerm-admin-config-js-' . $suffix;
		$script     = $this->script_asset();

		wp_enqueue_style(
			$css_handle,
			$this->asset_url( 'admin-config.css' ),
			array( 'wp-codemirror' ),
			$this->asset_version()
		);
		wp_enqueue_script(
			$js_handle,
			$this->asset_url( $script['file'] ),
			$script['dependencies'],
			$script['version'],
			true
		);

		wp_set_script_translations(
			$js_handle,
			'lerm-admin-config',
			dirname( PackageAssets::directory() ) . '/languages/'
		);

		wp_localize_script(
			$js_handle,
			$this->js_global,
			I18nStrings::for_admin_page( $code_editor_settings )
		);
	}

	/**
	 * Render the page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( $this->capability() ) ) {
			return;
		}

		$view        = is_array( $this->definition['view'] ?? null ) ? $this->definition['view'] : array();
		$sections    = PageSchema::sections( $this->definition );
		$current_tab = $this->current_tab();
		$values      = $this->store->all();
		$flash       = ValidationFlash::consume( 'options_page', $this->schema_id(), $this->flash_resource_key() );
		?>
		<div class="wrap lerm-settings-wrap">
			<div class="lerm-settings-shell">
				<aside class="lerm-settings-sidebar">
					<div class="lerm-settings-sidebar__brand">
						<p class="lerm-settings-eyebrow"><?php echo esc_html( (string) ( $view['eyebrow'] ?? __( 'Native admin', 'lerm-admin-config' ) ) ); ?></p>
						<h1><?php echo esc_html( (string) ( $view['title'] ?? __( 'Admin Config', 'lerm-admin-config' ) ) ); ?></h1>
						<p><?php echo esc_html( (string) ( $view['description'] ?? __( 'A native, extensible settings page built on schema, storage, and reusable field renderers.', 'lerm-admin-config' ) ) ); ?></p>
					</div>

					<nav class="lerm-settings-nav" aria-label="<?php esc_attr_e( 'Settings sections', 'lerm-admin-config' ); ?>">
						<?php foreach ( $sections as $section_id => $section ) : ?>
							<?php $section_field_count = count( PageSchema::section_fields( $section ) ); ?>
									<a class="lerm-settings-nav__item <?php echo esc_attr( $section_id === $current_tab ? 'is-active' : '' ); ?>"
								href="
								<?php
								echo esc_url(
									add_query_arg(
										array(
											'page' => $this->page_slug(),
											'tab'  => $section_id,
										),
										$this->admin_parent_url()
									)
								);
								?>
										"
								data-tab-target="<?php echo esc_attr( $section_id ); ?>">
								<span class="lerm-settings-nav__title"><?php echo esc_html( (string) $section['title'] ); ?></span>
								<span class="lerm-settings-nav__meta">
									<?php
									echo esc_html(
										// translators: %s is the number of fields in the section, e.g. "5 fields". Do not translate the number itself.
										sprintf( _n( '%s field', '%s fields', $section_field_count, 'lerm-admin-config' ), number_format_i18n( $section_field_count ) )
									);
									?>
								</span>
							</a>
						<?php endforeach; ?>
					</nav>
				</aside>

				<section class="lerm-settings-main">
					<div class="lerm-settings-panel">
						<?php
						// Intro header: title/description swapped by JS on tab switch.
						// PHP seeds the initially-active tab; JS takes over from there.
						$active_section = $sections[ $current_tab ] ?? reset( $sections );
						?>
						<div class="lerm-settings-panel__intro" data-lerm-tab-intro>
							<div>
								<p class="lerm-settings-eyebrow"><?php esc_html_e( 'Current section', 'lerm-admin-config' ); ?></p>
								<h2 data-lerm-tab-intro-title><?php echo esc_html( (string) ( $active_section['title'] ?? '' ) ); ?></h2>
								<p data-lerm-tab-intro-desc><?php echo esc_html( (string) ( $active_section['description'] ?? '' ) ); ?></p>
							</div>
						</div>

						<?php
						$active_section_definition = is_array( $active_section ) ? $active_section : array();
						$active_section_groups     = PageSchema::section_groups( $active_section_definition );
						$current_subsection        = $this->section_uses_subsections( $active_section_definition, $active_section_groups )
							? $this->current_subsection_for_section( (string) $current_tab, $active_section_groups )
							: '';
						?>
						<form method="post" action="<?php echo esc_url( $this->admin_post_url() ); ?>"
								class="lerm-settings-form"
								data-option-name="<?php echo esc_attr( $this->option_name() ); ?>"
								data-schema-id="<?php echo esc_attr( $this->schema_id() ); ?>"
								data-js-global="<?php echo esc_attr( $this->js_global ); ?>"
								novalidate>
							<input type="hidden" name="action" value="<?php echo esc_attr( $this->save_action() ); ?>">
							<input type="hidden" name="lerm_settings_tab" value="<?php echo esc_attr( $current_tab ); ?>" data-lerm-current-tab="1">
							<input type="hidden" name="lerm_settings_subsection" value="<?php echo esc_attr( $current_subsection ); ?>" data-lerm-current-subsection="1">
							<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( $this->nonce_action( $current_tab ) ) ); ?>" data-lerm-current-nonce="1">

							<?php foreach ( $sections as $section_id => $section ) : ?>
								<?php
								$section_fields     = PageSchema::section_fields( $section );
								$section_groups     = PageSchema::section_groups( $section );
								$use_subsections    = $this->section_uses_subsections( $section, $section_groups );
								$current_subsection = $use_subsections ? $this->current_subsection_for_section( (string) $section_id, $section_groups ) : '';
								$section_errors     = $this->section_flash_errors( $flash, (string) $section_id );
								$section_values     = $this->section_render_values( $values, $flash, (string) $section_id );
								$section_notice     = $this->section_flash_notice( $flash, (string) $section_id );
								?>
							<div data-tab-panel="<?php echo esc_attr( $section_id ); ?>"
								data-tab-title="<?php echo esc_attr( (string) ( $section['title'] ?? '' ) ); ?>"
								data-tab-description="<?php echo esc_attr( (string) ( $section['description'] ?? '' ) ); ?>"
								data-tab-nonce="<?php echo esc_attr( wp_create_nonce( $this->nonce_action( (string) $section_id ) ) ); ?>"
								data-current-subsection="<?php echo esc_attr( $current_subsection ); ?>"
								<?php echo esc_attr( $section_id !== $current_tab ? 'hidden' : '' ); ?>>

								<?php if ( null !== $section_notice ) : ?>
									<div class="lerm-settings-form-notice notice <?php echo esc_attr( $section_notice['class'] ); ?> inline">
										<p><?php echo esc_html( $section_notice['message'] ); ?></p>
									</div>
								<?php endif; ?>

								<div class="lerm-settings-sticky-wrap" data-lerm-sticky-wrap>
									<div class="lerm-settings-actions lerm-settings-actions--sticky lerm-settings-sticky-bar" data-lerm-sticky-bar>
										<button type="submit" class="button button-primary button-large" data-lerm-save><?php esc_html_e( 'Save changes', 'lerm-admin-config' ); ?></button>
										<button type="button" class="button button-secondary" data-lerm-reset="section"><?php esc_html_e( 'Reset current page', 'lerm-admin-config' ); ?></button>
										<button type="button" class="button button-secondary button-link-delete" data-lerm-reset="all"><?php esc_html_e( 'Reset all tabs', 'lerm-admin-config' ); ?></button>
										<span class="spinner lerm-settings-spinner"></span>
										<span class="lerm-settings-actions__hint"><?php esc_html_e( 'Changes are saved instantly without reloading the page. Use Ctrl/Cmd + S to save faster.', 'lerm-admin-config' ); ?></span>
										<span class="lerm-settings-actions__spacer" aria-hidden="true"></span>
										<span class="lerm-status-pill lerm-settings-actions__status" data-lerm-status="idle"><?php esc_html_e( 'Synced', 'lerm-admin-config' ); ?></span>
									</div>
								</div>

								<?php if ( $use_subsections ) : ?>
									<div class="lerm-settings-sticky-wrap lerm-settings-sticky-wrap--subnav" data-lerm-sticky-wrap>
										<?php /* translators: %s: section title. */ ?>
										<nav class="lerm-settings-subnav lerm-settings-subnav--sticky lerm-settings-sticky-bar" data-lerm-sticky-bar aria-label="<?php echo esc_attr( sprintf( __( '%s groups', 'lerm-admin-config' ), (string) ( $section['title'] ?? __( 'Section', 'lerm-admin-config' ) ) ) ); ?>">
											<?php foreach ( $section_groups as $group_index => $group ) : ?>
												<button type="button"
													class="lerm-settings-subnav__item <?php echo esc_attr( (string) $group['id'] === $current_subsection ? 'is-active' : '' ); ?>"
													data-subsection-target="<?php echo esc_attr( (string) $group['id'] ); ?>"
													aria-pressed="<?php echo esc_attr( (string) $group['id'] === $current_subsection ? 'true' : 'false' ); ?>">
													<?php echo esc_html( (string) $group['label'] ); ?>
												</button>
											<?php endforeach; ?>
										</nav>
									</div>

									<div class="lerm-settings-subsections">
										<?php foreach ( $section_groups as $group_index => $group ) : ?>
											<section class="lerm-settings-subsection"
												data-subsection-panel="<?php echo esc_attr( (string) $group['id'] ); ?>"
												<?php echo esc_attr( (string) $group['id'] !== $current_subsection ? 'hidden' : '' ); ?>>
												<div class="lerm-settings-stack" role="group" aria-label="<?php echo esc_attr( (string) $group['label'] ); ?>">
													<?php if ( ! empty( $group['fields'] ) ) : ?>
														<?php $this->render_fields( (array) $group['fields'], $section_values, (string) $section_id, $this->subsection_uses_group_headings( (array) $group['fields'], (string) $group['label'] ), 'stack', $section_errors ); ?>
													<?php else : ?>
														<div class="lerm-settings-empty-group"><?php esc_html_e( 'No settings in this group yet.', 'lerm-admin-config' ); ?></div>
													<?php endif; ?>
												</div>
											</section>
										<?php endforeach; ?>
									</div>
								<?php else : ?>
									<div class="lerm-settings-stack" role="group" aria-label="<?php echo esc_attr( (string) ( $section['title'] ?? __( 'Section', 'lerm-admin-config' ) ) ); ?>">
										<?php $this->render_fields( $section_fields, $section_values, (string) $section_id, true, 'stack', $section_errors ); ?>
									</div>
								<?php endif; ?>

								<div class="lerm-settings-actions lerm-settings-actions--footer">
									<button type="submit" class="button button-primary button-large" data-lerm-save><?php esc_html_e( 'Save changes', 'lerm-admin-config' ); ?></button>
									<button type="button" class="button button-secondary" data-lerm-reset="section"><?php esc_html_e( 'Reset current page', 'lerm-admin-config' ); ?></button>
									<button type="button" class="button button-secondary button-link-delete" data-lerm-reset="all"><?php esc_html_e( 'Reset all tabs', 'lerm-admin-config' ); ?></button>
								</div>
							</div>
							<?php endforeach; ?>
						</form>

						<?php $this->render_debug_panel(); ?>

					</div>
				</section>
			</div>
		</div>
		<?php
	}

	public function schema_id(): string {
		$id = isset( $this->definition['id'] ) && is_scalar( $this->definition['id'] ) ? sanitize_key( (string) $this->definition['id'] ) : '';

		return '' !== $id ? $id : sanitize_key( $this->option_name() );
	}

	private function debug_panel_enabled(): bool {
		$view = is_array( $this->definition['view'] ?? null ) ? $this->definition['view'] : array();

		if ( array_key_exists( 'debug', $view ) ) {
			return ! empty( $view['debug'] );
		}

		return defined( 'WP_DEBUG' ) ? (bool) constant( 'WP_DEBUG' ) : false;
	}

	private function render_debug_panel(): void {
		if ( ! $this->debug_panel_enabled() ) {
			return;
		}

		$json = wp_json_encode(
			$this->debug_payload(),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);

		if ( false === $json ) {
			return;
		}
		?>
		<section class="lerm-debug-panel" data-lerm-debug-panel>
			<div class="lerm-debug-panel__header">
				<div>
					<h3><?php esc_html_e( 'Runtime Debug', 'lerm-admin-config' ); ?></h3>
					<p><?php esc_html_e( 'Schema, storage, module, and data-source summary for this admin screen.', 'lerm-admin-config' ); ?></p>
				</div>
				<button type="button" class="button button-secondary" data-lerm-debug-copy><?php esc_html_e( 'Copy JSON', 'lerm-admin-config' ); ?></button>
			</div>
			<pre class="lerm-debug-panel__json" data-lerm-debug-json><?php echo esc_html( $json ); ?></pre>
		</section>
		<?php
	}

	/**
	 * @return array<string, mixed>
	 */
	private function debug_payload(): array {
		$container       = is_array( $this->definition['container'] ?? null ) ? $this->definition['container'] : array();
		$store           = is_array( $this->definition['store'] ?? null ) ? $this->definition['store'] : array();
		$menu            = is_array( $this->definition['menu'] ?? null ) ? $this->definition['menu'] : array();
		$sections        = PageSchema::sections( $this->definition );
		$section_summary = array();

		foreach ( $sections as $section_id => $section ) {
			$section_summary[ (string) $section_id ] = array(
				'title'  => (string) ( $section['title'] ?? $section_id ),
				'fields' => count( PageSchema::section_fields( $section ) ),
				'groups' => count( PageSchema::section_groups( $section ) ),
			);
		}

		return array(
			'schema_id'     => $this->schema_id(),
			'page_slug'     => $this->page_slug(),
			'option_name'   => $this->option_name(),
			'capability'    => $this->capability(),
			'network_admin' => $this->network_admin,
			'container'     => array(
				'type'       => (string) ( $container['type'] ?? 'options_page' ),
				'capability' => (string) ( $container['capability'] ?? $menu['capability'] ?? $this->capability() ),
			),
			'store'         => array(
				'type' => (string) ( $store['type'] ?? 'option' ),
				'key'  => (string) ( $store['key'] ?? $this->option_name() ),
			),
			'summary'       => array(
				'sections' => count( $sections ),
				'fields'   => count( PageSchema::fields( $this->definition ) ),
				'defaults' => count( PageSchema::defaults( $this->definition ) ),
			),
			'sections'      => $section_summary,
			'field_types'   => $this->field_types_for_debug(),
			'modules'       => $this->field_modules ? $this->field_modules->modules_for_definition( $this->definition ) : array(),
			'data_sources'  => $this->schema_data_sources(),
		);
	}

	/**
	 * @return array<int, string>
	 */
	private function field_types_for_debug(): array {
		if ( $this->field_modules ) {
			return $this->field_modules->field_types_for_definition( $this->definition );
		}

		$types = array();

		foreach ( PageSchema::fields( $this->definition ) as $field ) {
			$type = sanitize_key( (string) ( $field['type'] ?? 'text' ) );

			if ( '' !== $type ) {
				$types[ $type ] = $type;
			}
		}

		return array_values( $types );
	}

	/**
	 * @return array<int, string>
	 */
	private function schema_data_sources(): array {
		$sources = array();

		foreach ( PageSchema::sections( $this->definition ) as $section ) {
			$this->collect_data_sources_from_fields( PageSchema::section_fields( $section ), $sources );
		}

		return array_values( $sources );
	}

	/**
	 * @param array<int, array<string, mixed>> $fields
	 * @param array<string, string>            $sources
	 */
	private function collect_data_sources_from_fields( array $fields, array &$sources ): void {
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$source_id = sanitize_key( (string) ( $field['source'] ?? $field['data_source'] ?? '' ) );

			if ( '' !== $source_id ) {
				$sources[ $source_id ] = $source_id;
			}

			$child_fields = is_array( $field['fields'] ?? null ) ? $field['fields'] : array();

			if ( ! empty( $child_fields ) ) {
				$this->collect_data_sources_from_fields( $child_fields, $sources );
			}

			$items = is_array( $field['items'] ?? null ) ? $field['items'] : array();

			foreach ( $items as $item ) {
				if ( ! is_array( $item ) || ! is_array( $item['fields'] ?? null ) ) {
					continue;
				}

				$this->collect_data_sources_from_fields( $item['fields'], $sources );
			}
		}
	}

	/**
	 * Determine whether a section should render secondary navigation.
	 *
	 * @param array<string, mixed>               $section Section definition.
	 * @param array<int, array<string, mixed>>   $groups  Section groups.
	 */
	private function section_uses_subsections( array $section, array $groups ): bool {
		if ( array_key_exists( 'use_subsections', $section ) ) {
			return ! empty( $section['use_subsections'] ) && count( $groups ) > 1;
		}

		return count( $groups ) > 1;
	}

	/**
	 * Determine whether subsection panels should still render field group headings.
	 *
	 * @param array<int, array<string, mixed>> $fields          Subsection fields.
	 * @param string                           $subsection_label Current subsection label.
	 */
	private function subsection_uses_group_headings( array $fields, string $subsection_label ): bool {
		$labels = array();

		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$heading = trim( (string) ( $field['group_heading'] ?? '' ) );

			if ( '' === $heading || in_array( $heading, $labels, true ) ) {
				continue;
			}

			$labels[] = $heading;
		}

		if ( count( $labels ) > 1 ) {
			return true;
		}

		if ( empty( $labels ) ) {
			return false;
		}

		return trim( $labels[0] ) !== trim( $subsection_label );
	}

	/**
	 * Resolve which subsection should be rendered initially for a section.
	 *
	 * @param string                           $section_id Section ID.
	 * @param array<int, array<string, mixed>> $groups     Section groups.
	 */
	private function current_subsection_for_section( string $section_id, array $groups ): string {
		if ( empty( $groups ) ) {
			return '';
		}

		$fallback_subsection = (string) ( $groups[0]['id'] ?? '' );

		if ( '' === $fallback_subsection || $section_id !== $this->current_tab() ) {
			return $fallback_subsection;
		}

		$requested_subsection = isset( $_GET['subsection'] ) ? sanitize_key( wp_unslash( $_GET['subsection'] ) ) : '';

		if ( '' === $requested_subsection ) {
			return $fallback_subsection;
		}

		foreach ( $groups as $group ) {
			if ( (string) ( $group['id'] ?? '' ) === $requested_subsection ) {
				return $requested_subsection;
			}
		}

		return $fallback_subsection;
	}

	/**
	 * Render all fields for a section.
	 *
	 * @param array<int, array<string, mixed>> $fields     Field definitions.
	 * @param array<string, mixed>             $values     Saved values.
	 * @param string                           $section_id Current section ID.
	 * @param bool                             $show_group_headings Whether group headings should be rendered.
	 * @param string                           $layout Layout mode.
	 */
	public function render_fields( array $fields, array $values, string $section_id = '', bool $show_group_headings = true, string $layout = 'table', array $field_errors = array() ): void {
		$current_group_heading = '';

		foreach ( $fields as $field ) {
			$group_heading = (string) ( $field['group_heading'] ?? '' );

			if ( $show_group_headings && $group_heading && $group_heading !== $current_group_heading ) {
				$current_group_heading = $group_heading;

				if ( 'stack' === $layout ) {
					printf(
						'<div class="lerm-settings-group lerm-settings-group--stack"><h3>%s</h3></div>',
						esc_html( $group_heading )
					);
				} else {
					printf(
						'<tr class="lerm-settings-group"><td colspan="2"><h3>%s</h3></td></tr>',
						esc_html( $group_heading )
					);
				}
			}

			$this->render_field( $field, $values, $layout, $field_errors );
		}
	}

	/**
	 * Render a single field row.
	 *
	 * @param array<string, mixed> $field      Field definition.
	 * @param array<string, mixed> $values     Saved values.
	 * @param string               $layout Layout mode.
	 */
	public function render_field( array $field, array $values, string $layout = 'table', array $field_errors = array() ): void {

		if ( 'stack' === $layout ) {
			$this->render_stack_field_row( $field, $values, $field_errors );
			return;
		}

		$this->render_table_field_row( $field, $values, $field_errors );
	}

	/**
	 * @param array<string, mixed> $field
	 * @param array<string, mixed> $values
	 * @param array<string, mixed> $field_errors
	 */
	private function render_stack_field_row( array $field, array $values, array $field_errors ): void {
		$context   = $this->field_row_context( $field, $values, $field_errors );
		$row_attrs = $context['row_attrs'];

		if ( '' === $context['label'] ) {
			$row_attrs[0] = 'class="lerm-settings-row lerm-settings-row--nolabel' . ( $context['has_errors'] ? ' is-invalid' : '' ) . '"';
		}

		echo '<div ' . implode( ' ', $row_attrs ) . '>';

		if ( '' !== $context['label'] ) {
			printf(
				'<div class="lerm-settings-row__head"><label for="%1$s">%2$s</label></div>',
				esc_attr( $context['field_id'] ),
				esc_html( $context['label'] )
			);
		}

		echo '<div class="lerm-settings-row__body">';

		$this->render_field_control( $field, $context, $field_errors );
		$this->render_field_notes( $context['description'], $context['field_error'] );

		echo '</div></div>';
	}

	/**
	 * @param array<string, mixed> $field
	 * @param array<string, mixed> $values
	 * @param array<string, mixed> $field_errors
	 */
	private function render_table_field_row( array $field, array $values, array $field_errors ): void {
		$context = $this->field_row_context( $field, $values, $field_errors );

		echo '<tr ' . implode( ' ', $context['row_attrs'] ) . '>';

		if ( '' !== $context['label'] ) {
			printf(
				'<th scope="row"><label for="%1$s">%2$s</label></th>',
				esc_attr( $context['field_id'] ),
				esc_html( $context['label'] )
			);
		} else {
			echo '<th scope="row"></th>';
		}

		echo '<td>';

		$this->render_field_control( $field, $context, $field_errors );
		$this->render_field_notes( $context['description'], $context['field_error'] );

		echo '</td></tr>';
	}

	/**
	 * @param array<string, mixed> $field
	 * @param array<string, mixed> $values
	 * @param array<string, mixed> $field_errors
	 * @return array{field_id: string, field_type: string, field_name: string, field_value: mixed, description: string, field_error: string, has_errors: bool, label: string, row_attrs: array<int, string>}
	 */
	private function field_row_context( array $field, array $values, array $field_errors ): array {
		$field_id    = (string) $field['id'];
		$field_type  = sanitize_key( (string) ( $field['type'] ?? 'text' ) );
		$field_name  = $this->option_name() . '[' . $field_id . ']';
		$field_value = $values[ $field_id ] ?? ( $field['default'] ?? '' );
		$description = (string) ( $field['description'] ?? '' );
		$field_error = $this->field_error_message( $field_errors, $field_id );
		$has_errors  = $this->field_has_errors( $field_errors, $field_id, true );
		$dependency  = $this->field_dependency( $field );
		$label       = isset( $field['label'] ) ? (string) $field['label'] : '';
		$row_attrs   = array(
			'class="lerm-settings-row' . ( $has_errors ? ' is-invalid' : '' ) . '"',
			'data-field-id="' . esc_attr( $field_id ) . '"',
			'data-field-path="' . esc_attr( $field_id ) . '"',
			'data-field-type="' . esc_attr( $field_type ) . '"',
		);

		if ( ! empty( $dependency ) ) {
			$row_attrs[] = 'data-dependency-field="' . esc_attr( (string) $dependency['field'] ) . '"';
			$row_attrs[] = 'data-dependency-operator="' . esc_attr( (string) $dependency['operator'] ) . '"';
			$row_attrs[] = 'data-dependency-value="' . esc_attr( $this->dependency_attribute_value( $dependency['value'] ) ) . '"';

			if ( ! $this->dependency_is_satisfied( $field, $values ) ) {
				$row_attrs[] = 'hidden';
			}
		}

		return array(
			'field_id'    => $field_id,
			'field_type'  => $field_type,
			'field_name'  => $field_name,
			'field_value' => $field_value,
			'description' => $description,
			'field_error' => $field_error,
			'has_errors'  => $has_errors,
			'label'       => $label,
			'row_attrs'   => $row_attrs,
		);
	}

	/**
	 * @param array<string, mixed> $field
	 * @param array{field_id: string, field_type: string, field_name: string, field_value: mixed} $context
	 * @param array<string, mixed> $field_errors
	 */
	private function render_field_control( array $field, array $context, array $field_errors ): void {
		$custom_render = $this->field_types->render_callback( $context['field_type'] );

		$previous_errors           = $this->render_field_errors;
		$this->render_field_errors = $field_errors;
		$this->render_path_stack[] = $context['field_id'];

		try {
			if ( is_callable( $custom_render ) ) {
				call_user_func( $custom_render, $field, $context['field_value'], $context['field_name'], $this );
			} else {
				printf(
					'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text" %5$s placeholder="%6$s">',
					esc_attr( (string) ( $field['input_type'] ?? 'text' ) ),
					esc_attr( $context['field_id'] ),
					esc_attr( $context['field_name'] ),
					esc_attr( $this->scalar_string( $context['field_value'] ) ),
					$this->dependency_controller_attribute( $field ),
					esc_attr( (string) ( $field['placeholder'] ?? '' ) )
				);
			}
		} finally {
			array_pop( $this->render_path_stack );
			$this->render_field_errors = $previous_errors;
		}
	}

	public function container_field_renderer(): ContainerFieldRenderer {
		return new ContainerFieldRenderer(
			function ( array $field, $value, string $field_name, string $input_id, string $name_template = '', string $id_template = '' ): void {
				$this->nested_render_proxy( $field, $value, $field_name, $input_id, $name_template, $id_template );
			},
			$this->render_field_errors,
			$this->current_render_path()
		);
	}

	/**
	 * Proxy for rendering a nested sub-field inside a structured container.
	 *
	 * Contains the FieldTypeRegistry lookup and fallback rendering logic that
	 * was previously in ContainerFieldRenderer::render_nested_field().
	 *
	 * @param array<string, mixed> $field        Field definition.
	 * @param mixed                $value        Field value.
	 * @param string               $field_name   Form field name attribute.
	 * @param string               $input_id     DOM id attribute.
	 * @param string               $name_template Template for the name attribute in repeaters.
	 * @param string               $id_template   Template for the id attribute in repeaters.
	 */
	public function nested_render_proxy( array $field, $value, string $field_name, string $input_id, string $name_template = '', string $id_template = '' ): void {
		$field_type    = sanitize_key( (string) ( $field['type'] ?? 'text' ) );
		$custom_render = $this->field_types->nested_render_callback( $field_type );

		if ( is_callable( $custom_render ) ) {
			call_user_func( $custom_render, $field, $value, $field_name, $input_id, $this, $name_template, $id_template );
			return;
		}

		$name_attr = '' !== $name_template ? ' data-name-template="' . esc_attr( $name_template ) . '"' : '';
		$id_attr   = '' !== $id_template ? ' data-id-template="' . esc_attr( $id_template ) . '"' : '';

		printf(
			'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text" placeholder="%5$s"%6$s%7$s>',
			esc_attr( (string) ( $field['input_type'] ?? 'text' ) ),
			esc_attr( $input_id ),
			esc_attr( $field_name ),
			esc_attr( PageSchema::scalar_value( $value ) ),
			esc_attr( (string) ( $field['placeholder'] ?? '' ) ),
			$name_attr,
			$id_attr
		);
	}

	/**
	 * @param array<string, mixed>               $values
	 * @param array<string, mixed>|null          $flash
	 * @return array<string, mixed>
	 */
	private function section_render_values( array $values, ?array $flash, string $section_id ): array {
		if ( ! is_array( $flash ) ) {
			return $values;
		}

		if ( ! $this->is_global_flash( $flash ) && (string) ( $flash['tab'] ?? '' ) !== $section_id ) {
			return $values;
		}

		$submitted = is_array( $flash['submitted'] ?? null ) ? $flash['submitted'] : array();

		return $this->merge_section_submitted_values( $section_id, $values, $submitted );
	}

	/**
	 * Merge flashed submission data back into one section for non-JS validation retries.
	 *
	 * Some controls intentionally submit no key when emptied (for example multi-selects,
	 * checkbox lists, or an emptied group). A plain `wp_parse_args()` merge would
	 * resurrect the last saved value after a validation failure, so we replay those
	 * omissions as their empty state instead.
	 *
	 * @param array<string, mixed> $values
	 * @param array<string, mixed> $submitted
	 * @return array<string, mixed>
	 */
	private function merge_section_submitted_values( string $section_id, array $values, array $submitted ): array {
		$section = PageSchema::section( $this->definition, $section_id );

		if ( null === $section ) {
			return $values;
		}

		foreach ( PageSchema::section_fields( $section ) as $field ) {
			if ( ! is_array( $field ) || ! isset( $field['id'] ) ) {
				continue;
			}

			$field_id = (string) $field['id'];

			if ( array_key_exists( $field_id, $submitted ) ) {
				$values[ $field_id ] = $submitted[ $field_id ];
				continue;
			}

			$missing = $this->missing_submission_render_value( $field );

			if ( $missing['apply'] ) {
				$values[ $field_id ] = $missing['value'];
			}
		}

		return $values;
	}

	/**
	 * Controls like multi-selects and empty repeaters omit their key entirely.
	 *
	 * @param array<string, mixed> $field
	 * @return array{apply: bool, value: mixed}
	 */
	private function missing_submission_render_value( array $field ): array {
		if ( array_key_exists( 'missing_submission_value', $field ) ) {
			return array(
				'apply' => true,
				'value' => $field['missing_submission_value'],
			);
		}

		$type     = sanitize_key( (string) ( $field['type'] ?? 'text' ) );
		$callback = $this->field_types->missing_submission_callback( $type );

		if ( is_callable( $callback ) ) {
			$missing = call_user_func( $callback, $field );

			if ( is_array( $missing ) ) {
				return array(
					'apply' => ! empty( $missing['apply'] ),
					'value' => $missing['value'] ?? null,
				);
			}
		}

		return array(
			'apply' => false,
			'value' => null,
		);
	}

	/**
	 * @param array<string, mixed>|null $flash
	 * @return array<string, array<int, string>>
	 */
	private function section_flash_errors( ?array $flash, string $section_id ): array {
		if ( ! is_array( $flash ) ) {
			return array();
		}

		if ( $this->is_global_flash( $flash ) ) {
			$errors = is_array( $flash['errors'] ?? null ) ? $flash['errors'] : array();

			return $this->filter_section_errors( $errors, $section_id );
		}

		if ( (string) ( $flash['tab'] ?? '' ) !== $section_id ) {
			return array();
		}

		return is_array( $flash['errors'] ?? null ) ? $flash['errors'] : array();
	}

	/**
	 * @return string The sanitized redirect status from the URL, or '' if absent.
	 */
	private function redirect_status(): string {
		return isset( $_GET['lerm_admin_config_status'] )
			? sanitize_key( wp_unslash( $_GET['lerm_admin_config_status'] ) )
			: '';
	}

	/**
	 * @param array<string, mixed>|null $flash
	 * @return array{class: string, message: string}|null
	 */
	private function section_flash_notice( ?array $flash, string $section_id ): ?array {
		if ( is_array( $flash ) && (string) ( $flash['tab'] ?? '' ) === $section_id ) {
			$message = isset( $flash['message'] ) && is_scalar( $flash['message'] ) ? (string) $flash['message'] : '';

			if ( '' === $message ) {
				return null;
			}

			return array(
				'class'   => 'validation_error' === $this->redirect_status() ? 'notice-error' : 'notice-warning',
				'message' => $message,
			);
		}

		if ( 'success' === $this->redirect_status() ) {
			return array(
				'class'   => 'notice-success',
				'message' => __( 'Settings saved.', 'lerm-admin-config' ),
			);
		}

		return null;
	}

	/**
	 * @param array<string, mixed> $flash
	 */
	private function is_global_flash( array $flash ): bool {
		return ! empty( $flash['global'] );
	}

	/**
	 * @param array<string, array<int, string>> $errors
	 * @return array<string, array<int, string>>
	 */
	private function filter_section_errors( array $errors, string $section_id ): array {
		$filtered = array();

		foreach ( $errors as $path => $messages ) {
			if ( $section_id !== $this->field_target( (string) $path )['tab'] ) {
				continue;
			}

			$filtered[ (string) $path ] = $messages;
		}

		return $filtered;
	}

	/**
	 * Resolve the first tab/subsection that contains a validation error.
	 *
	 * @param array<string, array<int, string>> $errors
	 * @return array{tab: string, subsection: string}
	 */
	private function first_validation_target( array $errors ): array {
		$fallback_tab = (string) array_key_first( PageSchema::sections( $this->definition ) );

		foreach ( array_keys( $errors ) as $path ) {
			$target = $this->field_target( (string) $path );

			if ( '' !== $target['tab'] ) {
				return $target;
			}
		}

		return array(
			'tab'        => $fallback_tab,
			'subsection' => '',
		);
	}

	/**
	 * Lazily-built field-id → {tab, subsection} map.
	 *
	 * @return array<string, array{tab: string, subsection: string}>
	 */
	private function field_section_map(): array {
		if ( null !== $this->field_section_map_cache ) {
			return $this->field_section_map_cache;
		}

		$map = array();

		foreach ( PageSchema::sections( $this->definition ) as $section_id => $section ) {
			$groups       = PageSchema::section_groups( $section );
			$use_subsects = $this->section_uses_subsections( $section, $groups );

			foreach ( PageSchema::section_fields( $section ) as $field ) {
				$field_id = (string) ( $field['id'] ?? '' );

				if ( '' === $field_id ) {
					continue;
				}

				$subsection = '';

				if ( $use_subsects ) {
					foreach ( $groups as $group ) {
						foreach ( (array) ( $group['fields'] ?? array() ) as $gf ) {
							if ( (string) ( $gf['id'] ?? '' ) === $field_id ) {
								$subsection = sanitize_key( (string) ( $group['id'] ?? '' ) );
								break 2;
							}
						}
					}
				}

				$map[ $field_id ] = array(
					'tab'        => (string) $section_id,
					'subsection' => $subsection,
				);
			}
		}

		$this->field_section_map_cache = $map;

		return $this->field_section_map_cache;
	}

	/**
	 * Resolve the owning tab/subsection for a dotted field path.
	 *
	 * @return array{tab: string, subsection: string}
	 */
	private function field_target( string $field_path ): array {
		$field_id = sanitize_key( (string) strtok( $field_path, '.' ) );

		return $this->field_section_map()[ $field_id ] ?? array(
			'tab'        => '',
			'subsection' => '',
		);
	}

	private function flash_resource_key(): string {
		return $this->page_slug();
	}

	private function current_render_path(): string {
		if ( empty( $this->render_path_stack ) ) {
			return '';
		}

		return (string) end( $this->render_path_stack );
	}

	/**
	 * Resolve the posted tab from save/reset requests.
	 */
	private function posted_tab(): string {
		$sections = PageSchema::sections( $this->definition );
		$tab      = isset( $_POST['lerm_settings_tab'] ) ? sanitize_key( wp_unslash( $_POST['lerm_settings_tab'] ) ) : (string) array_key_first( $sections );

		if ( ! isset( $sections[ $tab ] ) ) {
			return (string) array_key_first( $sections );
		}

		return $tab;
	}

	/**
	 * Resolve the posted subsection from AJAX reset requests.
	 */
	private function posted_subsection(): string {
		return isset( $_POST['lerm_settings_subsection'] ) ? sanitize_key( wp_unslash( $_POST['lerm_settings_subsection'] ) ) : '';
	}

	/**
	 * Resolve the current tab.
	 */
	private function current_tab(): string {
		$sections = PageSchema::sections( $this->definition );
		$tab      = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : (string) array_key_first( $sections );

		if ( ! isset( $sections[ $tab ] ) ) {
			return (string) array_key_first( $sections );
		}

		return $tab;
	}

	/**
	 * Resolve the capability required to use the page.
	 */
	private function capability(): string {
		$menu = is_array( $this->definition['menu'] ?? null ) ? $this->definition['menu'] : array();

		return (string) ( $menu['capability'] ?? $this->definition['capability'] ?? 'manage_options' );
	}

	/**
	 * Resolve the page slug.
	 */
	private function page_slug(): string {
		$page_id = isset( $this->definition['id'] ) ? sanitize_key( (string) $this->definition['id'] ) : '';

		return '' !== $page_id ? $page_id : 'admin-config';
	}

	/**
	 * Resolve the form input namespace / option name.
	 *
	 * Delegates to the store's backing StorageBackend so that the HTML form
	 * field names always match the key used by whichever backend is active
	 * (option row, term meta, user meta, post meta).
	 */
	private function option_name(): string {
		return $this->store->storage_key();
	}

	/**
	 * Resolve the admin path for the configured parent.
	 */
	private function admin_parent_url(): string {
		$menu   = is_array( $this->definition['menu'] ?? null ) ? $this->definition['menu'] : array();
		$parent = (string) ( $menu['parent_slug'] ?? 'themes.php' );

		if ( false !== strpos( $parent, '.php' ) ) {
			return $this->admin_base_url( $parent );
		}

		return add_query_arg( 'page', $parent, $this->admin_base_url( 'admin.php' ) );
	}

	/**
	 * Resolve the correct WordPress admin menu hook for this page.
	 */
	private function menu_action(): string {
		return $this->network_admin ? 'network_admin_menu' : 'admin_menu';
	}

	/**
	 * Resolve the correct admin base URL for site or network admin.
	 */
	private function admin_base_url( string $path = '' ): string {
		return $this->network_admin ? network_admin_url( $path ) : admin_url( $path );
	}

	/**
	 * Resolve the form target for non-JS submissions.
	 */
	private function admin_post_url(): string {
		return $this->admin_base_url( 'admin-post.php' );
	}

	/**
	 * Nonce action for a section.
	 */
	private function nonce_action( string $tab ): string {
		return 'lerm_admin_config_' . $this->page_slug() . '_' . sanitize_key( $tab );
	}

	/**
	 * Non-JS admin-post action.
	 */
	private function save_action(): string {
		return 'lerm_admin_config_save_' . $this->page_slug();
	}

	/**
	 * Normalize scalar-like values to strings for safe rendering.
	 * Unified with OptionStore::string_value(); both delegate here via PageSchema.
	 *
	 * @param mixed  $value Source value.
	 * @param string $default_value Fallback string.
	 */
	private function scalar_string( $value, string $default_value = '' ): string {
		return PageSchema::scalar_value( $value, $default_value );
	}

	/**
	 * Return the change-listener attribute for fields that control dependencies.
	 *
	 * @param array<string, mixed> $field Field definition.
	 */
	public function dependency_controller_attribute( array $field ): string {
		$field_id = isset( $field['id'] ) ? sanitize_key( (string) $field['id'] ) : '';

		if ( '' === $field_id ) {
			return '';
		}

		return isset( $this->dependency_controller_fields()[ $field_id ] )
			? ' data-lerm-controller="1"'
			: '';
	}

	/**
	 * Resolve whether a field's dependency chain is currently satisfied.
	 *
	 * @param array<string, mixed> $field  Field definition.
	 * @param array<string, mixed> $values Current form values.
	 * @param array<string, bool>  $seen   Recursion guard for malformed cycles.
	 */
	private function dependency_is_satisfied( array $field, array $values, array $seen = array() ): bool {
		$dependency = $this->field_dependency( $field );

		if ( empty( $dependency ) ) {
			return true;
		}

		$field_id = isset( $field['id'] ) ? sanitize_key( (string) $field['id'] ) : '';

		if ( '' !== $field_id && isset( $seen[ $field_id ] ) ) {
			return false;
		}

		$controller_id = (string) $dependency['field'];
		$controller    = PageSchema::field( $this->definition, $controller_id );

		if ( ! is_array( $controller ) ) {
			return false;
		}

		if ( '' !== $field_id ) {
			$seen[ $field_id ] = true;
		}

		if ( ! $this->dependency_is_satisfied( $controller, $values, $seen ) ) {
			return false;
		}

		$actual = array_key_exists( $controller_id, $values )
			? $values[ $controller_id ]
			: ( $controller['default'] ?? '' );

		return $this->dependency_matches(
			$actual,
			(string) $dependency['operator'],
			$dependency['value']
		);
	}

	/**
	 * @return array<string, bool>
	 */
	private function dependency_controller_fields(): array {
		if ( null !== $this->dependency_controller_fields ) {
			return $this->dependency_controller_fields;
		}

		$controllers = array();

		foreach ( PageSchema::fields( $this->definition ) as $field ) {
			$dependency = $this->field_dependency( $field );

			if ( empty( $dependency ) ) {
				continue;
			}

			$controllers[ (string) $dependency['field'] ] = true;
		}

		$this->dependency_controller_fields = $controllers;

		return $controllers;
	}

	/**
	 * @param array<string, mixed> $field Field definition.
	 * @return array<string, mixed>
	 */
	private function field_dependency( array $field ): array {
		$dependency = $field['dependency'] ?? null;

		if ( ! is_array( $dependency ) || empty( $dependency[0] ) ) {
			return array();
		}

		$controller = sanitize_key( (string) $dependency[0] );
		$operator   = isset( $dependency[1] ) && is_scalar( $dependency[1] ) ? trim( (string) $dependency[1] ) : '==';

		if ( '' === $controller ) {
			return array();
		}

		return array(
			'field'    => $controller,
			'operator' => '' !== $operator ? $operator : '==',
			'value'    => $dependency[2] ?? true,
		);
	}

	/**
	 * @param mixed $actual Actual controller value.
	 * @param mixed $expected Expected dependency value.
	 */
	private function dependency_matches( $actual, string $operator, $expected ): bool {
		$operator        = '' !== trim( $operator ) ? trim( $operator ) : '==';
		$actual_values   = $this->dependency_scalar_list( $actual );
		$expected_values = $this->dependency_scalar_list( $expected );
		$expected_value  = (string) ( $expected_values[0] ?? '' );

		if ( '!=' === $operator || '!==' === $operator ) {
			return ! in_array( $expected_value, $actual_values, true );
		}

		if ( 'in' === $operator ) {
			return count( array_intersect( $actual_values, $expected_values ) ) > 0;
		}

		if ( 'not_in' === $operator || 'not in' === $operator ) {
			return 0 === count( array_intersect( $actual_values, $expected_values ) );
		}

		if ( in_array( $operator, array( '>', '>=', '<', '<=' ), true ) ) {
			$actual_number   = isset( $actual_values[0] ) && is_numeric( $actual_values[0] ) ? (float) $actual_values[0] : null;
			$expected_number = is_numeric( $expected_value ) ? (float) $expected_value : null;

			if ( null === $actual_number || null === $expected_number ) {
				return false;
			}

			if ( '>' === $operator ) {
				return $actual_number > $expected_number;
			}

			if ( '>=' === $operator ) {
				return $actual_number >= $expected_number;
			}

			if ( '<' === $operator ) {
				return $actual_number < $expected_number;
			}

			return $actual_number <= $expected_number;
		}

		return in_array( $expected_value, $actual_values, true );
	}

	private function dependency_attribute_value( $value ): string {
		if ( is_array( $value ) ) {
			$encoded = wp_json_encode( array_values( $this->dependency_scalar_list( $value ) ) );

			return false !== $encoded ? $encoded : '';
		}

		return $this->dependency_scalar( $value );
	}

	/**
	 * @param mixed $value Controller value.
	 * @return array<int, string>
	 */
	private function dependency_scalar_list( $value ): array {
		if ( is_array( $value ) ) {
			return array_map( array( $this, 'dependency_scalar' ), $value );
		}

		return array( $this->dependency_scalar( $value ) );
	}

	/**
	 * Normalize a dependency controller value for reliable string comparisons.
	 *
	 * @param mixed $value Controller value.
	 */
	private function dependency_scalar( $value ): string {
		if ( is_bool( $value ) ) {
			return $value ? '1' : '0';
		}

		if ( is_numeric( $value ) ) {
			return (string) $value;
		}

		return PageSchema::scalar_value( $value );
	}

	/**
	 * Asset URL, delegated to the injected AssetResolver.
	 */
	private function asset_url( string $asset ): string {
		return $this->asset_resolver->url( $asset );
	}

	/**
	 * Asset path, delegated to resolvers that expose filesystem locations.
	 */
	private function asset_path( string $asset ): string {
		if ( $this->asset_resolver instanceof AssetPathResolver ) {
			return $this->asset_resolver->path( $asset );
		}

		return PackageAssets::path( $asset );
	}

	/**
	 * Resolve the built JavaScript asset metadata, falling back to the packaged
	 * browser file for source checkouts that have not run the build yet.
	 *
	 * @return array{file: string, dependencies: array<int, string>, version: string}
	 */
	private function script_asset(): array {
		return ScriptAssetMetadata::resolve(
			'admin-config',
			'admin-config.js',
			array( 'wp-theme-plugin-editor', 'wp-api-fetch' ),
			$this->asset_version(),
			function ( string $asset ): string {
				return $this->asset_path( $asset );
			}
		);
	}

	/**
	 * Asset version, delegated to the injected AssetResolver.
	 */
	private function asset_version(): string {
		$version = $this->asset_resolver->version();
		$assets  = array(
			$this->asset_path( 'admin-config.css' ),
			$this->asset_path( 'admin-config.js' ),
		);
		$mtime   = 0;

		foreach ( $assets as $asset_path ) {
			if ( is_readable( $asset_path ) ) {
				$asset_mtime = (int) filemtime( $asset_path );

				if ( $asset_mtime > $mtime ) {
					$mtime = $asset_mtime;
				}
			}
		}

		return $mtime > 0 ? $version . '.' . (string) $mtime : $version;
	}
}
