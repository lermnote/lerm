<?php // phpcs:disable WordPress.Files.FileName
/**
 * Generic native options page container.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\OptionsFramework\Admin;

use Lerm\OptionsFramework\Registry\FieldTypeRegistry;
use Lerm\OptionsFramework\Stores\OptionStore;
use Lerm\OptionsFramework\Support\PageSchema;

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

	/**
	 * @param array<string, mixed> $definition Page definition.
	 */
	public function __construct( array $definition, OptionStore $store, FieldTypeRegistry $field_types ) {
		$this->definition  = $definition;
		$this->store       = $store;
		$this->field_types = $field_types;

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_' . $this->save_action(), array( $this, 'handle_save' ) );
		add_action( 'wp_ajax_' . $this->ajax_save_action(), array( $this, 'handle_ajax_save' ) );
		add_action( 'wp_ajax_' . $this->ajax_reset_action(), array( $this, 'handle_ajax_reset' ) );
		add_action( 'wp_ajax_' . $this->ajax_export_action(), array( $this, 'handle_ajax_export' ) );
		add_action( 'wp_ajax_' . $this->ajax_import_action(), array( $this, 'handle_ajax_import' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the page under its configured parent.
	 */
	public function register_menu(): void {
		$menu = is_array( $this->definition['menu'] ?? null ) ? $this->definition['menu'] : array();

		$this->page_hook = (string) add_submenu_page(
			(string) ( $menu['parent_slug'] ?? 'themes.php' ),
			(string) ( $menu['page_title'] ?? __( 'Options Framework', 'lerm' ) ),
			(string) ( $menu['menu_title'] ?? __( 'Options Framework', 'lerm' ) ),
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

		$tab = $this->posted_tab();

		check_admin_referer( $this->nonce_action( $tab ) );

		$submitted = isset( $_POST[ $this->option_name() ] ) && is_array( $_POST[ $this->option_name() ] )
			? wp_unslash( $_POST[ $this->option_name() ] )
			: array();

		$this->store->save_section( $tab, $submitted );

		$redirect_url = add_query_arg(
			array(
				'page'                => $this->page_slug(),
				'tab'                 => $tab,
				'options_framework_ok' => '1',
			),
			$this->admin_parent_url()
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Save the current tab without a full reload.
	 */
	public function handle_ajax_save(): void {
		if ( ! current_user_can( $this->capability() ) ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'You are not allowed to manage these settings.', 'lerm' ) ),
				403
			);
		}

		$tab = $this->posted_tab();
		check_ajax_referer( $this->nonce_action( $tab ) );

		$submitted = isset( $_POST[ $this->option_name() ] ) && is_array( $_POST[ $this->option_name() ] )
			? wp_unslash( $_POST[ $this->option_name() ] )
			: array();

		if ( ! $this->store->save_section( $tab, $submitted ) ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'Unable to save this settings tab.', 'lerm' ) ),
				500
			);
		}

		wp_send_json_success(
			array(
				'message' => esc_html__( 'Settings saved.', 'lerm' ),
				'values'  => $this->store->section_values( $tab ),
			)
		);
	}

	/**
	 * Reset the current tab or the whole page without a full reload.
	 */
	public function handle_ajax_reset(): void {
		if ( ! current_user_can( $this->capability() ) ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'You are not allowed to manage these settings.', 'lerm' ) ),
				403
			);
		}

		$tab   = $this->posted_tab();
		$scope = isset( $_POST['reset_scope'] ) ? sanitize_key( wp_unslash( $_POST['reset_scope'] ) ) : 'section';

		check_ajax_referer( $this->nonce_action( $tab ) );

		$success = ( 'all' === $scope )
			? $this->store->reset_all_sections()
			: $this->store->reset_section( $tab );

		if ( ! $success ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'Unable to reset the requested settings.', 'lerm' ) ),
				500
			);
		}

		wp_send_json_success(
			array(
				'message' => ( 'all' === $scope )
					? esc_html__( 'All sections have been reset to defaults.', 'lerm' )
					: esc_html__( 'This section has been reset to defaults.', 'lerm' ),
				'values'  => $this->store->section_values( $tab ),
			)
		);
	}

	/**
	 * Export all settings as JSON for backup workflows.
	 */
	public function handle_ajax_export(): void {
		if ( ! current_user_can( $this->capability() ) ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'You are not allowed to manage these settings.', 'lerm' ) ),
				403
			);
		}

		$tab = $this->posted_tab();
		check_ajax_referer( $this->nonce_action( $tab ) );

		$json = wp_json_encode( $this->store->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		if ( ! is_string( $json ) || '' === $json ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'Unable to export the current settings snapshot.', 'lerm' ) ),
				500
			);
		}

		wp_send_json_success(
			array(
				'message' => esc_html__( 'Current settings snapshot generated.', 'lerm' ),
				'json'    => $json,
			)
		);
	}

	/**
	 * Import a full JSON payload from the backup tools UI.
	 */
	public function handle_ajax_import(): void {
		if ( ! current_user_can( $this->capability() ) ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'You are not allowed to manage these settings.', 'lerm' ) ),
				403
			);
		}

		$tab = $this->posted_tab();
		check_ajax_referer( $this->nonce_action( $tab ) );

		$json = isset( $_POST['backup_json'] ) && is_scalar( $_POST['backup_json'] )
			? trim( (string) wp_unslash( $_POST['backup_json'] ) )
			: '';

		if ( '' === $json ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'Paste a JSON snapshot before importing.', 'lerm' ) ),
				400
			);
		}

		$decoded = json_decode( $json, true );

		if ( ! is_array( $decoded ) ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'The backup JSON is invalid.', 'lerm' ) ),
				400
			);
		}

		if ( ! $this->store->import_all( $decoded ) ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'Unable to import the provided settings JSON.', 'lerm' ) ),
				500
			);
		}

		wp_send_json_success(
			array(
				'message' => esc_html__( 'Settings imported successfully.', 'lerm' ),
				'values'  => $this->store->section_values( $tab ),
			)
		);
	}

	/**
	 * Enqueue page assets.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( $hook_suffix !== $this->page_hook ) {
			return;
		}

		$code_editor_settings = wp_enqueue_code_editor(
			array(
				'type' => 'text/html',
			)
		);

		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'wp-codemirror' );
		wp_enqueue_script( 'wp-theme-plugin-editor' );
		wp_enqueue_style(
			'lerm-options-framework',
			$this->asset_url( 'options-framework.css' ),
			array( 'wp-color-picker', 'wp-codemirror' ),
			$this->asset_version()
		);
		wp_enqueue_script(
			'lerm-options-framework',
			$this->asset_url( 'options-framework.js' ),
			array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker', 'wp-theme-plugin-editor' ),
			$this->asset_version(),
			true
		);
		wp_localize_script(
			'lerm-options-framework',
			'lermOptionsFramework',
			array(
				'ajaxUrl'             => admin_url( 'admin-ajax.php' ),
				'saveAction'          => $this->ajax_save_action(),
				'resetAction'         => $this->ajax_reset_action(),
				'exportAction'        => $this->ajax_export_action(),
				'importAction'        => $this->ajax_import_action(),
				'codeEditor'          => $code_editor_settings,
				'selectMedia'         => __( 'Choose image', 'lerm' ),
				'useMedia'            => __( 'Use this image', 'lerm' ),
				'selectImages'        => __( 'Choose images', 'lerm' ),
				'useImages'           => __( 'Use these images', 'lerm' ),
				'removeMedia'         => __( 'Remove image', 'lerm' ),
				'clearGallery'        => __( 'Clear gallery', 'lerm' ),
				'noMedia'             => __( 'No image selected.', 'lerm' ),
				'noGallery'           => __( 'No gallery images selected.', 'lerm' ),
				'saving'              => __( 'Saving...', 'lerm' ),
				'saveSuccess'         => __( 'Settings saved.', 'lerm' ),
				'saveError'           => __( 'Unable to save the settings right now.', 'lerm' ),
				'resetting'           => __( 'Resetting...', 'lerm' ),
				'resetSectionSuccess' => __( 'This section has been reset to defaults.', 'lerm' ),
				'resetAllSuccess'     => __( 'All sections have been reset to defaults.', 'lerm' ),
				'resetError'          => __( 'Unable to reset the settings right now.', 'lerm' ),
				'confirmResetSection' => __( 'Reset the current tab back to its default values?', 'lerm' ),
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

		$view         = is_array( $this->definition['view'] ?? null ) ? $this->definition['view'] : array();
		$sections     = PageSchema::sections( $this->definition );
		$current_tab  = $this->current_tab();
		$current_page = $sections[ $current_tab ] ?? reset( $sections );
		$values       = $this->store->all();
		$legacy_panel = is_array( $view['legacy_panel'] ?? null ) ? $view['legacy_panel'] : array();
		?>
		<div class="wrap lerm-settings-wrap">
			<div class="lerm-settings-shell">
				<aside class="lerm-settings-sidebar">
					<div class="lerm-settings-sidebar__brand">
						<p class="lerm-settings-eyebrow"><?php echo esc_html( (string) ( $view['eyebrow'] ?? __( 'Native admin', 'lerm' ) ) ); ?></p>
						<h1><?php echo esc_html( (string) ( $view['title'] ?? __( 'Options Framework', 'lerm' ) ) ); ?></h1>
						<p><?php echo esc_html( (string) ( $view['description'] ?? __( 'A native, extensible settings page built on schema, storage, and reusable field renderers.', 'lerm' ) ) ); ?></p>
					</div>

					<nav class="lerm-settings-nav" aria-label="<?php esc_attr_e( 'Settings sections', 'lerm' ); ?>">
						<?php foreach ( $sections as $section_id => $section ) : ?>
							<a class="lerm-settings-nav__item <?php echo $section_id === $current_tab ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => $this->page_slug(), 'tab' => $section_id ), $this->admin_parent_url() ) ); ?>">
								<span class="lerm-settings-nav__title"><?php echo esc_html( (string) $section['title'] ); ?></span>
								<span class="lerm-settings-nav__meta"><?php echo esc_html( sprintf( _n( '%s field', '%s fields', count( $section['fields'] ), 'lerm' ), number_format_i18n( count( $section['fields'] ) ) ) ); ?></span>
							</a>
						<?php endforeach; ?>
					</nav>

					<?php if ( ! empty( $legacy_panel['url'] ) ) : ?>
						<div class="lerm-settings-sidebar__card">
							<h2><?php echo esc_html( (string) ( $legacy_panel['title'] ?? __( 'Legacy panel', 'lerm' ) ) ); ?></h2>
							<p><?php echo esc_html( (string) ( $legacy_panel['description'] ?? __( 'Older sections can still live in a separate panel while the framework migration is in progress.', 'lerm' ) ) ); ?></p>
							<a class="button button-secondary" href="<?php echo esc_url( (string) $legacy_panel['url'] ); ?>"><?php echo esc_html( (string) ( $legacy_panel['button_label'] ?? __( 'Open legacy panel', 'lerm' ) ) ); ?></a>
						</div>
					<?php endif; ?>
				</aside>

				<section class="lerm-settings-main">
					<?php if ( isset( $_GET['options_framework_ok'] ) ) : ?>
						<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'lerm' ); ?></p></div>
					<?php endif; ?>

					<div class="lerm-settings-panel">
						<div class="lerm-settings-panel__intro">
							<div>
								<p class="lerm-settings-eyebrow"><?php esc_html_e( 'Current section', 'lerm' ); ?></p>
								<h2><?php echo esc_html( (string) ( $current_page['title'] ?? '' ) ); ?></h2>
								<p><?php echo esc_html( (string) ( $current_page['description'] ?? '' ) ); ?></p>
							</div>
							<div class="lerm-settings-panel__status">
								<span class="lerm-status-pill" data-lerm-status="idle"><?php esc_html_e( 'Synced', 'lerm' ); ?></span>
								<span class="lerm-settings-panel__hint"><?php esc_html_e( 'Saving and reset actions happen without leaving this page.', 'lerm' ); ?></span>
							</div>
						</div>

						<div class="lerm-settings-flash" data-lerm-flash aria-live="polite"></div>

						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="lerm-settings-form" data-option-name="<?php echo esc_attr( $this->option_name() ); ?>" novalidate>
							<input type="hidden" name="action" value="<?php echo esc_attr( $this->save_action() ); ?>">
							<input type="hidden" name="lerm_settings_tab" value="<?php echo esc_attr( $current_tab ); ?>">
							<?php wp_nonce_field( $this->nonce_action( $current_tab ) ); ?>

							<div class="lerm-settings-actions">
								<button type="submit" class="button button-primary button-large" data-lerm-save><?php esc_html_e( 'Save changes', 'lerm' ); ?></button>
								<button type="button" class="button button-secondary" data-lerm-reset="section"><?php esc_html_e( 'Reset this tab', 'lerm' ); ?></button>
								<button type="button" class="button button-secondary button-link-delete" data-lerm-reset="all"><?php esc_html_e( 'Reset all tabs', 'lerm' ); ?></button>
								<span class="spinner lerm-settings-spinner"></span>
								<span class="lerm-settings-actions__hint"><?php esc_html_e( 'Changes are saved instantly without reloading the page. Use Ctrl/Cmd + S to save faster.', 'lerm' ); ?></span>
							</div>

							<table class="form-table lerm-settings-table" role="presentation">
								<tbody>
									<?php $this->render_fields( (array) ( $current_page['fields'] ?? array() ), $values ); ?>
								</tbody>
							</table>

							<div class="lerm-settings-actions lerm-settings-actions--footer">
								<button type="submit" class="button button-primary button-large" data-lerm-save><?php esc_html_e( 'Save changes', 'lerm' ); ?></button>
								<button type="button" class="button button-secondary" data-lerm-reset="section"><?php esc_html_e( 'Reset this tab', 'lerm' ); ?></button>
								<button type="button" class="button button-secondary button-link-delete" data-lerm-reset="all"><?php esc_html_e( 'Reset all tabs', 'lerm' ); ?></button>
							</div>
						</form>
					</div>
				</section>
			</div>
		</div>
		<?php
	}

	/**
	 * Render all fields for a section.
	 *
	 * @param array<int, array<string, mixed>> $fields Field definitions.
	 * @param array<string, mixed>             $values Saved values.
	 */
	public function render_fields( array $fields, array $values ): void {
		$current_group = '';

		foreach ( $fields as $field ) {
			$group = (string) ( $field['group'] ?? '' );

			if ( $group && $group !== $current_group ) {
				$current_group = $group;
				printf(
					'<tr class="lerm-settings-group"><td colspan="2"><h3>%s</h3></td></tr>',
					esc_html( $group )
				);
			}

			$this->render_field( $field, $values );
		}
	}

	/**
	 * Render a single field row.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param array<string, mixed> $values Saved values.
	 */
	public function render_field( array $field, array $values ): void {
		$field_id    = (string) $field['id'];
		$field_type  = sanitize_key( (string) ( $field['type'] ?? 'text' ) );
		$field_name  = $this->option_name() . '[' . $field_id . ']';
		$field_value = $values[ $field_id ] ?? ( $field['default'] ?? '' );
		$description = (string) ( $field['description'] ?? '' );
		$dependency  = (string) ( $field['dependency_field'] ?? '' );
		$dep_value   = (string) ( $field['dependency_value'] ?? '1' );
		$row_attrs   = array(
			'class="lerm-settings-row"',
			'data-field-id="' . esc_attr( $field_id ) . '"',
			'data-field-type="' . esc_attr( $field_type ) . '"',
		);

		if ( '' !== $dependency ) {
			$row_attrs[] = 'data-dependency-field="' . esc_attr( $dependency ) . '"';
			$row_attrs[] = 'data-dependency-value="' . esc_attr( $dep_value ) . '"';
		}

		echo '<tr ' . implode( ' ', $row_attrs ) . '>';
		printf(
			'<th scope="row"><label for="%1$s">%2$s</label></th>',
			esc_attr( $field_id ),
			esc_html( (string) $field['label'] )
		);
		echo '<td>';

		$custom_render = $this->field_types->render_callback( $field_type );

		if ( is_callable( $custom_render ) ) {
			call_user_func( $custom_render, $field, $field_value, $field_name, $this );
		} else {
			switch ( $field_type ) {
				case 'backup_tools':
					$this->render_backup_tools_field( $field );
					break;

				case 'fieldset':
					$this->render_fieldset_field( $field, $field_value, $field_name );
					break;

				case 'group':
					$this->render_group_field( $field, $field_value, $field_name );
					break;

				case 'media':
					$this->render_media_field( $field, $field_value, $field_name );
					break;

				case 'gallery':
					$this->render_gallery_field( $field, $field_value, $field_name );
					break;

				case 'color':
					printf(
						'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="regular-text lerm-color-field">',
						esc_attr( $field_id ),
						esc_attr( $field_name ),
						esc_attr( (string) $field_value )
					);
					break;

				case 'button_set':
				case 'radio':
					$this->render_choice_field( $field, $field_value, $field_name );
					break;

				case 'select':
					$choices = PageSchema::choices( $field );
					$multiple = ! empty( $field['multiple'] );
					$current_values = $multiple && is_array( $field_value ) ? array_map( 'strval', $field_value ) : array();
					printf(
						'<select id="%1$s" name="%2$s" class="regular-text" data-lerm-controller="1" %3$s %4$s>',
						esc_attr( $field_id ),
						esc_attr( $multiple ? $field_name . '[]' : $field_name ),
						$multiple ? 'multiple="multiple"' : '',
						$multiple ? 'size="' . esc_attr( (string) min( max( count( $choices ), 4 ), 10 ) ) . '"' : ''
					);
					foreach ( $choices as $value => $label ) {
						printf(
							'<option value="%1$s" %2$s>%3$s</option>',
							esc_attr( $value ),
							$multiple
								? selected( in_array( (string) $value, $current_values, true ), true, false )
								: selected( (string) $field_value, (string) $value, false ),
							esc_html( $label )
						);
					}
					echo '</select>';
					break;

				case 'checkbox_list':
					$choices = PageSchema::choices( $field );
					$current = is_array( $field_value ) ? array_map( 'strval', $field_value ) : array();
					echo '<fieldset class="lerm-checkbox-list"><legend class="screen-reader-text">' . esc_html( (string) $field['label'] ) . '</legend>';
					foreach ( $choices as $value => $label ) {
						printf(
							'<label><input type="checkbox" name="%1$s[]" value="%2$s" %3$s> <span>%4$s</span></label>',
							esc_attr( $field_name ),
							esc_attr( $value ),
							checked( in_array( (string) $value, $current, true ), true, false ),
							esc_html( $label )
						);
					}
					echo '</fieldset>';
					break;

				case 'switcher':
					printf(
						'<input type="hidden" name="%1$s" value="0"><label class="lerm-switch"><input type="checkbox" id="%2$s" name="%1$s" value="1" %3$s data-lerm-controller="1"><span>%4$s</span></label>',
						esc_attr( $field_name ),
						esc_attr( $field_id ),
						checked( ! empty( $field_value ), true, false ),
						esc_html__( 'Enabled', 'lerm' )
					);
					break;

				case 'number':
					printf(
						'<input type="number" id="%1$s" name="%2$s" value="%3$s" class="small-text" min="%4$s" max="%5$s" step="%6$s" %7$s>',
						esc_attr( $field_id ),
						esc_attr( $field_name ),
						esc_attr( (string) $field_value ),
						esc_attr( (string) ( $field['min'] ?? '' ) ),
						esc_attr( (string) ( $field['max'] ?? '' ) ),
						esc_attr( (string) ( $field['step'] ?? 1 ) ),
						$dependency ? 'data-lerm-controller="1"' : ''
					);
					break;

				case 'sorter':
					$this->render_sorter_field( $field, $field_value );
					break;

				case 'textarea':
					printf(
						'<textarea id="%1$s" name="%2$s" class="large-text" rows="%3$s" %4$s placeholder="%5$s">%6$s</textarea>',
						esc_attr( $field_id ),
						esc_attr( $field_name ),
						esc_attr( (string) ( $field['rows'] ?? 4 ) ),
						$dependency ? 'data-lerm-controller="1"' : '',
						esc_attr( (string) ( $field['placeholder'] ?? '' ) ),
						esc_textarea( (string) $field_value )
					);
					break;

				case 'code_editor':
					printf(
						'<textarea id="%1$s" name="%2$s" class="large-text lerm-code-editor" rows="%3$s" placeholder="%4$s">%5$s</textarea>',
						esc_attr( $field_id ),
						esc_attr( $field_name ),
						esc_attr( (string) ( $field['rows'] ?? 10 ) ),
						esc_attr( (string) ( $field['placeholder'] ?? '' ) ),
						esc_textarea( (string) $field_value )
					);
					break;

				case 'wp_editor':
					$editor_args = array_merge(
						array(
							'textarea_name' => $field_name,
							'textarea_rows' => 6,
						),
						(array) ( $field['editor_args'] ?? array() )
					);

					wp_editor(
						(string) $field_value,
						sanitize_html_class( 'lerm-' . $field_id ),
						$editor_args
					);
					break;

				case 'url':
				case 'text':
				default:
					printf(
						'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text" %5$s placeholder="%6$s">',
						esc_attr( (string) ( $field['input_type'] ?? ( in_array( $field_type, array( 'url', 'text' ), true ) ? $field_type : 'text' ) ) ),
						esc_attr( $field_id ),
						esc_attr( $field_name ),
						esc_attr( (string) $field_value ),
						$dependency ? 'data-lerm-controller="1"' : '',
						esc_attr( (string) ( $field['placeholder'] ?? '' ) )
					);
					break;
			}
		}

		if ( $description ) {
			printf( '<p class="description">%s</p>', esc_html( $description ) );
		}

		echo '</td></tr>';
	}

	/**
	 * Render backup export/import controls.
	 *
	 * @param array<string, mixed> $field Field definition.
	 */
	private function render_backup_tools_field( array $field ): void {
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
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Field value.
	 */
	private function render_fieldset_field( array $field, $value, string $field_name ): void {
		$field_id = (string) $field['id'];
		$values   = is_array( $value ) ? $value : array();
		$fields   = is_array( $field['fields'] ?? null ) ? $field['fields'] : array();

		echo '<div class="lerm-fieldset" data-target="' . esc_attr( $field_id ) . '">';
		foreach ( $fields as $child ) {
			if ( ! is_array( $child ) || ! isset( $child['id'] ) ) {
				continue;
			}

			$child_id    = (string) $child['id'];
			$child_name  = $field_name . '[' . $child_id . ']';
			$child_value = $values[ $child_id ] ?? ( $child['default'] ?? '' );

			echo '<div class="lerm-fieldset__item" data-subfield-id="' . esc_attr( $child_id ) . '" data-field-type="' . esc_attr( sanitize_key( (string) ( $child['type'] ?? 'text' ) ) ) . '">';
			echo '<label class="lerm-fieldset__label" for="' . esc_attr( $field_id . '__' . $child_id ) . '">' . esc_html( (string) ( $child['label'] ?? $child_id ) ) . '</label>';
			$this->render_nested_field( $child, $child_value, $child_name, $field_id . '__' . $child_id );

			if ( ! empty( $child['description'] ) ) {
				echo '<p class="description">' . esc_html( (string) $child['description'] ) . '</p>';
			}

			echo '</div>';
		}
		echo '</div>';
	}

	/**
	 * Render repeatable groups.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Field value.
	 */
	private function render_group_field( array $field, $value, string $field_name ): void {
		$field_id    = (string) $field['id'];
		$items       = is_array( $value ) ? array_values( $value ) : array();
		$button_text = (string) ( $field['button_text'] ?? __( 'Add item', 'lerm' ) );

		echo '<div class="lerm-group" data-target="' . esc_attr( $field_id ) . '">';
		echo '<div class="lerm-group__toolbar">';
		echo '<button type="button" class="button button-secondary" data-lerm-group-add>' . esc_html( $button_text ) . '</button>';
		echo '</div>';
		echo '<div class="lerm-group__empty" ' . ( ! empty( $items ) ? 'hidden' : '' ) . '>' . esc_html__( 'No items added yet.', 'lerm' ) . '</div>';
		echo '<div class="lerm-group-list" data-lerm-group-list>';

		foreach ( $items as $index => $item ) {
			echo $this->group_item_markup( $field, $field_name, is_array( $item ) ? $item : array(), (string) $index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '</div>';
		echo '<script type="text/html" class="lerm-group-template">' . $this->group_item_markup( $field, $field_name, array(), '__INDEX__' ) . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}

	/**
	 * Build one repeatable group item.
	 *
	 * @param array<string, mixed> $field Group definition.
	 * @param array<string, mixed> $item  Current item values.
	 */
	private function group_item_markup( array $field, string $field_name, array $item, string $index ): string {
		$fields = is_array( $field['fields'] ?? null ) ? $field['fields'] : array();

		ob_start();
		?>
		<div class="lerm-group-item" data-lerm-group-item data-index="<?php echo esc_attr( $index ); ?>">
			<div class="lerm-group-item__header">
				<span class="lerm-sorter-handle" aria-hidden="true">&#8645;</span>
				<strong class="lerm-group-item__title"><?php echo esc_html( sprintf( __( 'Item %s', 'lerm' ), is_numeric( $index ) ? (string) ( (int) $index + 1 ) : '#' ) ); ?></strong>
				<button type="button" class="button button-secondary button-link-delete" data-lerm-group-remove><?php esc_html_e( 'Remove', 'lerm' ); ?></button>
			</div>
			<div class="lerm-group-item__body">
				<?php foreach ( $fields as $child ) : ?>
					<?php
					if ( ! is_array( $child ) || ! isset( $child['id'] ) ) {
						continue;
					}

					$child_id      = (string) $child['id'];
					$current_value = $item[ $child_id ] ?? ( $child['default'] ?? '' );
					$name          = $field_name . '[' . $index . '][' . $child_id . ']';
					$id            = (string) $field['id'] . '__' . $index . '__' . $child_id;
					$name_template = $field_name . '[__INDEX__][' . $child_id . ']';
					$id_template   = (string) $field['id'] . '__' . '__INDEX__' . '__' . $child_id;
					?>
					<div class="lerm-group-item__field" data-subfield-id="<?php echo esc_attr( $child_id ); ?>" data-field-type="<?php echo esc_attr( sanitize_key( (string) ( $child['type'] ?? 'text' ) ) ); ?>">
						<label class="lerm-fieldset__label" for="<?php echo esc_attr( $id ); ?>" data-for-template="<?php echo esc_attr( $id_template ); ?>"><?php echo esc_html( (string) ( $child['label'] ?? $child_id ) ); ?></label>
						<?php $this->render_nested_field( $child, $current_value, $name, $id, $name_template, $id_template ); ?>
						<?php if ( ! empty( $child['description'] ) ) : ?>
							<p class="description"><?php echo esc_html( (string) $child['description'] ); ?></p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
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
		$field_type = sanitize_key( (string) ( $field['type'] ?? 'text' ) );
		$name_attr  = '' !== $name_template ? ' data-name-template="' . esc_attr( $name_template ) . '"' : '';
		$id_attr    = '' !== $id_template ? ' data-id-template="' . esc_attr( $id_template ) . '"' : '';

		switch ( $field_type ) {
			case 'media':
				$this->render_media_field( $field, $value, $field_name, $input_id, $name_attr, $id_attr );
				return;

			case 'gallery':
				$this->render_gallery_field( $field, $value, $field_name, $input_id, $name_attr, $id_attr );
				return;

			case 'color':
				printf(
					'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="regular-text lerm-color-field"%4$s%5$s>',
					esc_attr( $input_id ),
					esc_attr( $field_name ),
					esc_attr( (string) $value ),
					$name_attr,
					$id_attr
				);
				return;

			case 'button_set':
			case 'radio':
				$choices = PageSchema::choices( $field );
				$current = is_scalar( $value ) ? (string) $value : '';
				$class   = 'button_set' === $field_type ? 'lerm-button-set' : 'lerm-radio-list';

				echo '<fieldset class="' . esc_attr( $class ) . '">';
				foreach ( $choices as $choice_value => $choice_label ) {
					printf(
						'<label><input type="radio" name="%1$s" value="%2$s" %3$s%4$s> <span>%5$s</span></label>',
						esc_attr( $field_name ),
						esc_attr( $choice_value ),
						checked( $current, (string) $choice_value, false ),
						$name_attr,
						esc_html( $choice_label )
					);
				}
				echo '</fieldset>';
				return;

			case 'switcher':
				printf(
					'<input type="hidden" name="%1$s" value="0"%4$s><label class="lerm-switch"><input type="checkbox" id="%2$s" name="%1$s" value="1" %3$s%4$s%5$s><span>%6$s</span></label>',
					esc_attr( $field_name ),
					esc_attr( $input_id ),
					checked( ! empty( $value ), true, false ),
					$name_attr,
					$id_attr,
					esc_html__( 'Enabled', 'lerm' )
				);
				return;

			case 'select':
				$choices  = PageSchema::choices( $field );
				$multiple = ! empty( $field['multiple'] );
				$current  = $multiple && is_array( $value ) ? array_map( 'strval', $value ) : array();
				printf(
					'<select id="%1$s" name="%2$s" class="regular-text"%3$s%4$s%5$s%6$s>',
					esc_attr( $input_id ),
					esc_attr( $multiple ? $field_name . '[]' : $field_name ),
					$multiple ? ' multiple="multiple"' : '',
					$multiple ? ' size="' . esc_attr( (string) min( max( count( $choices ), 4 ), 10 ) ) . '"' : '',
					$name_attr,
					$id_attr
				);
				foreach ( $choices as $choice_value => $choice_label ) {
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $choice_value ),
						$multiple
							? selected( in_array( (string) $choice_value, $current, true ), true, false )
							: selected( (string) $value, (string) $choice_value, false ),
						esc_html( $choice_label )
					);
				}
				echo '</select>';
				return;

			case 'textarea':
				printf(
					'<textarea id="%1$s" name="%2$s" class="large-text" rows="%3$s" placeholder="%4$s"%5$s%6$s>%7$s</textarea>',
					esc_attr( $input_id ),
					esc_attr( $field_name ),
					esc_attr( (string) ( $field['rows'] ?? 4 ) ),
					esc_attr( (string) ( $field['placeholder'] ?? '' ) ),
					$name_attr,
					$id_attr,
					esc_textarea( (string) $value )
				);
				return;

			case 'number':
				printf(
					'<input type="number" id="%1$s" name="%2$s" value="%3$s" class="small-text" min="%4$s" max="%5$s" step="%6$s"%7$s%8$s>',
					esc_attr( $input_id ),
					esc_attr( $field_name ),
					esc_attr( (string) $value ),
					esc_attr( (string) ( $field['min'] ?? '' ) ),
					esc_attr( (string) ( $field['max'] ?? '' ) ),
					esc_attr( (string) ( $field['step'] ?? 1 ) ),
					$name_attr,
					$id_attr
				);
				return;

			case 'url':
			case 'text':
			default:
				printf(
					'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text" placeholder="%5$s"%6$s%7$s>',
					esc_attr( (string) ( $field['input_type'] ?? ( in_array( $field_type, array( 'url', 'text' ), true ) ? $field_type : 'text' ) ) ),
					esc_attr( $input_id ),
					esc_attr( $field_name ),
					esc_attr( (string) $value ),
					esc_attr( (string) ( $field['placeholder'] ?? '' ) ),
					$name_attr,
					$id_attr
				);
				return;
		}
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
		$attachment_id = is_array( $value ) ? absint( $value['id'] ?? 0 ) : 0;
		$image_url     = $attachment_id > 0 ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';
		$button_text   = (string) ( $field['button_text'] ?? __( 'Choose image', 'lerm' ) );

		printf(
			'<div class="lerm-media-field" data-target="%1$s"><input type="hidden" name="%2$s[id]" value="%3$s"%8$s%9$s><div class="lerm-media-preview">%4$s</div><div class="lerm-media-actions"><button type="button" class="button lerm-media-select">%5$s</button><button type="button" class="button button-secondary button-link-delete lerm-media-remove" %6$s>%7$s</button></div></div>',
			esc_attr( $target ),
			esc_attr( $name_prefix ),
			esc_attr( (string) $attachment_id ),
			$image_url ? '<img src="' . esc_url( $image_url ) . '" alt="">' : '<span class="lerm-media-placeholder">' . esc_html__( 'No image selected.', 'lerm' ) . '</span>',
			esc_html( $button_text ),
			$attachment_id > 0 ? '' : 'hidden',
			esc_html__( 'Remove', 'lerm' ),
			$name_attr,
			$id_attr
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
		$ids         = is_array( $value ) ? array_values( array_filter( array_map( 'absint', $value ) ) ) : array();

		echo '<div class="lerm-gallery-field" data-target="' . esc_attr( $target ) . '">';
		echo '<input type="hidden" name="' . esc_attr( $name_prefix . '[ids]' ) . '" value="' . esc_attr( implode( ',', $ids ) ) . '"' . $name_attr . $id_attr . '>';
		echo '<div class="lerm-gallery-preview">';

		if ( empty( $ids ) ) {
			echo '<span class="lerm-media-placeholder">' . esc_html__( 'No gallery images selected.', 'lerm' ) . '</span>';
		} else {
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

		return '' !== $page_id ? $page_id : 'options-framework';
	}

	/**
	 * Resolve the stored option name.
	 */
	private function option_name(): string {
		$option_name = isset( $this->definition['option_name'] ) ? sanitize_key( (string) $this->definition['option_name'] ) : '';

		return '' !== $option_name ? $option_name : 'options_framework';
	}

	/**
	 * Resolve the admin path for the configured parent.
	 */
	private function admin_parent_url(): string {
		$menu   = is_array( $this->definition['menu'] ?? null ) ? $this->definition['menu'] : array();
		$parent = (string) ( $menu['parent_slug'] ?? 'themes.php' );

		if ( false !== strpos( $parent, '.php' ) ) {
			return admin_url( $parent );
		}

		return add_query_arg( 'page', $parent, admin_url( 'admin.php' ) );
	}

	/**
	 * Nonce action for a section.
	 */
	private function nonce_action( string $tab ): string {
		return 'lerm_options_framework_' . $this->page_slug() . '_' . sanitize_key( $tab );
	}

	/**
	 * Non-JS admin-post action.
	 */
	private function save_action(): string {
		return 'lerm_options_framework_save_' . $this->page_slug();
	}

	/**
	 * AJAX save action.
	 */
	private function ajax_save_action(): string {
		return 'lerm_options_framework_ajax_save_' . $this->page_slug();
	}

	/**
	 * AJAX reset action.
	 */
	private function ajax_reset_action(): string {
		return 'lerm_options_framework_ajax_reset_' . $this->page_slug();
	}

	/**
	 * AJAX export action.
	 */
	private function ajax_export_action(): string {
		return 'lerm_options_framework_ajax_export_' . $this->page_slug();
	}

	/**
	 * AJAX import action.
	 */
	private function ajax_import_action(): string {
		return 'lerm_options_framework_ajax_import_' . $this->page_slug();
	}

	/**
	 * Asset base URL for the framework bundle.
	 */
	private function asset_url( string $asset ): string {
		return trailingslashit( LERM_URI . 'app/OptionsFramework/assets' ) . ltrim( $asset, '/' );
	}

	/**
	 * Asset version string.
	 */
	private function asset_version(): string {
		return defined( 'LERM_VERSION' ) ? (string) LERM_VERSION : '1.0.0';
	}
}
