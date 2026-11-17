<?php
namespace Affibloom\Tests\Unit\Domain\LinkIndex;

use Affibloom\Domain\LinkIndex\RenderedAttributes;
use PHPUnit\Framework\TestCase;

/**
 * F21 R38-R40 at render time (D13, D14; Task 35H).
 */
final class RenderedAttributesTest extends TestCase {

	public function test_a_tied_link_takes_the_products_link_target_rel_and_click_attributes(): void {
		self::assertSame(
			array(
				'href'                    => 'https://site.test/shoe/',
				'target'                  => '_blank',
				'rel'                     => 'nofollow sponsored noopener',
				'data-affibloom-id'       => '7',
				'data-affibloom-location' => '42',
			),
			RenderedAttributes::tied( 'https://old.example/shoe?ref=1', 'https://site.test/shoe/', true, true, true, 7, 42 )
		);
		$same_tab = RenderedAttributes::tied( 'https://old.example/shoe', 'https://www.example.com/shoe', false, false, false, 7, 42 );
		self::assertNull( $same_tab['target'] );
		self::assertNull( $same_tab['rel'] );
	}

	public function test_a_tied_link_keeps_a_sponsored_amazon_url_and_its_own_query_on_the_public_link(): void {
		$sponsored = 'https://www.amazon.com/sspa/click?ie=UTF8&spc=x';
		self::assertSame( $sponsored, RenderedAttributes::tied( $sponsored, 'https://www.amazon.com/dp/B01?tag=a-20', true, true, false, 7, 1 )['href'] );
		self::assertSame( 'https://site.test/shoe/?utm_source=ig', RenderedAttributes::tied( 'https://site.test/shoe/?utm_source=ig', 'https://site.test/shoe/', true, true, false, 7, 1 )['href'] );
	}

	public function test_any_other_blank_link_gets_noopener_and_loses_noreferrer(): void {
		self::assertSame( array( 'rel' => 'nofollow noopener' ), RenderedAttributes::untied( '_blank', 'nofollow noreferrer' ) );
		self::assertSame( array( 'rel' => 'noopener' ), RenderedAttributes::untied( '_blank', '' ) );
		self::assertSame( array( 'rel' => null ), RenderedAttributes::untied( '', '1' ) );
		self::assertSame( array(), RenderedAttributes::untied( '', 'nofollow' ) );
		self::assertSame( array(), RenderedAttributes::untied( '_blank', 'noopener' ) );
	}
}
