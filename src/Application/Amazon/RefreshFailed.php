<?php
/**
 * Refresh could not bring a product's Amazon data.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Amazon;

use RuntimeException;

/**
 * Refresh could not bring a product's Amazon data (C1 R35): no such product, not an Amazon product with an ASIN, no
 * configured credentials, or Amazon failed (with Lasso's error code) or gave no image.
 */
final class RefreshFailed extends RuntimeException {

	public const NOT_FOUND      = 'not_found';
	public const NOT_AMAZON     = 'not_amazon';
	public const NOT_CONFIGURED = 'amazon_not_configured';
	public const FAILED         = 'amazon_failed';
	public const NO_IMAGE       = 'amazon_no_image';

	/**
	 * Builds the refusal.
	 *
	 * @param string $reason One of the constants.
	 * @param string $kind   For FAILED, Lasso's error code (CatalogError::NOT_FOUND …); else ''.
	 */
	public function __construct( public readonly string $reason, public readonly string $kind = '' ) {
		parent::__construct( $reason );
	}
}
