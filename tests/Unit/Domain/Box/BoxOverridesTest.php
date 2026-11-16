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

	/**
	 * Task 20B: the theme, buttons, tracking ID, disclosure, anchor and style from both forms; `origin="widget"` draws the
	 * phone layout; an unknown style is ignored.
	 */
	public function test_the_theme_button_tracking_disclosure_anchor_and_style_fields(): void {
		$block = BoxOverrides::from_block(
			array(
				'theme'          => 'Money',
				'primaryUrl'     => 'https://www.example.com/p',
				'primaryText'    => 'hide',
				'secondaryUrl'   => 'https://www.example.com/s',
				'secondaryText'  => 'Read Review',
				'trackingId'     => 'box-20',
				'disclosureText' => 'Box disclosure.',
				'anchorId'       => 'my-anchor',
				'linkId'         => '77',
				'style'          => 'Mobile',
			)
		);
		self::assertSame( array( 'Money', 'https://www.example.com/p', 'hide', 'https://www.example.com/s', 'Read Review', 'box-20', 'Box disclosure.', 'my-anchor', '77', 'mobile' ), array( $block->theme, $block->primary_url, $block->primary_text, $block->secondary_url, $block->secondary_text, $block->tracking_id, $block->disclosure_text, $block->anchor_id, $block->link_id, $block->style ) );

		self::assertSame( 'mobile', BoxOverrides::from_shortcode( array( 'origin' => 'widget' ) )->style );
		self::assertSame( 'desktop', BoxOverrides::from_shortcode( array( 'origin' => 'widget', 'style' => 'desktop' ) )->style );
		self::assertSame( '', BoxOverrides::from_shortcode( array( 'style' => 'tablet' ) )->style );
		self::assertSame( 'box-20', BoxOverrides::from_shortcode( array( 'tracking_id' => 'box-20' ) )->tracking_id );
	}

	/**
	 * Task 20C: price, Prime, basis price, rating, Pros and Cons with labels, fields and show_pros_cons from both forms;
	 * Lasso's "-br-" is a line break; `field="hide"` is `fields="hide"`.
	 */
	public function test_the_price_rating_pros_cons_and_fields_fields(): void {
		$block = BoxOverrides::from_block(
			array(
				'price'        => 'hide',
				'prime'        => true,
				'basisPrice'   => '$99.99',
				'rating'       => '4.5',
				'pros'         => 'Light',
				'consLabel'    => 'Watch out',
				'fields'       => 'hide',
				'showProsCons' => false,
			)
		);
		self::assertSame( array( 'hide', true, '$99.99', '4.5', 'Light', 'Watch out', 'hide', false ), array( $block->price, $block->prime, $block->basis_price, $block->rating, $block->pros, $block->cons_label, $block->fields, $block->show_pros_cons ) );
		self::assertNull( BoxOverrides::from_block( array() )->show_pros_cons );

		$short = BoxOverrides::from_shortcode( array( 'pros' => 'One-br-Two', 'field' => 'hide', 'prime' => 'yes', 'show_pros_cons' => 'false' ) );
		self::assertSame( "One\nTwo", $short->pros );
		self::assertSame( 'hide', $short->fields );
		self::assertTrue( $short->prime );
		self::assertFalse( $short->show_pros_cons );
		self::assertSame( '', BoxOverrides::from_shortcode( array( 'fields' => 'other' ) )->fields );
	}
}
