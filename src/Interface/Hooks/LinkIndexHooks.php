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
 * post, content and status per request (edge case 1: the editor's meta box save repeats it); its status decides whether it keeps rows
 * (a trashed post has none). A deleted post loses its rows. Revisions and autosaves are not hosts.
 */
final class LinkIndexHooks implements Module {

	/**
	 * The posts indexed in this request, with their status and content then.
	 *
	 * @var array<int, string>
	 */
	private array $done = array();

	/**
	 * Builds the hooks.
	 *
	 * @param LinkIndexer            $indexer Indexes one post.
	 * @param LinkLocationRepository $rows    The index, for a deleted post's rows.
	 * @param HostQuery              $hosts   The indexed post types.
	 */
	public function __construct( private LinkIndexer $indexer, private LinkLocationRepository $rows, private HostQuery $hosts ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'save_post', array( $this, 'saved' ), 20, 2 );
		add_action( 'trashed_post', array( $this, 'changed' ) );
		add_action( 'untrashed_post', array( $this, 'changed' ) );
		add_action( 'deleted_post', array( $this, 'deleted' ) );
		// Builders and recipe plugins (Task 35E): a recipe or a Create card is read through the posts that show it.
		add_action( 'save_post_wprm_recipe', array( $this, 'recipe' ), 20 );
		add_action( 'mv_create_card_post_publish', array( $this, 'card' ), 10 );
	}

	/**
	 * A saved WP Recipe Maker recipe: the post it belongs to (`wprm_parent_post_id`) is indexed again.
	 *
	 * @param int $recipe_id The recipe.
	 */
	public function recipe( int $recipe_id ): void {
		$parent = (int) get_post_meta( $recipe_id, 'wprm_parent_post_id', true );
		if ( $parent > 0 ) {
			$this->changed( $parent );
		}
	}

	/**
	 * A published Mediavine Create card: the posts it is shown in (its `associated_posts`, Lasso's
	 * get_mediavine_create_post_ids_to_scan) are indexed again.
	 *
	 * @param mixed $creation The card, as Create passes it.
	 */
	public function card( mixed $creation ): void {
		$card  = is_object( $creation ) ? get_object_vars( $creation ) : ( is_array( $creation ) ? $creation : array() );
		$posts = $card['associated_posts'] ?? array();
		$posts = is_string( $posts ) ? json_decode( $posts, true ) : $posts;
		foreach ( is_array( $posts ) ? $posts : array() as $post_id ) {
			if ( is_numeric( $post_id ) ) {
				$this->changed( (int) $post_id );
			}
		}
	}

	/**
	 * A saved post (`save_post`).
	 *
	 * @param int     $post_id The post.
	 * @param WP_Post $post    The post as saved.
	 */
	public function saved( int $post_id, WP_Post $post ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! in_array( $post->post_type, $this->hosts->types(), true ) ) {
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
		if ( $post instanceof WP_Post && in_array( $post->post_type, $this->hosts->types(), true ) ) {
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
	 * Indexes the post unless it was indexed in this request as it is now: the same content and status (the editor's
	 * meta box save repeats the save); a second save that changed either is indexed again.
	 *
	 * @param int $post_id The post.
	 */
	private function index( int $post_id ): void {
		$as_now = md5( get_post_status( $post_id ) . '|' . get_post_field( 'post_content', $post_id ) );
		if ( ( $this->done[ $post_id ] ?? '' ) === $as_now ) {
			return;
		}
		$this->done[ $post_id ] = $as_now;
		$this->indexer->index( $post_id );
	}
}
