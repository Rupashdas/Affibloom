<?php
/**
 * The link check a product save starts.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\LinkHealth;

use DateTimeImmutable;

/**
 * The link check a product save starts (F11 R15, D12): when the product was last saved, read before the save, and the
 * check scheduled after it, so the save itself stays fast.
 */
interface ProductLinkChecks {

	/**
	 * When the product was last saved; null when that is not known.
	 *
	 * @param int $product_id The product.
	 */
	public function last_saved( int $product_id ): ?DateTimeImmutable;

	/**
	 * Schedules the product's link check to run now, in the background.
	 *
	 * @param int $product_id The product.
	 */
	public function schedule( int $product_id ): void;
}
