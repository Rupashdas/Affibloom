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

	public function stats(): LinkIndexStats {
		return new LinkIndexStats();
	}

	public function host_links( int $host_id ): array {
		$links = array();
		foreach ( $this->rows[ $host_id ] ?? array() as $index => $row ) {
			$product = $row->assigned_product_id > 0 ? $row->assigned_product_id : $row->product_id;
			$links[ $row->key() ] = array(
				'id'      => $index + 1,
				'product' => $product,
			);
		}
		return $links;
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
