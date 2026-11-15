<?php
namespace Affibloom\Tests\Unit\Domain\Box;

use Affibloom\Domain\Box\DescriptionRule;
use PHPUnit\Framework\TestCase;

/**
 * Whether the box prints the description (F5 R22; Lasso's get_lasso_url 186-187, 324-327 and
 * Lasso_Post::is_show_description), decided without Lasso's write of show_description = 1 (D2).
 */
final class DescriptionRuleTest extends TestCase {

	public function test_an_unset_flag_shows_the_description_even_when_empty(): void {
		self::assertTrue( DescriptionRule::shows( null, '' ) );
		self::assertTrue( DescriptionRule::shows( true, '' ) );
	}

	public function test_a_flag_turned_off_hides_a_blank_description(): void {
		self::assertFalse( DescriptionRule::shows( false, '' ) );
		self::assertFalse( DescriptionRule::shows( false, '<p> </p>' ) );
		self::assertFalse( DescriptionRule::shows( false, '<p>   </p>' ) );
	}

	/**
	 * Lasso then shows it, and writes the flag back on; Affibloom only shows it.
	 */
	public function test_a_flag_turned_off_still_shows_a_description_with_text(): void {
		self::assertTrue( DescriptionRule::shows( false, 'Grippy.' ) );
	}

	/**
	 * More than ten characters forces it on, whatever the flag, even when the text is only an empty paragraph.
	 */
	public function test_more_than_ten_characters_force_it_on(): void {
		self::assertTrue( DescriptionRule::shows( false, '<p>  </p><p></p>' ) );
		// Lasso's own blank "<p><br></p>" is eleven characters, so its blank check never sees it.
		self::assertTrue( DescriptionRule::shows( false, '<p><br></p>' ) );
	}

	public function test_empty_paragraphs_are_removed_from_the_text(): void {
		self::assertSame( '<p>Grippy.</p>', DescriptionRule::text( '<p></p><p>Grippy.</p><p></p>' ) );
	}
}
