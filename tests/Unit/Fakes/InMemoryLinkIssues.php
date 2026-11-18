<?php
namespace Affibloom\Tests\Unit\Fakes;

use Affibloom\Domain\LinkHealth\CheckTarget;
use Affibloom\Domain\LinkHealth\LinkIssue;
use Affibloom\Domain\LinkHealth\LinkIssueRepository;
use DateTimeImmutable;

/**
 * Link issues kept in memory. $destinations lists the products the HTTP check covers (published, not Amazon) with
 * their stored link, and $amazon the published Amazon products with an ASIN, in the order due() returns them.
 */
final class InMemoryLinkIssues implements LinkIssueRepository {

	/** @var array<int, LinkIssue> */
	public array $rows = array();

	/** @var array<int, string> */
	public array $destinations = array();

	/** @var array<int, array{string, string, string}> Product ID => link, ASIN, store. */
	public array $amazon = array();

	public int $orphans_deleted = 0;

	public ?bool $locations_only = null;

	/**
	 * @param array<int, string> $destinations Product ID => stored link.
	 */
	public function __construct( array $destinations = array() ) {
		$this->destinations = $destinations;
	}

	public function get( int $product_id ): ?LinkIssue {
		return $this->rows[ $product_id ] ?? null;
	}

	public function save( LinkIssue $issue ): void {
		$this->rows[ $issue->product_id ] = $issue;
	}

	public function due( bool $locations_only, bool $amazon ): array {
		$this->locations_only = $locations_only;
		return array_merge( array_keys( $this->destinations ), $amazon ? array_keys( $this->amazon ) : array() );
	}

	public function confirmations( DateTimeImmutable $checked_before, bool $locations_only, int $limit ): array {
		$ids = array();
		foreach ( $this->suspects() as $id => $row ) {
			if ( $row->last_checked_at <= $checked_before ) {
				$ids[] = $id;
			}
		}
		return array_slice( $ids, 0, $limit );
	}

	public function next_confirmation( bool $locations_only ): ?DateTimeImmutable {
		$times = array_map( static fn( LinkIssue $row ): DateTimeImmutable => $row->last_checked_at, $this->suspects() );
		return array() === $times ? null : min( $times );
	}

	public function destination( int $product_id, bool $amazon ): ?CheckTarget {
		if ( isset( $this->destinations[ $product_id ] ) ) {
			return new CheckTarget( $this->destinations[ $product_id ] );
		}
		if ( $amazon && isset( $this->amazon[ $product_id ] ) ) {
			return new CheckTarget( ...$this->amazon[ $product_id ] );
		}
		return null;
	}

	public function with_asin( string $asin, string $country ): array {
		return array_keys( array_filter( $this->amazon, static fn( array $row ): bool => $row[1] === $asin && $row[2] === $country ) );
	}

	public function delete_orphans(): int {
		++$this->orphans_deleted;
		return 0;
	}

	/**
	 * Rows of products still to be checked after one failure.
	 *
	 * @return array<int, LinkIssue>
	 */
	private function suspects(): array {
		return array_filter( $this->rows, fn( LinkIssue $row ): bool => $row->failures > 0 && isset( $this->destinations[ $row->product_id ] ) );
	}
}
