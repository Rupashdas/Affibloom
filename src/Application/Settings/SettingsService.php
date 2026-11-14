<?php
/**
 * Reading and saving settings, one tab at a time.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Settings;

use InvalidArgumentException;
use LogicException;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Domain\Settings\SettingsRepository;

/**
 * Reading and saving settings, one tab at a time.
 */
final class SettingsService {

	/** The fields each tab owns, with the type that decides how a value is cleaned. */
	private const FIELDS = array(
		'general' => array(
			'new_tab'         => 'bool',
			'nofollow'        => 'bool',
			'sponsored'       => 'bool',
			'show_disclosure' => 'bool',
		),
		'display' => array(
			'disclosure_text'         => 'text',
			'show_price'              => 'bool',
			'show_disclosure'         => 'bool',
			'show_disclosure_grid'    => 'bool',
			'show_disclosure_list'    => 'bool',
			'show_disclosure_table'   => 'bool',
			'link_from_display_title' => 'bool',
			'keep_site_stripe_ui'     => 'bool',
			'custom_css'              => 'css',
		),
		'amazon'  => array(
			'tracking_id'                     => 'tracking_id',
			'multiple_tracking_ids'           => 'bool',
			'tracking_id_whitelist'           => 'tracking_ids',
			'auto_monetize_amazon'            => 'bool',
			'show_prime'                      => 'bool',
			'show_discount_pricing'           => 'bool',
			'commission_upgrades'             => 'bool',
			'update_pricing_daily'            => 'bool',
			'pricing_locations_only'          => 'bool',
			'force_change_from_link_details'  => 'bool',
			'keep_title_when_url_replaced'    => 'bool',
			'shorten_amazon_links'            => 'bool',
			'mobile_deep_linking'             => 'bool',
			'international_click'             => 'bool',
			'keep_genius_formatting'          => 'bool',
			'scan_sitestripe_in_post_content' => 'bool',
			'days_before_out_of_stock_alerts' => 'days',
		),
	);

	/**
	 * Builds the service.
	 *
	 * @param SettingsRepository $repository Where settings are kept.
	 */
	public function __construct( private SettingsRepository $repository ) {}

	/**
	 * The current settings.
	 */
	public function current(): Settings {
		return $this->repository->get();
	}

	/**
	 * Tab keys, in display order.
	 *
	 * @return list<string>
	 */
	public function tabs(): array {
		return array_keys( self::FIELDS );
	}

	/**
	 * Saves the fields of one tab. Invalid values keep their old value.
	 *
	 * @param string               $tab   Tab key.
	 * @param array<string, mixed> $input Raw, unslashed form values.
	 * @return list<string> Keys whose values were refused.
	 * @throws InvalidArgumentException For an unknown tab.
	 */
	public function update_tab( string $tab, array $input ): array {
		if ( ! isset( self::FIELDS[ $tab ] ) ) {
			throw new InvalidArgumentException( 'Unknown settings tab.' );
		}

		$changes = array();
		$refused = array();
		foreach ( self::FIELDS[ $tab ] as $key => $type ) {
			// Only a checkbox says something by being absent (unticked). Any other field that was not posted, such as
			// the whitelist while it is disabled, keeps its value, as Lasso's save does.
			if ( 'bool' !== $type && ! array_key_exists( $key, $input ) ) {
				continue;
			}

			$value = $this->clean( $type, $input[ $key ] ?? null );
			if ( null === $value ) {
				$refused[] = $key;
				continue;
			}
			$changes[ $key ] = $value;
		}

		$before = $this->repository->get();
		$after  = $before->with( $changes );

		// Lasso adds every ID removed from the whitelist to a blacklist, which the link rewriter uses to replace
		// that tag (Helper::update_amazon_blacklist_ids). The removal can only be seen now, so it is recorded now.
		if ( 'amazon' === $tab && array_key_exists( 'tracking_id_whitelist', $changes ) ) {
			$removed = array_diff( $before->list( 'tracking_id_whitelist' ), $after->list( 'tracking_id_whitelist' ) );
			if ( array() !== $removed ) {
				$after = $after->with( array( 'tracking_id_blacklist' => self::sorted_set( array_merge( $after->list( 'tracking_id_blacklist' ), $removed ) ) ) );
			}
		}

		$this->repository->save( $after );

		return $refused;
	}

	/**
	 * Sets the tracking ID alone, by the Amazon tab's rule: the product page's offer to save a link's tag as the site's
	 * (Lasso's amazon-url-detected notice, lasso_save_amazon_tracking_id; Task 14D). Every other setting stays as it
	 * is, which update_tab( 'amazon', … ) would not do (an absent checkbox there means "off").
	 *
	 * @param string $id The tracking ID.
	 * @return bool Whether it was valid and saved.
	 */
	public function set_tracking_id( string $id ): bool {
		$id = trim( $id );
		if ( '' === $id || ! self::is_tracking_id( $id ) ) {
			return false;
		}
		$this->repository->save( $this->repository->get()->with( array( 'tracking_id' => $id ) ) );

		return true;
	}

	/**
	 * Puts every field of one tab back to its default, as Lasso's Reset Settings does for the Display tab. Keys
	 * shared with another tab (show_disclosure is also on General) are reset with it, as in Lasso.
	 *
	 * @param string $tab Tab key.
	 * @throws InvalidArgumentException For an unknown tab.
	 */
	public function reset_tab( string $tab ): void {
		if ( ! isset( self::FIELDS[ $tab ] ) ) {
			throw new InvalidArgumentException( 'Unknown settings tab.' );
		}

		$defaults = array_intersect_key( Settings::DEFAULTS, self::FIELDS[ $tab ] );
		$this->repository->save( $this->repository->get()->with( $defaults ) );
	}

	/**
	 * Lasso's rule for an Amazon Associates tracking ID (Lasso_Amazon_Api::TRACKING_ID_REGEX): letters, digits,
	 * dashes, underscores and dots, then a dash and two or three digits, in any case.
	 *
	 * @param string $id A tracking ID.
	 */
	private static function is_tracking_id( string $id ): bool {
		return 1 === preg_match( '/^[a-zA-Z0-9-_.]+-\d{2,3}$/i', $id );
	}

	/**
	 * The whitelist, as Lasso keeps it: IDs separated by spaces, sorted, each once. One invalid ID refuses the list.
	 *
	 * @param mixed $raw Raw value: a space-separated string, or a list of IDs.
	 * @return list<string>|null
	 */
	private function clean_tracking_ids( mixed $raw ): ?array {
		if ( is_string( $raw ) ) {
			$raw = preg_split( '/\s+/', trim( $raw ), -1, PREG_SPLIT_NO_EMPTY );
		}
		if ( ! is_array( $raw ) ) {
			return null;
		}

		$ids = array();
		foreach ( $raw as $id ) {
			if ( ! is_string( $id ) || ! self::is_tracking_id( trim( $id ) ) ) {
				return null;
			}
			$ids[] = trim( $id );
		}

		return self::sorted_set( $ids );
	}

	/**
	 * A whole number of days from 0 to 30, Lasso's range for out-of-stock alerts.
	 *
	 * @param mixed $raw Raw value.
	 */
	private static function clean_days( mixed $raw ): ?int {
		if ( ! is_int( $raw ) && ! ( is_string( $raw ) && 1 === preg_match( '/^\s*\d+\s*$/', $raw ) ) ) {
			return null;
		}

		$days = (int) $raw;

		return $days <= 30 ? $days : null;
	}

	/**
	 * A sorted list without duplicates.
	 *
	 * @param array<int|string, string> $items Items.
	 * @return list<string>
	 */
	private static function sorted_set( array $items ): array {
		$items = array_values( array_unique( $items ) );
		sort( $items );

		return $items;
	}

	/**
	 * Custom CSS through Lasso's own allowlist (Helper::format_css): it is printed inside a style element, so `<`
	 * is removed and `</style>` can never be written; the characters CSS needs stay.
	 *
	 * @param string $raw Raw CSS.
	 */
	private static function clean_css( string $raw ): string {
		return trim( (string) preg_replace( '~[^a-zA-Z0-9\-\_\{\}\@\#\!\;\:\.\,\s\(\)\%\[\]=*"\'\~\|\^\$/>+?&]~', '', $raw ) );
	}

	/**
	 * Cleans one value. Null means the value is refused and the old one stays.
	 *
	 * @param string $type Field type from FIELDS.
	 * @param mixed  $raw  Raw value; a checkbox that is not ticked is absent (null).
	 * @return bool|string|int|list<string>|null
	 * @throws LogicException For a type without a cleaner.
	 */
	private function clean( string $type, mixed $raw ): bool|string|int|array|null {
		return match ( $type ) {
			// A ticked checkbox sends "1"; an unticked one sends nothing. true/"true"/"on" cover other callers (Task 26).
			'bool'       => in_array( $raw, array( true, 1, '1', 'on', 'true', 'yes' ), true ),
			'text'       => is_string( $raw ) && strlen( trim( $raw ) ) <= 500 ? sanitize_text_field( $raw ) : null,
			'css'        => is_string( $raw ) ? self::clean_css( $raw ) : null,
			'tracking_id'  => is_string( $raw ) && ( '' === trim( $raw ) || self::is_tracking_id( trim( $raw ) ) ) ? trim( $raw ) : null,
			'tracking_ids' => $this->clean_tracking_ids( $raw ),
			// Lasso's number field takes 0-30 (amazon.php: min="0" max="30").
			'days'         => self::clean_days( $raw ),
			default      => throw new LogicException( "No cleaner for type $type." ),
		};
	}
}
