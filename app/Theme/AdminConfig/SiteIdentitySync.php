<?php // phpcs:disable WordPress.Files.FileName
/**
 * Keep WordPress Site Identity settings and theme settings in sync.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\Theme\AdminConfig;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SiteIdentitySync {

	private const MIGRATION_FLAG = 'lerm_site_identity_sync_migrated';

	private static bool $bootstrapped = false;

	private static bool $syncing = false;

	public static function init(): void {
		if ( self::$bootstrapped ) {
			return;
		}

		self::$bootstrapped = true;

		self::bootstrap_existing_values();

		add_action( 'lerm_admin_config_after_save', array( __CLASS__, 'sync_admin_to_wordpress' ), 10, 3 );
		add_action( 'updated_option', array( __CLASS__, 'sync_wordpress_option_to_admin' ), 10, 3 );
		add_action( 'added_option', array( __CLASS__, 'sync_added_wordpress_option_to_admin' ), 10, 2 );
		add_action( 'customize_save_after', array( __CLASS__, 'sync_wordpress_to_admin' ), 10, 0 );
	}

	/**
	 * Return a media field value compatible with AdminConfig media controls.
	 *
	 * @return array<string, mixed>
	 */
	public static function media_value( int $attachment_id ): array {
		if ( $attachment_id <= 0 ) {
			return array();
		}

		$attachment_url = wp_get_attachment_url( $attachment_id );

		if ( ! $attachment_url ) {
			return array();
		}

		$thumbnail_url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );

		return array_filter(
			array(
				'id'        => $attachment_id,
				'url'       => $attachment_url,
				'thumbnail' => $thumbnail_url ? $thumbnail_url : '',
			)
		);
	}

	public static function display_header_text(): bool {
		return 'blank' !== (string) get_theme_mod( 'header_textcolor', '' );
	}

	/**
	 * Sync a completed AdminConfig save into WordPress core options/theme mods.
	 *
	 * @param string               $page_id Stored option key.
	 * @param array<string, mixed> $data    Saved settings payload.
	 */
	public static function sync_admin_to_wordpress( string $page_id, array $data ): void {
		if ( ThemeOptionsDefinition::OPTION_NAME !== $page_id || self::$syncing ) {
			return;
		}

		self::$syncing = true;

		try {
			if ( array_key_exists( 'blogname', $data ) ) {
				update_option( 'blogname', sanitize_text_field( self::scalar_value( $data['blogname'] ) ) );
			}

			if ( array_key_exists( 'tagline', $data ) ) {
				update_option( 'blogdescription', sanitize_text_field( self::scalar_value( $data['tagline'] ) ) );
			}

			if ( array_key_exists( 'large_logo', $data ) ) {
				$logo_id = self::media_id( $data['large_logo'] );

				if ( $logo_id > 0 ) {
					set_theme_mod( 'custom_logo', $logo_id );
				} else {
					remove_theme_mod( 'custom_logo' );
				}
			}

			if ( array_key_exists( 'site_icon', $data ) ) {
				update_option( 'site_icon', self::media_id( $data['site_icon'] ) );
			}

			if ( array_key_exists( 'display_header_text', $data ) ) {
				if ( ! empty( $data['display_header_text'] ) ) {
					remove_theme_mod( 'header_textcolor' );
				} else {
					set_theme_mod( 'header_textcolor', 'blank' );
				}
			}
		} finally {
			self::$syncing = false;
		}
	}

	/**
	 * Sync WordPress option/theme-mod changes into the theme settings option.
	 *
	 * @param string $option Option name.
	 * @param mixed  $old_value Old option value.
	 * @param mixed  $value New option value.
	 */
	public static function sync_wordpress_option_to_admin( string $option, $old_value, $value ): void {
		unset( $old_value );

		if ( self::$syncing || ThemeOptionsDefinition::OPTION_NAME === $option ) {
			return;
		}

		$updates = self::updates_for_wordpress_option( $option, $value );

		if ( ! empty( $updates ) ) {
			self::merge_theme_options( $updates );
		}
	}

	/**
	 * @param mixed $value Added option value.
	 */
	public static function sync_added_wordpress_option_to_admin( string $option, $value ): void {
		self::sync_wordpress_option_to_admin( $option, null, $value );
	}

	public static function sync_wordpress_to_admin(): void {
		if ( self::$syncing ) {
			return;
		}

		self::merge_theme_options( self::current_wordpress_identity() );
	}

	private static function bootstrap_existing_values(): void {
		if ( self::$syncing ) {
			return;
		}

		if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
			return;
		}

		if ( get_option( self::MIGRATION_FLAG, false ) ) {
			self::sync_wordpress_to_admin();
			return;
		}

		$raw = self::theme_options();

		$updates = array(
			'display_header_text' => self::display_header_text(),
			'site_icon'           => self::media_value( absint( get_option( 'site_icon', 0 ) ) ),
		);

		if ( self::filled_scalar( $raw['blogname'] ?? '' ) ) {
			update_option( 'blogname', sanitize_text_field( self::scalar_value( $raw['blogname'] ) ) );
		} else {
			$updates['blogname'] = (string) get_option( 'blogname', '' );
		}

		if ( self::filled_scalar( $raw['tagline'] ?? '' ) ) {
			update_option( 'blogdescription', sanitize_text_field( self::scalar_value( $raw['tagline'] ) ) );
		} else {
			$updates['tagline'] = (string) get_option( 'blogdescription', '' );
		}

		$stored_logo_id = self::media_id( $raw['large_logo'] ?? array() );

		if ( $stored_logo_id > 0 ) {
			set_theme_mod( 'custom_logo', $stored_logo_id );
		} else {
			$updates['large_logo'] = self::media_value( absint( get_theme_mod( 'custom_logo', 0 ) ) );
		}

		self::merge_theme_options( $updates );
		update_option( self::MIGRATION_FLAG, '1' );
	}

	/**
	 * @param mixed $value Option value.
	 * @return array<string, mixed>
	 */
	private static function updates_for_wordpress_option( string $option, $value ): array {
		if ( 'blogname' === $option ) {
			return array(
				'blogname' => sanitize_text_field( self::scalar_value( $value ) ),
			);
		}

		if ( 'blogdescription' === $option ) {
			return array(
				'tagline' => sanitize_text_field( self::scalar_value( $value ) ),
			);
		}

		if ( 'site_icon' === $option ) {
			return array(
				'site_icon' => self::media_value( absint( $value ) ),
			);
		}

		if ( self::theme_mods_option_name() === $option ) {
			$mods = is_array( $value ) ? $value : array();

			return array(
				'large_logo'          => self::media_value( absint( $mods['custom_logo'] ?? 0 ) ),
				'display_header_text' => 'blank' !== (string) ( $mods['header_textcolor'] ?? '' ),
			);
		}

		return array();
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function current_wordpress_identity(): array {
		return array(
			'large_logo'          => self::media_value( absint( get_theme_mod( 'custom_logo', 0 ) ) ),
			'blogname'            => (string) get_option( 'blogname', '' ),
			'tagline'             => (string) get_option( 'blogdescription', '' ),
			'display_header_text' => self::display_header_text(),
			'site_icon'           => self::media_value( absint( get_option( 'site_icon', 0 ) ) ),
		);
	}

	/**
	 * @param array<string, mixed> $updates Values to merge into theme options.
	 */
	private static function merge_theme_options( array $updates ): void {
		if ( empty( $updates ) || self::$syncing ) {
			return;
		}

		self::$syncing = true;

		try {
			$options = self::theme_options();
			$next    = array_merge( $options, $updates );

			if ( $next !== $options ) {
				update_option( ThemeOptionsDefinition::OPTION_NAME, $next );
			}
		} finally {
			self::$syncing = false;
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function theme_options(): array {
		$options = get_option( ThemeOptionsDefinition::OPTION_NAME, array() );

		return is_array( $options ) ? $options : array();
	}

	/**
	 * @param mixed $value Media field value.
	 */
	private static function media_id( $value ): int {
		if ( is_array( $value ) ) {
			return absint( $value['id'] ?? 0 );
		}

		return absint( $value );
	}

	/**
	 * @param mixed $value Candidate scalar.
	 */
	private static function scalar_value( $value ): string {
		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * @param mixed $value Candidate scalar.
	 */
	private static function filled_scalar( $value ): bool {
		return '' !== trim( self::scalar_value( $value ) );
	}

	private static function theme_mods_option_name(): string {
		return 'theme_mods_' . get_stylesheet();
	}
}
