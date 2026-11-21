<?php
namespace Affibloom\Tests\Unit\Domain\Opportunity;

use Affibloom\Domain\Opportunity\OpportunityPage;
use Affibloom\Domain\Opportunity\OpportunityQuery;
use PHPUnit\Framework\TestCase;

/**
 * Link Opportunities' arguments (F22 R7, R9, R10, D6; Task O1).
 */
final class OpportunityQueryTest extends TestCase {

	public function test_only_url_content_and_suggestion_sort_and_a_header_starts_ascending(): void {
		self::assertSame( array( '', '' ), array( ( new OpportunityQuery( '', 'domain', 'asc' ) )->orderby, ( new OpportunityQuery( '', 'domain', 'asc' ) )->order ) );
		$sorted = new OpportunityQuery( '', 'suggestion', 'asc' );

		self::assertSame( array( 'suggestion', 'desc' ), $sorted->next( 'suggestion' ) );
		self::assertSame( array( 'url', 'asc' ), $sorted->next( 'url' ) );
		self::assertSame( array( 'suggestion', 'asc' ), ( new OpportunityQuery( '', 'suggestion', 'desc' ) )->next( 'suggestion' ) );
	}

	public function test_spaces_split_the_search_into_wildcard_words(): void {
		self::assertSame( array( 'luminous', 'ball' ), ( new OpportunityQuery( '  luminous   ball ' ) )->words() );
		self::assertSame( array(), ( new OpportunityQuery( '   ' ) )->words() );
	}

	public function test_a_page_past_the_end_is_the_last(): void {
		$page = new OpportunityPage( array(), 27, 7 );

		self::assertSame( array( 3, 21, 27, 20 ), array( $page->current(), $page->first(), $page->last(), $page->offset() ) );
	}
}
