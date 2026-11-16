<?php
namespace Affibloom\Tests\Unit\Domain\Shortcode;

use Affibloom\Domain\Shortcode\Address;
use Affibloom\Domain\Shortcode\AnchorId;
use Affibloom\Domain\Shortcode\DisplayKind;
use Affibloom\Domain\Shortcode\DisplayRequest;
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
}
