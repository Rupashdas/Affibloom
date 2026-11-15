<?php
namespace Affibloom\Tests\Integration;

use WP_UnitTestCase;

/**
 * The Product box block on the front end (Task 18A; F5 R1, AC3): registered, rendered on the server, and empty for a
 * product that is missing, a draft, or retired without a published target.
 * Runs only where wp-env is available (progress.md, deviation 10).
 */
final class ProductBoxBlockTest extends WP_UnitTestCase {

	/**
	 * A published product.
	 *
	 * @param string $status Its status.
	 */
	private function product( string $status = 'publish' ): int {
		return self::factory()->post->create(
			array(
				'post_type'   => 'affibloom_product',
				'post_status' => $status,
				'post_title'  => 'Ligra 8',
				'meta_input'  => array(
					'_affibloom_url'             => 'https://www.amazon.com/dp/B0D2JFK15W?tag=vbvault-20',
					'_affibloom_new_tab'         => '1',
					'_affibloom_nofollow'        => '1',
					'_affibloom_show_disclosure' => '1',
				),
			)
		);
	}

	public function test_the_block_prints_the_products_box(): void {
		$id   = $this->product();
		$html = do_blocks( '<!-- wp:affibloom/product-box {"productId":' . $id . '} /-->' );

		$this->assertStringContainsString( '<!-- PRODUCT BOX -->', $html );
		$this->assertStringContainsString( 'class="affibloom-box"', $html );
		$this->assertStringContainsString( 'data-affibloom-id="' . $id . '"', $html );
		$this->assertStringContainsString( 'rel="nofollow noopener"', $html );
	}

	public function test_a_draft_or_missing_product_prints_nothing(): void {
		$this->assertSame( '', trim( do_blocks( '<!-- wp:affibloom/product-box {"productId":' . $this->product( 'draft' ) . '} /-->' ) ) );
		$this->assertSame( '', trim( do_blocks( '<!-- wp:affibloom/product-box {"productId":999999} /-->' ) ) );
	}

	public function test_a_retired_product_prints_its_target(): void {
		$target  = $this->product();
		$retired = $this->product( 'affibloom_retired' );
		update_post_meta( $retired, '_affibloom_repointed_to', (string) $target );

		$this->assertStringContainsString( 'data-affibloom-id="' . $target . '"', do_blocks( '<!-- wp:affibloom/product-box {"productId":' . $retired . '} /-->' ) );
	}
}
