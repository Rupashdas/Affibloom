<?php
/**
 * Pushing a changed link default to every existing product.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Settings;

use Affibloom\Domain\Product\ProductRepository;
use InvalidArgumentException;

/**
 * Pushing a changed link default to every existing product (Task 9D; Lasso's "Update Link Settings" dialog and its
 * lasso_override_display_option). Only the switches a product has a field for can be pushed; Link Cloaking writes the
 * product's own cloak flag, Amazon products included (F24 R31, D15; Task CL5); Detect Opportunities the product's own
 * switch (F22 R41-R43, D9; Task O6).
 */
final class LinkDefaultsService {

	/** The settings that are also product switches. */
	public const KEYS = array( 'new_tab', 'nofollow', 'sponsored', 'show_disclosure', 'link_cloaking', 'detect_opportunities' );

	/** The product field a setting is pushed to, where the names differ. */
	private const FIELDS = array( 'link_cloaking' => 'cloak' );

	/**
	 * Builds the service.
	 *
	 * @param ProductRepository $products Where products are kept.
	 */
	public function __construct( private ProductRepository $products ) {}

	/**
	 * Sets one switch on every product, whatever its status.
	 *
	 * @param string $key   One of KEYS.
	 * @param bool   $value The new value.
	 * @return int How many products hold the value now.
	 * @throws InvalidArgumentException For a key that is not one of KEYS.
	 */
	public function apply_to_all( string $key, bool $value ): int {
		if ( ! in_array( $key, self::KEYS, true ) ) {
			throw new InvalidArgumentException( 'This default cannot be applied to existing products.' );
		}

		return $this->products->set_flag_for_all( self::FIELDS[ $key ] ?? $key, $value );
	}
}
