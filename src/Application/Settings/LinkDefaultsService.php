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
 * lasso_override_display_option). Only the switches a product has a field for can be pushed; Link Cloaking and Detect
 * Opportunities join when the features that own those product fields exist (F24, F22).
 */
final class LinkDefaultsService {

	/** The settings that are also product switches. */
	public const KEYS = array( 'new_tab', 'nofollow', 'sponsored', 'show_disclosure' );

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

		return $this->products->set_flag_for_all( $key, $value );
	}
}
