<?php
namespace Affibloom\Tests\Unit\Domain\Cloak;

use Affibloom\Domain\Cloak\NetworkSubIds;
use Affibloom\Domain\Cloak\RedirectQuery;
use PHPUnit\Framework\TestCase;

/**
 * Lasso's query pass-through on a cloaked redirect (F24 R22, D7; Task CL2).
 */
final class RedirectQueryTest extends TestCase {

	private static function apply( string $destination, array $request, string $referer = 'https://elsewhere.test/page?x=1' ): string {
		$networks = new class() implements NetworkSubIds {
			public function first_for( string $url ): ?string {
				return str_contains( $url, 'impact.test' ) ? 'subId1' : null;
			}
		};

		return RedirectQuery::apply( $destination, $request, $referer, 'https://site.test', $networks );
	}

	public function test_nothing_to_pass_on_leaves_the_destination_exactly_as_it_was(): void {
		self::assertSame( 'https://shop.test/p?b=2&a=1#top', self::apply( 'https://shop.test/p?b=2&a=1#top', array( 'doing_wp_cron' => '1', 'empty' => '', 'lcid' => 'x' ) ) );
	}

	public function test_a_parameter_fills_one_the_destination_lacks_and_utm_always_replaces(): void {
		self::assertSame(
			'https://shop.test/p?a=1&utm_source=ig&foo=bar#top',
			self::apply( 'https://shop.test/p?a=1&utm_source=site#top', array( 'a' => 'kept-not', 'utm_source' => 'ig', 'foo' => 'bar' ) )
		);
	}

	public function test_url_placeholders_become_the_referring_page(): void {
		self::assertSame( 'https://shop.test/p?ref=https://elsewhere.test/page', self::apply( 'https://shop.test/p?ref={url}', array() ) );
		self::assertSame( 'https://shop.test/p?ref=https://elsewhere.test/page&x=1', self::apply( 'https://shop.test/p?ref={url_full}', array() ) );
		self::assertSame( 'https://shop.test/p?ref=https://site.test', self::apply( 'https://shop.test/p?ref={url}', array(), '' ) );
	}

	public function test_sid_from_another_site_becomes_the_network_s_sub_id(): void {
		self::assertSame( 'https://go.impact.test/c/1?subId1=youtube', self::apply( 'https://go.impact.test/c/1', array( 'sid' => 'youtube' ) ) );
		// From the site itself, or to no known network, sid is an ordinary parameter.
		self::assertSame( 'https://go.impact.test/c/1?sid=youtube', self::apply( 'https://go.impact.test/c/1', array( 'sid' => 'youtube' ), 'https://www.site.test/post/' ) );
		self::assertSame( 'https://shop.test/p?sid=youtube', self::apply( 'https://shop.test/p', array( 'sid' => 'youtube' ) ) );
	}
}
