<?php // phpcs:disable WordPress.Files.FileName
/**
 * Breadcrumb navigation.
 *
 * @package Lerm
 * @since   2.0.0
 *
 * Primary API — use the global helper instead of instantiating directly:
 *
 *   echo lerm_breadcrumb( array( 'separator' => '›' ) );
 */

declare( strict_types=1 );

namespace Lerm\View;

/**
 * Builds and renders Schema.org-annotated Bootstrap breadcrumb HTML.
 *
 * Design notes:
 *  - final + pure instance: no Singleton, no static state, fully re-entrant.
 *  - All state lives in instance properties → safe to instantiate multiple
 *    times per request (e.g. breadcrumb in header AND in content area).
 *  - Favour private over protected; only methods child themes may legitimately
 *    override are protected.
 */
final class Breadcrumb {

	// ── Instance state ────────────────────────────────────────────────────────

	/** @var string[] Resolved breadcrumb items (raw HTML fragments). */
	private array $items = array();

	/** @var array<string, mixed> Merged runtime arguments. */
	private array $args = array();

	/** @var array<string, string> UI label strings. */
	private array $labels = array();

	/** @var array<string, string> post_type → taxonomy mapping. */
	private array $post_taxonomy = array();

	// ── Defaults ──────────────────────────────────────────────────────────────

	private const DEFAULT_ARGS = array(
		'container'     => 'nav',
		'before'        => '',
		'after'         => '',
		'list_tag'      => 'ol',
		'item_tag'      => 'li',
		'separator'     => '/',
		'show_on_front' => false,
		'network'       => false,
		'show_title'    => true,
		'labels'        => array(),
		'post_taxonomy' => array(),
	);

	// ── Constructor ───────────────────────────────────────────────────────────

	public function __construct( array $params = array() ) {
		$this->args = (array) apply_filters(
			'lerm_breadcrumb_args',
			wp_parse_args( $params, self::DEFAULT_ARGS )
		);

		$this->init_labels();
		$this->init_post_taxonomy();
		$this->build_items();
	}

	// ── Public API ────────────────────────────────────────────────────────────

	/**
	 * Render the breadcrumb HTML.
	 * Returns an empty string when no items were resolved (e.g. on the front
	 * page with show_on_front disabled).
	 */
	public function render(): string {
		if ( empty( $this->items ) ) {
			return '';
		}

		$list = $this->build_list();

		$html = sprintf(
			'<%1$s role="navigation" aria-label="%2$s" itemprop="breadcrumb">%3$s%4$s%5$s</%1$s>',
			tag_escape( $this->args['container'] ),
			esc_attr( $this->labels['aria_label'] ),
			$this->args['before'],
			$list,
			$this->args['after']
		);

		return (string) apply_filters( 'lerm_breadcrumb', $html, $this->args );
	}

	/** Allow `echo $breadcrumb` in templates. */
	public function __toString(): string {
		return $this->render();
	}

	// ── Initialisation ────────────────────────────────────────────────────────

	private function init_labels(): void {
		$defaults = array(
			'browse'         => esc_html__( 'Browse:', 'lerm' ),
			'aria_label'     => esc_attr_x( 'breadcrumb', 'breadcrumbs aria label', 'lerm' ),
			'home'           => esc_html__( 'Home', 'lerm' ),
			'post'           => esc_html__( 'Article', 'lerm' ),
			'error_404'      => esc_html__( '404 Not Found', 'lerm' ),
			'archives'       => esc_html__( 'Archives', 'lerm' ),
			/* translators: %s: search query */
			'search'         => esc_html__( 'Search results for: %s', 'lerm' ),
			/* translators: %s: page number */
			'paged'          => esc_html__( 'Page %s', 'lerm' ),
			/* translators: %s: comment page number */
			'paged_comments' => esc_html__( 'Comment Page %s', 'lerm' ),
			'archive_day'    => '%s',
			'archive_month'  => '%s',
			'archive_year'   => '%s',
			'archive_author' => '%s',
		);

		$this->labels = (array) apply_filters(
			'lerm_breadcrumb_labels',
			wp_parse_args( $this->args['labels'], $defaults )
		);
	}

	private function init_post_taxonomy(): void {
		$this->post_taxonomy = (array) apply_filters(
			'lerm_breadcrumb_post_taxonomy',
			wp_parse_args( $this->args['post_taxonomy'], array( 'post' => 'category' ) )
		);
	}

	// ── Item collection ───────────────────────────────────────────────────────

	private function build_items(): void {
		if ( is_front_page() ) {
			$this->add_front_page_items();
		} else {
			$this->add_network_home_link();
			$this->add_site_home_link();

			if ( is_home() ) {
				$this->add_blog_items();
			} elseif ( is_singular() ) {
				$this->add_singular_items();
			} elseif ( is_archive() ) {
				$this->add_archive_items();
			} elseif ( is_search() ) {
				$this->add_search_items();
			} elseif ( is_404() ) {
				$this->items[] = $this->labels['error_404'];
			}
		}

		$this->add_paged_items();

		$this->items = array_values(
			array_unique(
				(array) apply_filters( 'lerm_breadcrumb_items', $this->items, $this->args )
			)
		);
	}

	// ── HTML rendering ────────────────────────────────────────────────────────

	private function build_list(): string {
		$item_count    = count( $this->items );
		$item_position = 0;

		$list  = sprintf(
			'<%s style="--bs-breadcrumb-divider: \'%s\';" class="breadcrumb small mb-0 py-1" itemscope itemtype="https://schema.org/BreadcrumbList">',
			tag_escape( $this->args['list_tag'] ),
			esc_attr( $this->args['separator'] )
		);
		$list .= sprintf( '<meta name="numberOfItems" content="%d" />', absint( $item_count ) );
		$list .= '<meta name="itemListOrder" content="Ascending" />';

		foreach ( $this->items as $item ) {
			++$item_position;
			$list .= $this->build_list_item( $item, $item_position, $item_count );
		}

		$list .= sprintf( '</%s>', tag_escape( $this->args['list_tag'] ) );

		return $list;
	}

	/**
	 * Wrap a single item fragment in Schema.org-annotated <li> markup.
	 */
	private function build_list_item( string $item, int $position, int $total ): string {
		preg_match( '/(<a[^>]*>)(.*?)(<\/a>)/is', $item, $m );

		if ( ! empty( $m ) ) {
			// Link item: wrap text with itemprop="name", inject itemprop="item" on <a>.
			$item = sprintf( '%s<span itemprop="name">%s</span>%s', $m[1], $m[2], $m[3] );
			$item = (string) preg_replace( '/(<a[^>]*?)(\s*>)/i', '$1 itemprop="item"$2', $item, 1 );
		} else {
			// Plain-text item (last crumb / no link).
			$item = sprintf(
				'<span itemprop="item"><span itemprop="name">%s</span></span>',
				$item
			);
		}

		$class = 'breadcrumb-item' . ( $position === $total ? ' active' : '' );
		$meta  = sprintf( '<meta itemprop="position" content="%d" />', $position );

		return sprintf(
			'<%1$s itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" class="%2$s">%3$s%4$s</%1$s>',
			tag_escape( $this->args['item_tag'] ),
			esc_attr( $class ),
			$item,
			$meta
		);
	}

	// ── Item builders ─────────────────────────────────────────────────────────

	private function add_front_page_items(): void {
		if ( ! $this->args['show_on_front'] && ! is_paged() && ! ( is_singular() && get_query_var( 'page' ) > 1 ) ) {
			return;
		}

		$this->add_network_home_link();

		if ( is_paged() ) {
			$this->add_site_home_link();
		} elseif ( $this->args['show_title'] ) {
			$this->items[] = ( is_multisite() && $this->args['network'] )
				? get_bloginfo( 'name' )
				: $this->labels['home'];
		}
	}

	private function add_network_home_link(): void {
		if ( is_multisite() && ! is_main_site() && $this->args['network'] ) {
			$this->items[] = sprintf(
				'<a href="%s" rel="home">%s</a>',
				esc_url( network_home_url() ),
				esc_html( $this->labels['home'] )
			);
		}
	}

	private function add_site_home_link(): void {
		$is_network = is_multisite() && ! is_main_site() && $this->args['network'];
		$label      = $is_network ? get_bloginfo( 'name' ) : $this->labels['home'];
		$rel        = $is_network ? '' : ' rel="home"';

		$this->items[] = sprintf(
			'<a href="%s"%s>%s</a>',
			esc_url( user_trailingslashit( home_url() ) ),
			$rel,
			esc_html( $label )
		);
	}

	private function add_blog_items(): void {
		$post = get_queried_object();
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		if ( $post->post_parent > 0 ) {
			$this->add_page_parents( $post->post_parent );
		}

		$title = get_the_title( $post->ID );

		$this->items[] = is_paged()
			? sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $post->ID ) ), esc_html( $title ) )
			: ( ( $title && $this->args['show_title'] ) ? $title : $this->labels['home'] );
	}

	private function add_singular_items(): void {
		$post = get_queried_object();
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		if ( $post->post_parent > 0 ) {
			$this->add_page_parents( $post->post_parent );
		} else {
			$this->add_post_hierarchy( $post->ID );
		}

		// Append taxonomy terms for post types that have a mapping.
		$taxonomy = $this->post_taxonomy[ $post->post_type ] ?? '';
		if ( '' !== $taxonomy ) {
			$this->add_post_terms( $post->ID, $taxonomy );
		}

		$is_paged =
			get_query_var( 'page' ) > 1
			|| ( get_option( 'page_comments' ) && absint( get_query_var( 'cpage' ) ) > 1 );

		$this->items[] = $is_paged
			? sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $post->ID ) ), esc_html( (string) single_post_title( '', false ) ) )
			: ( $this->args['show_title'] ? (string) single_post_title( '', false ) : $this->labels['post'] );
	}

	private function add_page_parents( int $parent_id ): void {
		$parents = array();

		while ( $parent_id > 0 ) {
			$parent = get_post( $parent_id );

			if ( ! $parent instanceof \WP_Post ) {
				break;
			}

			// Skip if this parent is the static front page.
			if (
				'page' === $parent->post_type
				&& 'page' === get_option( 'show_on_front' )
				&& (int) get_option( 'page_on_front' ) === $parent_id
			) {
				break;
			}

			$parents[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( get_permalink( $parent_id ) ),
				esc_html( get_the_title( $parent_id ) )
			);

			if ( $parent->post_parent <= 0 ) {
				break;
			}

			$parent_id = $parent->post_parent;
		}

		// After climbing the page tree, look for CPT archive links.
		if ( $parent_id > 0 ) {
			$this->add_post_hierarchy( $parent_id );
		}

		if ( ! empty( $parents ) ) {
			array_push( $this->items, ...array_reverse( $parents ) );
		}
	}

	private function add_post_hierarchy( int $post_id ): void {
		$post_type = (string) get_post_type( $post_id );

		if ( 'post' === $post_type ) {
			// Use the configured taxonomy (defaults to 'category').
			$taxonomy = $this->post_taxonomy['post'] ?? 'category';
			$this->add_post_terms( $post_id, $taxonomy );
			return;
		}

		$pto = get_post_type_object( $post_type );
		if ( $pto && ! empty( $pto->has_archive ) ) {
			$this->items[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( (string) get_post_type_archive_link( $post_type ) ),
				esc_html( (string) post_type_archive_title( '', false ) )
			);
		}
	}

	private function add_post_terms( int $post_id, string $taxonomy ): void {
		$terms = get_the_terms( $post_id, $taxonomy );

		if ( ! $terms || is_wp_error( $terms ) ) {
			return;
		}

		// Use the term with the highest term_id as the canonical term.
		$terms = wp_list_sort( $terms, 'term_id' );
		$term  = array_pop( $terms );

		if ( $term instanceof \WP_Term ) {
			$this->add_term_parents( $term->term_id, $taxonomy );
		}
	}

	private function add_term_parents( int $term_id, string $taxonomy ): void {
		$parents = array();

		while ( $term_id > 0 ) {
			$term = get_term( $term_id, $taxonomy );

			if ( ! $term instanceof \WP_Term ) {
				break;
			}

			$parents[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( (string) get_term_link( $term, $taxonomy ) ),
				esc_html( $term->name )
			);

			if ( $term->parent <= 0 ) {
				break;
			}

			$term_id = $term->parent;
		}

		if ( ! empty( $parents ) ) {
			array_push( $this->items, ...array_reverse( $parents ) );
		}
	}

	private function add_archive_items(): void {
		if ( is_date() ) {
			$this->add_date_archive_items();
		} elseif ( is_author() ) {
			$this->add_author_archive_items();
		} elseif ( is_post_type_archive() ) {
			$this->add_post_type_archive_items();
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$this->add_term_archive_items();
		} else {
			$this->items[] = $this->labels['archives'];
		}
	}

	private function add_term_archive_items(): void {
		$term = get_queried_object();
		if ( ! $term instanceof \WP_Term ) {
			return;
		}

		// Link to posts page or CPT archive.
		$taxonomy_obj = get_taxonomy( $term->taxonomy );
		$post_type    = ( $taxonomy_obj && ! empty( $taxonomy_obj->object_type ) )
			? $taxonomy_obj->object_type[0]
			: '';

		if ( $post_type && post_type_exists( $post_type ) ) {
			if ( 'post' === $post_type ) {
				$posts_page_id = (int) get_option( 'page_for_posts' );
				if ( 'posts' !== get_option( 'show_on_front' ) && $posts_page_id > 0 ) {
					$this->items[] = sprintf(
						'<a href="%s">%s</a>',
						esc_url( get_permalink( $posts_page_id ) ),
						esc_html( get_the_title( $posts_page_id ) )
					);
				}
			} else {
				$pto = get_post_type_object( $post_type );
				if ( $pto && ! empty( $pto->has_archive ) ) {
					$this->items[] = sprintf(
						'<a href="%s">%s</a>',
						esc_url( (string) get_post_type_archive_link( $post_type ) ),
						esc_html( (string) post_type_archive_title( '', false ) )
					);
				}
			}
		}

		// Climb ancestor terms for hierarchical taxonomies.
		if ( is_taxonomy_hierarchical( $term->taxonomy ) && $term->parent > 0 ) {
			$this->add_term_parents( $term->parent, $term->taxonomy );
		}

		$this->items[] = is_paged()
			? sprintf(
				'<a href="%s">%s</a>',
				esc_url( (string) get_term_link( $term, $term->taxonomy ) ),
				esc_html( (string) single_term_title( '', false ) )
			)
			: ( $this->args['show_title'] ? (string) single_term_title( '', false ) : '' );
	}

	private function add_search_items(): void {
		// FIX: escape the search query before embedding in the label string.
		$query        = esc_html( get_search_query() );
		$search_label = sprintf( $this->labels['search'], $query );

		$this->items[] = is_paged()
			? sprintf( '<a href="%s">%s</a>', esc_url( get_search_link() ), $search_label )
			: $search_label;
	}

	private function add_author_archive_items(): void {
		$posts_page_id = (int) get_option( 'page_for_posts' );
		if ( $posts_page_id > 0 ) {
			$this->items[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( get_permalink( $posts_page_id ) ),
				esc_html( $this->labels['archives'] )
			);
		}

		$this->items[] = sprintf(
			$this->labels['archive_author'],
			esc_html( (string) get_the_author_meta( 'display_name', (int) get_query_var( 'author' ) ) )
		);
	}

	private function add_post_type_archive_items(): void {
		$post_type = get_query_var( 'post_type' );

		// 'post_type' query var can be an array when multiple types are queried.
		if ( is_array( $post_type ) ) {
			$post_type = reset( $post_type );
		}

		if ( ! $post_type ) {
			return;
		}

		$pto = get_post_type_object( (string) $post_type );
		if ( ! $pto ) {
			return;
		}

		if ( 'post' !== $post_type ) {
			$this->items[] = esc_html( $pto->labels->name );
		}

		$this->items[] = is_paged()
			? sprintf(
				'<a href="%s">%s</a>',
				esc_url( (string) get_post_type_archive_link( $pto->name ) ),
				esc_html( (string) post_type_archive_title( '', false ) )
			)
			: (string) post_type_archive_title( '', false );
	}

	private function add_date_archive_items(): void {
		$year_fmt  = esc_html_x( 'Y', 'yearly archives date format', 'lerm' );
		$month_fmt = esc_html_x( 'F', 'monthly archives date format', 'lerm' );
		$day_fmt   = esc_html_x( 'j', 'daily archives date format', 'lerm' );

		if ( is_day() ) {
			$this->items[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( get_year_link( (int) get_the_time( 'Y' ) ) ),
				esc_html( (string) get_the_time( $year_fmt ) )
			);
			$this->items[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( get_month_link( (int) get_the_time( 'Y' ), (int) get_the_time( 'm' ) ) ),
				esc_html( (string) get_the_time( $month_fmt ) )
			);
			$this->items[] = esc_html( (string) get_the_time( $day_fmt ) );
		} elseif ( is_month() ) {
			$this->items[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( get_year_link( (int) get_the_time( 'Y' ) ) ),
				esc_html( (string) get_the_time( $year_fmt ) )
			);
			$this->items[] = esc_html( (string) get_the_time( $month_fmt ) );
		} elseif ( is_year() ) {
			$this->items[] = esc_html( (string) get_the_time( $year_fmt ) );
		}
	}

	private function add_paged_items(): void {
		if ( is_singular() ) {
			$page_num    = absint( get_query_var( 'page' ) );
			$comment_num = absint( get_query_var( 'cpage' ) );

			if ( $page_num > 1 ) {
				$this->items[] = sprintf( $this->labels['paged'], number_format_i18n( $page_num ) );
			} elseif ( get_option( 'page_comments' ) && $comment_num > 0 ) {
				$this->items[] = sprintf( $this->labels['paged_comments'], number_format_i18n( $comment_num ) );
			}
		} elseif ( is_paged() ) {
			$paged = absint( get_query_var( 'paged' ) );
			if ( $paged > 1 ) {
				$this->items[] = sprintf( $this->labels['paged'], number_format_i18n( $paged ) );
			}
		}
	}
}
