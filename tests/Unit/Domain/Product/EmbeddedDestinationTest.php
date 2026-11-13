<?php
namespace Affibloom\Tests\Unit\Domain\Product;

use Affibloom\Domain\Product\EmbeddedDestination;
use PHPUnit\Framework\TestCase;

/**
 * Lasso_Helper::get_final_url_from_url_param().
 */
final class EmbeddedDestinationTest extends TestCase {
	/**
	 * @dataProvider links
	 */
	public function test_lassos_rules( string $link, ?string $destination ): void {
		self::assertSame( $destination, EmbeddedDestination::in( $link ) );
	}

	/**
	 * @return array<string, array{string, ?string}>
	 */
	public static function links(): array {
		return array(
			'impact u='              => array( 'https://mizuno.pxf.io/c/123/456/789?u=https%3A%2F%2Fmizunousa.com%2Fwave&subId1=vv', 'https://mizunousa.com/wave' ),
			'first link-like param'  => array( 'https://x.example/go?id=7&to=https%3A%2F%2Fa.example%2F1&u=https%3A%2F%2Fb.example%2F2', 'https://a.example/1' ),
			'referrer is skipped'    => array( 'https://x.example/go?referrer=https%3A%2F%2Fa.example&u=https%3A%2F%2Fb.example%2Fp', 'https://b.example/p' ),
			'trailing slash trimmed' => array( 'https://x.example/go?u=https%3A%2F%2Fb.example%2Fp%2F', 'https://b.example/p' ),
			'no link in the query'   => array( 'https://x.example/p?a=1&b=two', null ),
			'no query'               => array( 'https://x.example/p', null ),
			'a param that is no url' => array( 'https://x.example/go?u=not%20a%20link', null ),
			'shareasale urllink'     => array( 'https://www.shareasale.com/r.cfm?b=1&u=2&m=3&urllink=www.example.com%2Fp&afftrack=', 'https://www.example.com/p' ),
			'shareasale only'        => array( 'https://shareasale.com/r.cfm?b=1&u=https%3A%2F%2Fa.example', null ),
			'pntra url'              => array( 'https://www.pntra.com/t/abc?url=https%3A%2F%2Fexample.com%2Fp', 'https://example.com/p' ),
			'wordseed url'           => array( 'https://wordseed.com/t?x=https%3A%2F%2Fa.example&url=https%3A%2F%2Fexample.com%2Fq', 'https://example.com/q' ),
			'titan redirect'         => array( 'https://titan.fitness/go?redirect=%2Fproducts%2Frack', 'https://www.titan.fitness/products/rack' ),
			'prf.hn destination'     => array( 'https://prf.hn/click/camref:1101l/destination:https%3A%2F%2Fexample.com%2Fp', 'https://example.com/p' ),
			'amp written out'        => array( 'https://pntra.com/t?a=1&amp;url=https%3A%2F%2Fexample.com%2Fr', 'https://example.com/r' ),
		);
	}
}
