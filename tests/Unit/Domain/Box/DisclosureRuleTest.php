<?php
namespace Affibloom\Tests\Unit\Domain\Box;

use Affibloom\Domain\Box\DisclosureRule;
use PHPUnit\Framework\TestCase;

/**
 * The box's disclosure (F5 R24, R25; AC21; Task 22B).
 */
final class DisclosureRuleTest extends TestCase {

	/**
	 * R24: the product's text, trimmed, else the site's.
	 */
	public function test_the_products_text_wins_over_the_sites(): void {
		self::assertSame( 'Our own words.', DisclosureRule::text( '  Our own words. ', true, 'Site words.' ) );
		self::assertSame( 'Site words.', DisclosureRule::text( '   ', true, 'Site words.' ) );
	}

	/**
	 * R25: shown when the product's Show Disclosure is on and there is text.
	 */
	public function test_it_shows_when_the_product_says_so_and_there_is_text(): void {
		self::assertNull( DisclosureRule::text( 'Our own words.', false, 'Site words.' ) );
		self::assertNull( DisclosureRule::text( '', true, '' ) );
	}
}
