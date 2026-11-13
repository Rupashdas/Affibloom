<?php
namespace Affibloom\Tests\Unit\Domain\Product;

use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Settings\Settings;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase {
	private function url(): AffiliateUrl {
		return AffiliateUrl::from_string( 'https://www.example.com/p' );
	}

	public function test_a_draft_copies_the_sites_link_defaults(): void {
		$product = Product::draft( 'Example', $this->url(), Settings::from_array( array() ) );

		// Fresh-install defaults (docs/spec/interface.md, section 5): New Window, NoFollow, Show Disclosure on.
		self::assertTrue( $product->new_tab );
		self::assertTrue( $product->nofollow );
		self::assertFalse( $product->sponsored );
		self::assertTrue( $product->show_disclosure );
	}

	public function test_each_default_follows_its_setting(): void {
		$settings = Settings::from_array(
			array(
				'new_tab'         => false,
				'nofollow'        => false,
				'sponsored'       => true,
				'show_disclosure' => false,
			)
		);

		$product = Product::draft( 'Example', $this->url(), $settings );

		self::assertFalse( $product->new_tab );
		self::assertFalse( $product->nofollow );
		self::assertTrue( $product->sponsored );
		self::assertFalse( $product->show_disclosure );
	}

	public function test_a_draft_has_only_its_default_button(): void {
		$product = Product::draft( 'Example', $this->url(), Settings::from_array( array() ) );

		self::assertSame( '', $product->button_text );
		self::assertSame( array(), $product->buttons->all() );
		self::assertSame( 0, $product->buttons->main_position() );
	}

	/**
	 * Lasso's display fields start "not set": the type's theme, the site's Show Price and disclosure, no badge, price,
	 * description or Prime logo (get_lasso_url's defaults).
	 */
	public function test_a_draft_leaves_its_display_fields_to_the_sites_defaults(): void {
		$product = Product::draft( 'Example', $this->url(), Settings::from_array( array() ) );

		self::assertNull( $product->theme );
		self::assertNull( $product->show_price );
		self::assertSame( '', $product->badge );
		self::assertSame( '', $product->price );
		self::assertSame( '', $product->description );
		self::assertSame( '', $product->disclosure );
		self::assertFalse( $product->prime );
		self::assertSame( 0, $product->image_id );
	}

	public function test_a_copy_keeps_the_defaults(): void {
		$product = Product::draft( 'Example', $this->url(), Settings::from_array( array( 'sponsored' => true ) ) );

		self::assertTrue( $product->with( array( 'id' => 7 ) )->sponsored );
	}
}
