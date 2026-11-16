<?php
namespace Affibloom\Tests\Unit\Domain\Shortcode;

use Affibloom\Domain\Shortcode\Address;
use Affibloom\Domain\Shortcode\AnchorId;
use Affibloom\Domain\Shortcode\DisplayKind;
use Affibloom\Domain\Shortcode\DisplayRequest;
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
}
