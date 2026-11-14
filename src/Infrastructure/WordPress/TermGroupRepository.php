<?php
/**
 * Groups kept as terms of the group taxonomy.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Group\Group;
use Affibloom\Domain\Group\GroupPage;
use Affibloom\Domain\Group\GroupRepository;
use Affibloom\Domain\Product\SearchTerm;

/**
 * Groups kept as terms of the group taxonomy (F23 D1). The list's count joins the published products of the group, as
 * Lasso's `get_groups_query` does: a trashed or retired product is not counted (F23 edge case 3).
 */
final class TermGroupRepository implements GroupRepository {

	/** Term meta holding a group's product IDs in display order (F23 D1). */
	private const ORDER_META = '_affibloom_order';

	/**
	 * One page of groups, newest first, with the published products in each.
	 *
	 * @param SearchTerm $term     What to look for in the name.
	 * @param int        $page     Page number, from 1.
	 * @param int        $per_page Groups per page.
	 */
	public function page( SearchTerm $term, int $page, int $per_page ): GroupPage {
		global $wpdb;

		// Names only: Lasso's `t.name LIKE` with the spaces as wildcards (R2).
		$where = '';
		$args  = array( GroupTaxonomy::NAME );
		if ( ! $term->is_empty() ) {
			$where  = 'AND t.name LIKE %s';
			$args[] = $term->like( static fn( string $value ): string => $wpdb->esc_like( $value ) );
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- the count of members per group is a join WP_Term_Query cannot make (Lasso's get_groups_query does the same); $where holds only the fixed text above and its own %s, $wpdb->terms and its siblings are core table names, and the values go in as one array that the sniff cannot count.
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->terms} AS t INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id AND tt.taxonomy = %s WHERE 1=1 {$where}",
				$args
			)
		);

		// A page past the end is the last page, as the Dashboard's is.
		$page = max( 1, min( $page, (int) ceil( $total / max( 1, $per_page ) ) ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.term_id, t.name, t.slug, tt.description, COUNT(p.ID) AS members
				FROM {$wpdb->terms} AS t
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id AND tt.taxonomy = %s
				LEFT JOIN {$wpdb->term_relationships} AS tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
				LEFT JOIN {$wpdb->posts} AS p ON tr.object_id = p.ID AND p.post_type = %s AND p.post_status = 'publish'
				WHERE 1=1 {$where}
				GROUP BY t.term_id, t.name, t.slug, tt.description
				ORDER BY t.term_id DESC
				LIMIT %d OFFSET %d",
				array_merge( array( GroupTaxonomy::NAME, ProductPostType::NAME ), array_slice( $args, 1 ), array( $per_page, ( $page - 1 ) * $per_page ) )
			)
		);
		// phpcs:enable

		$groups = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$groups[] = new Group( (int) $row->term_id, (string) $row->name, (string) $row->slug, (string) $row->description, (int) $row->members );
		}

		return new GroupPage( $groups, $total, $page, $per_page );
	}

	/**
	 * Whether a group already has this name. term_exists() compares the slug the name would make first and then the
	 * name itself, which is the comparison wp_insert_term() refuses on, so the two agree.
	 *
	 * @param string $name   The name.
	 * @param int    $except A group to leave out (the one being renamed); 0 for none.
	 */
	public function name_taken( string $name, int $except = 0 ): bool {
		$found = term_exists( $name, GroupTaxonomy::NAME );
		if ( ! is_array( $found ) ) {
			return false;
		}

		return (int) $found['term_id'] !== $except;
	}

	/**
	 * The ID of the group with this exact name, or null.
	 *
	 * @param string $name The name.
	 */
	public function find_id_by_name( string $name ): ?int {
		$term = get_term_by( 'name', $name, GroupTaxonomy::NAME );

		return $term instanceof \WP_Term ? $term->term_id : null;
	}

	/**
	 * Every group by name, without counts.
	 *
	 * @return list<Group>
	 */
	public function all(): array {
		$terms  = get_terms(
			array(
				'taxonomy'   => GroupTaxonomy::NAME,
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);
		$groups = array();
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			$groups[] = new Group( $term->term_id, $term->name, $term->slug, $term->description, 0 );
		}

		return $groups;
	}

	/**
	 * Changes a group's name and description with wp_update_term(), which leaves the slug alone when none is given.
	 *
	 * @param int    $id          The group's ID.
	 * @param string $name        The name.
	 * @param string $description The description.
	 */
	public function update( int $id, string $name, string $description ): ?Group {
		$result = wp_update_term(
			$id,
			GroupTaxonomy::NAME,
			array(
				'name'        => $name,
				'description' => $description,
			)
		);

		return is_wp_error( $result ) ? null : $this->find( $id );
	}

	/**
	 * Deletes the term; wp_delete_term() removes its relationships and its meta, and the products stay.
	 *
	 * @param int $id The group's ID.
	 */
	public function delete( int $id ): bool {
		return true === wp_delete_term( $id, GroupTaxonomy::NAME );
	}

	/**
	 * The published products in a group.
	 *
	 * @param int $id The group's ID.
	 * @return list<int>
	 */
	public function member_ids( int $id ): array {
		$query = new \WP_Query(
			array(
				'post_type'      => ProductPostType::NAME,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => -1, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- one group's members, the list has no pages (R17).
				'no_found_rows'  => true,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- the group's own relationships.
					array(
						'taxonomy'         => GroupTaxonomy::NAME,
						'field'            => 'term_id',
						'terms'            => array( $id ),
						'include_children' => false,
					),
				),
			)
		);

		return array_values( array_map( 'intval', $query->posts ) );
	}

	/**
	 * The stored order of a group: term meta (D1).
	 *
	 * @param int $id The group's ID.
	 * @return list<int>
	 */
	public function saved_order( int $id ): array {
		$stored = get_term_meta( $id, self::ORDER_META, true );

		return is_array( $stored ) ? array_values( array_map( 'intval', $stored ) ) : array();
	}

	/**
	 * Stores a group's order.
	 *
	 * @param int       $id    The group's ID.
	 * @param list<int> $order Product IDs in display order.
	 */
	public function save_order( int $id, array $order ): void {
		update_term_meta( $id, self::ORDER_META, $order );
	}

	/**
	 * The groups of each product, by name, with one query for the whole page.
	 *
	 * @param list<int> $products Product IDs.
	 * @return array<int, list<Group>>
	 */
	public function groups_of( array $products ): array {
		$by_product = array_fill_keys( $products, array() );
		$terms      = wp_get_object_terms(
			$products,
			GroupTaxonomy::NAME,
			array(
				'fields'  => 'all_with_object_id',
				'orderby' => 'name',
			)
		);
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			$by_product[ (int) ( get_object_vars( $term )['object_id'] ?? 0 ) ][] = new Group( $term->term_id, $term->name, $term->slug, $term->description, 0 );
		}

		return $by_product;
	}

	/**
	 * Makes a product's groups exactly these.
	 *
	 * @param int       $product The product.
	 * @param list<int> $groups  Group IDs.
	 */
	public function set_groups( int $product, array $groups ): void {
		wp_set_object_terms( $product, $groups, GroupTaxonomy::NAME, false );
	}

	/**
	 * Puts products into groups and leaves the groups they have.
	 *
	 * @param list<int> $products Product IDs.
	 * @param list<int> $groups   Group IDs.
	 */
	public function add_to_groups( array $products, array $groups ): void {
		foreach ( $products as $product ) {
			wp_set_object_terms( $product, $groups, GroupTaxonomy::NAME, true );
		}
	}

	/**
	 * Takes products out of one group.
	 *
	 * @param int       $id       The group's ID.
	 * @param list<int> $products Product IDs.
	 */
	public function remove_from_group( int $id, array $products ): void {
		foreach ( $products as $product ) {
			wp_remove_object_terms( $product, array( $id ), GroupTaxonomy::NAME );
		}
	}

	/**
	 * Makes a group.
	 *
	 * @param string $name        The name.
	 * @param string $description The description.
	 * @return Group|null The new group, or null when WordPress refused it.
	 */
	public function create( string $name, string $description ): ?Group {
		$made = wp_insert_term( $name, GroupTaxonomy::NAME, array( 'description' => $description ) );
		if ( is_wp_error( $made ) ) {
			return null;
		}

		return $this->find( (int) $made['term_id'] );
	}

	/**
	 * One group by its ID, or null when there is none in the group taxonomy.
	 *
	 * @param int $id The group's ID.
	 */
	public function find( int $id ): ?Group {
		$term = get_term( $id, GroupTaxonomy::NAME );
		if ( ! $term instanceof \WP_Term ) {
			return null;
		}

		return new Group( $term->term_id, $term->name, $term->slug, $term->description, 0 );
	}
}
