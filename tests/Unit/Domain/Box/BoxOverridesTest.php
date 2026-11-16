<?php
namespace Affibloom\Tests\Unit\Domain\Box;

use Affibloom\Domain\Box\BoxOverrides;
use PHPUnit\Framework\TestCase;

/**
 * One box's overrides from the block's attributes and from Lasso's shortcode names (F5 R66-R69, R76, R79; F7 AC1-AC4;
 * Task 20A).
 */
final class BoxOverridesTest extends TestCase {

	/**
	 * The block's camelCase attributes; each hide switch wins over its text.
	 */
	public function test_the_block_reads_its_texts_and_hide_switches(): void {
		$box = BoxOverrides::from_block(
			array(
				'productId'       => '14727',
				'badge'           => ' Top Pick ',
				'hideDescription' => true,
				'description'     => 'ignored while hidden',
				'title'           => 'Ligra Kids',
				'titleUrl'        => 'https://www.example.com/review',
				'titleType'       => 'H3',
				'hideImage'       => true,
				'imageAlt'        => 'A white shoe',
			)
		);

		self::assertSame( 14727, $box->product_id );
		self::assertSame( 'Top Pick', $box->badge );
		self::assertSame( 'hide', $box->description );
		self::assertSame( 'Ligra Kids', $box->title );
		self::assertSame( 'https://www.example.com/review', $box->title_url );
		self::assertSame( 'h3', $box->title_type );
		self::assertSame( 'hide', $box->image_url );
		self::assertSame( 'A white shoe', $box->image_alt );
	}

	/**
	 * Lasso's shortcode names map onto the same fields; `lasso-br-code` becomes a line break (R67).
	 */
	public function test_lassos_shortcode_names_map_onto_the_same_fields(): void {
		$box = BoxOverrides::from_shortcode(
			array(
				'id'          => 9,
				'badge'       => 'hide',
				'description' => 'One.lasso-br-codeTwo.',
				'title_url'   => 'https://www.example.com/r',
				'title_type'  => 'h2',
				'image_url'   => 'https://www.example.com/p.jpg',
			)
		);

		self::assertSame( 9, $box->product_id );
		self::assertTrue( BoxOverrides::hides( $box->badge ) );
		self::assertSame( 'One.<br>Two.', $box->description );
		self::assertSame( 'h2', $box->title_type );
		self::assertSame( 'https://www.example.com/p.jpg', $box->image_url );
		self::assertSame( '', $box->title );
	}

	/**
	 * A title tag outside the headings and plain containers is dropped, so an attribute cannot add markup.
	 */
	public function test_a_title_tag_must_be_a_heading_or_plain_container(): void {
		self::assertSame( '', BoxOverrides::from_block( array( 'titleType' => 'script' ) )->title_type );
		self::assertSame( '', BoxOverrides::from_shortcode( array( 'title_type' => 'h2 onclick=x' ) )->title_type );
		self::assertSame( 'span', BoxOverrides::from_shortcode( array( 'title_type' => 'span' ) )->title_type );
	}

	/**
	 * Nothing set: every field empty, a missing ID 0.
	 */
	public function test_nothing_set_leaves_every_field_empty(): void {
		$box = BoxOverrides::from_block( array( 'productId' => 'x' ) );

		self::assertSame( 0, $box->product_id );
		self::assertSame( array( '', '', '', '', '', '', '' ), array( $box->badge, $box->description, $box->title, $box->title_url, $box->title_type, $box->image_url, $box->image_alt ) );
	}
}
