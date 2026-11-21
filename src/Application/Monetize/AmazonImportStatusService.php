<?php
/**
 * The Amazon import's status and its notice.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Monetize;

use Affibloom\Application\Amazon\BannerDismissals;
use Affibloom\Domain\LinkIndex\LinkLocationRepository;
use Affibloom\Domain\Monetize\AmazonImportStatus;
use Affibloom\Domain\Product\LinkKind;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Domain\Settings\SettingsRepository;
use Closure;

/**
 * Where the Amazon import stands (F35 R29, R30, R35; D19; Task AM4): its queue from the link index, the Dashboard's
 * Amazon products, the index's placements and the batch the cron last recorded; the notice shows while
 * Auto-Monetization is on and the import has work, unless this person dismissed these counts.
 */
final class AmazonImportStatusService {

	/** The first day after activation, in seconds (R32's window). */
	private const FIRST_DAY = 86400;

	/**
	 * Builds the service.
	 *
	 * @param LinkLocationRepository $rows       The link index.
	 * @param ProductRepository      $products   The products.
	 * @param SettingsRepository     $settings   The site's settings.
	 * @param BannerDismissals       $dismissals Who hid the notice for which counts.
	 * @param Closure                $batch      The batch the cron recorded: fn(): array{running?: bool, total?: int, completed?: int}.
	 * @param Closure|null           $activated  When Affibloom was activated, as a Unix time: fn(): int (Task AM5; D19).
	 */
	public function __construct(
		private LinkLocationRepository $rows,
		private ProductRepository $products,
		private SettingsRepository $settings,
		private BannerDismissals $dismissals,
		private Closure $batch,
		private ?Closure $activated = null,
	) {}

	/**
	 * Settings → Amazon's "Amazon import status" card (R33; Task AM5): the status while Auto-Monetization is on.
	 */
	public function card(): ?AmazonImportStatus {
		return $this->on() ? $this->status() : null;
	}

	/**
	 * Settings → General's figures in the Link Index card (R34, D21; Task AM5), under R32's rule.
	 *
	 * @param int $now Now, as a Unix time.
	 */
	public function general( int $now ): ?AmazonImportStatus {
		if ( ! $this->on() ) {
			return null;
		}
		$status = $this->status();

		return $status->shows_context( $this->first_day( $now ) ) ? $status : null;
	}

	/**
	 * The Dashboard's notice (R32; Task AM5): the import idle or complete, in the first 24 hours after activation, with
	 * more placements indexed than Dashboard links, and not dismissed for these counts.
	 *
	 * @param int $user The person.
	 * @param int $now  Now, as a Unix time.
	 */
	public function dashboard_notice( int $user, int $now ): ?AmazonImportStatus {
		if ( ! $this->on() ) {
			return null;
		}
		$status = $this->status();
		if ( $status->busy() || ! $status->shows_context( $this->first_day( $now ) ) ) {
			return null;
		}

		return $this->dismissals->get( $user ) !== $status->fingerprint() ? $status : null;
	}

	/**
	 * Within 24 hours of Affibloom's activation (D19: Lasso's Pro warm-up is licence-bound and has no equivalent).
	 *
	 * @param int $now Now, as a Unix time.
	 */
	private function first_day( int $now ): bool {
		$at = null === $this->activated ? 0 : (int) ( $this->activated )();

		return $at > 0 && $now - $at < self::FIRST_DAY;
	}

	/**
	 * Whether Auto-Monetization is on.
	 */
	private function on(): bool {
		return $this->settings->get()->bool( 'auto_monetize_amazon' );
	}

	/**
	 * The status now.
	 */
	public function status(): AmazonImportStatus {
		$batch = ( $this->batch )();

		return new AmazonImportStatus(
			$this->rows->amazon_queue_size(),
			$this->products->page( SearchTerm::from_input( '' ), LinkKind::Amazon, 1, 0 )->total,
			$this->rows->stats()->links,
			(bool) ( $batch['running'] ?? false ),
			(int) ( $batch['total'] ?? 0 ),
			(int) ( $batch['completed'] ?? 0 )
		);
	}

	/**
	 * The notice for a person, or null (R30): only with Auto-Monetization on, while the import has work, and not after
	 * they dismissed these counts.
	 *
	 * @param int $user The person.
	 */
	public function notice( int $user ): ?AmazonImportStatus {
		if ( ! $this->on() ) {
			return null;
		}
		$status = $this->status();

		return $status->busy() && $this->dismissals->get( $user ) !== $status->fingerprint() ? $status : null;
	}

	/**
	 * Hides the notice for a person until the counts change (R35).
	 *
	 * @param int $user The person.
	 */
	public function dismiss( int $user ): void {
		$this->dismissals->set( $user, $this->status()->fingerprint() );
	}
}
