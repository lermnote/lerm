<?php
/**
 * WordPress user profile container.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\WordPress\Containers;

use Lerm\AdminConfig\Compiler\CompiledSchema;
use Lerm\AdminConfig\Contracts\Container;
use Lerm\AdminConfig\Stores\StoreResolver;
use Lerm\AdminConfig\Framework\Admin\OptionsPage;
use Lerm\AdminConfig\Framework\Framework;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\Framework\Support\PageSchema;
use Lerm\AdminConfig\WordPress\Support\ContainerSaveSupport;
use Lerm\AdminConfig\WordPress\Support\ValidationFlash;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ProfileContainer implements Container {

	/**
	 * @var array<string, CompiledSchema>
	 */
	private array $schemas = array();

	private bool $hooks_registered       = false;
	private bool $assets_hook_registered = false;

	public function __construct(
		private Framework $framework,
		private StoreResolver $stores
	) {
	}

	public function type(): string {
		return 'profile';
	}

	public function mount( CompiledSchema $schema ): void {
		$this->schemas[ $schema->id() ] = $schema;

		if ( ! $this->hooks_registered ) {
			add_action( 'show_user_profile', array( $this, 'render_user_profile' ) );
			add_action( 'edit_user_profile', array( $this, 'render_user_profile' ) );
			add_action( 'personal_options_update', array( $this, 'save_user_profile' ) );
			add_action( 'edit_user_profile_update', array( $this, 'save_user_profile' ) );
			$this->hooks_registered = true;
		}

		if ( ! $this->assets_hook_registered ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			$this->assets_hook_registered = true;
		}
	}

	public function enqueue_assets(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || ! in_array( $screen->id, array( 'profile', 'user-edit' ), true ) ) {
			return;
		}

		foreach ( $this->schemas as $schema ) {
			$this->renderer( $schema, get_current_user_id() )->enqueue_support_assets( 'profile-' . $schema->id() );
		}
	}

	public function render_user_profile( \WP_User $user ): void {
		foreach ( $this->schemas as $schema ) {
			$container = $schema->container();
			$title     = isset( $container['title'] ) && is_scalar( $container['title'] ) ? (string) $container['title'] : __( 'Profile Settings', 'lerm-admin-config' );
			$store     = $this->stores->store( $schema, array( 'user_id' => $user->ID ) );
			$renderer  = $this->renderer( $schema, $user->ID );
			$flash     = ValidationFlash::consume( 'profile', $schema->id(), (string) $user->ID );
			$values    = ValidationFlash::render_values( $store->all(), $flash );
			$errors    = ValidationFlash::field_errors( $flash );
			$notice    = ValidationFlash::notice( $flash );

			echo '<h2>' . esc_html( $title ) . '</h2>';
			echo '<table class="form-table" role="presentation">';

			if ( null !== $notice ) {
				printf(
					'<tr class="user-admin-config-notice"><td colspan="2"><div class="notice %1$s inline"><p>%2$s</p></div></td></tr>',
					esc_attr( $notice['class'] ),
					esc_html( $notice['message'] )
				);
			}

			foreach ( PageSchema::sections( $schema->definition() ) as $section_id => $section ) {
				$section_title = isset( $section['title'] ) && is_scalar( $section['title'] ) ? (string) $section['title'] : '';

				if ( '' !== $section_title ) {
					printf(
						'<tr class="user-admin-config-group"><th scope="row" colspan="2"><h3>%s</h3></th></tr>',
						esc_html( $section_title )
					);
				}

				$renderer->render_fields(
					PageSchema::section_fields( $section ),
					$values,
					(string) $section_id,
					false,
					'table',
					$errors
				);
			}

			printf(
				'<tr class="user-admin-config-nonce"><td colspan="2">%s</td></tr>',
				wp_nonce_field( ContainerSaveSupport::nonce_action( 'profile', $schema ), ContainerSaveSupport::nonce_name( 'profile', $schema ), true, false )
			);
			echo '</table>';
		}
	}

	public function save_user_profile( int $user_id ): void {
		$user = get_userdata( $user_id );

		if ( ! $user instanceof \WP_User ) {
			return;
		}

		foreach ( $this->schemas as $schema ) {
			$nonce = ContainerSaveSupport::posted_nonce( ContainerSaveSupport::nonce_name( 'profile', $schema ) );

			if ( '' === $nonce || ! wp_verify_nonce( $nonce, ContainerSaveSupport::nonce_action( 'profile', $schema ) ) ) {
				continue;
			}

			if ( ! current_user_can( $this->capability_for_schema( $schema ), $user_id ) ) {
				continue;
			}

			$store     = $this->stores->store( $schema, array( 'user_id' => $user_id ) );
			$submitted = ContainerSaveSupport::submitted_values( $store );

			ContainerSaveSupport::persist(
				'profile',
				$schema->id(),
				(string) $user_id,
				$store,
				$submitted,
				__( 'Please review the highlighted profile fields before saving again.', 'lerm-admin-config' ),
				__( 'Unable to save these profile settings right now.', 'lerm-admin-config' )
			);
		}
	}

	private function renderer( CompiledSchema $schema, int $user_id ): OptionsPage {
		return new OptionsPage(
			$schema->definition(),
			$this->stores->store( $schema, array( 'user_id' => $user_id ) ),
			$this->framework->field_types(),
			$this->framework->asset_resolver(),
			false,
			$this->framework->field_modules()
		);
	}

	private function capability_for_schema( CompiledSchema $schema ): string {
		$container = $schema->container();

		if ( ! empty( $container['capability'] ) && is_scalar( $container['capability'] ) ) {
			return (string) $container['capability'];
		}

		return 'edit_user';
	}
}
