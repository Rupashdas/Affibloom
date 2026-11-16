<?php
namespace Affibloom\Tests\Unit\Domain\Shortcode;

use Affibloom\Domain\Shortcode\Address;
use Affibloom\Domain\Shortcode\AnchorId;
use Affibloom\Domain\Shortcode\DisplayKind;
use Affibloom\Domain\Shortcode\DisplayRequest;
use Affibloom\Domain\Shortcode\GroupAttributes;
use Affibloom\Domain\Shortcode\FieldPart;
use Affibloom\Domain\Shortcode\ShortcodeAttributes;
use PHPUnit\Framework\TestCase;

/**
 * `[affibloom]`'s attributes, address, anchor and request (F31 R4, R5, A1, A2, A39, D5; Task 18S1).
 */
final class ShortcodeAttributesTest extends TestCase {

	/**
	 * R4: a broken pair under an integer key is repaired; R5: `&quot;` around a value goes; names are lower case.
	 */
	public function test_lassos_repairs(): void {
		self::assertSame(
			array(
				'id'    => '12',
				'theme' => 'Cactus',
				'badge' => 'Top',
			),
			ShortcodeAttributes::repaired(
				array(
					'id'    => '&quot;12&quot;',
					0       => 'theme="Cactus"',
					'BADGE' => 'Top',
					1       => 'plain',
				)
			)
		);
		self::assertSame( array(), ShortcodeAttributes::repaired( '' ) );
	}

	/**
	 * A1, A2: `id` as a number, `slug` beside it (the renderer lets the slug win when it finds a product).
	 */
	public function test_the_address(): void {
		$address = Address::of( array( 'id' => '42', 'slug' => ' ligra-kids ' ) );
		self::assertSame( 42, $address->id );
		self::assertSame( 'ligra-kids', $address->slug );
		self::assertSame( 0, Address::of( array( 'id' => 'abc' ) )->id );

		// A6, A7 (Task 18S6): an Amazon link and a destination, trimmed.
		$by_link = Address::of( array( 'amazon_url' => ' https://www.amazon.com/dp/B0D2JFK15W ', 'geni_url' => ' https://example.com/x ' ) );
		self::assertSame( 'https://www.amazon.com/dp/B0D2JFK15W', $by_link->amazon_url );
		self::assertSame( 'https://example.com/x', $by_link->destination );
	}

	/**
	 * A39, D5: the box's `anchor_id`, else the product and the box's number on the page.
	 */
	public function test_the_anchor(): void {
		self::assertSame( 'affibloom-anchor-id-42-2', AnchorId::of( '', 42, 2 ) );
		self::assertSame( 'mine', AnchorId::of( 'mine', 42, 2 ) );
	}

	/**
	 * The request: a single box, the address and Lasso's attribute language through BoxOverrides.
	 */
	public function test_the_request(): void {
		$request = DisplayRequest::of( array( 'id' => '42', 'theme' => 'Money', 'anchor_id' => 'here' ) );
		self::assertSame( DisplayKind::Single, $request->kind );
		self::assertSame( 42, $request->address->id );
		self::assertSame( 'Money', $request->overrides->theme );
		self::assertSame( 'here', $request->overrides->anchor_id );
	}

	/**
	 * A10, A11, A34 (Task 18S4): `type="button"`, `type="image"` / `"image-link"` / `style="image"`, `button_type`.
	 */
	public function test_the_lone_button_and_image_kinds(): void {
		self::assertSame( DisplayKind::Button, DisplayRequest::of( array( 'type' => 'Button' ) )->kind );
		self::assertSame( DisplayKind::Image, DisplayRequest::of( array( 'type' => 'image-link' ) )->kind );
		self::assertSame( DisplayKind::Image, DisplayRequest::of( array( 'style' => 'image' ) )->kind );
		self::assertSame( DisplayKind::Single, DisplayRequest::of( array( 'type' => 'other' ) )->kind );
		self::assertTrue( DisplayRequest::of( array( 'type' => 'button', 'button_type' => 'secondary' ) )->secondary );
	}

	/**
	 * A10 link, R11, A14 (Task 18S5): `type="link"`, `type="field"` and the 15 inline parts; any other part is none.
	 */
	public function test_the_link_and_inline_field_kinds(): void {
		self::assertSame( DisplayKind::Link, DisplayRequest::of( array( 'type' => 'link', 'label' => 'Buy' ) )->kind );
		self::assertSame( 'Buy', DisplayRequest::of( array( 'type' => 'link', 'label' => 'Buy' ) )->attributes['label'] );
		self::assertSame( DisplayKind::Field, DisplayRequest::of( array( 'type' => 'field', 'value' => 'price' ) )->kind );
		self::assertCount( 15, FieldPart::cases() );
		self::assertSame( FieldPart::StarRating, FieldPart::tryFrom( 'star_rating' ) );
		self::assertNull( FieldPart::tryFrom( 'bogus' ) );
	}

	/**
	 * F30 R2, R6 (Task DT2): `group` asks for a grid (a list or gallery is DT4's and DT6's); `limit` empty is all, a
	 * number its whole value, 0 or junk 10; `latest` only as `true`; a grid's anchor names the group.
	 */
	public function test_a_group_display(): void {
		self::assertSame( DisplayKind::Grid, DisplayRequest::of( array( 'group' => 'shoes' ) )->kind );
		self::assertSame( DisplayKind::Grid, DisplayRequest::of( array( 'group' => 'shoes', 'type' => 'grid' ) )->kind );
		self::assertSame( DisplayKind::Single, DisplayRequest::of( array( 'group' => 'shoes', 'type' => 'list' ) )->kind );

		self::assertNull( GroupAttributes::of( array( 'limit' => '' ) )->limit );
		self::assertNull( GroupAttributes::of( array() )->limit );
		self::assertSame( 3, GroupAttributes::of( array( 'limit' => '3' ) )->limit );
		self::assertSame( 10, GroupAttributes::of( array( 'limit' => '0' ) )->limit );
		self::assertSame( 10, GroupAttributes::of( array( 'limit' => 'all' ) )->limit );
		self::assertTrue( GroupAttributes::of( array( 'latest' => 'true' ) )->latest );
		self::assertFalse( GroupAttributes::of( array( 'latest' => '1' ) )->latest );
		self::assertSame( 'shoes', GroupAttributes::of( array( 'group' => ' shoes ' ) )->group );
		self::assertSame( 'affibloom-anchor-id-shoes-2', AnchorId::of( '', 'shoes', 2 ) );
	}

	/**
	 * F30 R13, R14, N1 (Task DT3): `columns` 1-5, else none; `compact` with PHP's true values and `hide`.
	 */
	public function test_a_grids_columns_and_compact(): void {
		self::assertSame( 4, GroupAttributes::of( array( 'columns' => '4' ) )->columns );
		self::assertNull( GroupAttributes::of( array( 'columns' => '6' ) )->columns );
		self::assertNull( GroupAttributes::of( array( 'columns' => 'x' ) )->columns );
		foreach ( array( 'true', '1', 'yes', 'on', 'hide', 'HIDE' ) as $value ) {
			self::assertTrue( GroupAttributes::of( array( 'compact' => $value ) )->compact, $value );
		}
		foreach ( array( '', 'false', 'no', '0' ) as $value ) {
			self::assertFalse( GroupAttributes::of( array( 'compact' => $value ) )->compact, $value );
		}
	}
}
