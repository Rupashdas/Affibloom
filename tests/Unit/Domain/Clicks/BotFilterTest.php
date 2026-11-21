<?php
namespace Affibloom\Tests\Unit\Domain\Clicks;

use Affibloom\Domain\Clicks\BotFilter;
use PHPUnit\Framework\TestCase;

/**
 * Machines do not count (F12 D5, AC6; Task 33B).
 */
final class BotFilterTest extends TestCase {

	public function test_crawlers_previews_headless_browsers_and_empty_agents_are_bots(): void {
		foreach ( array(
			'',
			'   ',
			'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
			'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
			'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
			'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/130.0.0.0 Safari/537.36',
			'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome-Lighthouse',
			'curl/8.4.0',
			'python-requests/2.31.0',
			'WordPress/7.1.4; https://example.com',
		) as $agent ) {
			self::assertTrue( BotFilter::is_bot( $agent ), $agent );
		}
	}

	public function test_readers_browsers_are_not(): void {
		foreach ( array(
			'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
			'Mozilla/5.0 (iPhone; CPU iPhone OS 18_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.1 Mobile/15E148 Safari/604.1',
			'Mozilla/5.0 (Macintosh; Intel Mac OS X 14.6; rv:132.0) Gecko/20100101 Firefox/132.0',
			'Mozilla/5.0 (Linux; Android 14; SM-S921B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36',
		) as $agent ) {
			self::assertFalse( BotFilter::is_bot( $agent ), $agent );
		}
	}
}
