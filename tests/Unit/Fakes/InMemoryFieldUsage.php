<?php
namespace Affibloom\Tests\Unit\Fakes;

use Affibloom\Domain\Fields\FieldUsage;
use Affibloom\Domain\Fields\FieldUsageRepository;

/** Field usage counted from the in-memory products' field rows. */
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
}
