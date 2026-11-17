<?php
namespace Affibloom\Tests\Unit\Infrastructure;

use Affibloom\Infrastructure\WordPress\BoxDetector;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

/**
 * Smart CSS Loading's question (F5 R46, D12; Lasso's Helper::page_needs_lasso_frontend_css).
 */
final class BoxDetectorTest extends TestCase {

	/**
	 * A single post or page with the given content and Elementor data.
	 *
	 * @param string       $content   The post content.
	 * @param string|false $elementor The `_elementor_data` meta ('' when none).
	 */
	private function single( string $content, string|false $elementor = '' ): BoxDetector {
		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'get_queried_object_id' )->justReturn( 42 );
		Functions\when( 'get_post_field' )->justReturn( $content );
		Functions\when( 'get_post_meta' )->justReturn( $elementor );

		return new BoxDetector();
	}

	public function test_a_view_that_is_not_a_single_post_may_show_boxes(): void {
		Functions\when( 'is_singular' )->justReturn( false );

		self::assertTrue( ( new BoxDetector() )->page_needs_css() );
	}

	public function test_a_post_with_affibloom_s_block_or_shortcode_needs_it(): void {
		self::assertTrue( $this->single( '<!-- wp:affibloom/product-box {"productId":7} /-->' )->page_needs_css() );
		self::assertTrue( $this->single( 'Text [affibloom id="7"] more' )->page_needs_css() );
	}

	public function test_lasso_s_markers_are_not_read(): void {
		foreach ( array( '[lasso id="3"]', '<!-- wp:affiliate-plugin/lasso /-->', 'class="lasso-display"', '[lasso type="table" id="1"]' ) as $content ) {
			self::assertFalse( $this->single( $content, false )->page_needs_css(), $content );
		}
	}

	public function test_elementor_data_with_a_box_needs_it(): void {
		self::assertTrue( $this->single( '', '[{"settings":{"shortcode":"[affibloom id=\\"7\\"]"}}]' )->page_needs_css() );
	}

	public function test_a_post_without_a_box_does_not(): void {
		self::assertFalse( $this->single( '<!-- wp:paragraph --><p>No box here.</p><!-- /wp:paragraph -->', false )->page_needs_css() );
	}
}
