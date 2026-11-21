<?php
/**
 * Comparison tables as posts.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Domain\Table\Table;
use Affibloom\Domain\Table\TablePage;
use Affibloom\Domain\Table\TableProduct;
use Affibloom\Domain\Table\TableRepository;
use Affibloom\Domain\Table\TableSearch;
use Affibloom\Domain\Table\TableStyle;
use RuntimeException;
use WP_Post;

/**
 * Tables stored as `affibloom_table` posts, the whole table in one meta (F25 D1; Task T1). A site has a handful (four
 * here, the largest five products by four fields), so the list reads them all, oldest first, and searches in PHP
 * (TableSearch, R8).
 */
final class PostTypeTableRepository implements TableRepository {

	/**
	 * One page of tables, oldest first.
	 *
	 * @param SearchTerm $term     The search.
	 * @param int        $page     The page, from 1.
	 * @param int        $per_page Tables per page.
	 */
	public function page( SearchTerm $term, int $page, int $per_page ): TablePage {
		$tables = $this->all();
		if ( ! $term->is_empty() ) {
			$tables = array_values(
				array_filter(
					$tables,
					static fn( Table $table ): bool => TableSearch::matches(
						$term,
						$table->name,
						array_map( static fn( int $id ): string => wp_specialchars_decode( get_the_title( $id ), ENT_QUOTES ), $table->product_ids() )
					)
				)
			);
		}
		$shell = new TablePage( array(), count( $tables ), $page, $per_page );

		return new TablePage( array_slice( $tables, $shell->offset(), $per_page ), count( $tables ), $shell->current(), $per_page );
	}

	/**
	 * How many tables there are.
	 */
	public function count(): int {
		$counts = wp_count_posts( TablePostType::NAME );

		return isset( $counts->publish ) ? (int) $counts->publish : 0;
	}

	/**
	 * One table, or null.
	 *
	 * @param int $id The table.
	 */
	public function get( int $id ): ?Table {
		$post = get_post( $id );

		return $post instanceof WP_Post && TablePostType::NAME === $post->post_type && 'publish' === $post->post_status ? self::hydrate( $post ) : null;
	}

	/**
	 * Every table, oldest first.
	 *
	 * @return list<Table>
	 */
	private function all(): array {
		$posts = get_posts(
			array(
				'post_type'      => TablePostType::NAME,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);

		return array_values( array_map( array( self::class, 'hydrate' ), $posts ) );
	}

	/**
	 * A table from its post.
	 *
	 * @param WP_Post $post The post.
	 */
	private static function hydrate( WP_Post $post ): Table {
		$data     = get_post_meta( $post->ID, TablePostType::META, true );
		$data     = is_array( $data ) ? $data : array();
		$products = array();
		foreach ( (array) ( $data['products'] ?? array() ) as $entry ) {
			if ( is_array( $entry ) && (int) ( $entry['id'] ?? 0 ) > 0 ) {
				$products[] = new TableProduct( (int) $entry['id'], (string) ( $entry['badge'] ?? '' ) );
			}
		}

		return new Table(
			$post->ID,
			$post->post_title,
			TableStyle::from_stored( (string) ( $data['style'] ?? '' ) ),
			(string) ( $data['theme'] ?? 'Cactus' ),
			$products,
			array_values( (array) ( $data['groups'] ?? array() ) ),
			(array) ( $data['values'] ?? array() ),
			(bool) ( $data['show_headers'] ?? true ),
			(bool) ( $data['show_field_name'] ?? false ),
			(int) ( $data['revision'] ?? 0 )
		);
	}

	/**
	 * Stores a table whole: its name as the post's title, the rest in one meta (D1).
	 *
	 * @param Table $table The table.
	 * @return Table The table as stored, with its ID.
	 * @throws RuntimeException When WordPress refuses the post.
	 */
	public function save( Table $table ): Table {
		$post = array(
			'post_type'   => TablePostType::NAME,
			'post_status' => 'publish',
			'post_title'  => $table->name,
		);
		if ( null !== $table->id ) {
			$post['ID'] = $table->id;
		}
		$id = null === $table->id ? wp_insert_post( wp_slash( $post ), true ) : wp_update_post( wp_slash( $post ), true );
		if ( is_wp_error( $id ) ) {
			throw new RuntimeException( 'Could not save the table.' );
		}
		update_post_meta(
			(int) $id,
			TablePostType::META,
			wp_slash(
				array(
					'style'           => $table->style->value,
					'theme'           => $table->theme,
					'products'        => array_map(
						static fn( TableProduct $product ): array => array(
							'id'    => $product->id,
							'badge' => $product->badge,
						),
						$table->products
					),
					'groups'          => $table->groups,
					'values'          => $table->values,
					'show_headers'    => $table->show_headers,
					'show_field_name' => $table->show_field_name,
					'revision'        => $table->revision,
				)
			)
		);

		return $table->with( array( 'id' => (int) $id ) );
	}
}
