<?php
/**
 * Runs the upgrade on admin pages and watches the plugin version.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface;

use Affibloom\Application\Lifecycle;
use Affibloom\Application\Shell\NoticeProvider;
use Affibloom\Application\Upgrade\Upgrader;
use Affibloom\Application\Upgrade\UpgradeResult;
use Affibloom\Domain\Shell\ShellNotice;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Module;

/**
 * Runs the upgrade on admin pages (not AJAX or cron, as Lasso's Update_DB) and watches the plugin version on init.
 */
final class UpgradeHooks implements Module, NoticeProvider {

	/**
	 * A failed run on this request, for the notice.
	 *
	 * @var UpgradeResult|null
	 */
	private ?UpgradeResult $failure = null;

	/**
	 * Builds the module.
	 *
	 * @param Upgrader  $upgrader  The pending steps.
	 * @param Lifecycle $lifecycle The version watch.
	 */
	public function __construct( private Upgrader $upgrader, private Lifecycle $lifecycle ) {}

	/**
	 * Hooks into WordPress. The version watch runs after init's default priority, once post types are registered.
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'upgrade' ) );
		add_action( 'init', array( $this, 'watch_version' ), 20 );
	}

	/**
	 * Runs the pending steps on an admin request. A failure does not break the page: it is shown as a notice, and the
	 * next admin page tries again.
	 */
	public function upgrade(): void {
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}
		$result = $this->upgrader->run();
		if ( UpgradeResult::FAILED === $result->status ) {
			$this->failure = $result;
			add_action( 'admin_notices', array( $this, 'failure_notice' ) );
		}
	}

	/**
	 * The failed step and its error, for those who manage the plugin's settings, on WordPress's own pages.
	 */
	public function failure_notice(): void {
		foreach ( $this->notices( '' ) as $notice ) {
			printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( NoticeText::for( $notice )['text'] ) );
		}
	}

	/**
	 * The same failure in the notice region of Affibloom's pages, which hide every admin_notices notice (Task ON2).
	 *
	 * @param string $page The Affibloom admin page slug being shown.
	 * @return list<ShellNotice>
	 */
	public function notices( string $page ): array {
		if ( null === $this->failure || ! current_user_can( Capabilities::SETTINGS ) ) {
			return array();
		}

		return array( new ShellNotice( 'upgrade_failed', ShellNotice::ERROR, array( $this->failure->failed_step, $this->failure->message ) ) );
	}

	/**
	 * Rebuilds the rewrite rules once after the plugin version changed.
	 */
	public function watch_version(): void {
		$this->lifecycle->watch_version();
	}
}
