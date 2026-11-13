<?php
namespace Affibloom\Tests\Unit\Domain\Icons;

use Affibloom\Domain\Icons\Icon;
use Affibloom\Domain\Icons\IconSet;
use Affibloom\Domain\InvalidField;
use PHPUnit\Framework\TestCase;

final class IconSetTest extends TestCase {
	private function site(): IconSet {
		return new IconSet(
			array(
				Icon::imported( 'medal-icon', '/wp-content/uploads/2023/06/medal-icon.svg', 9730 ),
				Icon::imported( 'full-review-icon', '/wp-content/uploads/2023/04/full-review-icon.svg', 0 ),
			)
		);
	}

	public function test_an_imported_icon_is_named_after_its_class(): void {
		$icons = $this->site()->all();

		self::assertSame( 'Medal', $icons[0]->name );
		self::assertSame( 'Full review', $icons[1]->name );
		self::assertTrue( $icons[0]->imported );
	}

	public function test_an_added_icon_takes_its_class_from_its_name(): void {
		$set = $this->site()->add( 'Under Armour', 'https://example.com/ua.svg', 12 );

		$added = $set->find( 'under-armour-icon' );
		self::assertNotNull( $added );
		self::assertSame( 'Under Armour', $added->name );
		self::assertFalse( $added->imported );
		self::assertCount( 3, $set->all() );
	}

	public function test_a_class_already_in_the_set_gets_a_number(): void {
		$set = $this->site()->add( 'Medal', 'https://example.com/m.svg', 12 )->add( 'medal!', 'https://example.com/m2.svg', 13 );

		self::assertNotNull( $set->find( 'medal-2-icon' ) );
		self::assertNotNull( $set->find( 'medal-3-icon' ) );
	}

	public function test_a_name_with_no_letters_or_digits_still_gets_a_class(): void {
		self::assertNotNull( $this->site()->add( '★★', 'https://example.com/s.svg', 12 )->find( 'custom-icon' ) );
	}

	/**
	 * @dataProvider bad_names
	 */
	public function test_a_name_must_have_one_to_forty_characters( string $name ): void {
		$this->expectException( InvalidField::class );
		$this->site()->add( $name, 'https://example.com/x.svg', 12 );
	}

	/** @return array<string, array{string}> */
	public static function bad_names(): array {
		return array(
			'empty'  => array( '   ' ),
			'too long' => array( str_repeat( 'a', 41 ) ),
		);
	}

	/**
	 * The URL ends up inside url("…") in CSS: a quote, a bracket, a backslash or a space could close it.
	 *
	 * @dataProvider bad_urls
	 */
	public function test_an_image_url_that_could_break_out_of_css_is_refused( string $url ): void {
		$this->expectException( InvalidField::class );
		$this->site()->add( 'Ok', $url, 12 );
	}

	/** @return array<string, array{string}> */
	public static function bad_urls(): array {
		return array(
			'quote'     => array( 'https://example.com/a".svg' ),
			'bracket'   => array( 'https://example.com/a).svg' ),
			'space'     => array( 'https://example.com/a b.svg' ),
			'backslash' => array( 'https://example.com/a\\b.svg' ),
			'empty'     => array( '' ),
		);
	}

	public function test_only_an_added_icon_can_be_removed(): void {
		$set = $this->site()->add( 'Star', 'https://example.com/s.svg', 12 );

		self::assertNull( $set->remove( 'star-icon' )->find( 'star-icon' ) );

		$this->expectException( InvalidField::class );
		$set->remove( 'medal-icon' );
	}

	public function test_it_survives_storage(): void {
		$set = $this->site()->add( 'Star', 'https://example.com/s.svg', 12 );

		self::assertEquals( $set, IconSet::from_array( $set->to_array() ) );
	}

	public function test_stored_rows_that_are_not_icons_are_skipped(): void {
		$set = IconSet::from_array(
			array(
				array( 'class' => 'medal-icon', 'name' => 'Medal', 'url' => '/m.svg', 'attachment' => 1, 'imported' => true ),
				array( 'class' => 'not a class', 'name' => 'X', 'url' => '/x.svg', 'attachment' => 0, 'imported' => false ),
				'junk',
			)
		);

		self::assertCount( 1, $set->all() );
	}
}
