<?php // phpcs:disable WordPress.Files.FileName
/**
 * Enqueue theme styles and scripts.
 *
 * @package Lerm
 */

declare( strict_types = 1 );

namespace Lerm\Core;

use Lerm\Traits\Singleton;

class Enqueue {
	use Singleton;

	private const ASSET_VERSION = LERM_VERSION;
	private const LERM_URI      = LERM_URI;

	/**
	 * Default enqueue arguments.
	 *
	 * @var array<string, mixed>
	 */
	private static $args = array(
		'enable_code_highlight' => true,
		'cdn_jquery'            => '',
	);

	/**
	 * Registered theme styles.
	 *
	 * @var array<string, string>
	 */
	private static $styles = array(
		'main_style' => 'assets/dist/main.css',
		'solarized'  => 'assets/resources/css/solarized-dark.min.css',
	);

	/**
	 * Registered theme scripts.
	 *
	 * @var array<string, string>
	 */
	private static array $scripts = array(
		'main-js' => 'assets/dist/bundle.js',
	);

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $params Optional parameters.
	 */
	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_assets_args', wp_parse_args( $params, self::$args ) );
		$this->hooks();
	}

	/**
	 * Register hooks.
	 */
	public static function hooks(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

		add_filter( 'script_loader_tag', array( __CLASS__, 'add_module_type' ), 10, 2 );
	}

	/**
	 * Enqueue front-end styles.
	 */
	public static function enqueue_styles(): void {
		foreach ( self::$styles as $handle => $relative_path ) {
			if ( 'solarized' === $handle && ! ( is_singular() && self::$args['enable_code_highlight'] ) ) {
				continue;
			}

			wp_enqueue_style(
				$handle,
				self::LERM_URI . $relative_path,
				array(),
				self::ASSET_VERSION
			);
		}
	}

	/**
	 * Enqueue front-end scripts.
	 */
	public static function enqueue_scripts(): void {
		$scripts = apply_filters( 'lerm_enqueue_scripts', self::$scripts );

		foreach ( $scripts as $handle => $relative_path ) {
			wp_register_script(
				$handle,
				self::LERM_URI . $relative_path,
				array(),
				self::ASSET_VERSION,
				true
			);
			wp_enqueue_script( $handle );
		}

		if ( self::$args['cdn_jquery'] ) {
			wp_enqueue_script( 'jquery_cdn', self::$args['cdn_jquery'], array(), self::ASSET_VERSION, true );
		}

		wp_localize_script(
			'main-js',
			'lermData',
			apply_filters(
				'lerm_l10n_data',
				array(
					'rest_url'       => esc_url_raw( rest_url( 'lerm/v1/' ) ),
					'nonce'          => wp_create_nonce( 'wp_rest' ),
					'profile_nonce'  => wp_create_nonce( 'lerm_profile' ),
					'loggedin'       => is_user_logged_in(),
					'post_id'        => is_singular() ? get_the_ID() : 0,
					'route_like'     => 'like',
					'route_views'    => 'views',
					'route_search'   => 'search',
					'route_loadmore' => 'posts',
					'route_comment'  => 'comment',
					'route_profile'  => 'profile',
					'redirect'       => esc_url(
						is_user_logged_in()
							? ( get_edit_profile_url() !== false ? get_edit_profile_url() : home_url( '/' ) )
							: home_url( '/' )
					),
					'i18n'           => array(
						'like'                   => __( 'Like', 'lerm' ),
						'unlike'                 => __( 'Unlike', 'lerm' ),
						'missing_nonce'          => __( 'Security token is missing.', 'lerm' ),
						'click_success'          => __( 'Action completed successfully.', 'lerm' ),
						'click_failed'           => __( 'Unable to complete the requested action.', 'lerm' ),
						'form_submitted'         => __( 'Form submitted successfully!', 'lerm' ),
						'error_occurred'         => __( 'An error occurred: {message}', 'lerm' ),
						'invalid_format'         => __( 'Invalid format.', 'lerm' ),
						'invalid_email_format'   => __( 'Please enter a valid email address.', 'lerm' ),
						'register_username_min'  => __( 'Username must be at least {minLength} characters long.', 'lerm' ),
						'comment_username_min'   => __( 'Name must be at least {minLength} characters long.', 'lerm' ),
						'password_min'           => __( 'Password must be at least {minLength} characters long.', 'lerm' ),
						'password_uppercase'     => __( 'Password must contain at least one uppercase letter.', 'lerm' ),
						'password_number'        => __( 'Password must contain at least one number.', 'lerm' ),
						'password_special'       => __( 'Password must contain at least one special character.', 'lerm' ),
						'password_mismatch'      => __( 'Passwords do not match.', 'lerm' ),
						'comment_min'            => __( 'Comment must be at least {minLength} characters long.', 'lerm' ),
						'show'                   => __( 'Show', 'lerm' ),
						'hide'                   => __( 'Hide', 'lerm' ),
						'show_password'          => __( 'Show password', 'lerm' ),
						'hide_password'          => __( 'Hide password', 'lerm' ),
					),
				)
			)
		);

		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}

	/**
	 * Determine whether social share scripts should be enqueued.
	 */
	private static function should_enqueue_social_share(): bool {
		$should = is_singular( 'post' ) || is_page_template( 'templates/account.php' );

		return (bool) apply_filters( 'lerm_enqueue_social_share', $should );
	}

	/**
	 * Add type="module" to the main bundle.
	 */
	public static function add_module_type( string $tag, string $handle ): string {
		if ( 'main-js' === $handle ) {
			return str_replace( '<script ', '<script type="module" ', $tag );
		}

		return $tag;
	}
}
