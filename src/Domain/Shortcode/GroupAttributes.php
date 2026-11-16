<?php
/**
 * What a group display reads from its shortcode.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Shortcode;

/**
 * A group display's own attributes (F30 R6, R13-R15, R17; D1, D22; N1): the group's slug (`group`), how many products
 * (`limit`), newest first (`latest`), the grid's disclosure (`disclosure`), its columns (`columns`) and whether its
 * cards are compact (`compact`).
 */
final class GroupAttributes {

	/**
	 * Lasso's count for a `limit` that is 0 or not a number (R6).
	 */
	public const FALLBACK_LIMIT = 10;

	/**
	 * The `compact` values that hide the cards' description, fields and disclosure: PHP's boolean filter's true
	 * values, as Lasso reads it (R13), and `hide`, which its help text names (N1).
	 */
	private const COMPACT = array( '1', 'true', 'yes', 'on', 'hide' );

	/**
	 * Builds the attributes.
	 *
	 * @param string   $group      The group's slug; '' none.
	 * @param int|null $limit      How many products; null all.
	 * @param bool     $latest     Newest first.
	 * @param string   $disclosure `enable`, `disable` or '' (the setting decides).
	 * @param int|null $columns    The grid's columns, 1-5; null none (GridColumns decides).
	 * @param bool     $compact    Cards without description, fields and disclosure.
	 * @param string   $bullets    A list's `bullets` as written ('' none; ListBullets reads it).
	 */
	public function __construct(
		public readonly string $group,
		public readonly ?int $limit,
		public readonly bool $latest,
		public readonly string $disclosure,
		public readonly ?int $columns = null,
		public readonly bool $compact = false,
		public readonly string $bullets = '',
	) {}

	/**
	 * The group's slug (F31 A8, D2): `group`, else Lasso's `categories`, which wins over its `category`.
	 *
	 * @param array<string, string> $attributes Repaired attributes.
	 */
	public static function slug( array $attributes ): string {
		foreach ( array( 'group', 'categories', 'category' ) as $name ) {
			$slug = trim( $attributes[ $name ] ?? '' );
			if ( '' !== $slug ) {
				return $slug;
			}
		}

		return '';
	}

	/**
	 * The attributes of a repaired shortcode. R6: an empty `limit` is no limit; otherwise its whole number, and 0 (or
	 * anything that is not a number) is 10. `latest` only as `true`, as Lasso compares it.
	 *
	 * @param array<string, string> $attributes Repaired attributes.
	 */
	public static function of( array $attributes ): self {
		$limit = trim( $attributes['limit'] ?? '' );
		// WordPress's absint(): abs( (int) ), so "3x" is 3 and "x" is 0.
		$count  = '' === $limit ? null : abs( (int) $limit );
		$latest = $attributes['latest'] ?? '';
		$column = trim( $attributes['columns'] ?? '' );

		return new self(
			self::slug( $attributes ),
			0 === $count ? self::FALLBACK_LIMIT : $count,
			'true' === $latest,
			strtolower( trim( $attributes['disclosure'] ?? '' ) ),
			// R14: 1 to 5, anything else none.
			in_array( $column, array( '1', '2', '3', '4', '5' ), true ) ? (int) $column : null,
			in_array( strtolower( trim( $attributes['compact'] ?? '' ) ), self::COMPACT, true ),
			trim( $attributes['bullets'] ?? '' )
		);
	}
}
