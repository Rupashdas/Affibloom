<?php
namespace Affibloom\Tests\Unit\Domain\Product;

use Affibloom\Domain\Product\SearchTerm;
use PHPUnit\Framework\TestCase;

/**
 * Lasso's Dashboard search rules: the typed text is cleaned (view-js.php, the search box's focusout), its
 * spaces become wildcards and some shapes mean more than their text (Lasso_DB::build_dashboard_search_term_string).
 */
final class SearchTermTest extends TestCase {

	public function test_a_pasted_link_keeps_the_parts_that_identify_it(): void {
		$term = SearchTerm::from_input( 'https://www.amazon.com/dp/B0CPH9NPK8?tag=vbvault-20' );

		self::assertSame( 'https www.amazon.com dp B0CPH9NPK8 tag vbvault-20', $term->text() );
	}

	public function test_punctuation_becomes_one_space_and_the_edges_are_trimmed(): void {
		self::assertSame( 'Mizuno Wave', SearchTerm::from_input( '  Mizuno—Wave!!  ' )->text() );
		self::assertSame( 'a b', SearchTerm::from_input( 'a  ???  b' )->text() );
	}

	public function test_the_characters_lasso_keeps_are_kept(): void {
		self::assertSame( 'v2.1+pro-x#1', SearchTerm::from_input( 'v2.1+pro-x#1' )->text() );
	}

	public function test_text_in_another_script_survives(): void {
		self::assertSame( '排球', SearchTerm::from_input( '排球' )->text() );
	}

	public function test_a_term_of_punctuation_only_is_empty(): void {
		self::assertTrue( SearchTerm::from_input( '  ???  ' )->is_empty() );
		self::assertTrue( SearchTerm::from_input( '' )->is_empty() );
		self::assertFalse( SearchTerm::from_input( 'wave' )->is_empty() );
	}

	public function test_spaces_become_wildcards_inside_one_like(): void {
		self::assertSame( '%Wave%Momentum%', SearchTerm::from_input( 'Wave Momentum' )->like( fn( string $v ): string => $v ) );
		self::assertSame( '%Wave%', SearchTerm::from_input( 'Wave' )->like( fn( string $v ): string => $v ) );
	}

	/** A % or _ an editor types is the character, not a wildcard; Lasso does not escape them at all. */
	public function test_each_word_is_escaped_before_the_wildcards_go_in(): void {
		$term = SearchTerm::from_input( 'a b' );

		self::assertSame( '%[a]%[b]%', $term->like( fn( string $v ): string => "[$v]" ) );
	}

	public function test_an_all_digits_term_is_also_a_product_id(): void {
		self::assertSame( 15160, SearchTerm::from_input( '15160' )->post_id() );
		self::assertNull( SearchTerm::from_input( '12a' )->post_id() );
		self::assertNull( SearchTerm::from_input( '0' )->post_id() );
	}

	public function test_an_asin_shaped_term_is_also_an_asin(): void {
		self::assertSame( 'B0CPH9NPK8', SearchTerm::from_input( 'b0cph9npk8' )->asin() );
		self::assertSame( 'B0CPH9NPK8', SearchTerm::from_input( 'B0CPH9NPK8' )->asin() );
		self::assertNull( SearchTerm::from_input( 'B0C' )->asin() );
		self::assertNull( SearchTerm::from_input( 'Mizuno' )->asin() );
	}
}
