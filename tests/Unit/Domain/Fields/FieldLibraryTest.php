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

		// The product page's library leaves out the six table built-ins (F26 D2): 3 + 12 fields.
		$first = $library->search( '', 1, 10, true );
		self::assertSame( 15, $first->total );
		self::assertSame( array( 1, 2, 3 ), array_slice( array_map( static fn( Field $f ): int => $f->id, $first->items ), 0, 3 ) );
		self::assertCount( 10, $first->items );
		self::assertCount( 5, $library->search( '', 2, 10, true )->items );

		self::assertSame( 12, $library->search( 'SIZE', 1, 10, true )->total );
		self::assertSame( array( 'Pros' ), array_map( static fn( Field $f ): string => $f->name, $library->search( 'pro', 1, 10, true )->items ) );
	}

	/** Lasso's nine built-ins, with their IDs and kinds, and the six that tables fill (F26 R2, D2). */
	public function test_there_are_nine_built_in_fields_and_six_are_table_only(): void {
		$library = FieldLibrary::from_array( array() );
		$all     = $library->all();

		self::assertSame( array( 1, 2, 3, 99989, 99990, 99991, 99992, 99993, 99994 ), array_map( static fn( Field $f ): int => $f->id, $all ) );
		$table = array_values( array_map( static fn( Field $f ): int => $f->id, array_filter( $all, static fn( Field $f ): bool => $f->table_only ) ) );
		self::assertSame( array( 99989, 99990, 99991, 99992, 99993, 99994 ), $table );
		self::assertSame( FieldType::Label, $library->find( 99994 )?->type );
		self::assertSame( 'Prices automatically update every 24 hours with integrations like Amazon.', $library->find( 99994 )?->description );
		self::assertFalse( FieldType::Label->is_choosable() );
		self::assertCount( 7, FieldType::choosable() );
		self::assertSame( FieldType::Text, FieldType::choosable()[0] );
	}

	/** A built-in's own title is read, kept beside the created fields, and an invalid one is ignored (F26 R2, edge case 7). */
	public function test_a_built_in_fields_title_can_be_renamed(): void {
		$library = FieldLibrary::from_array( array(), array( 1 => 'Our Rating', 2 => '  Good things ', 3 => '', 99994 => 'Cost', 7 => 'Not a field', 99990 => 'Product name' ) );

		self::assertSame( 'Our Rating', $library->find( 1 )?->name );
		self::assertSame( 'Good things', $library->find( 2 )?->name );
		self::assertSame( 'Cons', $library->find( 3 )?->name );
		self::assertSame( 'Cost', $library->find( 99994 )?->name );
		// A title equal to the default is not a rename; an ID that is not built in has no title to give.
		self::assertSame(
			array(
				1     => 'Our Rating',
				2     => 'Good things',
				99994 => 'Cost',
			),
			$library->names()
		);
		self::assertTrue( $library->find( 99994 )?->table_only );
	}

	/** "pri rat" finds Primary Rating: each space is a wildcard, the words in order, in any case (F26 R8, D11). */
	public function test_a_search_matches_the_words_in_order_with_spaces_as_wildcards(): void {
		$library = FieldLibrary::from_array( array() );

		self::assertSame( array( 'Primary Rating' ), array_map( static fn( Field $f ): string => $f->name, $library->search( 'pri rat', 1, 10 )->items ) );
		self::assertSame( 0, $library->search( 'rat pri', 1, 10 )->total );
		self::assertSame( 2, $library->search( 'BUTTON', 1, 10 )->total );
		self::assertSame( 9, $library->search( '   ', 1, 10 )->total );
		// "%" is the character it is.
		self::assertSame( 0, $library->search( 'pri%', 1, 10 )->total );
	}

	/** A built-in is renamed, never retyped; a rename back to the default is no rename; the title's rules apply (F26 D4). */
	public function test_renaming_a_built_in_changes_only_its_title(): void {
		$library = FieldLibrary::from_array( array() )->rename( 99990, '  Title ' );

		self::assertSame( 'Title', $library->find( 99990 )?->name );
		self::assertSame( FieldType::ProductName, $library->find( 99990 )?->type );
		self::assertSame( 'Product name.', $library->find( 99990 )?->description );
		self::assertSame( array( 99990 => 'Title' ), $library->names() );
		self::assertSame( array(), $library->rename( 99990, 'Product name' )->names() );
		// An ID that is not built in is left alone.
		self::assertSame( array( 99990 => 'Title' ), $library->rename( 4, 'Nope' )->names() );

		$this->expectException( InvalidField::class );
		$library->rename( 1, '   ' );
	}

	public function test_a_title_over_80_characters_is_refused_on_rename(): void {
		$this->expectException( InvalidField::class );
		FieldLibrary::from_array( array() )->rename( 1, str_repeat( 'a', 81 ) );
	}

	public function test_a_created_field_is_changed_and_deleted_but_a_built_in_is_not(): void {
		[ $library ] = FieldLibrary::from_array( array() )->create( 'Sizes', FieldType::Text, 'Before.' );

		$changed = $library->update( 100, ' Weight ', FieldType::Number, ' After. ' );
		self::assertSame( 'Weight', $changed->find( 100 )?->name );
		self::assertSame( FieldType::Number, $changed->find( 100 )?->type );
		self::assertSame( 'After.', $changed->find( 100 )?->description );
		// A built-in is not changed through update().
		self::assertSame( 'Pros', $changed->update( 2, 'Other', FieldType::Text, 'x' )->find( 2 )?->name );

		self::assertNull( $changed->delete( 100 )->find( 100 ) );
		self::assertSame( 'Primary Rating', $changed->delete( 1 )->find( 1 )?->name );
		// Deleting the last field leaves the next ID free to be reused only from 100 on.
		self::assertSame( 100, $changed->delete( 100 )->create( 'Again', FieldType::Text, '' )[1]->id );
	}

	public function test_a_description_over_1000_characters_is_refused(): void {
		$this->expectException( InvalidField::class );
		FieldLibrary::from_array( array() )->create( 'Sizes', FieldType::Text, str_repeat( 'a', 1001 ) );
	}

	public function test_a_page_knows_its_range(): void {
		$library = FieldLibrary::from_array( array() );

		$second = $library->search( '', 2, 4 );
		self::assertSame( 5, $second->first() );
		self::assertSame( 8, $second->last() );
		self::assertSame( 9, $library->search( '', 9, 4 )->last() );
		self::assertSame( 0, $library->search( 'zzz', 1, 4 )->first() );
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
