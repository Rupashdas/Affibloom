<?php
/**
 * The geni.us clean-up job.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Button;
use Affibloom\Domain\Product\ProductRepository;
use RuntimeException;

/**
 * The geni.us clean-up job (C1P; R63, D14; Lasso's Lasso_Process_Remove_Genius_Formatting): turning "Keep Geni.us
 * Formatting" off queues it, turning it on clears it. Each run re-saves ten products whose Primary URL is a geni.us
 * link, newest first, through the product page's save, which stores each link as where it leads and keeps the geni.us
 * link as the old one (C1K); then it queues the next ten. A run goes on below the last product it did, so a link that
 * cannot be followed is passed over, not done again; it never touches posts (D14).
 */
final class GeniusCleanupService {

	/** Products per run, as Lasso's LIMIT. */
	public const PER_RUN = 10;

	/**
	 * Builds the service.
	 *
	 * @param ProductRepository     $products The products, to find the geni.us ones.
	 * @param ProductService        $service  The product page's save.
	 * @param GeniusCleanupSchedule $schedule When the next run is.
	 */
	public function __construct(
		private ProductRepository $products,
		private ProductService $service,
		private GeniusCleanupSchedule $schedule,
	) {}

	/**
	 * The Amazon tab was saved: only a change of the switch acts, as Lasso compares it with the stored value.
	 *
	 * @param bool $before "Keep Geni.us Formatting" before the save.
	 * @param bool $after  And after it.
	 */
	public function switched( bool $before, bool $after ): void {
		if ( $before === $after ) {
			return;
		}
		if ( $after ) {
			$this->schedule->cancel();
			return;
		}
		$this->schedule->next( 0 );
	}

	/**
	 * One run: the next ten geni.us products below $before are saved again; ten of them queue the next run.
	 *
	 * @param int $before Go on below this product ID; 0 starts from the newest.
	 */
	public function run( int $before ): void {
		$ids = $this->products->genius_ids( $before, self::PER_RUN );
		foreach ( $ids as $id ) {
			$this->resave( $id );
		}
		if ( self::PER_RUN === count( $ids ) ) {
			$this->schedule->next( min( $ids ) );
		}
	}

	/**
	 * Saves a product again with its own Primary URL, and its buttons when one has a geni.us link (Lasso re-saves the
	 * name, link, slug, second link, price, description and image, all as stored). A product the save refuses (its
	 * link's destination belongs to another product, or the switch is on again) is left as it is.
	 *
	 * @param int $id The product.
	 */
	private function resave( int $id ): void {
		$product = $this->products->get( $id );
		if ( null === $product ) {
			return;
		}

		$input   = array( 'url' => $product->url->value() );
		$buttons = $product->buttons->all();
		if ( array() !== array_filter( $buttons, static fn( Button $button ): bool => self::is_geni_us( $button->url ) ) ) {
			$input['buttons']          = $product->buttons->to_array();
			$input['primary_position'] = $product->buttons->main_position();
		}

		try {
			$this->service->update( $id, $input );
		} catch ( DuplicateLink | ValidationFailed | ProductNotFound | RuntimeException $refused ) {
			return;
		}
	}

	/**
	 * Whether a stored button link is a geni.us link.
	 *
	 * @param string $url The link.
	 */
	private static function is_geni_us( string $url ): bool {
		try {
			return '' !== $url && AffiliateUrl::from_string( $url )->is_geni_us();
		} catch ( InvalidField $not_a_link ) {
			return false;
		}
	}
}
