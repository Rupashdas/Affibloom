<?php
/**
 * Settings stored in one autoloaded option, read once per request.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Settings\Settings;
use Affibloom\Domain\Settings\SettingsRepository;

/**
 * Settings stored in one autoloaded option, read once per request.
 */
final class OptionSettingsRepository implements SettingsRepository {

	public const OPTION = 'affibloom_settings';

	/**
	 * Settings that were removed, which schema steps take out of the stored option (2026-11-25): Commission Upgrades,
	 * Mobile Deep Linking and Send Readers to Their Local Store worked only through Lasso's cloud (deviation 52; decision
	 * B4 reversed; step 13); the others were read by nothing or did less than they said (deviation 168; step 14).
	 */
	public const RETIRED = array(
		'commission_upgrades',
		'mobile_deep_linking',
		'international_click',
		'no_feature_description',
		'restrict_prefix',
		'disable_amazon_notice',
		'disable_import_notice',
		'performance_mode',
		'show_disclosure_list',
		'force_change_from_link_details',
	);

	/**
	 * Settings already read in this request.
	 *
	 * @var Settings|null
	 */
	private ?Settings $loaded = null;

	/**
	 * The current settings.
	 */
	public function get(): Settings {
		$this->loaded ??= Settings::from_array( (array) get_option( self::OPTION, array() ) );
		return $this->loaded;
	}

	/**
	 * Replaces the stored settings. Autoloaded, because every page with a box reads them.
	 *
	 * @param Settings $settings New settings.
	 */
	public function save( Settings $settings ): void {
		update_option( self::OPTION, $settings->to_array(), true );
		$this->loaded = $settings;
	}

	/**
	 * Takes the retired keys out of the stored option, leaving every other stored value as it is. Writes nothing when
	 * none is stored. Settings already drops unknown keys when it reads, so this only tidies what a site keeps.
	 */
	public static function remove_retired(): void {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			return;
		}
		$kept = array_diff_key( $stored, array_flip( self::RETIRED ) );
		if ( count( $kept ) !== count( $stored ) ) {
			update_option( self::OPTION, $kept, true );
		}
	}
}
