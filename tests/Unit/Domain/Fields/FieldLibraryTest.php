<?php
namespace Affibloom\Tests\Unit\Domain\Fields;

use Affibloom\Domain\Fields\Field;
use Affibloom\Domain\Fields\FieldLibrary;
use Affibloom\Domain\Fields\FieldType;
use Affibloom\Domain\InvalidField;
use PHPUnit\Framework\TestCase;

final class FieldLibraryTest extends TestCase {
	/** Lasso's built-in fields keep Lasso's IDs, so migrated mappings keep their meaning (F1 analysis). */
	public function test_the_built_in_fields_have_lassos_ids(): void {
		$library = FieldLibrary::from_array( array() );

		self::assertSame( 'Primary Rating', $library->find( 1 )?->name );
		self::assertSame( FieldType::Rating, $library->find( 1 )?->type );
		self::assertSame( 'Pros', $library->find( 2 )?->name );
		self::assertSame( FieldType::Textarea, $library->find( 3 )?->type );
		self::assertTrue( $library->find( 1 )?->built_in );
	}

	public function test_a_created_field_gets_the_next_id_from_100(): void {
		$library = FieldLibrary::from_array( array() );

		[ $library, $first ]  = $library->create( '  Sizes ', FieldType::BulletedList, 'One size per line.' );
		[ $library, $second ] = $library->create( 'Weight', FieldType::Text, '' );

		self::assertSame( 100, $first->id );
		self::assertSame( 'Sizes', $first->name );
		self::assertSame( 101, $second->id );
		self::assertSame( $first, $library->find( 100 ) );
		self::assertEquals( $library, FieldLibrary::from_array( $library->to_array() ) );
	}

	/**
	 * Lasso's create refuses an empty title; a name longer than Lasso's column (80) is refused too.
	 *
	 * @dataProvider bad_names
	 */
	public function test_a_field_needs_a_title_of_1_to_80_characters( string $name ): void {
		$this->expectException( InvalidField::class );
		FieldLibrary::from_array( array() )->create( $name, FieldType::Text, '' );
	}

	/** @return array<string, array{string}> */
	public static function bad_names(): array {
		return array(
			'empty'    => array( '  ' ),
			'too long' => array( str_repeat( 'a', 81 ) ),
		);
	}

	/** The library dialog: Lasso's name search (LIKE, any case), ten a page, built-ins first. */
	public function test_the_library_is_searched_by_name_ten_a_page(): void {
		$library = FieldLibrary::from_array( array() );
		for ( $i = 1; $i <= 12; $i++ ) {
			[ $library ] = $library->create( 'Size ' . $i, FieldType::Text, '' );
		}

		$first = $library->search( '', 1, 10 );
		self::assertSame( 15, $first->total );
		self::assertSame( array( 1, 2, 3 ), array_slice( array_map( static fn( Field $f ): int => $f->id, $first->items ), 0, 3 ) );
		self::assertCount( 10, $first->items );
		self::assertCount( 5, $library->search( '', 2, 10 )->items );

		self::assertSame( 12, $library->search( 'SIZE', 1, 10 )->total );
		self::assertSame( array( 'Pros' ), array_map( static fn( Field $f ): string => $f->name, $library->search( 'pro', 1, 10 )->items ) );
	}

	public function test_stored_rows_that_are_not_fields_are_skipped(): void {
		$library = FieldLibrary::from_array(
			array(
				array( 'id' => 100, 'name' => 'Sizes', 'type' => 'bulleted_list', 'description' => '' ),
				array( 'id' => 101, 'name' => 'Odd', 'type' => 'no-such-type', 'description' => '' ),
				array( 'id' => 2, 'name' => 'Not a built-in', 'type' => 'text', 'description' => '' ),
				'junk',
			)
		);

		self::assertSame( 'Sizes', $library->find( 100 )?->name );
		self::assertNull( $library->find( 101 ) );
		self::assertSame( 'Pros', $library->find( 2 )?->name );
	}
}
