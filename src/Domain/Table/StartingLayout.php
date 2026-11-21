<?php
/**
 * The layout a table's first product brings.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Table;

use Closure;

/**
 * Lasso's starting layout (F25 R19; Task T4; class-table-mapping.php 202-218): the heading group holds Product Image;
 * the second group Product name, Primary Rating and Price; the third Primary Button. Lasso's built-in field IDs.
 */
final class StartingLayout {

	/** Product Image. */
	public const IMAGE = 99991;

	/** Product name. */
	public const PRODUCT_NAME = 99990;

	/** Primary Rating. */
	public const PRIMARY_RATING = 1;

	/** Price. */
	public const PRICE = 99994;

	/** Primary Button. */
	public const PRIMARY_BUTTON = 99992;

	/**
	 * The three groups, each with a new ID.
	 *
	 * @param Closure $new_id A new group ID: fn(): string.
	 * @return list<array{id: string, fields: list<int>}>
	 */
	public static function groups( Closure $new_id ): array {
		return array(
			array(
				'id'     => (string) $new_id(),
				'fields' => array( self::IMAGE ),
			),
			array(
				'id'     => (string) $new_id(),
				'fields' => array( self::PRODUCT_NAME, self::PRIMARY_RATING, self::PRICE ),
			),
			array(
				'id'     => (string) $new_id(),
				'fields' => array( self::PRIMARY_BUTTON ),
			),
		);
	}
}
