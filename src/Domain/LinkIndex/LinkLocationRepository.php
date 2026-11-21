<?php
/**
 * Where the link index is stored.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkIndex;

/**
 * The link index's rows (F21 D1).
 */
interface LinkLocationRepository {

	/**
	 * Replaces a host's rows with these, in one transaction. A row whose key (LocationKey) was stored before keeps its
	 * first URL, its dismissal and its assignment (D10).
	 *
	 * @param int                $host_id The host post.
	 * @param list<LinkLocation> $rows    Its rows now.
	 */
	public function replace_host( int $host_id, array $rows ): void;

	/**
	 * Removes a host's rows (a post that is no longer indexed).
	 *
	 * @param int $host_id The host post.
	 */
	public function delete_host( int $host_id ): void;

	/**
	 * Pins a product's rows to it (`assigned_product_id`), so they stay tied after its link changes while the posts still
	 * hold the old link (F21 R61, D16; Task 35I).
	 *
	 * @param int $product_id The product.
	 */
	public function pin_product( int $product_id ): void;

	/**
	 * Moves a retired product's rows to the product it points to, pinned there (F21 R60, F1 D14; Task 35I).
	 *
	 * @param int $from The retired product.
	 * @param int $to   The product it points to.
	 */
	public function move_product( int $from, int $to ): void;

	/**
	 * Every host post that has rows (the daily pass, Task 35F).
	 *
	 * @return list<int>
	 */
	public function host_ids(): array;

	/**
	 * The whole index's figures.
	 */
	public function stats(): LinkIndexStats;

	/**
	 * A host's links by their key (LocationKey::of()), with the row's ID and the product it is tied to (an editor's
	 * assignment first; 0 when none): render time counts every link of a place, tied or not (F21 D13; Tasks 35H, 35E).
	 *
	 * @param int $host_id The post.
	 * @return array<string, array{id: int, product: int}>
	 */
	public function host_links( int $host_id ): array;

	/**
	 * How many places each product is used in (F12 L1, D20; Tasks 31D, 36A): its rows (an editor's assignment first)
	 * plus the group displays whose group holds it.
	 *
	 * @param list<int> $product_ids The products.
	 * @return array<int, int> By product ID; a product used nowhere is left out.
	 */
	public function counts_for( array $product_ids ): array;

	/**
	 * One page of the posts that use a product, each with its places (F12 L5-L7, D22, D23; Task 36B): the rows that
	 * L1 counts, hosts ordered by title, their rows by ID; a search keeps the rows whose host title, text or URL holds
	 * it.
	 *
	 * @param int    $product_id The product.
	 * @param string $search     The search, '' for none.
	 * @param string $order      'asc' or 'desc', by host title.
	 * @param int    $page       The page, from 1.
	 * @param int    $per_page   Hosts per page.
	 */
	public function product_locations( int $product_id, string $search, string $order, int $page, int $per_page ): LocationPage;

	/**
	 * One row by its ID (the Locations tab's preview, Task 36C); null when it is gone.
	 *
	 * @param int $id The row's ID.
	 */
	public function find( int $id ): ?LinkLocation;

	/**
	 * Dismisses a row on the Opportunities screens, or brings it back (F22 R44-R49, D10, D11; Task O7). A rescan keeps
	 * the flag while the row's identity stays (replace_host()).
	 *
	 * @param int  $id      The row's ID.
	 * @param bool $ignored True to dismiss, false to undo.
	 * @return bool False when the row is gone.
	 */
	public function set_ignored( int $id, bool $ignored ): bool;

	/**
	 * Ties a row to a product at render time, or clears that (F22 N1-A, R64-R72; Task O10): `assigned_product_id`, kept
	 * by the row's identity on a rescan (replace_host()).
	 *
	 * @param int $id         The row's ID.
	 * @param int $product_id The product; 0 clears the assignment.
	 * @return bool False when the row is gone.
	 */
	public function set_assigned( int $id, int $product_id ): bool;
}
