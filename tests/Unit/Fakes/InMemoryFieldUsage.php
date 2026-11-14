<?php
namespace Affibloom\Tests\Unit\Fakes;

use Affibloom\Domain\Fields\FieldUsage;
use Affibloom\Domain\Fields\FieldUsageRepository;

/** Field usage counted from the in-memory products' field rows; every saved product is published. */
final class InMemoryFieldUsage implements FieldUsageRepository {
	public function __construct( private InMemoryProductRepository $products ) {}

	public function usage(): FieldUsage {
		$counts = array();
		foreach ( $this->products->fields as $fields ) {
			foreach ( $fields->ids() as $id ) {
				$counts[ $id ] = ( $counts[ $id ] ?? 0 ) + 1;
			}
		}

		return new FieldUsage( $counts );
	}

	public function published_with( int $field ): array {
		$ids = array();
		foreach ( $this->products->fields as $product => $fields ) {
			if ( in_array( $field, $fields->ids(), true ) ) {
				$ids[] = $product;
			}
		}

		return $ids;
	}
}
