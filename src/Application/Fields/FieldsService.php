<?php
/**
 * Custom field use cases (F1, Task 14C).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Fields;

use Affibloom\Domain\Fields\Field;
use Affibloom\Domain\Fields\FieldLibrary;
use Affibloom\Domain\Fields\FieldPage;
use Affibloom\Domain\Fields\FieldRepository;
use Affibloom\Domain\Fields\FieldType;
use Affibloom\Domain\Fields\FieldUsage;
use Affibloom\Domain\Fields\FieldUsageRepository;
use Affibloom\Domain\Fields\ProductField;
use Affibloom\Domain\Fields\ProductFields;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\ProductRepository;

/**
 * Custom field use cases: the library (search, create) and a product's fields (add, save, remove), each stored as
 * it happens, as Lasso's product page does (F1 requirements B6, D3). A product that is not a published product gives
 * null, which the routes answer with 404.
 */
final class FieldsService {

	/** Lasso's library dialog shows ten fields a page (view-js.php: limit 10 outside the tables screen). */
	public const PER_PAGE = 10;

	/**
	 * Builds the service.
	 *
	 * @param FieldRepository      $library  The field definitions.
	 * @param ProductRepository    $products The products, for their fields.
	 * @param FieldUsageRepository $usage    How many products have each field (the Fields list's Usage column).
	 */
	public function __construct(
		private FieldRepository $library,
		private ProductRepository $products,
		private FieldUsageRepository $usage,
	) {}

	/**
	 * The whole library, for drawing a product's fields with their names and types.
	 */
	public function all(): FieldLibrary {
		return $this->library->load();
	}

	/**
	 * One page of the "Add from library" tab: the fields a product can take, which leaves out the six that comparison
	 * tables fill (F26 R24, D2).
	 *
	 * @param string $search What was typed.
	 * @param int    $page   From 1.
	 */
	public function library( string $search, int $page ): FieldPage {
		return $this->library->load()->search( $search, $page, self::PER_PAGE, true );
	}

	/**
	 * One page of the Fields list (F26 R7-R9): every field, searched by name, with how many products have each.
	 *
	 * @param string $search What was typed in "Search Fields".
	 * @param int    $page   From 1.
	 */
	public function list( string $search, int $page ): FieldListing {
		return new FieldListing( $this->library->load()->search( $search, $page, self::PER_PAGE ), $this->usage->usage() );
	}

	/**
	 * "Create Field": a new definition, stored. It is not added to any product: Lasso's dialog then shows the library,
	 * where the editor adds it.
	 *
	 * @param string $name        1-80 characters.
	 * @param string $type        One of FieldType's values.
	 * @param string $description Its description.
	 * @throws InvalidField When the name or the type breaks its rule.
	 */
	public function create( string $name, string $type, string $description ): Field {
		$kind = FieldType::tryFrom( $type );
		if ( null === $kind ) {
			throw new InvalidField( 'type', 'invalid_type' );
		}
		[ $library, $field ] = $this->library->load()->create( $name, $kind, $description );
		$this->library->save( $library );

		return $field;
	}

	/**
	 * A product's fields, or null when it is not a published product.
	 *
	 * @param int $product The product's ID.
	 */
	public function fields( int $product ): ?ProductFields {
		return $this->products->fields( $product );
	}

	/**
	 * Adds a field to a product (nothing changes if it has it already).
	 *
	 * @param int $product The product's ID.
	 * @param int $field   The field's ID.
	 * @throws InvalidField When there is no such field.
	 */
	public function attach( int $product, int $field ): ?ProductFields {
		$fields = $this->products->fields( $product );
		if ( null === $fields ) {
			return null;
		}
		$definition = $this->library->load()->find( $field );
		if ( null === $definition ) {
			throw new InvalidField( 'field', 'not_found' );
		}

		return $this->store( $product, $fields->attach( $definition ) );
	}

	/**
	 * Saves a product's fields as the editor left them: order, values and toggles.
	 *
	 * @param int                $product The product's ID.
	 * @param list<ProductField> $rows    The rows, in the order shown.
	 */
	public function update( int $product, array $rows ): ?ProductFields {
		$fields = $this->products->fields( $product );

		return null === $fields ? null : $this->store( $product, $fields->update( $this->library->load(), $rows ) );
	}

	/**
	 * Removes a field from a product.
	 *
	 * @param int $product The product's ID.
	 * @param int $field   The field's ID.
	 */
	public function detach( int $product, int $field ): ?ProductFields {
		$fields = $this->products->fields( $product );

		return null === $fields ? null : $this->store( $product, $fields->detach( $field ) );
	}

	/**
	 * Stores a product's fields; returns them.
	 *
	 * @param int           $product The product's ID.
	 * @param ProductFields $fields  Its fields.
	 */
	private function store( int $product, ProductFields $fields ): ProductFields {
		$this->products->save_fields( $product, $fields );

		return $fields;
	}
}
