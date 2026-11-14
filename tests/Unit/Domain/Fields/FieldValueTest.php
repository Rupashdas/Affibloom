<?php
namespace Affibloom\Tests\Unit\Domain\Fields;

use Affibloom\Domain\Fields\FieldValue;
use Affibloom\Domain\InvalidField;
use PHPUnit\Framework\TestCase;

/** Lasso's lasso_save_field_positions trims each line and drops blank lines (F26 R26, D10); a value holds 1,000 characters (D9). */
final class FieldValueTest extends TestCase {

	/**
	 * @return array<string, array{string, string}>
	 */
	public static function typed(): array {
		return array(
			'padded and blank lines' => array( "   38  \n\n  \n 39\t\n", "38\n39" ),
			'Windows line breaks'    => array( "a \r\n\r\n b\r\n", "a\nb" ),
			'one line'               => array( '  Grippy  ', 'Grippy' ),
			'only blanks'            => array( " \n \n", '' ),
			'nothing'                => array( '', '' ),
		);
	}

	/**
	 * @dataProvider typed
	 */
	public function test_each_line_is_trimmed_and_blank_lines_dropped( string $typed, string $kept ): void {
		self::assertSame( $kept, FieldValue::clean( $typed ) );
	}

	public function test_a_value_of_1000_characters_is_kept_counted_after_cleaning(): void {
		self::assertSame( str_repeat( 'é', 1000 ), FieldValue::accepted( 7, '  ' . str_repeat( 'é', 1000 ) . "  \n\n" ) );
	}

	public function test_a_longer_value_is_refused_under_its_row(): void {
		try {
			FieldValue::accepted( 7, str_repeat( 'x', 1001 ) );
			self::fail( 'A value of 1,001 characters was accepted.' );
		} catch ( InvalidField $error ) {
			self::assertSame( 'rows.7', $error->field );
			self::assertSame( 'field_value_long', $error->reason );
		}
	}
}
