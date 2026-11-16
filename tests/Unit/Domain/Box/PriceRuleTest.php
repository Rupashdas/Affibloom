<?php
namespace Affibloom\Tests\Unit\Domain\Box;

use Affibloom\Domain\Box\BoxOverrides;
use Affibloom\Domain\Box\ElementVisibility;
use Affibloom\Domain\Box\PriceRule;
use Affibloom\Domain\Display\Elements;
use PHPUnit\Framework\TestCase;

/**
 * Lasso's price and Prime rules for a box (F5 R18-R20, R28, D7; AC20; Task 22A).
 */
final class PriceRuleTest extends TestCase {

	/**
	 * R19: the product's Show Price, else the site's; never without a price.
	 */
	public function test_the_price_prints_by_the_products_show_price_else_the_sites(): void {
		self::assertTrue( PriceRule::prints( '$89.99', null, true ) );
		self::assertFalse( PriceRule::prints( '$89.99', null, false ) );
		self::assertTrue( PriceRule::prints( '$89.99', true, false ) );
		self::assertFalse( PriceRule::prints( '$89.99', false, true ) );
		self::assertFalse( PriceRule::prints( '', true, true ) );
	}

	/**
	 * R28 (#5997): the site's Price element is only the default; a product's own Show Price saved on shows it, and the
	 * wrapper's flag follows.
	 */
	public function test_a_products_own_show_price_wins_over_the_price_element(): void {
		self::assertTrue( PriceRule::element_shows( true, null ) );
		self::assertFalse( PriceRule::element_shows( false, null ) );
		self::assertFalse( PriceRule::element_shows( false, false ) );
		self::assertTrue( PriceRule::element_shows( false, true ) );

		$off = Elements::from_array( array( 'price' => false ) );
		self::assertContains( 'price', ElementVisibility::of( $off, BoxOverrides::from_block( array() ) )->hidden() );
		self::assertNotContains( 'price', ElementVisibility::of( $off, BoxOverrides::from_block( array() ), true )->hidden() );
	}

}
