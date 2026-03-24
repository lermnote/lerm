<?php // phpcs:disable WordPress.Files.FileName
/**
 * Baidu search engine submission handler.
 *
 * Automatically submits new and updated post URLs to Baidu's real-time push API
 * to improve indexing speed and SEO performance.
 *
 * @package Lerm\SEO
 */
declare( strict_types=1 );

namespace Lerm\SEO;

use Lerm\Traits\Singleton;

final class BaiduSubmit {
	use Singleton;

	/**
	 * Default configuration for Baidu submission.
	 *
	 * @var array Default settings including submission URL, token, and throttle interval.
	 */
	protected static array $args = array(
		'baidu_submit'    => false,
		'submit_url'      => '',
		'submit_token'    => '',
		'submit_interval' => 300,
	);

	/**
	 * Constructor.
	 *
	 * @param array $params Optional parameters to override default settings.
	 */
	public function __construct( array $params = array() ) {
		self::$args = apply_filters( 'lerm_baidu_submit_args', wp_parse_args( $params, self::$args ) );
		$this->hooks();
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		if ( ! empty( self::$args['baidu_submit'] ) ) {
			add_action( 'publish_post', array( __CLASS__, 'on_publish' ) );
			add_action( 'publish_future_post', array( __CLASS__, 'on_publish' ) );
		}
	}

	/**
	 * Handle post publish event.
	 *
	 * Submits the post URL to Baidu when a post is published. Implements throttling
	 * to prevent duplicate submissions within the configured interval.
	 *
	 * @param int $post_ID The published post ID.
	 * @return void
	 */
	public static function on_publish( int $post_ID ): void {
		if ( ! $post_ID ) {
			return;
		}

		$throttle_key = 'lerm_baidu_push_' . $post_ID;
		if ( get_transient( $throttle_key ) ) {
			return;
		}

		$post_url = get_permalink( $post_ID );
		if ( ! $post_url ) {
			return;
		}

		$result = self::submit(
			$post_url,
			self::$args['submit_url'],
			self::$args['submit_token']
		);

		// Only set throttle lock on success to allow retry on failure
		if ( ! is_wp_error( $result ) && ! empty( $result ) ) {
			set_transient( $throttle_key, 1, (int) self::$args['submit_interval'] );
		}
	}

	/**
	 * Submit URL to Baidu's real-time push API.
	 *
	 * Sends a POST request to Baidu's URL submission API with the given URL.
	 * Uses non-blocking mode to avoid affecting page load times.
	 *
	 * @param string $url   The URL to submit.
	 * @param string $site  The Baidu verified site URL.
	 * @param string $token The Baidu push token.
	 * @return array<string,mixed>|\WP_Error|null Response data or error.
	 */
	public static function submit( string $url, string $site = '', string $token = '' ): array|\WP_Error|null {
		if ( ! $site || ! $token || ! $url ) {
			return null;
		}

		$api = add_query_arg(
			array(
				'site'  => urlencode( $site ),
				'token' => urlencode( $token ),
			),
			'https://data.zz.baidu.com/urls'
		);

		$response = wp_remote_post(
			$api,
			array(
				'headers'  => array( 'Content-Type' => 'text/plain' ),
				'body'     => $url,
				'timeout'  => 5,
				'blocking' => false, // Non-blocking: don't wait for response to avoid affecting publish speed
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		return $body ? json_decode( $body, true ) : null;
	}
}
