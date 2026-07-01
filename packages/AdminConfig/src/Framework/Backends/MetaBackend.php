<?php // phpcs:disable WordPress.Files.FileName
/**
 * Configuration-driven meta storage backend.
 *
 * Replaces the four near-identical CommentMetaBackend, PostMetaBackend,
 * TermMetaBackend and UserMetaBackend with a single class parameterised
 * by the WordPress meta function pair.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Backends;

use Lerm\AdminConfig\Framework\Contracts\StorageBackend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MetaBackend implements StorageBackend {

	/**
	 * Map of meta type prefixes to their WordPress function triples.
	 *
	 * Each entry: [ read_fn, write_fn, delete_fn, key_prefix ]
	 *
	 * @var array<string, array{read: string, write: string, delete: string, prefix: string}>
	 */
	private const TYPE_MAP = array(
		'comment' => array(
			'read'   => 'get_comment_meta',
			'write'  => 'update_comment_meta',
			'delete' => 'delete_comment_meta',
			'prefix' => 'comment',
		),
		'post'    => array(
			'read'   => 'get_post_meta',
			'write'  => 'update_post_meta',
			'delete' => 'delete_post_meta',
			'prefix' => 'post',
		),
		'term'    => array(
			'read'   => 'get_term_meta',
			'write'  => 'update_term_meta',
			'delete' => 'delete_term_meta',
			'prefix' => 'term',
		),
		'user'    => array(
			'read'   => 'get_user_meta',
			'write'  => 'update_user_meta',
			'delete' => 'delete_user_meta',
			'prefix' => 'user',
		),
	);

	private int $object_id;
	private string $meta_key;
	private string $type;
	private array $config;

	/**
	 * @param int    $object_id The object whose meta this backend reads/writes.
	 * @param string $meta_key The single meta key used to store the payload.
	 * @param string $type      Meta type: 'comment', 'post', 'term', or 'user'.
	 */
	public function __construct( int $object_id, string $meta_key, string $type = 'post' ) {
		$this->object_id = $object_id;
		$this->meta_key  = sanitize_key( $meta_key );
		$this->type      = sanitize_key( $type );

		if ( ! isset( self::TYPE_MAP[ $this->type ] ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: invalid meta type */
					esc_html__( 'Invalid meta type "%s" provided. Falling back to "post".', 'lerm-admin-config' ),
					esc_html( $this->type )
				),
				'0.4.0'
			);
			$this->type = 'post';
		}

		$this->config = self::TYPE_MAP[ $this->type ];
	}

	public function read(): array {
		$data = ( $this->config['read'] )( $this->object_id, $this->meta_key, true );
		return is_array( $data ) ? $data : array();
	}

	public function write( array $data ): bool {
		$result = ( $this->config['write'] )( $this->object_id, $this->meta_key, $data );
		return false !== $result;
	}

	public function key(): string {
		return $this->config['prefix'] . '_' . $this->object_id . '_' . $this->meta_key;
	}

	public function delete(): bool {
		return ( $this->config['delete'] )( $this->object_id, $this->meta_key );
	}
}
