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
use Affibloom\Domain\Fields\FieldUsageRepository;
use Affibloom\Domain\Fields\ProductField;
use Affibloom\Domain\Fields\ProductFields;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\ProductPage;
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
	 * One field, or null when there is none (a field's page, F26 R12).
	 *
	 * @param int $id The field's ID.
	 */
	public function get( int $id ): ?Field {
		return $this->library->load()->find( $id );
	}

	/**
	 * How many products have this field, in any status: what the Delete This Field button and rule go by.
	 *
	 * @param int $id The field's ID.
	 */
	public function usage_of( int $id ): int {
		return $this->usage->usage()->of( $id );
	}

	/**
	 * One page of the Links tab (F26 R20, D7): the published products that have the field, by name, ten a page. The total is
	 * what the tab's number shows.
	 *
	 * @param int $id   The field's ID.
	 * @param int $page From 1; a page past the end is the last page.
	 */
	public function products_using( int $id, int $page ): ProductPage {
		$ids   = $this->usage->published_with( $id );
		$pages = max( 1, (int) ceil( count( $ids ) / self::PER_PAGE ) );
		$page  = min( max( 1, $page ), $pages );

		$items = array();
		foreach ( array_slice( $ids, ( $page - 1 ) * self::PER_PAGE, self::PER_PAGE ) as $product ) {
			$found = $this->products->get( $product );
			if ( null !== $found ) {
				$items[] = $found;
			}
		}

		return new ProductPage( $items, count( $ids ), $page, self::PER_PAGE );
	}

	/**
	 * Saves a field's Details (F26 R15-R17). A built-in field takes only its title; the kind and the description in the
	 * request are ignored (D4). A created field takes all three, and a changed kind touches no product's value.
	 *
	 * @param int    $id          The field's ID.
	 * @param string $name        The title, 1-80 characters.
	 * @param string $type        One of FieldType's choosable values; ignored for a built-in field.
	 * @param string $description The description; ignored for a built-in field.
	 * @throws FieldNotFound When there is no such field.
	 * @throws InvalidField  When the title, the kind or the description breaks its rule.
	 */
	public function edit( int $id, string $name, string $type, string $description ): Field {
		$library = $this->library->load();
		$field   = $library->find( $id );
		if ( null === $field ) {
			throw new FieldNotFound( 'No such field.' );
		}

		if ( $field->built_in ) {
			$library = $library->rename( $id, $name );
		} else {
			$kind = FieldType::tryFrom( $type );
			if ( null === $kind || ! $kind->is_choosable() ) {
				throw new InvalidField( 'type', 'invalid_type' );
			}
			$library = $library->update( $id, $name, $kind, $description );
		}
		$this->library->save( $library );

		return $library->find( $id ) ?? $field;
	}

	/**
	 * Deletes a field, never a built-in one and never one a product has (F26 R18, D5): the server checks at this moment,
	 * where Lasso's checks nothing. No stored value is left behind to be orphaned.
	 *
	 * @param int $id The field's ID.
	 * @throws FieldNotFound When there is no such field.
	 * @throws FieldBuiltIn  When it is one of the built-in fields.
	 * @throws FieldInUse    When a product has it.
	 */
	public function delete( int $id ): void {
		$library = $this->library->load();
		$field   = $library->find( $id );
		if ( null === $field ) {
			throw new FieldNotFound( 'No such field.' );
		}
		if ( $field->built_in ) {
			throw new FieldBuiltIn( 'A built-in field cannot be deleted.' );
		}
		if ( $this->usage->usage()->of( $id ) > 0 ) {
			throw new FieldInUse( 'A product has this field.' );
		}

		$this->library->save( $library->delete( $id ) );
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
