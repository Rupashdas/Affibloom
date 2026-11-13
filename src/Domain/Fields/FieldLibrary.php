<?php
/**
 * Every custom field a product can take (F1, Task 14C).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Fields;

use Affibloom\Domain\InvalidField;

/**
 * Every custom field a product can take: Lasso's built-in ones (IDs 1-3) and the ones created here (100 and up).
 * Lasso's 99989-99995 rows are comparison-table columns, not product fields; they belong to the Tables area.
 */
final class FieldLibrary {

	/** The first ID of a field created here, clear of Lasso's built-in 1-3. */
	private const FIRST_ID = 100;

	/**
	 * The fields created here, in creation order.
	 *
	 * @var list<Field>
	 */
	private array $created;

	/**
	 * Builds the library.
	 *
	 * @param list<Field> $created The fields created here.
	 */
	private function __construct( array $created ) {
		$this->created = $created;
	}

	/**
	 * Lasso's built-in fields, with this site's names (Lasso 369 called the rating "Our Rating"; the live site's row says
	 * "Primary Rating").
	 *
	 * @return list<Field>
	 */
	public static function built_in(): array {
		return array(
			new Field( 1, 'Primary Rating', FieldType::Rating, 'Editor Rating. Position in display is fixed.', true ),
			new Field( 2, 'Pros', FieldType::Textarea, 'A list of all of the Pros. One per line.', true ),
			new Field( 3, 'Cons', FieldType::Textarea, 'A list of all of the Cons. One per line.', true ),
		);
	}

	/**
	 * The library from storage (the fields created here). A row that is not a valid field, or that claims a built-in
	 * ID, is skipped.
	 *
	 * @param array<mixed> $stored Rows as to_array() writes them.
	 */
	public static function from_array( array $stored ): self {
		$created = array();
		foreach ( $stored as $row ) {
			$type = is_array( $row ) && is_string( $row['type'] ?? null ) ? FieldType::tryFrom( $row['type'] ) : null;
			$id   = is_array( $row ) && is_int( $row['id'] ?? null ) ? $row['id'] : 0;
			if ( null === $type || $id < self::FIRST_ID ) {
				continue;
			}
			try {
				$created[] = new Field( $id, is_string( $row['name'] ?? null ) ? $row['name'] : '', $type, is_string( $row['description'] ?? null ) ? $row['description'] : '' );
			} catch ( InvalidField ) {
				continue;
			}
		}

		return new self( $created );
	}

	/**
	 * The fields created here, as stored.
	 *
	 * @return list<array{id: int, name: string, type: string, description: string}>
	 */
	public function to_array(): array {
		return array_map(
			static fn( Field $field ): array => array(
				'id'          => $field->id,
				'name'        => $field->name,
				'type'        => $field->type->value,
				'description' => $field->description,
			),
			$this->created
		);
	}

	/**
	 * Every field: the built-in ones first, then the created ones in creation order.
	 *
	 * @return list<Field>
	 */
	public function all(): array {
		return array_merge( self::built_in(), $this->created );
	}

	/**
	 * The field with this ID, or null.
	 *
	 * @param int $id The ID.
	 */
	public function find( int $id ): ?Field {
		foreach ( $this->all() as $field ) {
			if ( $field->id === $id ) {
				return $field;
			}
		}
		return null;
	}

	/**
	 * A library with a new field, and that field (Lasso's "Create Field").
	 *
	 * @param string    $name        1-80 characters, trimmed.
	 * @param FieldType $type        The kind of value.
	 * @param string    $description Its description.
	 * @return array{0: self, 1: Field}
	 * @throws InvalidField When the name breaks its rule.
	 */
	public function create( string $name, FieldType $type, string $description ): array {
		$next  = array_reduce( $this->created, static fn( int $max, Field $field ): int => max( $max, $field->id + 1 ), self::FIRST_ID );
		$field = new Field( $next, trim( $name ), $type, trim( $description ) );

		return array( new self( array_merge( $this->created, array( $field ) ) ), $field );
	}

	/**
	 * One page of the library dialog: the fields whose name holds the search, in any case, as Lasso's `field_name LIKE`.
	 *
	 * @param string $search   What was typed; '' for every field.
	 * @param int    $page     From 1.
	 * @param int    $per_page Fields a page.
	 */
	public function search( string $search, int $page, int $per_page ): FieldPage {
		$search  = trim( $search );
		$matches = array_values(
			array_filter(
				$this->all(),
				static fn( Field $field ): bool => '' === $search || false !== mb_stripos( $field->name, $search )
			)
		);
		$pages   = max( 1, (int) ceil( count( $matches ) / max( 1, $per_page ) ) );
		$page    = min( max( 1, $page ), $pages );

		return new FieldPage( array_slice( $matches, ( $page - 1 ) * $per_page, $per_page ), count( $matches ), $page, $pages );
	}
}
