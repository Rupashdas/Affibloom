<?php
/**
 * Which clicks come from machines.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Clicks;

/**
 * Crawlers, previews and scripts do not count (F12 R9 replaced, D5; Task 33B): Lasso's script carried a bot list it
 * never called and left filtering to its cloud, so the local counter filters itself. An empty user agent is a machine.
 */
final class BotFilter {

	/** The words that mark a machine: search and social crawlers, link previews, headless browsers, HTTP libraries. */
	private const PATTERN = '/bot\b|bot\/|crawl|spider|slurp|mediapartners|facebookexternalhit|facebookcatalog|embedly|quora link preview|outbrain|vkshare|w3c_validator|whatsapp|skypeuripreview|bingpreview|preview|lighthouse|pagespeed|headless|phantomjs|puppeteer|playwright|selenium|curl|wget|python|java\/|go-http-client|okhttp|axios|node-fetch|libwww|httpclient|guzzle|wordpress\//i';

	/**
	 * Whether a click with this user agent is not counted.
	 *
	 * @param string $user_agent The request's User-Agent.
	 */
	public static function is_bot( string $user_agent ): bool {
		return '' === trim( $user_agent ) || 1 === preg_match( self::PATTERN, $user_agent );
	}
}
