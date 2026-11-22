<?php
namespace Affibloom\Tests\Unit\Fakes;

use Affibloom\Domain\LinkIndex\LinkIndexStats;
use Affibloom\Domain\LinkIndex\LinkLocationRepository;

/**
 * The link index as a list of what was asked of it ("pin 7", "move 7 9"), for the product tests (Task 35I).
 */
final class InMemoryLinkLocations implements LinkLocationRepository {

	/** @var list<string> */
	public array $calls = array();

	/** @var array<int, list<\Affibloom\Domain\LinkIndex\LinkLocation>> The rows stored, by host. */
	public array $rows = array();

	/** @var array<string, list<int>> The members of each group display's `display_ref` ("group:5" => products). */
	public array $groups = array();

	public function replace_host( int $host_id, array $rows ): void {
		$this->calls[] = 'replace ' . $host_id;
		$this->rows[ $host_id ] = $rows;
	}

	public function delete_host( int $host_id ): void {
		$this->calls[] = 'delete ' . $host_id;
	}

	public function pin_product( int $product_id ): void {
		$this->calls[] = 'pin ' . $product_id;
	}

	public function move_product( int $from, int $to ): void {
		$this->calls[] = 'move ' . $from . ' ' . $to;
	}

	public function host_ids(): array {
		return array();
	}

	/** What stats() answers (Task AM5's status tests). */
	public ?LinkIndexStats $figures = null;

	public function stats(): LinkIndexStats {
		return $this->figures ?? new LinkIndexStats();
	}

	public function host_links( int $host_id ): array {
		$links = array();
		foreach ( $this->rows[ $host_id ] ?? array() as $index => $row ) {
			$product = $row->assigned_product_id > 0 ? $row->assigned_product_id : $row->product_id;
			$links[ $row->key() ] = array(
				'id'       => $index + 1,
				'product'  => $row->use_original ? 0 : $product,
				'original' => $row->use_original ? $row->original_url : '',
			);
		}
		return $links;
	}

	/** @var list<array{int, string, string, int, int}> product_locations()' arguments, in order. */
	public array $asked = array();

	public function product_locations( int $product_id, string $search, string $order, int $page, int $per_page ): \Affibloom\Domain\LinkIndex\LocationPage {
		$this->asked[] = array( $product_id, $search, $order, $page, $per_page );
		return new \Affibloom\Domain\LinkIndex\LocationPage( array(), 0, $page, $per_page );
	}

	/** Rows are numbered from 1 across the hosts, in the order they were stored. */
	public function find( int $id ): ?\Affibloom\Domain\LinkIndex\LinkLocation {
		$all = array_merge( array(), ...array_values( $this->rows ) );
		return $all[ $id - 1 ] ?? null;
	}

	/** @var list<array{asin: string, country: string, domain: string, tag: string}> untied_amazon()'s answer. */
	public array $amazon = array();

	/** @var list<string> untied_short_links()'s answer. */
	public array $short = array();

	/** @var list<array{string, int}> tie_url()'s calls. */
	public array $tied_urls = array();

	public function amazon_queue_size(): int {
		return count( $this->amazon ) + count( $this->short );
	}

	public function untied_short_links( int $limit ): array {
		return array_slice( $this->short, 0, $limit );
	}

	public function tie_url( string $url, int $product_id ): int {
		$this->tied_urls[] = array( $url, $product_id );
		return 1;
	}

	/** @var list<array{string, string, int}> tie_amazon()'s calls. */
	public array $tied = array();

	public function untied_amazon( int $limit ): array {
		return array_slice( $this->amazon, 0, $limit );
	}

	public function tie_amazon( string $asin, string $country, int $product_id ): int {
		$this->tied[] = array( $asin, $country, $product_id );
		return 1;
	}

	/** @var array<int, bool> set_ignored()'s writes, by row. */
	public array $ignored = array();

	public function set_ignored( int $id, bool $ignored ): bool {
		if ( null === $this->find( $id ) ) {
			return false;
		}
		$this->ignored[ $id ] = $ignored;
		return true;
	}

	/** @var array<int, int> set_assigned()'s writes, by row. */
	public array $assigned = array();

	public function set_assigned( int $id, int $product_id ): bool {
		if ( null === $this->find( $id ) ) {
			return false;
		}
		$this->assigned[ $id ] = $product_id;
		return true;
	}

	/** @var list<int> set_original()'s rows, in order. */
	public array $originals = array();

	public function set_original( int $id ): bool {
		$row = $this->find( $id );
		if ( null === $row || $row->assigned_product_id > 0 || $row->original_url === $row->url ) {
			return false;
		}
		$this->originals[] = $id;
		return true;
	}

	/** @var list<array{int, string, string}> set_original_url()'s writes, in order. */
	public array $original_urls = array();

	public function set_original_url( int $host_id, string $url, string $original ): int {
		$changed = 0;
		foreach ( $this->rows[ $host_id ] ?? array() as $index => $row ) {
			if ( $row->url_md5 === \Affibloom\Domain\LinkIndex\LocationKey::md5( $url ) && $row->original_url === $row->url ) {
				$this->rows[ $host_id ][ $index ] = $row->keeping( $original, $row->is_ignored, $row->assigned_product_id );
				++$changed;
			}
		}
		$this->original_urls[] = array( $host_id, $url, $original );
		return $changed;
	}

	public function counts_for( array $product_ids ): array {
		$counts = array();
		foreach ( $this->rows as $rows ) {
			foreach ( $rows as $row ) {
				$shown = $row->assigned_product_id > 0 ? array( $row->assigned_product_id ) : ( $row->product_id > 0 ? array( $row->product_id ) : array() );
				if ( null !== $row->display_ref ) {
					$shown = array_merge( $shown, $this->groups[ $row->display_ref ] ?? array() );
				}
				foreach ( array_intersect( array_unique( $shown ), $product_ids ) as $id ) {
					$counts[ $id ] = ( $counts[ $id ] ?? 0 ) + 1;
				}
			}
		}
		return $counts;
	}
}
