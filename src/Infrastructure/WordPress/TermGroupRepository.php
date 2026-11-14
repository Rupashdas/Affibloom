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
	 * @param string $name The name.
	 */
	public function name_taken( string $name ): bool {
		return null !== term_exists( $name, GroupTaxonomy::NAME );
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
