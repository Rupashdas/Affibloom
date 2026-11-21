<?php
/**
 * Detection when a post is indexed.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Hooks;

use Affibloom\Application\Monetize\AffiliateDetector;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Infrastructure\WordPress\OptionSettingsRepository;
use Affibloom\Module;

/**
 * "Auto-Detect Affiliate Links" (F35 R1, R12, D3, D5, D23; Task C2B): after the link index writes a post's rows
 * (`affibloom_link_index_indexed`, on save, rebuild and the daily pass), its network links are recorded, and a link
 * still without a product schedules `affibloom_auto_detect` once, 5 seconds later (C2C makes the products). With the
 * setting off nothing is recorded, and switching it off clears the scheduled run.
 */
final class AutoDetectHooks implements Module {

	/** The background run that makes products for detected links (Task C2C). */
	public const EVENT = 'affibloom_auto_detect';

	/** The daily run that catches the rest (Task C2C; Lasso's daily `lasso_auto_monetize`). */
	public const DAILY = 'affibloom_auto_detect_daily';

	/**
	 * Builds the hooks.
	 *
	 * @param AffiliateDetector  $detector The detector.
	 * @param SettingsRepository $settings The site's settings.
	 */
	public function __construct( private AffiliateDetector $detector, private SettingsRepository $settings ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'affibloom_link_index_indexed', array( $this, 'indexed' ), 10, 2 );
		add_action( 'update_option_' . OptionSettingsRepository::OPTION, array( $this, 'switched' ), 10, 2 );
	}

	/**
	 * A post's rows were written: its network links are recorded.
	 *
	 * @param int                                            $host The post.
	 * @param list<\Affibloom\Domain\LinkIndex\LinkLocation> $rows Its rows.
	 */
	public function indexed( int $host, array $rows ): void {
		unset( $host );
		if ( ! $this->settings->get()->bool( 'auto_detect_links' ) ) {
			return;
		}
		if ( $this->detector->detect( $rows ) > 0 && false === wp_next_scheduled( self::EVENT ) ) {
			wp_schedule_single_event( time() + 5, self::EVENT );
		}
	}

	/**
	 * The settings were saved: with Auto-Detect off, both runs go (D23). The saved value is read from what WordPress
	 * stored, not from a repository that may have read the option before.
	 *
	 * @param mixed $stored The option before.
	 * @param mixed $saved  The option now.
	 */
	public function switched( $stored, $saved ): void {
		unset( $stored );
		if ( is_array( $saved ) && array_key_exists( 'auto_detect_links', $saved ) && ! $saved['auto_detect_links'] ) {
			wp_clear_scheduled_hook( self::EVENT );
			wp_clear_scheduled_hook( self::DAILY );
		}
	}
}
