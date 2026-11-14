<?php
namespace Affibloom\Tests\Unit\Infrastructure;

use Affibloom\Domain\Fields\FieldType;
use Affibloom\Infrastructure\WordPress\OptionFieldRepository;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

final class OptionFieldRepositoryTest extends TestCase {
	/** Task 14C wrote the fields alone, as a list: it is read as a library with no renamed built-ins (F26 edge case 7). */
	public function test_the_flat_list_of_task_14c_is_still_read(): void {
		Functions\when( 'get_option' )->justReturn( array( array( 'id' => 100, 'name' => 'Sizes', 'type' => 'bulleted_list', 'description' => '' ) ) );

		$library = ( new OptionFieldRepository() )->load();

		self::assertSame( 'Sizes', $library->find( 100 )?->name );
		self::assertSame( array(), $library->names() );
	}

	public function test_the_fields_and_names_shape_is_read_and_written(): void {
		Functions\when( 'get_option' )->justReturn( array( 'fields' => array( array( 'id' => 100, 'name' => 'Sizes', 'type' => 'text', 'description' => '' ) ), 'names' => array( 1 => 'Our Rating' ) ) );
		$library = ( new OptionFieldRepository() )->load();
		self::assertSame( 'Our Rating', $library->find( 1 )?->name );
		self::assertSame( FieldType::Text, $library->find( 100 )?->type );

		$written = null;
		Functions\when( 'update_option' )->alias(
			static function ( string $name, $value, $autoload ) use ( &$written ): bool {
				$written = array( $name, $value, $autoload );
				return true;
			}
		);
		( new OptionFieldRepository() )->save( $library );

		self::assertSame( 'affibloom_fields', $written[0] );
		self::assertSame( array( 1 => 'Our Rating' ), $written[1]['names'] );
		self::assertSame( 100, $written[1]['fields'][0]['id'] );
		self::assertFalse( $written[2] );
	}

	public function test_nothing_stored_is_the_nine_built_ins(): void {
		Functions\when( 'get_option' )->justReturn( false );

		self::assertCount( 9, ( new OptionFieldRepository() )->load()->all() );
	}
}
