<?php
/**
 * Lasso's "Migrating from Amazon SiteStripe?" notice.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Shell\NoticeProvider;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Domain\Shell\ShellNotice;
use Affibloom\Interface\Rest\ProductsController;

/**
 * Lasso's "Migrating from Amazon SiteStripe?" notice (C1N; auto-amazon.php, R72) in the region under the header: on
 * every Affibloom page while Auto-Monetization is off, with "Turn on Auto Amazon" linking to the Amazon tab, until its ×
 * dismisses it for the whole site (the option `affibloom_sitestripe_notice` set to "0", as Lasso's
 * lasso_enable_auto_amazon_notification). Only people who change settings see it: they are the ones who may dismiss it
 * (Lasso: manage_options) and turn the switch on.
 */
final class SiteStripeNotice implements NoticeProvider {

	public const KEY = 'sitestripe';

	public const OPTION = 'affibloom_sitestripe_notice';

	/** The × route, under the REST namespace (AmazonController registers it). */
	public const DISMISS_ROUTE = '/notices/sitestripe/dismiss';

	/**
	 * Builds the provider.
	 *
	 * @param SettingsRepository $settings The settings, for the Auto-Monetization switch.
	 */
	public function __construct( private SettingsRepository $settings ) {}

	/**
	 * The notice, while Auto-Monetization is off and nobody dismissed it.
	 *
	 * @param string $page The Affibloom admin page slug being shown.
	 * @return list<ShellNotice>
	 */
	public function notices( string $page ): array {
		unset( $page ); // On every Affibloom page, as Lasso's footer.
		if ( ! current_user_can( Capabilities::SETTINGS ) || '0' === (string) get_option( self::OPTION, '1' ) || $this->settings->get()->bool( 'auto_monetize_amazon' ) ) {
			return array();
		}

		return array(
			new ShellNotice(
				self::KEY,
				ShellNotice::WARNING,
				array(),
				admin_url( 'admin.php?page=' . SettingsScreen::SLUG . '&tab=amazon' ),
				true,
				'/' . ProductsController::NAMESPACE . self::DISMISS_ROUTE
			),
		);
	}

	/**
	 * Dismisses the notice for the whole site (Lasso's lasso_disable_auto_amazon_notification).
	 */
	public static function dismiss(): void {
		update_option( self::OPTION, '0' );
	}
}
