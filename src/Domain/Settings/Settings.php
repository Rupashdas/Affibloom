<?php
/**
 * Site-wide settings. Unknown keys are dropped; missing keys take the default.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Settings;

use InvalidArgumentException;

/**
 * Site-wide settings. Unknown keys are dropped; missing keys take the default.
 */
final class Settings {

	/** Defaults for a fresh install. Task 26 imports the site's real values from Lasso. */
	public const DEFAULTS = array(
		// General.
		'new_tab'                         => true,
		'nofollow'                        => true,
		'sponsored'                       => false,
		'show_disclosure'                 => true,
		// Display, site-wide. The per-type theme, colours and labels live in the display model
		// (Domain\Display\DisplaySettings), because Lasso keeps one set per display type.
		'disclosure_text'                 => 'We earn a commission if you make a purchase, at no additional cost to you.',
		'link_from_display_title'         => true,
		// Lasso's factory defaults (admin/lasso-constant.php 171-176, 236-240).
		'show_price'                      => true,
		'show_disclosure_grid'            => false,
		'show_disclosure_list'            => true,
		'show_disclosure_table'           => true,
		'keep_site_stripe_ui'             => false,
		'custom_css'                      => '',
		// Amazon: Lasso's factory defaults (admin/lasso-constant.php 111-160; docs/features/F17).
		'tracking_id'                     => '',
		'multiple_tracking_ids'           => true,
		'tracking_id_whitelist'           => array(),
		'tracking_id_blacklist'           => array(),
		'auto_monetize_amazon'            => false,
		'show_prime'                      => true,
		'show_discount_pricing'           => false,
		'commission_upgrades'             => true,
		'update_pricing_daily'            => true,
		'pricing_locations_only'          => true,
		'force_change_from_link_details'  => false,
		'keep_title_when_url_replaced'    => true,
		'shorten_amazon_links'            => true,
		'mobile_deep_linking'             => true,
		'international_click'             => true,
		'keep_genius_formatting'          => true,
		'scan_sitestripe_in_post_content' => false,
		'days_before_out_of_stock_alerts' => 0,
	);

	/**
	 * Use from_array().
	 *
	 * @param array<string, bool|string|int|list<string>> $values Complete set of values.
	 */
	private function __construct( private readonly array $values ) {}

	/**
	 * Builds settings from whatever is stored.
	 *
	 * @param array<string, mixed> $stored Stored option value.
	 */
	public static function from_array( array $stored ): self {
		$values = self::DEFAULTS;
		foreach ( $values as $key => $default ) {
			if ( array_key_exists( $key, $stored ) && self::same_type( $default, $stored[ $key ] ) ) {
				$values[ $key ] = $stored[ $key ];
			}
		}

		return new self( $values );
	}

	/**
	 * Whether a stored value has the type its default has, so a malformed option can never make an accessor fail.
	 *
	 * @param bool|string|int|list<string> $fallback The default value.
	 * @param mixed                        $value    The stored value.
	 */
	private static function same_type( bool|string|int|array $fallback, mixed $value ): bool {
		if ( is_array( $fallback ) ) {
			return is_array( $value ) && array_is_list( $value ) && array() === array_filter( $value, static fn( $item ) => ! is_string( $item ) );
		}

		return gettype( $fallback ) === gettype( $value );
	}

	/**
	 * A boolean setting.
	 *
	 * @param string $key Setting key.
	 */
	public function bool( string $key ): bool {
		return (bool) $this->value( $key );
	}

	/**
	 * A text setting.
	 *
	 * @param string $key Setting key.
	 */
	public function string( string $key ): string {
		$value = $this->value( $key );

		return is_array( $value ) ? implode( ' ', $value ) : (string) $value;
	}

	/**
	 * A whole-number setting.
	 *
	 * @param string $key Setting key.
	 */
	public function int( string $key ): int {
		return (int) $this->value( $key );
	}

	/**
	 * A list setting, such as the tracking ID whitelist.
	 *
	 * @param string $key Setting key.
	 * @return list<string>
	 */
	public function list( string $key ): array {
		$value = $this->value( $key );

		return is_array( $value ) ? $value : array();
	}

	/**
	 * A copy with some values changed. Unknown keys are ignored.
	 *
	 * @param array<string, bool|string|int|list<string>> $changes New values.
	 */
	public function with( array $changes ): self {
		return new self( array_merge( $this->values, array_intersect_key( $changes, self::DEFAULTS ) ) );
	}

	/**
	 * All values, for storage.
	 *
	 * @return array<string, bool|string|int|list<string>>
	 */
	public function to_array(): array {
		return $this->values;
	}

	/**
	 * One value.
	 *
	 * @param string $key Setting key.
	 * @return bool|string|int|list<string>
	 * @throws InvalidArgumentException For a key that does not exist; always a programming error.
	 */
	private function value( string $key ): bool|string|int|array {
		if ( ! array_key_exists( $key, $this->values ) ) {
			throw new InvalidArgumentException( "Unknown setting: $key" ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- never shown to users.
		}
		return $this->values[ $key ];
	}
}
