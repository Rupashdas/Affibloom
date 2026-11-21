<?php
namespace Affibloom\Tests\Unit\Domain\Opportunity;

use Affibloom\Domain\Opportunity\ContentFilter;
use PHPUnit\Framework\TestCase;

/**
 * Content Opportunities' filters (F22 R25, D18; Task O3).
 */
final class ContentFilterTest extends TestCase {

	public function test_values_outside_lassos_lists_are_the_defaults(): void {
		$filter = new ContentFilter( 'sometimes', 'product', -4, -1 );

		self::assertSame( array( 'all', '', 0, 0 ), array( $filter->show, $filter->post_type, $filter->category, $filter->min_clicks ) );
		self::assertSame( array(), $filter->args() );
	}

	public function test_the_address_carries_only_what_is_set(): void {
		self::assertSame(
			array(
				'show'         => 'unmonetized',
				'content_type' => 'page',
				'category'     => 12,
				'min_clicks'   => 3,
			),
			( new ContentFilter( 'unmonetized', 'page', 12, 3 ) )->args()
		);
		self::assertSame( array( 'content_type' => 'post' ), ( new ContentFilter( 'all', 'post' ) )->args() );
	}
}
