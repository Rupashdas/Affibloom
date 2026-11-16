<?php
namespace Affibloom\Tests\Unit\Domain\Amazon;

use Affibloom\Domain\Amazon\PrimeRule;
use PHPUnit\Framework\TestCase;

/**
 * Lasso's Prime rule (C1 R46, AC12; decision C4; Task C1G).
 */
final class PrimeRuleTest extends TestCase {

	/**
	 * Every Amazon product with an ASIN while the site shows Prime, unless its own switch is off; none otherwise.
	 */
	public function test_prime_shows_on_amazon_products_with_an_asin_while_the_site_says_so(): void {
		self::assertTrue( PrimeRule::shows( true, true, 'B0D2JFK15W', true ) );
		self::assertFalse( PrimeRule::shows( false, true, 'B0D2JFK15W', true ) );
		self::assertFalse( PrimeRule::shows( true, false, 'B0D2JFK15W', true ) );
		self::assertFalse( PrimeRule::shows( true, true, ' ', true ) );
		self::assertFalse( PrimeRule::shows( true, true, 'B0D2JFK15W', false ) );
	}
}
