<?php // phpcs:disable WordPress.Files.FileName
/**
 * User profile schema for theme-specific user_meta fields.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\Theme\AdminConfig;

use Lerm\AdminConfig\Compiler\CompiledSchema;
use Lerm\AdminConfig\WordPress\Runtime;
use Lerm\AdminConfig\Framework\Admin\OptionsPage;
use Lerm\AdminConfig\Framework\Contracts\StorageBackend;
use Lerm\AdminConfig\Framework\Storage\OptionStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ProfileSettingsSchema {

	private const STORE_TYPE = 'lerm_profile_meta';

	/**
	 * Cached schema definition.
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $definition = null;

	public static function schema_id(): string {
		return 'lerm-profile-settings';
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function definition(): array {
		if ( null !== self::$definition ) {
			return self::$definition;
		}

		self::$definition = array(
			'id'        => self::schema_id(),
			'title'     => __( 'Theme Profile Settings', 'lerm' ),
			'container' => array(
				'type'       => 'profile',
				'title'      => __( 'Theme Profile Settings', 'lerm' ),
				'capability' => 'edit_user',
			),
			'store'     => array(
				'type' => self::STORE_TYPE,
				'key'  => 'lerm_profile_meta',
			),
			'sections'  => array(
				'profile' => array(
					'title'       => __( 'Profile', 'lerm' ),
					'description' => __( 'Theme-specific profile fields used by the frontend account and author views.', 'lerm' ),
					'fields'      => array(
						array(
							'id'          => 'avatar_id',
							'type'        => 'avatar_media_id',
							'label'       => __( 'Custom Avatar', 'lerm' ),
							'description' => __( 'Choose an avatar image used by the theme avatar override.', 'lerm' ),
							'default'     => 0,
						),
						array(
							'id'          => 'gender',
							'type'        => 'radio',
							'label'       => __( 'Gender', 'lerm' ),
							'description' => __( 'Optional profile gender label used in the frontend profile editor.', 'lerm' ),
							'choices'     => array(
								''       => __( 'Unspecified', 'lerm' ),
								'female' => __( 'Female', 'lerm' ),
								'male'   => __( 'Male', 'lerm' ),
								'other'  => __( 'Other', 'lerm' ),
							),
							'default'     => '',
						),
						array(
							'id'          => 'address',
							'type'        => 'text',
							'label'       => __( 'Address', 'lerm' ),
							'description' => __( 'Optional address displayed in the frontend profile workflow.', 'lerm' ),
							'default'     => '',
						),
					),
				),
			),
		);

		return self::$definition;
	}

	public static function register( Runtime $runtime ): void {
		self::register_store_factory( $runtime );
		self::register_field_types( $runtime );

		if ( $runtime->has( self::schema_id() ) ) {
			return;
		}

		$runtime->register( self::definition() );
	}

	private static function register_store_factory( Runtime $runtime ): void {
		$runtime->register_store_factory(
			self::STORE_TYPE,
			static function ( CompiledSchema $schema, array $context ): StorageBackend {
				$user_id = absint( $context['user_id'] ?? $context['object_id'] ?? get_current_user_id() );

				return new ProfileMetaBackend( $user_id );
			}
		);
	}

	private static function register_field_types( Runtime $runtime ): void {
		$runtime->field_types()->register(
			'avatar_media_id',
			array(
				'render'   => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
					$field_id      = (string) $field['id'];
					$attachment_id = absint( $value );
					$image_url     = $attachment_id > 0 ? (string) wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';
					$button_text   = (string) ( $field['button_text'] ?? __( 'Choose image', 'lerm' ) );

					printf(
						'<div class="lerm-media-field" data-target="%1$s"><input type="hidden" name="%2$s" value="%3$s"><div class="lerm-media-preview" %6$s>%4$s</div><div class="lerm-media-actions"><button type="button" class="button lerm-media-select">%5$s</button><button type="button" class="button button-secondary button-link-delete lerm-media-remove" %7$s>%8$s</button></div></div>',
						esc_attr( $field_id ),
						esc_attr( $field_name ),
						esc_attr( (string) $attachment_id ),
						$image_url ? '<img src="' . esc_url( $image_url ) . '" alt="">' : '',
						esc_html( $button_text ),
						$image_url ? '' : 'hidden',
						$attachment_id > 0 ? '' : 'hidden',
						esc_html__( 'Remove', 'lerm' )
					);
				},
				'sanitize' => static function ( array $field, $value, bool $strict, OptionStore $store ) {
					return absint( $value );
				},
			)
		);
	}
}
