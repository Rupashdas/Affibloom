<?php
/**
 * Where Amazon short links are mapped to their destination.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Monetize;

/**
 * The Amazon short-link map (F35 D14; Task AM2; Lasso's `lasso_amazon_shortened_url`): one row per short link, with
 * where it leads ('' until resolved), the product made for it (0 until then) and how many times resolving failed.
 */
interface ShortLinkRepository {

	/** Attempts before a short link is given up (D14). */
	public const TRIES = 3;

	/**
	 * A short link's row, or null when it was never seen.
	 *
	 * @param string $short_url The short link.
	 * @return array{final: string, product: int, tries: int}|null
	 */
	public function find( string $short_url ): ?array;

	/**
	 * Writes a short link's row.
	 *
	 * @param string $short_url The short link.
	 * @param string $final_url Where it leads; '' while unresolved.
	 * @param int    $product   The product made for it; 0 for none.
	 * @param int    $tries     Failed attempts so far.
	 */
	public function save( string $short_url, string $final_url, int $product, int $tries ): void;
}
