<?php // phpcs:disable WordPress.Files.FileName
/**
 * Generic native options page container.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Admin;

use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\Framework\Contracts\AssetResolver;
use Lerm\AdminConfig\Framework\Support\PageSchema;
use Lerm\AdminConfig\Registry\FieldModuleRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OptionsPage {

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
		$this->js_global = 'lermAdminConfig';

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
			(string) ( $menu['page_title'] ?? __( 'Admin Config', 'lerm' ) ),
			(string) ( $menu['menu_title'] ?? __( 'Admin Config', 'lerm' ) ),
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
			wp_die( esc_html__( 'You are not allowed to manage these settings.', 'lerm' ) );
		}

		$tab        = $this->posted_tab();
		$subsection = $this->posted_subsection();

		check_admin_referer( $this->nonce_action( $tab ) );

		$submitted = isset( $_POST[ $this->option_name() ] ) && is_array( $_POST[ $this->option_name() ] )
			? wp_unslash( $_POST[ $this->option_name() ] )
			: array();

		$success             = $this->store->save_all( $submitted );
		$status              = 'success';
		$redirect_tab        = $tab;
		$redirect_subsection = $subsection;

		if ( $this->store->has_validation_errors() ) {
			$error_target = $this->first_validation_target( $this->store->validation_errors() );

			$status              = 'validation_error';
			$redirect_tab        = $error_target['tab'];
			$redirect_subsection = $error_target['subsection'];
			$this->store_flash(
				array(
					'tab'        => $redirect_tab,
					'subsection' => $redirect_subsection,
					'global'     => true,
					'message'    => __( 'Please review the highlighted fields before saving again.', 'lerm' ),
					'errors'     => $this->store->validation_errors(),
					'submitted'  => $submitted,
				)
			);
		} elseif ( ! $success ) {
			$status = 'error';
			$this->store_flash(
				array(
					'tab'        => $redirect_tab,
					'subsection' => $redirect_subsection,
					'global'     => true,
					'message'    => __( 'Unable to save these settings right now.', 'lerm' ),
					'errors'     => array(),
					'submitted'  => $submitted,
				)
			);
		} else {
			$this->clear_flash();
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

		wp_localize_script(
			$js_handle,
			$this->js_global,
			array(
				'restUrl'             => rest_url( 'lerm-admin-config/v1/' ),
				'restNonce'           => wp_create_nonce( 'wp_rest' ),
				'codeEditor'          => $code_editor_settings,
				'selectMedia'         => __( 'Choose image', 'lerm' ),
				'useMedia'            => __( 'Use this image', 'lerm' ),
				'selectFile'          => __( 'Choose file', 'lerm' ),
				'useFile'             => __( 'Use this file', 'lerm' ),
				'selectImages'        => __( 'Choose images', 'lerm' ),
				'useImages'           => __( 'Use these images', 'lerm' ),
				'removeMedia'         => __( 'Remove image', 'lerm' ),
				'clearGallery'        => __( 'Clear gallery', 'lerm' ),
				'noMedia'             => __( 'No image selected.', 'lerm' ),
				'noGallery'           => __( 'No gallery images selected.', 'lerm' ),
				'searchPrompt'        => __( 'Start typing to search.', 'lerm' ),
				'searchMinPrompt'     => __( 'Type more characters to search.', 'lerm' ),
				'loadingResults'      => __( 'Loading results...', 'lerm' ),
				'noResults'           => __( 'No matching results found.', 'lerm' ),
				'loadMoreResults'     => __( 'Load more', 'lerm' ),
				'clearSelection'      => __( 'Clear selection', 'lerm' ),
				'removeSelection'     => __( 'Remove selection', 'lerm' ),
				'saving'              => __( 'Saving...', 'lerm' ),
				'saveSuccess'         => __( 'Settings saved.', 'lerm' ),
				'saveError'           => __( 'Unable to save the settings right now.', 'lerm' ),
				'resetting'           => __( 'Resetting...', 'lerm' ),
				'resetSectionSuccess' => __( 'The current page has been reset to defaults.', 'lerm' ),
				'resetAllSuccess'     => __( 'All sections have been reset to defaults.', 'lerm' ),
				'resetError'          => __( 'Unable to reset the settings right now.', 'lerm' ),
				'confirmResetSection' => __( 'Reset the current page back to its default values?', 'lerm' ),
				'confirmResetAll'     => __( 'Reset every section on this page back to default values?', 'lerm' ),
				'confirmNavigate'     => __( 'You have unsaved changes in this tab. Leave without saving?', 'lerm' ),
				'confirmLeave'        => __( 'You have unsaved changes that have not been saved yet.', 'lerm' ),
				'statusReady'         => __( 'Synced', 'lerm' ),
				'statusDirty'         => __( 'Unsaved changes', 'lerm' ),
				'statusSaving'        => __( 'Saving...', 'lerm' ),
				'statusResetting'     => __( 'Resetting...', 'lerm' ),
				'statusSaved'         => __( 'Saved', 'lerm' ),
				'statusError'         => __( 'Error', 'lerm' ),
				'groupAdd'            => __( 'Add item', 'lerm' ),
				'groupRemove'         => __( 'Remove', 'lerm' ),
				'groupEmpty'          => __( 'No items added yet.', 'lerm' ),
				'confirmRemoveItem'   => __( 'Remove this item?', 'lerm' ),
				'exportSuccess'       => __( 'Current settings snapshot generated.', 'lerm' ),
				'importSuccess'       => __( 'Settings imported successfully.', 'lerm' ),
				'importError'         => __( 'Unable to import the provided settings JSON.', 'lerm' ),
				'confirmImport'       => __( 'Importing will overwrite the current saved settings. Continue?', 'lerm' ),
				'debugCopy'           => __( 'Copy JSON', 'lerm' ),
				'debugCopied'         => __( 'Copied', 'lerm' ),
			)
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
		$flash       = $this->consume_flash();
		?>
		<div class="wrap lerm-settings-wrap">
			<div class="lerm-settings-shell">
				<aside class="lerm-settings-sidebar">
					<div class="lerm-settings-sidebar__brand">
						<p class="lerm-settings-eyebrow"><?php echo esc_html( (string) ( $view['eyebrow'] ?? __( 'Native admin', 'lerm' ) ) ); ?></p>
						<h1><?php echo esc_html( (string) ( $view['title'] ?? __( 'Admin Config', 'lerm' ) ) ); ?></h1>
						<p><?php echo esc_html( (string) ( $view['description'] ?? __( 'A native, extensible settings page built on schema, storage, and reusable field renderers.', 'lerm' ) ) ); ?></p>
					</div>

					<nav class="lerm-settings-nav" aria-label="<?php esc_attr_e( 'Settings sections', 'lerm' ); ?>">
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
										sprintf( _n( '%s field', '%s fields', $section_field_count, 'lerm' ), number_format_i18n( $section_field_count ) )
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
								<p class="lerm-settings-eyebrow"><?php esc_html_e( 'Current section', 'lerm' ); ?></p>
								<h2 data-lerm-tab-intro-title><?php echo esc_html( (string) ( $active_section['title'] ?? '' ) ); ?></h2>
								<p data-lerm-tab-intro-desc><?php echo esc_html( (string) ( $active_section['description'] ?? '' ) ); ?></p>
							</div>
						</div>

						<?php
						$active_section_definition = is_array( $active_section ) ? $active_section : array();
						$active_section_fields     = PageSchema::section_fields( $active_section_definition );
						$active_section_groups     = $this->group_fields( $active_section_definition, $active_section_fields );
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
								$section_groups     = $this->group_fields( $section, $section_fields );
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
										<button type="submit" class="button button-primary button-large" data-lerm-save><?php esc_html_e( 'Save changes', 'lerm' ); ?></button>
										<button type="button" class="button button-secondary" data-lerm-reset="section"><?php esc_html_e( 'Reset current page', 'lerm' ); ?></button>
										<button type="button" class="button button-secondary button-link-delete" data-lerm-reset="all"><?php esc_html_e( 'Reset all tabs', 'lerm' ); ?></button>
										<span class="spinner lerm-settings-spinner"></span>
										<span class="lerm-settings-actions__hint"><?php esc_html_e( 'Changes are saved instantly without reloading the page. Use Ctrl/Cmd + S to save faster.', 'lerm' ); ?></span>
										<span class="lerm-settings-actions__spacer" aria-hidden="true"></span>
										<span class="lerm-status-pill lerm-settings-actions__status" data-lerm-status="idle"><?php esc_html_e( 'Synced', 'lerm' ); ?></span>
									</div>
								</div>

								<?php if ( $use_subsections ) : ?>
									<div class="lerm-settings-sticky-wrap lerm-settings-sticky-wrap--subnav" data-lerm-sticky-wrap>
										<?php /* translators: %s: section title. */ ?>
										<nav class="lerm-settings-subnav lerm-settings-subnav--sticky lerm-settings-sticky-bar" data-lerm-sticky-bar aria-label="<?php echo esc_attr( sprintf( __( '%s groups', 'lerm' ), (string) ( $section['title'] ?? __( 'Section', 'lerm' ) ) ) ); ?>">
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
														<div class="lerm-settings-empty-group"><?php esc_html_e( 'No settings in this group yet.', 'lerm' ); ?></div>
													<?php endif; ?>
												</div>
											</section>
										<?php endforeach; ?>
									</div>
								<?php else : ?>
									<div class="lerm-settings-stack" role="group" aria-label="<?php echo esc_attr( (string) ( $section['title'] ?? __( 'Section', 'lerm' ) ) ); ?>">
										<?php $this->render_fields( $section_fields, $section_values, (string) $section_id, true, 'stack', $section_errors ); ?>
									</div>
								<?php endif; ?>

								<div class="lerm-settings-actions lerm-settings-actions--footer">
									<button type="submit" class="button button-primary button-large" data-lerm-save><?php esc_html_e( 'Save changes', 'lerm' ); ?></button>
									<button type="button" class="button button-secondary" data-lerm-reset="section"><?php esc_html_e( 'Reset current page', 'lerm' ); ?></button>
									<button type="button" class="button button-secondary button-link-delete" data-lerm-reset="all"><?php esc_html_e( 'Reset all tabs', 'lerm' ); ?></button>
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
					<h3><?php esc_html_e( 'Runtime Debug', 'lerm' ); ?></h3>
					<p><?php esc_html_e( 'Schema, storage, module, and data-source summary for this admin screen.', 'lerm' ); ?></p>
				</div>
				<button type="button" class="button button-secondary" data-lerm-debug-copy><?php esc_html_e( 'Copy JSON', 'lerm' ); ?></button>
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
	 * Group fields by their configured subsection definition.
	 *
	 * @param array<string, mixed>             $section Section definition.
	 * @param array<int, array<string, mixed>> $fields  Field definitions.
	 * @return array<int, array<string, mixed>>
	 */
	private function group_fields( array $section, array $fields ): array {
		unset( $fields );

		return PageSchema::section_groups( $section );
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

			$this->render_field( $field, $values, $section_id, $layout, $field_errors );
		}
	}

	/**
	 * Render a single field row.
	 *
	 * @param array<string, mixed> $field      Field definition.
	 * @param array<string, mixed> $values     Saved values.
	 * @param string               $section_id Current section ID.
	 * @param string               $layout Layout mode.
	 */
	public function render_field( array $field, array $values, string $section_id = '', string $layout = 'table', array $field_errors = array() ): void {
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

		if ( 'stack' === $layout ) {
			if ( '' === $label ) {
				$row_attrs[0] = 'class="lerm-settings-row lerm-settings-row--nolabel' . ( $has_errors ? ' is-invalid' : '' ) . '"';
			}

			echo '<div ' . implode( ' ', $row_attrs ) . '>';

			if ( '' !== $label ) {
				printf(
					'<div class="lerm-settings-row__head"><label for="%1$s">%2$s</label></div>',
					esc_attr( $field_id ),
					esc_html( $label )
				);
			}

			echo '<div class="lerm-settings-row__body">';
		} else {
			echo '<tr ' . implode( ' ', $row_attrs ) . '>';

			if ( '' !== $label ) {
				printf(
					'<th scope="row"><label for="%1$s">%2$s</label></th>',
					esc_attr( $field_id ),
					esc_html( $label )
				);
			} else {
				echo '<th scope="row"></th>';
			}

			echo '<td>';
		}

		$custom_render = $this->field_types->render_callback( $field_type );

		$previous_errors           = $this->render_field_errors;
		$this->render_field_errors = $field_errors;
		$this->render_path_stack[] = $field_id;

		try {
			if ( is_callable( $custom_render ) ) {
				call_user_func( $custom_render, $field, $field_value, $field_name, $this );
			} else {
				printf(
					'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text" %5$s placeholder="%6$s">',
					esc_attr( (string) ( $field['input_type'] ?? 'text' ) ),
					esc_attr( $field_id ),
					esc_attr( $field_name ),
					esc_attr( $this->scalar_string( $field_value ) ),
					$this->dependency_controller_attribute( $field ),
					esc_attr( (string) ( $field['placeholder'] ?? '' ) )
				);
			}
		} finally {
			array_pop( $this->render_path_stack );
			$this->render_field_errors = $previous_errors;
		}

		if ( $description ) {
			printf( '<p class="description">%s</p>', esc_html( $description ) );
		}

		if ( '' !== $field_error ) {
			printf( '<p class="lerm-field-error" data-lerm-field-error-message>%s</p>', esc_html( $field_error ) );
		}

		if ( 'stack' === $layout ) {
			echo '</div></div>';
			return;
		}

		echo '</td></tr>';
	}

	/**
	 * Render read-only notice / helper HTML.
	 *
	 * @param array<string, mixed> $field Field definition.
	 */
	public function render_notice_field( array $field ): void {
		$html = isset( $field['html'] ) && is_scalar( $field['html'] ) ? (string) $field['html'] : '';

		if ( '' === trim( $html ) ) {
			return;
		}

		echo '<div class="lerm-settings-notice">';
		echo wp_kses(
			$html,
			array(
				'a'      => array(
					'href'   => true,
					'target' => true,
					'rel'    => true,
					'class'  => true,
				),
				'br'     => array(),
				'code'   => array(),
				'em'     => array(),
				'p'      => array(
					'class' => true,
				),
				'span'   => array(
					'class' => true,
				),
				'strong' => array(),
			)
		);
		echo '</div>';
	}

	/**
	 * Render backup export/import controls.
	 *
	 * @param array<string, mixed> $field Field definition.
	 */
	public function render_backup_tools_field( array $field ): void {
		$export_label = (string) ( $field['export_label'] ?? __( 'Export current settings', 'lerm' ) );
		$import_label = (string) ( $field['import_label'] ?? __( 'Import settings JSON', 'lerm' ) );
		$placeholder  = (string) ( $field['placeholder'] ?? __( '{ "example": "Paste a backup snapshot here" }', 'lerm' ) );

		echo '<div class="lerm-backup-tools">';
		echo '<div class="lerm-backup-tools__block">';
		echo '<div class="lerm-backup-tools__header">';
		echo '<strong>' . esc_html( $export_label ) . '</strong>';
		echo '<button type="button" class="button button-secondary" data-lerm-backup-export>' . esc_html__( 'Generate snapshot', 'lerm' ) . '</button>';
		echo '</div>';
		echo '<textarea class="large-text code lerm-backup-tools__export" rows="10" readonly data-lerm-backup-export-output></textarea>';
		echo '</div>';
		echo '<div class="lerm-backup-tools__block">';
		echo '<div class="lerm-backup-tools__header">';
		echo '<strong>' . esc_html( $import_label ) . '</strong>';
		echo '<button type="button" class="button button-primary" data-lerm-backup-import>' . esc_html__( 'Import snapshot', 'lerm' ) . '</button>';
		echo '</div>';
		echo '<textarea class="large-text code lerm-backup-tools__import" rows="10" data-lerm-backup-import-input placeholder="' . esc_attr( $placeholder ) . '"></textarea>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Render fieldsets as a compact grid of nested controls.
	 *
	 * @param array<string, mixed> $field      Field definition.
	 * @param mixed                $value      Field value.
	 * @param string               $section_id Current section ID.
	 */
	public function render_fieldset_field( array $field, $value, string $field_name, string $section_id = '' ): void {
		$field_id = (string) $field['id'];
		$values   = is_array( $value ) ? $value : array();
		$fields   = is_array( $field['fields'] ?? null ) ? $field['fields'] : array();
		$path     = $this->resolve_render_path( $field_id );
		$invalid  = $this->field_has_errors( $this->render_field_errors, $path, true );
		$classes  = array_filter(
			array_map(
				'trim',
				explode( ' ', 'lerm-fieldset ' . (string) ( $field['wrapper_class'] ?? '' ) )
			)
		);

		if ( $invalid ) {
			$classes[] = 'is-invalid';
		}

		echo '<div class="' . esc_attr( implode( ' ', array_unique( $classes ) ) ) . '" data-target="' . esc_attr( $field_id ) . '" data-field-path="' . esc_attr( $path ) . '">';
		$this->render_container_child_fields( $fields, $values, $field_name, $field_id, $path );
		echo '</div>';
	}

	/**
	 * Render accordion field panels.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Field value.
	 */
	public function render_accordion_field( array $field, $value, string $field_name ): void {
		$field_id       = (string) $field['id'];
		$field_path     = $this->resolve_render_path( $field_id );
		$values         = is_array( $value ) ? $value : array();
		$items          = $this->panel_items( $field );
		$allow_multiple = ! empty( $field['allow_multiple'] );
		$open_first     = ! array_key_exists( 'open_first', $field ) || ! empty( $field['open_first'] );
		$invalid        = $this->field_has_errors( $this->render_field_errors, $field_path, true );

		echo '<div class="lerm-fieldset lerm-accordion-field' . ( $invalid ? ' is-invalid' : '' ) . '" data-target="' . esc_attr( $field_id ) . '" data-field-path="' . esc_attr( $field_path ) . '" data-lerm-accordion data-allow-multiple="' . esc_attr( $allow_multiple ? '1' : '0' ) . '">';

		foreach ( $items as $index => $item ) {
			$item_id      = (string) $item['id'];
			$item_path    = $this->compose_render_path( $field_path, $item_id );
			$item_title   = (string) $item['title'];
			$item_desc    = (string) ( $item['description'] ?? '' );
			$item_fields  = is_array( $item['fields'] ?? null ) ? $item['fields'] : array();
			$item_values  = is_array( $values[ $item_id ] ?? null ) ? $values[ $item_id ] : array();
			$item_invalid = $this->field_has_errors( $this->render_field_errors, $item_path, true );
			$is_open      = $item_invalid || ! empty( $item['open'] ) || ( $open_first && 0 === $index );
			$panel_id     = $field_id . '__' . $item_id;
			$button_id    = $panel_id . '__button';

			echo '<section class="lerm-accordion__item' . ( $item_invalid ? ' is-invalid' : '' ) . ( $is_open ? ' is-open' : '' ) . '" data-item-id="' . esc_attr( $item_id ) . '">';
			echo '<button type="button" id="' . esc_attr( $button_id ) . '" class="lerm-accordion__trigger" data-lerm-accordion-trigger aria-expanded="' . esc_attr( $is_open ? 'true' : 'false' ) . '" aria-controls="' . esc_attr( $panel_id ) . '">';
			echo '<span>' . esc_html( $item_title ) . '</span>';
			echo '<span class="lerm-accordion__chevron" aria-hidden="true"></span>';
			echo '</button>';
			echo '<div id="' . esc_attr( $panel_id ) . '" class="lerm-accordion__panel" data-lerm-accordion-panel aria-labelledby="' . esc_attr( $button_id ) . '"' . ( $is_open ? '' : ' hidden' ) . '>';

			if ( '' !== $item_desc ) {
				echo '<p class="description lerm-accordion__description">' . esc_html( $item_desc ) . '</p>';
			}

			$this->render_container_child_fields(
				$item_fields,
				$item_values,
				$field_name . '[' . $item_id . ']',
				$field_id . '__' . $item_id,
				$item_path
			);
			echo '</div></section>';
		}

		echo '</div>';
	}

	/**
	 * Render tabbed field panels.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Field value.
	 */
	public function render_tabbed_field( array $field, $value, string $field_name ): void {
		$field_id   = (string) $field['id'];
		$field_path = $this->resolve_render_path( $field_id );
		$values     = is_array( $value ) ? $value : array();
		$items      = $this->panel_items( $field );
		$active_tab = sanitize_key( (string) ( $field['default_tab'] ?? '' ) );

		if ( '' === $active_tab && ! empty( $items[0]['id'] ) ) {
			$active_tab = (string) $items[0]['id'];
		}

		foreach ( $items as $item ) {
			$item_id   = (string) ( $item['id'] ?? '' );
			$item_path = $this->compose_render_path( $field_path, $item_id );

			if ( '' !== $item_id && $this->field_has_errors( $this->render_field_errors, $item_path, true ) ) {
				$active_tab = $item_id;
				break;
			}
		}

		echo '<div class="lerm-fieldset lerm-tabbed-field' . ( $this->field_has_errors( $this->render_field_errors, $field_path, true ) ? ' is-invalid' : '' ) . '" data-target="' . esc_attr( $field_id ) . '" data-field-path="' . esc_attr( $field_path ) . '" data-lerm-tabbed data-default-tab="' . esc_attr( $active_tab ) . '">';
		echo '<div class="lerm-tabbed__nav" role="tablist">';

		foreach ( $items as $index => $item ) {
			$item_id      = (string) $item['id'];
			$item_path    = $this->compose_render_path( $field_path, $item_id );
			$item_invalid = $this->field_has_errors( $this->render_field_errors, $item_path, true );
			$is_active    = $item_id === $active_tab || ( '' === $active_tab && 0 === $index );
			$panel_id     = $field_id . '__' . $item_id;
			$trigger_id   = $panel_id . '__tab';

			echo '<button type="button" id="' . esc_attr( $trigger_id ) . '" class="lerm-tabbed__trigger' . ( $is_active ? ' is-active' : '' ) . ( $item_invalid ? ' is-invalid' : '' ) . '" data-lerm-tabbed-trigger data-lerm-tabbed-target="' . esc_attr( $item_id ) . '" role="tab" aria-selected="' . esc_attr( $is_active ? 'true' : 'false' ) . '" aria-controls="' . esc_attr( $panel_id ) . '" tabindex="' . esc_attr( $is_active ? '0' : '-1' ) . '">';
			echo esc_html( (string) $item['title'] );
			echo '</button>';
		}

		echo '</div><div class="lerm-tabbed__panels">';

		foreach ( $items as $index => $item ) {
			$item_id      = (string) $item['id'];
			$item_path    = $this->compose_render_path( $field_path, $item_id );
			$item_desc    = (string) ( $item['description'] ?? '' );
			$item_fields  = is_array( $item['fields'] ?? null ) ? $item['fields'] : array();
			$item_values  = is_array( $values[ $item_id ] ?? null ) ? $values[ $item_id ] : array();
			$item_invalid = $this->field_has_errors( $this->render_field_errors, $item_path, true );
			$is_active    = $item_id === $active_tab || ( '' === $active_tab && 0 === $index );
			$panel_id     = $field_id . '__' . $item_id;
			$trigger_id   = $panel_id . '__tab';

			echo '<section id="' . esc_attr( $panel_id ) . '" class="lerm-tabbed__panel' . ( $item_invalid ? ' is-invalid' : '' ) . '" data-item-id="' . esc_attr( $item_id ) . '" data-lerm-tabbed-panel="' . esc_attr( $item_id ) . '" role="tabpanel" aria-labelledby="' . esc_attr( $trigger_id ) . '"' . ( $is_active ? '' : ' hidden' ) . '>';

			if ( '' !== $item_desc ) {
				echo '<p class="description lerm-tabbed__description">' . esc_html( $item_desc ) . '</p>';
			}

			$this->render_container_child_fields(
				$item_fields,
				$item_values,
				$field_name . '[' . $item_id . ']',
				$field_id . '__' . $item_id,
				$item_path
			);
			echo '</section>';
		}

		echo '</div></div>';
	}

	/**
	 * Render repeatable groups.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Field value.
	 */
	public function render_group_field( array $field, $value, string $field_name ): void {
		$field_id    = (string) $field['id'];
		$field_path  = $this->resolve_render_path( $field_id );
		$items       = is_array( $value ) ? array_values( $value ) : array();
		$button_text = (string) ( $field['button_text'] ?? __( 'Add item', 'lerm' ) );

		echo '<div class="lerm-group' . ( $this->field_has_errors( $this->render_field_errors, $field_path, true ) ? ' is-invalid' : '' ) . '" data-target="' . esc_attr( $field_id ) . '" data-field-path="' . esc_attr( $field_path ) . '">';
		echo '<div class="lerm-group__toolbar">';
		echo '<button type="button" class="button button-secondary" data-lerm-group-add>' . esc_html( $button_text ) . '</button>';
		echo '</div>';
		echo '<div class="lerm-group__empty" ' . ( ! empty( $items ) ? 'hidden' : '' ) . '>' . esc_html__( 'No items added yet.', 'lerm' ) . '</div>';
		echo '<div class="lerm-group-list" data-lerm-group-list>';

		foreach ( $items as $index => $item ) {
			echo $this->group_item_markup( $field, $field_name, is_array( $item ) ? $item : array(), (string) $index, $field_path, $this->compose_render_path( $field_path, '__INDEX__' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '</div>';
		echo '<script type="text/html" class="lerm-group-template">' . $this->group_item_markup( $field, $field_name, array(), '__INDEX__', $field_path, $this->compose_render_path( $field_path, '__INDEX__' ) ) . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}

	/**
	 * Build one repeatable group item.
	 *
	 * @param array<string, mixed> $field Group definition.
	 * @param array<string, mixed> $item  Current item values.
	 */
	private function group_item_markup( array $field, string $field_name, array $item, string $index, string $field_path = '', string $path_template = '' ): string {
		$fields          = is_array( $field['fields'] ?? null ) ? $field['fields'] : array();
		$item_path       = $this->compose_render_path( $field_path, $index );
		$item_has_errors = $this->field_has_errors( $this->render_field_errors, $item_path, true );

		ob_start();
		?>
		<div class="lerm-group-item<?php echo esc_attr( $item_has_errors ? ' is-invalid' : '' ); ?>" data-lerm-group-item data-index="<?php echo esc_attr( $index ); ?>" data-field-path="<?php echo esc_attr( $item_path ); ?>" data-field-path-template="<?php echo esc_attr( $path_template ); ?>">
			<div class="lerm-group-item__header">
				<span class="lerm-sorter-handle" aria-hidden="true">&#8645;</span>
				<strong class="lerm-group-item__title">
				<?php
				// translators: %s is the item number in the group, starting from 1. For example: "Item 1", "Item 2", etc. Do not translate the number itself.
				echo esc_html( sprintf( __( 'Item %s', 'lerm' ), is_numeric( $index ) ? (string) ( (int) $index + 1 ) : '#' ) );
				?>
				</strong>
				<button type="button" class="button button-secondary button-link-delete" data-lerm-group-remove><?php esc_html_e( 'Remove', 'lerm' ); ?></button>
			</div>
			<div class="lerm-group-item__body">
				<?php
				$this->render_container_child_fields(
					$fields,
					$item,
					$field_name . '[' . $index . ']',
					(string) $field['id'] . '__' . $index,
					$item_path,
					$this->compose_render_path( $path_template, '' ),
					'lerm-group-item__field'
				);
				?>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Render a nested sub-field for fieldsets and repeaters.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Field value.
	 */
	private function render_nested_field( array $field, $value, string $field_name, string $input_id, string $name_template = '', string $id_template = '' ): void {
		$field_type    = sanitize_key( (string) ( $field['type'] ?? 'text' ) );
		$name_attr     = '' !== $name_template ? ' data-name-template="' . esc_attr( $name_template ) . '"' : '';
		$id_attr       = '' !== $id_template ? ' data-id-template="' . esc_attr( $id_template ) . '"' : '';
		$custom_render = $this->field_types->nested_render_callback( $field_type );

		if ( is_callable( $custom_render ) ) {
			call_user_func( $custom_render, $field, $value, $field_name, $input_id, $this, $name_template, $id_template );
			return;
		}

		printf(
			'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text" placeholder="%5$s"%6$s%7$s>',
			esc_attr( (string) ( $field['input_type'] ?? 'text' ) ),
			esc_attr( $input_id ),
			esc_attr( $field_name ),
			esc_attr( $this->scalar_string( $value ) ),
			esc_attr( (string) ( $field['placeholder'] ?? '' ) ),
			$name_attr,
			$id_attr
		);
	}

	/**
	 * Render a flat set of child controls inside a structured container.
	 *
	 * @param array<int, array<string, mixed>> $fields Child field definitions.
	 * @param array<string, mixed>             $values Child field values.
	 */
	private function render_container_child_fields( array $fields, array $values, string $field_name, string $field_id, string $base_path = '', string $base_path_template = '', string $item_class = 'lerm-fieldset__item' ): void {
		$base_path          = '' !== $base_path ? $base_path : $this->current_render_path();
		$base_path_template = '' !== $base_path_template ? $base_path_template : $base_path;

		foreach ( $fields as $child ) {
			if ( ! is_array( $child ) || ! isset( $child['id'] ) ) {
				continue;
			}

			$child_id    = (string) $child['id'];
			$child_name  = $field_name . '[' . $child_id . ']';
			$child_value = $values[ $child_id ] ?? ( $child['default'] ?? '' );
			$child_path  = $this->compose_render_path( $base_path, $child_id );
			$error       = $this->field_error_message( $this->render_field_errors, $child_path );
			$has_errors  = $this->field_has_errors( $this->render_field_errors, $child_path, true );
			$classes     = trim( $item_class . ( $has_errors ? ' is-invalid' : '' ) );

			echo '<div class="' . esc_attr( $classes ) . '" data-subfield-id="' . esc_attr( $child_id ) . '" data-field-type="' . esc_attr( sanitize_key( (string) ( $child['type'] ?? 'text' ) ) ) . '" data-field-path="' . esc_attr( $child_path ) . '"';

			if ( '' !== $base_path_template ) {
				echo ' data-field-path-template="' . esc_attr( $this->compose_render_path( $base_path_template, $child_id ) ) . '"';
			}

			echo '>';
			echo '<label class="lerm-fieldset__label" for="' . esc_attr( $field_id . '__' . $child_id ) . '">' . esc_html( (string) ( $child['label'] ?? $child_id ) ) . '</label>';
			$this->render_nested_field( $child, $child_value, $child_name, $field_id . '__' . $child_id );

			if ( ! empty( $child['description'] ) ) {
				echo '<p class="description">' . esc_html( (string) $child['description'] ) . '</p>';
			}

			if ( '' !== $error ) {
				printf( '<p class="lerm-field-error" data-lerm-field-error-message>%s</p>', esc_html( $error ) );
			}

			echo '</div>';
		}
	}

	/**
	 * Render a code editor field.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Field value.
	 */
	public function render_code_editor_field( array $field, $value, string $field_name, string $input_id, string $name_template = '', string $id_template = '' ): void {
		printf(
			'<textarea id="%1$s" name="%2$s" class="large-text lerm-code-editor" rows="%3$s" placeholder="%4$s"%5$s%6$s>%7$s</textarea>',
			esc_attr( $input_id ),
			esc_attr( $field_name ),
			esc_attr( (string) ( $field['rows'] ?? 10 ) ),
			esc_attr( (string) ( $field['placeholder'] ?? '' ) ),
			'' !== $name_template ? ' data-name-template="' . esc_attr( $name_template ) . '"' : '',
			'' !== $id_template ? ' data-id-template="' . esc_attr( $id_template ) . '"' : '',
			esc_textarea( $this->scalar_string( $value ) )
		);
	}

	/**
	 * Render a WordPress editor field.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Field value.
	 */
	public function render_wp_editor_field( array $field, $value, string $field_name, string $input_id, bool $rich_editor = true, string $name_template = '', string $id_template = '' ): void {
		if ( ! $rich_editor ) {
			printf(
				'<textarea id="%1$s" name="%2$s" class="large-text" rows="%3$s"%4$s%5$s>%6$s</textarea>',
				esc_attr( $input_id ),
				esc_attr( $field_name ),
				esc_attr( (string) ( $field['rows'] ?? 6 ) ),
				'' !== $name_template ? ' data-name-template="' . esc_attr( $name_template ) . '"' : '',
				'' !== $id_template ? ' data-id-template="' . esc_attr( $id_template ) . '"' : '',
				esc_textarea( $this->scalar_string( $value ) )
			);
			return;
		}

		$editor_args = array_merge(
			array(
				'textarea_name' => $field_name,
				'textarea_rows' => 6,
			),
			(array) ( $field['editor_args'] ?? array() )
		);

		wp_editor(
			$this->scalar_string( $value ),
			sanitize_html_class( 'lerm-' . $input_id ),
			$editor_args
		);
	}

	/**
	 * Render a media picker field.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Field value.
	 */
	public function render_media_field( array $field, $value, ?string $field_name = null, ?string $target = null, string $name_attr = '', string $id_attr = '' ): void {
		$field_id      = (string) $field['id'];
		$name_prefix   = $field_name ?? ( $this->option_name() . '[' . $field_id . ']' );
		$target        = $target ?? $field_id;
		$attachment_id = is_array( $value ) ? absint( $value['id'] ?? 0 ) : absint( $value );
		$image_url     = '';

		if ( $attachment_id > 0 ) {
			$image_url = (string) wp_get_attachment_image_url( $attachment_id, 'medium' );
		}

		if ( '' === $image_url && is_array( $value ) ) {
			$image_url = $this->scalar_string( $value['thumbnail'] ?? $value['url'] ?? '' );
		}

		$button_text = (string) ( $field['button_text'] ?? __( 'Choose image', 'lerm' ) );

		printf(
			'<div class="lerm-media-field" data-target="%1$s"><input type="hidden" name="%2$s[id]" value="%3$s"%8$s%9$s><div class="lerm-media-preview" %10$s>%4$s</div><div class="lerm-media-actions"><button type="button" class="button lerm-media-select">%5$s</button><button type="button" class="button button-secondary button-link-delete lerm-media-remove" %6$s>%7$s</button></div></div>',
			esc_attr( $target ),
			esc_attr( $name_prefix ),
			esc_attr( (string) $attachment_id ),
			$image_url ? '<img src="' . esc_url( $image_url ) . '" alt="">' : '',
			esc_html( $button_text ),
			$attachment_id > 0 ? '' : 'hidden',
			esc_html__( 'Remove', 'lerm' ),
			$name_attr,
			$id_attr,
			$image_url ? '' : 'hidden'
		);
	}

	/**
	 * Render a radio or button-set field.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Field value.
	 */
	public function render_choice_field( array $field, $value, string $field_name ): void {
		$choices = PageSchema::choices( $field );
		$current = is_scalar( $value ) ? (string) $value : '';
		$class   = ( ( $field['type'] ?? 'radio' ) === 'button_set' ) ? 'lerm-button-set' : 'lerm-radio-list';

		echo '<fieldset class="' . esc_attr( $class ) . '"><legend class="screen-reader-text">' . esc_html( (string) $field['label'] ) . '</legend>';

		foreach ( $choices as $choice_value => $choice_label ) {
			printf(
				'<label><input type="radio" name="%1$s" value="%2$s" %3$s data-lerm-controller="1"> <span>%4$s</span></label>',
				esc_attr( $field_name ),
				esc_attr( $choice_value ),
				checked( $current, (string) $choice_value, false ),
				esc_html( $choice_label )
			);
		}

		echo '</fieldset>';
	}

	/**
	 * Render a gallery field.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Field value.
	 */
	public function render_gallery_field( array $field, $value, ?string $field_name = null, ?string $target = null, string $name_attr = '', string $id_attr = '' ): void {
		$field_id    = (string) $field['id'];
		$name_prefix = $field_name ?? ( $this->option_name() . '[' . $field_id . ']' );
		$target      = $target ?? $field_id;
		$ids         = $this->normalize_gallery_ids( $value );

		echo '<div class="lerm-gallery-field" data-target="' . esc_attr( $target ) . '">';
		echo '<input type="hidden" name="' . esc_attr( $name_prefix . '[ids]' ) . '" value="' . esc_attr( implode( ',', $ids ) ) . '"' . $name_attr . $id_attr . '>';
		echo '<div class="lerm-gallery-preview" ' . ( empty( $ids ) ? 'hidden' : '' ) . '>';

		if ( ! empty( $ids ) ) {
			foreach ( $ids as $attachment_id ) {
				$thumbnail = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );

				if ( ! $thumbnail ) {
					continue;
				}

				echo '<img src="' . esc_url( $thumbnail ) . '" alt="">';
			}
		}

		echo '</div>';
		echo '<div class="lerm-media-actions">';
		echo '<button type="button" class="button lerm-gallery-select">' . esc_html__( 'Choose images', 'lerm' ) . '</button>';
		echo '<button type="button" class="button button-secondary button-link-delete lerm-gallery-remove" ' . ( empty( $ids ) ? 'hidden' : '' ) . '>' . esc_html__( 'Clear gallery', 'lerm' ) . '</button>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Render a sorter field.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Field value.
	 */
	public function render_sorter_field( array $field, $value ): void {
		$state      = $this->sorter_state( $field, $value );
		$field_id   = (string) $field['id'];
		$name_base  = $this->option_name() . '[' . $field_id . ']';
		$order      = $state['order'];
		$enabled    = $state['enabled'];
		$label_text = (string) $field['label'];

		echo '<div class="lerm-sorter" data-target="' . esc_attr( $field_id ) . '">';
		echo '<p class="description">' . esc_html__( 'Drag to reorder. Checked items stay enabled; unchecked items are hidden.', 'lerm' ) . '</p>';
		echo '<ul class="lerm-sorter-list">';

		foreach ( $order as $key => $label ) {
			$is_enabled = in_array( $key, $enabled, true );
			echo '<li class="lerm-sorter-item">';
			echo '<span class="lerm-sorter-handle" aria-hidden="true">&#8645;</span>';
			echo '<input type="hidden" name="' . esc_attr( $name_base . '[order][]' ) . '" value="' . esc_attr( $key ) . '">';
			echo '<label>';
			echo '<input type="checkbox" name="' . esc_attr( $name_base . '[enabled][]' ) . '" value="' . esc_attr( $key ) . '" ' . checked( $is_enabled, true, false ) . '>';
			echo '<span>' . esc_html( $label ) . '</span>';
			echo '</label>';
			echo '</li>';
		}

		echo '</ul>';
		echo '<span class="screen-reader-text">' . esc_html( $label_text ) . '</span>';
		echo '</div>';
	}

	/**
	 * Normalize sorter values into ordered labels and enabled keys.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Field value.
	 * @return array{order: array<string, string>, enabled: array<int, string>}
	 */
	public function sorter_state( array $field, $value ): array {
		$choices = PageSchema::choices( $field );
		$order   = array();
		$enabled = array();

		if ( is_array( $value ) ) {
			if ( isset( $value['order'] ) && is_array( $value['order'] ) ) {
				$order_keys = array_map( 'strval', $value['order'] );
				$enabled    = isset( $value['enabled'] ) && is_array( $value['enabled'] )
					? array_map( 'strval', $value['enabled'] )
					: array();

				foreach ( $order_keys as $key ) {
					if ( isset( $choices[ $key ] ) ) {
						$order[ $key ] = $choices[ $key ];
					}
				}
			} else {
				$enabled_values  = is_array( $value['enabled'] ?? null ) ? $value['enabled'] : array();
				$disabled_values = is_array( $value['disabled'] ?? null ) ? $value['disabled'] : array();

				foreach ( array_keys( $enabled_values ) as $key ) {
					if ( isset( $choices[ $key ] ) ) {
						$order[ $key ] = $choices[ $key ];
						$enabled[]     = $key;
					}
				}

				foreach ( array_keys( $disabled_values ) as $key ) {
					if ( isset( $choices[ $key ] ) && ! isset( $order[ $key ] ) ) {
						$order[ $key ] = $choices[ $key ];
					}
				}
			}
		}

		foreach ( $choices as $key => $label ) {
			if ( ! isset( $order[ $key ] ) ) {
				$order[ $key ] = $label;
			}
		}

		return array(
			'order'   => $order,
			'enabled' => $enabled,
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
		$type = sanitize_key( (string) ( $field['type'] ?? 'text' ) );

		if ( array_key_exists( 'missing_submission_value', $field ) ) {
			return array(
				'apply' => true,
				'value' => $field['missing_submission_value'],
			);
		}

		if ( 'select' === $type && ! empty( $field['multiple'] ) ) {
			return array(
				'apply' => true,
				'value' => array(),
			);
		}

		if ( 'checkbox_list' === $type || 'group' === $type ) {
			return array(
				'apply' => true,
				'value' => array(),
			);
		}

		if ( 'checkbox' === $type && ! empty( PageSchema::choices( $field ) ) ) {
			return array(
				'apply' => true,
				'value' => array(),
			);
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
	 * @param array<string, mixed>|null $flash
	 * @return array{class: string, message: string}|null
	 */
	private function section_flash_notice( ?array $flash, string $section_id ): ?array {
		if ( ! is_array( $flash ) || (string) ( $flash['tab'] ?? '' ) !== $section_id ) {
			$status = isset( $_GET['lerm_admin_config_status'] ) ? sanitize_key( wp_unslash( $_GET['lerm_admin_config_status'] ) ) : '';

			if ( 'success' !== $status ) {
				return null;
			}

			return array(
				'class'   => 'notice-success',
				'message' => __( 'Settings saved.', 'lerm' ),
			);
		}

		$message = isset( $flash['message'] ) && is_scalar( $flash['message'] ) ? (string) $flash['message'] : '';
		$status  = isset( $_GET['lerm_admin_config_status'] ) ? sanitize_key( wp_unslash( $_GET['lerm_admin_config_status'] ) ) : 'error';

		if ( '' === $message ) {
			return null;
		}

		return array(
			'class'   => 'validation_error' === $status ? 'notice-error' : 'notice-warning',
			'message' => $message,
		);
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
	 * @param array<string, mixed> $field_errors
	 */
	private function field_error_message( array $field_errors, string $field_id ): string {
		return implode( ' ', $this->field_error_messages( $field_errors, $field_id ) );
	}

	/**
	 * @param array<string, mixed> $field_errors
	 * @return array<int, string>
	 */
	private function field_error_messages( array $field_errors, string $field_path, bool $include_descendants = false ): array {
		$messages = array();

		foreach ( $field_errors as $path => $raw_messages ) {
			$is_match = (string) $path === $field_path;

			if ( ! $is_match && $include_descendants ) {
				$is_match = '' !== $field_path && str_starts_with( (string) $path, $field_path . '.' );
			}

			if ( ! $is_match ) {
				continue;
			}

			foreach ( is_array( $raw_messages ) ? $raw_messages : array( $raw_messages ) as $message ) {
				$message = is_scalar( $message ) ? trim( (string) $message ) : '';

				if ( '' === $message || in_array( $message, $messages, true ) ) {
					continue;
				}

				$messages[] = $message;
			}
		}

		return $messages;
	}

	/**
	 * @param array<string, mixed> $field_errors
	 */
	private function field_has_errors( array $field_errors, string $field_path, bool $include_descendants = false ): bool {
		return ! empty( $this->field_error_messages( $field_errors, $field_path, $include_descendants ) );
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
	 * Resolve the owning tab/subsection for a dotted field path.
	 *
	 * @return array{tab: string, subsection: string}
	 */
	private function field_target( string $field_path ): array {
		$field_id = sanitize_key( (string) strtok( $field_path, '.' ) );

		if ( '' === $field_id ) {
			return array(
				'tab'        => '',
				'subsection' => '',
			);
		}

		foreach ( PageSchema::sections( $this->definition ) as $section_id => $section ) {
			$section_fields  = PageSchema::section_fields( $section );
			$section_groups  = $this->group_fields( $section, $section_fields );
			$use_subsections = $this->section_uses_subsections( $section, $section_groups );

			foreach ( $section_fields as $field ) {
				if ( ! is_array( $field ) || (string) ( $field['id'] ?? '' ) !== $field_id ) {
					continue;
				}

				if ( ! $use_subsections ) {
					return array(
						'tab'        => (string) $section_id,
						'subsection' => '',
					);
				}

				foreach ( $section_groups as $group ) {
					foreach ( (array) ( $group['fields'] ?? array() ) as $group_field ) {
						if ( ! is_array( $group_field ) || (string) ( $group_field['id'] ?? '' ) !== $field_id ) {
							continue;
						}

						return array(
							'tab'        => (string) $section_id,
							'subsection' => sanitize_key( (string) ( $group['id'] ?? '' ) ),
						);
					}
				}

				return array(
					'tab'        => (string) $section_id,
					'subsection' => '',
				);
			}
		}

		return array(
			'tab'        => '',
			'subsection' => '',
		);
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	private function store_flash( array $payload ): void {
		set_transient( $this->flash_key(), $payload, MINUTE_IN_SECONDS );
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function consume_flash(): ?array {
		$flash = get_transient( $this->flash_key() );

		if ( ! is_array( $flash ) ) {
			return null;
		}

		delete_transient( $this->flash_key() );

		return $flash;
	}

	private function clear_flash(): void {
		delete_transient( $this->flash_key() );
	}

	private function flash_key(): string {
		return 'lerm_admin_config_flash_' . md5( $this->page_slug() . ':' . (string) get_current_user_id() );
	}

	private function current_render_path(): string {
		if ( empty( $this->render_path_stack ) ) {
			return '';
		}

		return (string) end( $this->render_path_stack );
	}

	private function resolve_render_path( string $field_id ): string {
		$current_path = $this->current_render_path();

		if ( '' === $current_path ) {
			return $field_id;
		}

		if ( '' === $field_id || $current_path === $field_id || str_ends_with( $current_path, '.' . $field_id ) ) {
			return $current_path;
		}

		return $this->compose_render_path( $current_path, $field_id );
	}

	private function compose_render_path( string $base_path, string $segment ): string {
		if ( '' === $segment ) {
			return $base_path;
		}

		if ( '' === $base_path ) {
			return $segment;
		}

		return $base_path . '.' . $segment;
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
	 * Normalize gallery values from stored arrays, legacy strings, or nested ids.
	 *
	 * @param mixed $value Gallery field value.
	 * @return array<int, int>
	 */
	private function normalize_gallery_ids( $value ): array {
		$ids = array();

		if ( is_array( $value ) ) {
			if ( isset( $value['ids'] ) && is_scalar( $value['ids'] ) ) {
				$ids = explode( ',', (string) $value['ids'] );
			} else {
				$ids = $value;
			}
		} elseif ( is_scalar( $value ) ) {
			$ids = explode( ',', (string) $value );
		}

		return array_values(
			array_filter(
				array_map( 'absint', $ids )
			)
		);
	}

	/**
	 * @param array<string, mixed> $field
	 * @return array<int, array<string, mixed>>
	 */
	private function panel_items( array $field ): array {
		$items      = is_array( $field['items'] ?? null ) ? $field['items'] : array();
		$normalized = array();

		foreach ( $items as $index => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$item_id    = isset( $item['id'] ) && is_scalar( $item['id'] ) ? sanitize_key( (string) $item['id'] ) : '';
			$item_title = isset( $item['title'] ) && is_scalar( $item['title'] ) ? (string) $item['title'] : '';
			$item_id    = '' !== $item_id ? $item_id : 'item_' . (string) ( (int) $index + 1 );

			$normalized[] = array(
				'id'          => $item_id,
				'title'       => '' !== $item_title ? $item_title : ucfirst( str_replace( '_', ' ', $item_id ) ),
				'description' => isset( $item['description'] ) && is_scalar( $item['description'] ) ? (string) $item['description'] : '',
				'fields'      => is_array( $item['fields'] ?? null ) ? $item['fields'] : array(),
				'open'        => ! empty( $item['open'] ),
			);
		}

		return $normalized;
	}

	/**
	 * Asset URL, delegated to the injected AssetResolver.
	 */
	private function asset_url( string $asset ): string {
		return $this->asset_resolver->url( $asset );
	}

	/**
	 * Resolve the built JavaScript asset metadata, falling back to the packaged
	 * browser file for source checkouts that have not run the build yet.
	 *
	 * @return array{file: string, dependencies: array<int, string>, version: string}
	 */
	private function script_asset(): array {
		$dependencies = array( 'wp-theme-plugin-editor', 'wp-api-fetch' );
		$fallback     = array(
			'file'         => 'admin-config.js',
			'dependencies' => $dependencies,
			'version'      => $this->asset_version(),
		);
		$asset_dir    = dirname( __DIR__, 3 ) . '/assets';
		$script_file  = $asset_dir . '/build/admin-config.js';
		$asset_file   = $asset_dir . '/build/admin-config.asset.php';

		if ( ! is_readable( $script_file ) || ! is_readable( $asset_file ) ) {
			return $fallback;
		}

		$asset = include $asset_file;

		if ( ! is_array( $asset ) ) {
			return $fallback;
		}

		foreach ( (array) ( $asset['dependencies'] ?? array() ) as $dependency ) {
			if ( is_string( $dependency ) && '' !== $dependency ) {
				$dependencies[] = $dependency;
			}
		}

		return array(
			'file'         => 'build/admin-config.js',
			'dependencies' => array_values( array_unique( $dependencies ) ),
			'version'      => isset( $asset['version'] ) && is_scalar( $asset['version'] )
				? (string) $asset['version']
				: $fallback['version'],
		);
	}

	/**
	 * Asset version, delegated to the injected AssetResolver.
	 */
	private function asset_version(): string {
		$version = $this->asset_resolver->version();
		$assets  = array(
			dirname( __DIR__, 3 ) . '/assets/admin-config.css',
			dirname( __DIR__, 3 ) . '/assets/admin-config.js',
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
