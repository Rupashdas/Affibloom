<?php
/**
 * The site's Google Analytics settings.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Analytics;

use Affibloom\Domain\Settings\Settings;

/**
 * Settings → General → Google Analytics as the site uses it (C3 R1-R6, D5; Task C3A): the IDs (Lasso reads the field as
 * a list split on commas and trimmed, R6; no format is checked, R4), Click Tracking, Pageview and IP Anonymization.
 * Click Tracking needs an ID and the other two need Click Tracking (R3), so a stored value that breaks a rule reads off.
 */
final class GaSettings {

	/**
	 * Describes the settings.
	 *
	 * @param list<string> $ids        The tracking or property IDs, in order.
	 * @param bool         $clicks     Click Tracking.
	 * @param bool         $pageview   Pageview.
	 * @param bool         $anonymize  IP Anonymization.
	 */
	public function __construct(
		public readonly array $ids,
		public readonly bool $clicks,
		public readonly bool $pageview,
		public readonly bool $anonymize,
	) {}

	/**
	 * The site's settings.
	 *
	 * @param Settings $settings The settings.
	 */
	public static function of( Settings $settings ): self {
		$ids    = array_values( array_filter( array_map( 'trim', explode( ',', $settings->string( 'ga_tracking_id' ) ) ), static fn( string $id ): bool => '' !== $id ) );
		$clicks = array() !== $ids && $settings->bool( 'ga_click_tracking' );

		return new self( $ids, $clicks, $clicks && $settings->bool( 'ga_pageview' ), $clicks && $settings->bool( 'ga_anonymize_ip' ) );
	}
}
