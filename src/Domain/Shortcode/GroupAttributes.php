<?php
/**
 * What a group display reads from its shortcode.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Shortcode;

/**
 * A group display's own attributes (F30 R6, R15, R17; D1, D22): the group's slug (`group`), how many products
 * (`limit`), newest first (`latest`) and the grid's disclosure (`disclosure`).
 */
final class GroupAttributes {

	/**
	 * Lasso's count for a `limit` that is 0 or not a number (R6).
	 */
	public const FALLBACK_LIMIT = 10;

	/**
	 * Builds the attributes.
	 *
	 * @param string   $group      The group's slug; '' none.
	 * @param int|null $limit      How many products; null all.
	 * @param bool     $latest     Newest first.
	 * @param string   $disclosure `enable`, `disable` or '' (the setting decides).
	 */
	public function __construct(
		public readonly string $group,
		public readonly ?int $limit,
		public readonly bool $latest,
		public readonly string $disclosure,
	) {}

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

		return new self(
			trim( $attributes['group'] ?? '' ),
			0 === $count ? self::FALLBACK_LIMIT : $count,
			'true' === $latest,
			strtolower( trim( $attributes['disclosure'] ?? '' ) )
		);
	}
}
