<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Box\SchemaBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Lasso's Product JSON-LD (F5 R90-R92; AC17; Task 20D).
 */
final class SchemaBuilderTest extends TestCase {

	/**
	 * Review schema: the offer (price without "$", two decimals) and the review with its author and rating; Pro/Con
	 * schema: the notes, numbered, empty lines left out; Lasso's key order.
	 */
	public function test_review_and_pro_con_schema_as_lasso_prints_them(): void {
		$schema = SchemaBuilder::build( 'Ligra', 'https://example.com/p.jpg', '$89.9', 'USD', '4.5', array( 'Light', '', 'Quick' ), array( 'Small' ), 'Harvey', true, true );

		self::assertSame(
			'{"@context":"http://schema.org/","@type":"Product","name":"Ligra","image":{"@context":"https://schema.org","@type":"ImageObject","url":"https://example.com/p.jpg"},'
				. '"offers":{"@type":"Offer","price":"89.90","priceCurrency":"USD","availability":"https://schema.org/InStock"},'
				. '"review":{"@context":"https://schema.org","@type":"Review","author":{"@type":"Person","name":"Harvey"},"reviewRating":{"@context":"https://schema.org","@type":"Rating","ratingValue":"4.5"},'
				. '"positiveNotes":{"@context":"https://schema.org","@type":"ItemList","numberOfItems":2,"itemListElement":[{"@context":"https://schema.org","@type":"ListItem","position":1,"name":"Light"},{"@context":"https://schema.org","@type":"ListItem","position":2,"name":"Quick"}]},'
				. '"negativeNotes":{"@context":"https://schema.org","@type":"ItemList","numberOfItems":1,"itemListElement":[{"@context":"https://schema.org","@type":"ListItem","position":1,"name":"Small"}]}}}',
			json_encode( $schema, JSON_UNESCAPED_SLASHES )
		);
	}

	/**
	 * A rating under 1 adds none (Lasso's (int) test); Pro/Con alone has no offer; Review alone has no notes.
	 */
	public function test_each_schema_alone(): void {
		$review = SchemaBuilder::build( 'Ligra', '', '', 'USD', '0.5', array( 'Light' ), array(), 'Harvey', true, false );
		self::assertArrayNotHasKey( 'reviewRating', $review['review'] );
		self::assertArrayNotHasKey( 'positiveNotes', $review['review'] );
		self::assertSame( '0.00', $review['offers']['price'] );

		$notes = SchemaBuilder::build( 'Ligra', '', '', 'USD', '4', array( 'Light' ), array(), 'Harvey', false, true );
		self::assertArrayNotHasKey( 'offers', $notes );
		self::assertArrayNotHasKey( 'reviewRating', $notes['review'] );
		self::assertSame( 1, $notes['review']['positiveNotes']['numberOfItems'] );
		self::assertArrayNotHasKey( 'negativeNotes', $notes['review'] );
	}
}
