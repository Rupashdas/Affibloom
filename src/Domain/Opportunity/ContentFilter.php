<?php
/**
 * Content Opportunities' filters.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Opportunity;

/**
 * Lasso's filter bar on Content (F22 R25, D17, D18; Task O3): Show (all content, or posts with unmonetized links),
 * Post type (all types, posts or pages; all types includes reusable blocks), Category (a category's term ID, 0 for
 * all) and Min link clicks (0 or more: the 30-day clicks of the products tied in the post).
 */
final class ContentFilter {

	public const ALL         = 'all';
	public const UNMONETIZED = 'unmonetized';

	/**
	 * Builds the filter; values outside the lists are the defaults.
	 *
	 * @param string $show       ALL or UNMONETIZED.
	 * @param string $post_type  '', 'post' or 'page'.
	 * @param int    $category   A category's term ID, 0 for all.
	 * @param int    $min_clicks At least this many clicks, 0 or more.
	 */
	public function __construct( string $show = self::ALL, string $post_type = '', int $category = 0, int $min_clicks = 0 ) {
		$this->show       = self::UNMONETIZED === $show ? self::UNMONETIZED : self::ALL;
		$this->post_type  = in_array( $post_type, array( 'post', 'page' ), true ) ? $post_type : '';
		$this->category   = max( 0, $category );
		$this->min_clicks = max( 0, $min_clicks );
	}

	/**
	 * ALL or UNMONETIZED.
	 *
	 * @var string
	 */
	public readonly string $show;

	/**
	 * '', 'post' or 'page'.
	 *
	 * @var string
	 */
	public readonly string $post_type;

	/**
	 * A category's term ID, 0 for all.
	 *
	 * @var int
	 */
	public readonly int $category;

	/**
	 * At least this many clicks.
	 *
	 * @var int
	 */
	public readonly int $min_clicks;

	/**
	 * The filter as an address's arguments, each left out at its default.
	 *
	 * @return array<string, string|int>
	 */
	public function args(): array {
		return array_filter(
			array(
				'show'         => self::ALL === $this->show ? '' : $this->show,
				'content_type' => $this->post_type,
				'category'     => $this->category,
				'min_clicks'   => $this->min_clicks,
			)
		);
	}
}
