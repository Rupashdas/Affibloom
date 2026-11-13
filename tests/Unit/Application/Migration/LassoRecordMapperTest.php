<?php
namespace Affibloom\Tests\Unit\Application\Migration;

use Affibloom\Application\Migration\LassoRecordMapper;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Migration\LassoRecord;
use Affibloom\Domain\Product\ProductType;
use PHPUnit\Framework\TestCase;

/**
 * Lasso's record → an Affibloom product, by Lasso's own reading rules (Lasso_Affiliate_Link::get_lasso_url() and
 * get_lasso_thumbnail(); Phase 5, Task 25's mapping table). The one-time staging import's mapping.
 */
final class LassoRecordMapperTest extends TestCase {

	/**
	 * @param array<string, string>      $meta   Lasso's post meta.
	 * @param array<string, string>|null $amazon Lasso's lasso_amazon_products row.
	 * @param array<string, mixed>       $more   Other LassoRecord arguments by name.
	 */
	private function record( array $meta = array(), ?array $amazon = null, array $more = array() ): LassoRecord {
		return new LassoRecord(
			...array_merge(
				array(
					'id'           => 14001,
					'title'        => 'Mizuno Wave Momentum 3',
					'date'         => '2024-01-02 03:04:05',
					'modified'     => '2025-06-07 08:09:10',
					'meta'         => $meta,
					'redirect_url' => 'https://www.example.com/shoe',
					'amazon'       => $amazon,
				),
				$more
			)
		);
	}

	private function amazon_row(): array {
		return array(
			'default_product_name' => 'Mizuno Wave Momentum 3 Volleyball Shoe',
			'monetized_url'        => 'https://www.amazon.com/dp/B0ABCDEFGH?tag=vbvault-20',
			'base_url'             => 'https://www.amazon.com/dp/B0ABCDEFGH',
			'default_image'        => 'https://m.media-amazon.com/images/I/shoe.jpg',
			'latest_price'         => '$139.99',
			'is_prime'             => '1',
		);
	}

	private function map( LassoRecord $record ) {
		return ( new LassoRecordMapper() )->map( $record );
	}

	public function test_an_amazon_record_uses_its_monetized_url_asin_and_store(): void {
		$product = $this->map(
			$this->record(
				array(),
				$this->amazon_row(),
				array(
					'product_type' => 'amazon',
					'product_id'   => 'B0ABCDEFGH',
					'country'      => 'us',
				)
			)
		);

		self::assertSame( 'https://www.amazon.com/dp/B0ABCDEFGH?tag=vbvault-20', $product->url->value() );
		self::assertSame( ProductType::Amazon, $product->type );
		self::assertSame( 'B0ABCDEFGH', $product->asin );
		self::assertSame( 'us', $product->amazon_country );
		self::assertTrue( $product->prime );
		self::assertSame( '$139.99', $product->price );
	}

	public function test_another_record_uses_url_details_then_the_custom_redirect(): void {
		self::assertSame( 'https://www.example.com/shoe', $this->map( $this->record() )->url->value() );

		$product = $this->map( $this->record( array( 'lasso_custom_redirect' => 'https://www.example.com/fallback' ), null, array( 'redirect_url' => '' ) ) );
		self::assertSame( 'https://www.example.com/fallback', $product->url->value() );
		self::assertSame( ProductType::Other, $product->type );
	}

	public function test_a_record_with_no_link_is_left_out(): void {
		self::assertNull( $this->map( $this->record( array(), null, array( 'redirect_url' => '' ) ) ) );
	}

	public function test_a_link_that_is_not_a_link_is_refused(): void {
		$this->expectException( InvalidField::class );
		$this->map( $this->record( array(), null, array( 'redirect_url' => 'not a link' ) ) );
	}

	public function test_the_name_is_decoded_and_amazon_uses_the_product_name(): void {
		self::assertSame( 'Tape & Wrap', $this->map( $this->record( array(), null, array( 'title' => 'Tape &amp; Wrap' ) ) )->name );
		self::assertSame( 'Mizuno Wave Momentum 3 Volleyball Shoe', $this->map( $this->record( array(), $this->amazon_row(), array( 'title' => 'Amazon' ) ) )->name );
	}

	public function test_toggles_are_on_only_when_lasso_stored_one(): void {
		$product = $this->map(
			$this->record(
				array(
					'open_new_tab'     => '1',
					'enable_nofollow'  => '0',
					'enable_sponsored' => '',
					'show_disclosure'  => '1',
				)
			)
		);

		self::assertTrue( $product->new_tab );
		self::assertFalse( $product->nofollow );
		self::assertFalse( $product->sponsored );
		self::assertTrue( $product->show_disclosure );
	}

	public function test_the_second_button_becomes_the_first_added_button(): void {
		$product = $this->map(
			$this->record(
				array(
					'second_btn_url'   => ' https://www.example.com/second ',
					'second_btn_text'  => 'See Price',
					'open_new_tab2'    => '1',
					'enable_nofollow2' => '',
				)
			)
		);

		self::assertSame(
			array(
				array(
					'url'      => 'https://www.example.com/second',
					'text'     => 'See Price',
					'new_tab'  => true,
					'nofollow' => false,
				),
			),
			$product->buttons->to_array()
		);
	}

	public function test_texts_theme_and_show_price_are_carried_over(): void {
		$product = $this->map(
			$this->record(
				array(
					'buy_btn_text'    => 'Buy Now',
					'badge_text'      => 'Best Overall',
					'affiliate_desc'  => '<p>A light, fast shoe.</p>',
					'disclosure_text' => 'We earn a commission.',
					'custom_theme'    => 'splash',
					'show_price'      => '0',
					'price'           => '$20',
				)
			)
		);

		self::assertSame( 'Buy Now', $product->button_text );
		self::assertSame( 'Best Overall', $product->badge );
		self::assertSame( '<p>A light, fast shoe.</p>', $product->description );
		self::assertSame( 'We earn a commission.', $product->disclosure );
		self::assertSame( 'Splash', $product->theme?->name() );
		self::assertFalse( $product->show_price );
		self::assertSame( '$20', $product->price );
	}

	public function test_unset_theme_and_show_price_mean_the_site_defaults(): void {
		$product = $this->map( $this->record() );

		self::assertNull( $product->theme );
		self::assertNull( $product->show_price );
	}

	public function test_a_short_description_switched_off_is_hidden_as_lasso_does(): void {
		self::assertSame(
			'',
			$this->map(
				$this->record(
					array(
						'affiliate_desc'   => 'Short',
						'show_description' => '0',
					)
				)
			)->description
		);
		self::assertSame( 'Short', $this->map( $this->record( array( 'affiliate_desc' => 'Short' ) ) )->description );
	}

	public function test_the_image_follows_lassos_order_and_drops_its_placeholder(): void {
		$custom = $this->record( array( 'lasso_custom_thumbnail' => 'https://cdn.example.com/custom.jpg' ), $this->amazon_row(), array( 'thumbnail_url' => 'https://cdn.example.com/post.jpg' ) );
		self::assertSame( 'https://cdn.example.com/custom.jpg', $this->map( $custom )->image_url );

		$post    = $this->record(
			array(),
			$this->amazon_row(),
			array(
				'thumbnail_url' => 'https://cdn.example.com/post.jpg',
				'thumbnail_id'  => 77,
			)
		);
		$product = $this->map( $post );
		self::assertSame( 'https://cdn.example.com/post.jpg', $product->image_url );
		self::assertSame( 77, $product->image_id );

		self::assertSame( 'https://m.media-amazon.com/images/I/shoe.jpg', $this->map( $this->record( array(), $this->amazon_row() ) )->image_url );

		$placeholder = $this->record( array( 'lasso_thumbnail_url' => 'https://site.test/wp-content/plugins/lasso/admin/assets/images/lasso-no-thumbnail.webp' ) );
		self::assertSame( '', $this->map( $placeholder )->image_url );
	}
}
