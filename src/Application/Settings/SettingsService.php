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
use Affibloom\Domain\Settings\PerformanceMode;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Domain\Settings\SettingsRepository;

/**
 * Reading and saving settings, one tab at a time.
 */
final class SettingsService {

	/** The fields each tab owns, with the type that decides how a value is cleaned. */
	private const FIELDS = array(
		'general' => array(
			'new_tab'                => 'bool',
			'nofollow'               => 'bool',
			'sponsored'              => 'bool',
			'show_disclosure'        => 'bool',
			'allow_duplicates'       => 'bool',
			'link_cloaking'          => 'bool',
			'detect_opportunities'   => 'bool',
			'auto_detect_links'      => 'bool',
			'ga_tracking_id'         => 'ga_ids',
			'ga_click_tracking'      => 'bool',
			'ga_pageview'            => 'bool',
			'ga_anonymize_ip'        => 'bool',
			'link_prefix'            => 'slug',
			'smart_css'              => 'bool',
			'performance_mode'       => 'performance_mode',
			'cpu_threshold'          => 'percent',
			'scan_post_types'        => 'post_types',
			'scan_custom_fields'     => 'custom_fields',
			'disable_amazon_notice'  => 'bool',
			'disable_help_tips'      => 'bool',
			'disable_import_notice'  => 'bool',
			// The support view's fields (`&support=1`); they are saved only when that view posted them.
			'keep_original_domains'  => 'domains',
			'cron_interval'          => 'cron_interval',
			'manual_process_limit'   => 'process_limit',
			'restrict_prefix'        => 'bool',
			'process_limit'          => 'bool',
			'fontawesome_svg'        => 'bool',
			'no_feature_description' => 'bool',
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
	 * The General fields that exist only in the support view. A checkbox there says "off" by being absent, which on
	 * the ordinary view would switch them all off, so they are saved only when the form says it showed them.
	 */
	public const SUPPORT_KEYS = array(
		'keep_original_domains',
		'cron_interval',
		'manual_process_limit',
		'restrict_prefix',
		'process_limit',
		'fontawesome_svg',
		'no_feature_description',
	);

	/** The form field that says the support fields were on the page. */
	public const SUPPORT_MARKER = '_support';

	/**
	 * Builds the service.
	 *
	 * @param SettingsRepository    $repository Where settings are kept.
	 * @param DetectionChoices|null $choices    The post types and ACF fields the site offers for Custom Link Detection;
	 *                                          without it no choice is valid.
	 */
	public function __construct( private SettingsRepository $repository, private ?DetectionChoices $choices = null ) {}

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
			if ( 'general' === $tab && in_array( $key, self::SUPPORT_KEYS, true ) && ! array_key_exists( self::SUPPORT_MARKER, $input ) ) {
				continue;
			}
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

		if ( 'general' === $tab ) {
			$after = self::analytics_rules( $after );
		}

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
	 * Google Analytics' switches depend on each other as in Lasso's form (F9 R12): Click Tracking needs a Tracking ID,
	 * Pageview and IP Anonymization need Click Tracking. A disabled switch posts nothing, so the browser already saves
	 * them off; this is the same rule on the server, for a request that did not come from the form.
	 *
	 * @param Settings $settings The settings after the posted values.
	 */
	private static function analytics_rules( Settings $settings ): Settings {
		$click = $settings->bool( 'ga_click_tracking' ) && '' !== $settings->string( 'ga_tracking_id' );

		return $settings->with(
			array(
				'ga_click_tracking' => $click,
				'ga_pageview'       => $click && $settings->bool( 'ga_pageview' ),
				'ga_anonymize_ip'   => $click && $settings->bool( 'ga_anonymize_ip' ),
			)
		);
	}

	/**
	 * Lasso's keyup rule for the Cloaked Link Prefix, stored through sanitize_title (F9 R17): lower case, anything that
	 * is not a letter or digit is a dash, no dashes at the ends. Nothing left means no prefix.
	 *
	 * @param mixed $raw Raw value.
	 */
	private static function clean_slug( mixed $raw ): ?string {
		return is_string( $raw ) && strlen( $raw ) <= 200 ? sanitize_title( $raw ) : null;
	}

	/**
	 * A whole number from $min to $max, as typed. Anything else is refused and the old value stays.
	 *
	 * @param mixed $raw Raw value.
	 * @param int   $min Lowest allowed.
	 * @param int   $max Highest allowed.
	 */
	private static function clean_range( mixed $raw, int $min, int $max ): ?int {
		if ( ! is_int( $raw ) && ! ( is_string( $raw ) && 1 === preg_match( '/^\s*\d+\s*$/', $raw ) ) ) {
			return null;
		}

		$number = (int) $raw;

		return $number >= $min && $number <= $max ? $number : null;
	}

	/**
	 * The Processes limit: Lasso clamps it to 1-3 instead of refusing it (F9 R43).
	 *
	 * @param mixed $raw Raw value.
	 */
	private static function clamp_process_limit( mixed $raw ): ?int {
		if ( ! is_int( $raw ) && ! ( is_string( $raw ) && 1 === preg_match( '/^\s*-?\d+\s*$/', $raw ) ) ) {
			return null;
		}

		return max( 1, min( 3, (int) $raw ) );
	}

	/**
	 * Keep Original URL (F9 R43): one domain a line, "http://" and "https://" taken off, kept only when "https://" and
	 * the line is a link with a dot in its host. Each domain once.
	 *
	 * @param mixed $raw Raw value: the textarea's text, or a list.
	 * @return list<string>|null
	 */
	private static function clean_domains( mixed $raw ): ?array {
		if ( is_array( $raw ) ) {
			$raw = implode( "\n", array_filter( $raw, 'is_string' ) );
		}
		if ( ! is_string( $raw ) ) {
			return null;
		}

		$domains = array();
		foreach ( (array) preg_split( '/\R/', $raw, -1, PREG_SPLIT_NO_EMPTY ) as $line ) {
			$line = trim( (string) preg_replace( '#^https?://#i', '', trim( (string) $line ) ) );
			$host = '' === $line ? null : parse_url( 'https://' . $line, PHP_URL_HOST ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Application code does not call WordPress.
			if ( is_string( $host ) && str_contains( $host, '.' ) ) {
				$domains[] = $line;
			}
		}

		return array_values( array_unique( $domains ) );
	}

	/**
	 * Custom Link Detection's choices: a list of names that the site offers. One unknown name refuses the list.
	 *
	 * @param mixed        $raw     Raw value: a list of names.
	 * @param list<string> $offered The names the site offers.
	 * @return list<string>|null
	 */
	private static function clean_choices( mixed $raw, array $offered ): ?array {
		if ( null === $raw || '' === $raw ) {
			return array();
		}
		if ( ! is_array( $raw ) ) {
			return null;
		}

		$names = array();
		foreach ( $raw as $name ) {
			if ( ! is_string( $name ) || ! in_array( $name, $offered, true ) ) {
				return null;
			}
			$names[] = $name;
		}

		return array_values( array_unique( $names ) );
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
			'ga_ids'           => is_string( $raw ) && strlen( $raw ) <= 200 ? trim( sanitize_text_field( $raw ) ) : null,
			'slug'             => self::clean_slug( $raw ),
			'performance_mode' => is_string( $raw ) && PerformanceMode::is_mode( $raw ) ? $raw : null,
			// Lasso stores anything and reads abs( int ); a whole number from 1 to 100 is the only sensible CPU level (edge case 2).
			'percent'          => self::clean_range( $raw, 1, 100 ),
			'cron_interval'    => self::clean_range( $raw, 0, 3 ),
			'process_limit'    => self::clamp_process_limit( $raw ),
			'domains'          => self::clean_domains( $raw ),
			'post_types'       => self::clean_choices( $raw, null === $this->choices ? array() : array_keys( $this->choices->post_types() ) ),
			'custom_fields'    => self::clean_choices( $raw, null === $this->choices ? array() : array_keys( $this->choices->custom_fields() ) ),
			default      => throw new LogicException( "No cleaner for type $type." ),
		};
	}
}
