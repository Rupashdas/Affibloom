<?php
namespace Affibloom\Tests\Unit\Fakes;

use Affibloom\Domain\Monetize\DetectedLink;
use Affibloom\Domain\Monetize\DetectedLinkRepository;

/**
 * The detected affiliate links in memory (Tasks C2B, C2C): one row per URL, as the table's unique key keeps them.
 */
final class InMemoryDetectedLinks implements DetectedLinkRepository {

	/** @var array<int, array{url: string, network: string, product: int}> By row ID, from 1. */
	public array $rows = array();

	/** @var list<int> Products that are no longer published. */
	public array $gone = array();

	public function record( string $url, DetectedLink $link, int $product_id ): void {
		foreach ( $this->rows as $id => $row ) {
			if ( $row['url'] === $url ) {
				if ( 0 === $row['product'] ) {
					$this->rows[ $id ]['product'] = $product_id;
				}
				return;
			}
		}
		$this->rows[ count( $this->rows ) + 1 ] = array(
			'url'     => $url,
			'network' => $link->network->integration,
			'product' => $product_id,
		);
	}

	public function without_product( int $limit ): array {
		$found = array();
		foreach ( $this->rows as $id => $row ) {
			if ( 0 === $row['product'] || in_array( $row['product'], $this->gone, true ) ) {
				$found[ $id ] = $row['url'];
			}
		}
		return array_slice( $found, 0, $limit, true );
	}

	public function set_product( int $id, int $product_id ): void {
		$this->rows[ $id ]['product'] = $product_id;
	}

	public function product_for( string $url ): int {
		foreach ( $this->rows as $row ) {
			if ( $row['url'] === $url ) {
				return $row['product'];
			}
		}
		return 0;
	}
}
