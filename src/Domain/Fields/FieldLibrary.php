<?php
/**
 * Every custom field (F1 Task 14C; F26 Task FL1).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Fields;

use Affibloom\Domain\InvalidField;

/**
 * Every custom field: Lasso's nine built-in ones, which live in code with Lasso's IDs, and the ones created here
 * (100 and up). A built-in field's title can be changed (F26 R2, R14); the rename is kept beside the fields created
 * here. Six built-ins (99989-99994) are comparison-table columns: they are listed on the Fields screens but are not
 * offered to a product (F26 D2).
 */
final class FieldLibrary {

	/** The first ID of a field created here, clear of Lasso's built-in ones. */
	private const FIRST_ID = 100;

	/**
	 * Lasso's built-in fields (`models/class-fields.php` 16-24 and the rows `Fields::create_table` writes): ID, name, kind,
	 * description, whether tables fill it. Field 1's name is this site's, "Primary Rating" (Lasso 369 says "Our Rating").
	 */
	private const BUILT_IN = array(
		array( 1, 'Primary Rating', FieldType::Rating, 'Editor Rating. Position in display is fixed.', false ),
		array( 2, 'Pros', FieldType::Textarea, 'A list of all of the Pros. One per line.', false ),
		array( 3, 'Cons', FieldType::Textarea, 'A list of all of the Cons. One per line.', false ),
		array( 99989, 'Description', FieldType::Editor, 'Add rich text to describe a product.', true ),
		array( 99990, 'Product name', FieldType::ProductName, 'Product name.', true ),
		array( 99991, 'Product Image', FieldType::Image, 'Feature image.', true ),
		array( 99992, 'Primary Button', FieldType::Button, 'Button uses the Primary URL.', true ),
		array( 99993, 'Secondary Button', FieldType::Button, 'Button uses the Secondary URL.', true ),
		array( 99994, 'Price', FieldType::Label, 'Prices automatically update every 24 hours with integrations like Amazon.', true ),
	);

	/**
	 * The fields created here, in creation order.
	 *
	 * @var list<Field>
	 */
	private array $created;

	/**
	 * The titles given to built-in fields, by ID. Only a title that differs from the default is kept.
	 *
	 * @var array<int, string>
	 */
	private array $names;

	/**
	 * Builds the library.
	 *
	 * @param list<Field>        $created The fields created here.
	 * @param array<int, string> $names   The built-in fields' own titles, by ID.
	 */
	private function __construct( array $created, array $names ) {
		$this->created = $created;
		$this->names   = $names;
	}

	/**
	 * Lasso's nine built-in fields, under their own titles where one was given.
	 *
	 * @param array<int, string> $names The titles given to built-in fields, by ID.
	 * @return list<Field>
	 */
	public static function built_in( array $names = array() ): array {
		$fields = array();
		foreach ( self::BUILT_IN as [ $id, $name, $type, $description, $table_only ] ) {
			$fields[] = new Field( $id, $names[ $id ] ?? $name, $type, $description, true, $table_only );
		}

		return $fields;
	}

	/**
	 * The library from storage. A row that is not a valid field, or that claims a built-in ID, is skipped; so is a
	 * title for a field that is not built in, or that is not a valid title.
	 *
	 * @param array<mixed> $stored Rows as to_array() writes them (the fields created here).
	 * @param array<mixed> $names  The built-in fields' own titles, by ID, as names() writes them.
	 */
	public static function from_array( array $stored, array $names = array() ): self {
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

		$kept = array();
		foreach ( self::BUILT_IN as [ $id, $default ] ) {
			$name = $names[ $id ] ?? null;
			if ( is_string( $name ) && trim( $name ) !== $default && '' !== trim( $name ) && mb_strlen( trim( $name ) ) <= Field::NAME_MAX ) {
				$kept[ $id ] = trim( $name );
			}
		}

		return new self( $created, $kept );
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
	 * The titles given to built-in fields, as stored.
	 *
	 * @return array<int, string>
	 */
	public function names(): array {
		return $this->names;
	}

	/**
	 * Every field, by ID: the nine built-in ones, then the created ones in creation order (Lasso's list has no ORDER
	 * BY, which comes to the same).
	 *
	 * @return list<Field>
	 */
	public function all(): array {
		return array_merge( self::built_in( $this->names ), $this->created );
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
	 * @param string    $description Its description, at most 1,000 characters.
	 * @return array{0: self, 1: Field}
	 * @throws InvalidField When the name or the description breaks its rule.
	 */
	public function create( string $name, FieldType $type, string $description ): array {
		$next  = array_reduce( $this->created, static fn( int $max, Field $field ): int => max( $max, $field->id + 1 ), self::FIRST_ID );
		$field = new Field( $next, trim( $name ), $type, trim( $description ) );

		return array( new self( array_merge( $this->created, array( $field ) ), $this->names ), $field );
	}

	/**
	 * One page of fields whose name holds the words typed, in any case, in that order: Lasso's `field_name LIKE %…%` with
	 * each space a wildcard (F26 R8, D11), so "pri rat" finds Primary Rating. `%` and `_` are ordinary characters here.
	 *
	 * @param string $search        What was typed; '' for every field.
	 * @param int    $page          From 1.
	 * @param int    $per_page      Fields a page.
	 * @param bool   $products_only Leave out the table-only built-ins: the product page's library (R24).
	 */
	public function search( string $search, int $page, int $per_page, bool $products_only = false ): FieldPage {
		$words   = preg_split( '/\s+/', trim( $search ), -1, PREG_SPLIT_NO_EMPTY );
		$words   = false === $words ? array() : $words;
		$matches = array_values(
			array_filter(
				$this->all(),
				static fn( Field $field ): bool => ( ! $products_only || ! $field->table_only ) && self::holds( $field->name, $words )
			)
		);
		$pages   = max( 1, (int) ceil( count( $matches ) / max( 1, $per_page ) ) );
		$page    = min( max( 1, $page ), $pages );

		return new FieldPage( array_slice( $matches, ( $page - 1 ) * $per_page, $per_page ), count( $matches ), $page, $pages, $per_page );
	}

	/**
	 * Whether a name holds every word, in order, in any case.
	 *
	 * @param string       $name  The field's name.
	 * @param list<string> $words The words typed.
	 */
	private static function holds( string $name, array $words ): bool {
		$from = 0;
		foreach ( $words as $word ) {
			$at = mb_stripos( $name, $word, $from );
			if ( false === $at ) {
				return false;
			}
			$from = $at + mb_strlen( $word );
		}

		return true;
	}
}
