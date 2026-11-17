<?php
/**
 * Keeps a post's link rows in step with the post.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Hooks;

use Affibloom\Application\LinkIndex\LinkIndexer;
use Affibloom\Domain\LinkIndex\LinkLocationRepository;
use Affibloom\Infrastructure\WordPress\HostQuery;
use Affibloom\Module;
use WP_Post;

/**
 * F21 D5, D6 (R10-R12): a post of an indexed type is indexed inline when it is saved, trashed or restored, once per
 * post per request (edge case 1: the editor's meta box save comes second); its status decides whether it keeps rows
 * (a trashed post has none). A deleted post loses its rows. Revisions and autosaves are not hosts.
 */
final class LinkIndexHooks implements Module {

	/**
	 * The posts indexed in this request.
	 *
	 * @var array<int, true>
	 */
	private array $done = array();

	/**
	 * Builds the hooks.
	 *
	 * @param LinkIndexer            $indexer Indexes one post.
	 * @param LinkLocationRepository $rows    The index, for a deleted post's rows.
	 */
	public function __construct( private LinkIndexer $indexer, private LinkLocationRepository $rows ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'save_post', array( $this, 'saved' ), 20, 2 );
		add_action( 'trashed_post', array( $this, 'changed' ) );
		add_action( 'untrashed_post', array( $this, 'changed' ) );
		add_action( 'deleted_post', array( $this, 'deleted' ) );
	}

	/**
	 * A saved post (`save_post`).
	 *
	 * @param int     $post_id The post.
	 * @param WP_Post $post    The post as saved.
	 */
	public function saved( int $post_id, WP_Post $post ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! in_array( $post->post_type, HostQuery::TYPES, true ) ) {
			return;
		}
		$this->index( $post_id );
	}

	/**
	 * A trashed or restored post.
	 *
	 * @param int $post_id The post.
	 */
	public function changed( int $post_id ): void {
		$post = get_post( $post_id );
		if ( $post instanceof WP_Post && in_array( $post->post_type, HostQuery::TYPES, true ) ) {
			$this->index( $post_id );
		}
	}

	/**
	 * A deleted post loses its rows, even when it was indexed earlier in this request. They are removed directly: at
	 * `deleted_post` WordPress's cache still returns the post, so indexing it would write its rows again.
	 *
	 * @param int $post_id The post.
	 */
	public function deleted( int $post_id ): void {
		$this->rows->delete_host( $post_id );
	}

	/**
	 * Indexes the post unless it was indexed in this request.
	 *
	 * @param int $post_id The post.
	 */
	private function index( int $post_id ): void {
		if ( isset( $this->done[ $post_id ] ) ) {
			return;
		}
		$this->done[ $post_id ] = true;
		$this->indexer->index( $post_id );
	}
}
