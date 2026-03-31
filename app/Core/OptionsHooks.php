<?php
/**
 * OptionsHooks — wires theme-options values into WordPress core settings
 * and injects dynamic front-end behaviour driven by the admin panel.
 *
 * Instantiated once from bootstrap.php after $template_options is built.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OptionsHooks {

	private array $opts;

	public function __construct( array $opts ) {
		$this->opts = $opts;
		$this->register();
	}

	public static function instance( array $opts ): self {
		return new self( $opts );
	}

	private function register(): void {
		// ── Comments ────────────────────────────────────────────────────────
		add_filter( 'comments_open', array( $this, 'filter_comments_open' ), 10, 2 );
		add_filter( 'pre_option_comment_registration', array( $this, 'filter_require_login' ) );
		add_filter( 'pre_option_comment_moderation', array( $this, 'filter_moderation' ) );
		add_filter( 'pre_option_comments_per_page', array( $this, 'filter_comments_per_page' ) );
		add_filter( 'pre_option_thread_comments_depth', array( $this, 'filter_nesting_depth' ) );
		add_filter( 'comment_form_defaults', array( $this, 'filter_comment_form' ) );
		add_filter( 'preprocess_comment', array( $this, 'validate_comment_length' ) );

		// ── Search results per page ──────────────────────────────────────────
		add_action( 'pre_get_posts', array( $this, 'filter_search_per_page' ) );

		// ── TOC injection into content ───────────────────────────────────────
		if ( ! empty( $this->opts['toc_enable'] ) ) {
			$pos = $this->opts['toc_position'] ?? 'before_content';
			if ( 'before_content' === $pos ) {
				add_filter( 'the_content', array( $this, 'inject_toc_before_content' ), 5 );
			}
			if ( 'floating' === $pos ) {
				add_action( 'wp_footer', array( $this, 'inject_floating_toc' ), 5 );
			}
			// Add id anchors to headings regardless of position
			add_filter( 'the_content', array( $this, 'add_heading_ids' ), 6 );
		}

		// ── Sticky header body class ─────────────────────────────────────────
		add_filter( 'body_class', array( $this, 'add_body_classes' ) );
	}

	// ── Comments ─────────────────────────────────────────────────────────────

	public function filter_comments_open( bool $open, int $post_id ): bool {
		if ( empty( $this->opts['comments_enable'] ) ) {
			return false;
		}
		return $open;
	}

	public function filter_require_login( $val ) {
		return ! empty( $this->opts['comments_require_login'] ) ? '1' : '0';
	}

	public function filter_moderation( $val ) {
		return ! empty( $this->opts['comment_moderation'] ) ? '1' : '0';
	}

	public function filter_comments_per_page( $val ) {
		$n = (int) ( $this->opts['comments_per_page'] ?? 20 );
		return $n > 0 ? $n : $val;
	}

	public function filter_nesting_depth( $val ) {
		$n = (int) ( $this->opts['comment_nesting_depth'] ?? 3 );
		return $n > 0 ? $n : $val;
	}

	public function filter_comment_form( array $defaults ): array {
		// Placeholder on textarea
		$placeholder = $this->opts['comment_placeholder'] ?? '';
		if ( $placeholder ) {
			$defaults['comment_field'] = preg_replace(
				'/(<textarea[^>]*?)>/',
				'$1 placeholder="' . esc_attr( $placeholder ) . '">',
				$defaults['comment_field'] ?? ''
			);
		}

		// Required fields: name / email / website
		$required = (array) ( $this->opts['comment_form_fields'] ?? array( 'name', 'email' ) );
		$fields   = $defaults['fields'] ?? array();

		foreach ( array( 'author', 'email', 'url' ) as $field ) {
			$key = $field === 'author' ? 'name' : ( $field === 'url' ? 'website' : $field );
			if ( ! in_array( $key, $required, true ) && isset( $fields[ $field ] ) ) {
				unset( $fields[ $field ] );
			}
		}
		$defaults['fields'] = $fields;

		return $defaults;
	}

	public function validate_comment_length( array $comment_data ): array {
		$text = trim( $comment_data['comment_content'] ?? '' );
		$min  = (int) ( $this->opts['comment_min_length'] ?? 0 );
		$max  = (int) ( $this->opts['comment_max_length'] ?? 0 );
		$len  = mb_strlen( $text );

		if ( $min > 0 && $len < $min ) {
			wp_die(
				sprintf(
					/* translators: 1: minimum chars, 2: actual chars */
					esc_html__( 'Comment too short. Minimum %1$d characters required (%2$d entered).', 'lerm' ),
					$min,
					$len
				),
				esc_html__( 'Comment too short', 'lerm' ),
				array( 'back_link' => true )
			);
		}

		if ( $max > 0 && $len > $max ) {
			wp_die(
				sprintf(
					/* translators: 1: maximum chars, 2: actual chars */
					esc_html__( 'Comment too long. Maximum %1$d characters allowed (%2$d entered).', 'lerm' ),
					$max,
					$len
				),
				esc_html__( 'Comment too long', 'lerm' ),
				array( 'back_link' => true )
			);
		}

		return $comment_data;
	}

	// ── Search ───────────────────────────────────────────────────────────────

	public function filter_search_per_page( \WP_Query $query ): void {
		if ( ! $query->is_main_query() || ! $query->is_search() ) {
			return;
		}
		$n = (int) ( $this->opts['search_results_per_page'] ?? 0 );
		if ( $n > 0 ) {
			$query->set( 'posts_per_page', $n );
		}
	}

	// ── TOC ──────────────────────────────────────────────────────────────────

	/**
	 * Add id= attributes to h2–h6 tags so TOC anchor links work.
	 */
	public function add_heading_ids( string $content ): string {
		if ( ! is_singular( 'post' ) ) {
			return $content;
		}
		$slug_counts = array();
		return preg_replace_callback(
			'/<h([2-6])([^>]*)>(.*?)<\/h\1>/is',
			function ( $m ) use ( &$slug_counts ) {
				// If already has an id, leave it alone
				if ( preg_match( '/\bid\s*=/i', $m[2] ) ) {
					return $m[0];
				}
				$raw                  = wp_strip_all_tags( $m[3] );
				$base                 = sanitize_title( $raw );
				$slug_counts[ $base ] = ( $slug_counts[ $base ] ?? 0 ) + 1;
				$id                   = $slug_counts[ $base ] > 1 ? $base . '-' . ( $slug_counts[ $base ] - 1 ) : $base;
				return "<h{$m[1]}{$m[2]} id=\"" . esc_attr( $id ) . "\">{$m[3]}</h{$m[1]}>";
			},
			$content
		);
	}

	public function inject_toc_before_content( string $content ): string {
		if ( ! is_singular( 'post' ) ) {
			return $content;
		}
		ob_start();
		get_template_part( 'template-parts/components/toc' );
		$toc = ob_get_clean();
		return $toc . $content;
	}

	public function inject_floating_toc(): void {
		if ( ! is_singular( 'post' ) ) {
			return;
		}
		get_template_part( 'template-parts/components/toc' );
	}

	// ── Body classes ─────────────────────────────────────────────────────────

	public function add_body_classes( array $classes ): array {
		if ( ! empty( $this->opts['dark_mode_enable'] ) ) {
			$classes[] = 'lerm-dark-mode-enabled';
		}
		if ( ! empty( $this->opts['sticky_header'] ) ) {
			$classes[] = 'has-sticky-header';
		}
		return $classes;
	}
}
