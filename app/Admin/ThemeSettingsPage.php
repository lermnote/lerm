<?php  // phpcs:disable WordPress.Files.FileName
/**
 * Native theme settings page.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\Admin;

use Lerm\Options\ThemeOptionsRepository;
use Lerm\Options\ThemeOptionsSchema;
use Lerm\Traits\Singleton;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ThemeSettingsPage {

	use Singleton;

	private const PAGE_SLUG = 'lerm-theme-settings';

	/**
	 * Settings page hook suffix.
	 */
	private string $page_hook = '';

	private ThemeOptionsRepository $repository;

	public function __construct() {
		$this->repository = ThemeOptionsRepository::instance();

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_lerm_save_theme_settings', array( $this, 'handle_save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the new native settings page under Appearance.
	 */
	public function register_menu(): void {
		$this->page_hook = (string) add_theme_page(
			__( 'Lerm Settings', 'lerm' ),
			__( 'Lerm Settings', 'lerm' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Handle saving a settings tab.
	 */
	public function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage theme settings.', 'lerm' ) );
		}

		$tab = isset( $_POST['lerm_settings_tab'] ) ? sanitize_key( wp_unslash( $_POST['lerm_settings_tab'] ) ) : 'header';

		check_admin_referer( 'lerm_save_theme_settings_' . $tab );

		$submitted = isset( $_POST[ ThemeOptionsSchema::OPTION_NAME ] ) && is_array( $_POST[ ThemeOptionsSchema::OPTION_NAME ] )
			? wp_unslash( $_POST[ ThemeOptionsSchema::OPTION_NAME ] )
			: array();

		$this->repository->save_section( $tab, $submitted );

		$redirect_url = add_query_arg(
			array(
				'page'             => self::PAGE_SLUG,
				'tab'              => $tab,
				'lerm_settings_ok' => '1',
			),
			admin_url( 'themes.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Enqueue admin page assets.
	 *
	 * @param string $hook_suffix Current hook suffix.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( $hook_suffix !== $this->page_hook ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style(
			'lerm-theme-settings',
			LERM_URI . 'app/Admin/assets/theme-settings.css',
			array( 'wp-color-picker' ),
			LERM_VERSION
		);
		wp_enqueue_script(
			'lerm-theme-settings',
			LERM_URI . 'app/Admin/assets/theme-settings.js',
			array( 'jquery', 'wp-color-picker' ),
			LERM_VERSION,
			true
		);
		wp_localize_script(
			'lerm-theme-settings',
			'lermThemeSettings',
			array(
				'selectMedia' => __( 'Choose image', 'lerm' ),
				'useMedia'    => __( 'Use this image', 'lerm' ),
				'removeMedia' => __( 'Remove image', 'lerm' ),
				'noMedia'     => __( 'No image selected.', 'lerm' ),
			)
		);
	}

	/**
	 * Render the native settings page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$sections     = ThemeOptionsSchema::sections();
		$current_tab  = $this->current_tab();
		$current_page = $sections[ $current_tab ] ?? reset( $sections );
		$values       = $this->repository->all();
		$legacy_url   = admin_url( 'admin.php?page=lerm_options' );
		?>
		<div class="wrap lerm-settings-wrap">
			<h1><?php esc_html_e( 'Lerm Settings', 'lerm' ); ?></h1>
			<p class="description"><?php esc_html_e( 'This page is the new native settings layer that replaces Codestar section by section while keeping the existing option storage.', 'lerm' ); ?></p>

			<?php if ( isset( $_GET['lerm_settings_ok'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Theme settings saved.', 'lerm' ); ?></p></div>
			<?php endif; ?>

			<div class="notice notice-info inline">
				<p>
					<?php esc_html_e( 'Unmigrated sections still live in the legacy settings panel for now.', 'lerm' ); ?>
					<a href="<?php echo esc_url( $legacy_url ); ?>"><?php esc_html_e( 'Open legacy panel', 'lerm' ); ?></a>
				</p>
			</div>

			<nav class="nav-tab-wrapper">
				<?php foreach ( $sections as $section_id => $section ) : ?>
					<a class="nav-tab <?php echo $section_id === $current_tab ? 'nav-tab-active' : ''; ?>" href="
					<?php
					echo esc_url(
						add_query_arg(
							array(
								'page' => self::PAGE_SLUG,
								'tab'  => $section_id,
							),
							admin_url( 'themes.php' )
						)
					);
					?>
										">
						<?php echo esc_html( $section['title'] ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="lerm-settings-panel">
				<div class="lerm-settings-panel__intro">
					<h2><?php echo esc_html( $current_page['title'] ); ?></h2>
					<p><?php echo esc_html( $current_page['description'] ?? '' ); ?></p>
				</div>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="lerm_save_theme_settings">
					<input type="hidden" name="lerm_settings_tab" value="<?php echo esc_attr( $current_tab ); ?>">
					<?php wp_nonce_field( 'lerm_save_theme_settings_' . $current_tab ); ?>

					<table class="form-table lerm-settings-table" role="presentation">
						<tbody>
							<?php $this->render_fields( $current_page['fields'], $values ); ?>
						</tbody>
					</table>

					<?php submit_button( __( 'Save settings', 'lerm' ) ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a field table.
	 *
	 * @param array<int, array<string, mixed>> $fields Field definitions.
	 * @param array<string, mixed>             $values Saved values.
	 */
	private function render_fields( array $fields, array $values ): void {
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
	private function render_field( array $field, array $values ): void {
		$field_id    = $field['id'];
		$field_type  = $field['type'] ?? 'text';
		$field_name  = ThemeOptionsSchema::OPTION_NAME . '[' . $field_id . ']';
		$field_value = $values[ $field_id ] ?? ( $field['default'] ?? '' );
		$description = (string) ( $field['description'] ?? '' );
		$dependency  = $field['dependency_field'] ?? '';
		$dep_value   = (string) ( $field['dependency_value'] ?? '1' );
		$row_attrs   = array();

		if ( $dependency ) {
			$row_attrs[] = 'data-dependency-field="' . esc_attr( (string) $dependency ) . '"';
			$row_attrs[] = 'data-dependency-value="' . esc_attr( $dep_value ) . '"';
		}

		echo '<tr ' . implode( ' ', $row_attrs ) . '>';
		printf(
			'<th scope="row"><label for="%1$s">%2$s</label></th>',
			esc_attr( $field_id ),
			esc_html( (string) $field['label'] )
		);
		echo '<td>';

		switch ( $field_type ) {
			case 'media':
				$this->render_media_field( $field, $field_value );
				break;

			case 'color':
				printf(
					'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="regular-text lerm-color-field">',
					esc_attr( $field_id ),
					esc_attr( $field_name ),
					esc_attr( (string) $field_value )
				);
				break;

			case 'select':
				$choices = ThemeOptionsSchema::choices( $field );
				printf(
					'<select id="%1$s" name="%2$s" class="regular-text" %3$s>',
					esc_attr( $field_id ),
					esc_attr( $field_name ),
					$dependency ? 'data-lerm-controller="1"' : ''
				);
				foreach ( $choices as $value => $label ) {
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $value ),
						selected( (string) $field_value, (string) $value, false ),
						esc_html( $label )
					);
				}
				echo '</select>';
				break;

			case 'checkbox_list':
				$choices = ThemeOptionsSchema::choices( $field );
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

			case 'url':
			case 'text':
				printf(
					'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text" %5$s placeholder="%6$s">',
					esc_attr( $field_type ),
					esc_attr( $field_id ),
					esc_attr( $field_name ),
					esc_attr( (string) $field_value ),
					$dependency ? 'data-lerm-controller="1"' : '',
					esc_attr( (string) ( $field['placeholder'] ?? '' ) )
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
		}

		if ( $description ) {
			printf( '<p class="description">%s</p>', esc_html( $description ) );
		}

		echo '</td></tr>';
	}

	/**
	 * Render a media picker field.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Field value.
	 */
	private function render_media_field( array $field, $value ): void {
		$field_id      = (string) $field['id'];
		$name_prefix   = ThemeOptionsSchema::OPTION_NAME . '[' . $field_id . ']';
		$attachment_id = is_array( $value ) ? absint( $value['id'] ?? 0 ) : 0;
		$image_url     = $attachment_id > 0 ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';
		$button_text   = (string) ( $field['button_text'] ?? __( 'Choose image', 'lerm' ) );

		printf(
			'<div class="lerm-media-field" data-target="%1$s"><input type="hidden" name="%2$s[id]" value="%3$s"><div class="lerm-media-preview">%4$s</div><div class="lerm-media-actions"><button type="button" class="button lerm-media-select">%5$s</button><button type="button" class="button-link-delete lerm-media-remove" %6$s>%7$s</button></div></div>',
			esc_attr( $field_id ),
			esc_attr( $name_prefix ),
			esc_attr( (string) $attachment_id ),
			$image_url ? '<img src="' . esc_url( $image_url ) . '" alt="">' : '<span class="lerm-media-placeholder">' . esc_html__( 'No image selected.', 'lerm' ) . '</span>',
			esc_html( $button_text ),
			$attachment_id > 0 ? '' : 'hidden',
			esc_html__( 'Remove', 'lerm' )
		);
	}

	/**
	 * Resolve the current tab.
	 */
	private function current_tab(): string {
		$sections = ThemeOptionsSchema::sections();
		$tab      = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'header';

		if ( ! isset( $sections[ $tab ] ) ) {
			return (string) array_key_first( $sections );
		}

		return $tab;
	}
}
