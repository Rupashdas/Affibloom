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

	public function replace_host( int $host_id, array $rows ): void {
		$this->calls[] = 'replace ' . $host_id;
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
}
