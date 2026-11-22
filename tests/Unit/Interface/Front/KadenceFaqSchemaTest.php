<?php
namespace Affibloom\Tests\Unit\Interface\Front;

use Affibloom\Interface\Front\KadenceFaqSchema;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

/**
 * Lasso's Kadence FAQ schema repairs (pages/class-hook-frontend.php 60-106; Task GF4).
 */
final class KadenceFaqSchemaTest extends TestCase {

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		// No unit test loads WordPress; the repair only asks whether it has a post (as LinkIndexHooksTest).
		if ( ! class_exists( \WP_Post::class ) ) {
			eval( 'final class WP_Post { public $ID = 0; public $post_type = "post"; public function __construct( $post ) {} }' ); // phpcs:ignore Squiz.PHP.Eval.Discouraged -- a test double for a WordPress class.
		}
	}

	public function test_an_faq_with_no_questions_prints_nothing(): void {
		$schema = new KadenceFaqSchema();

		self::assertSame( '', $schema->drop_empty( '<script type="application/ld+json">{"@type":"FAQPage","mainEntity": [ ]}</script>' ) );
		$full = '<script type="application/ld+json">{"@type":"FAQPage","mainEntity":[{"@type":"Question"}]}</script>';
		self::assertSame( $full, $schema->drop_empty( $full ) );
		self::assertSame( '', $schema->drop_empty( '' ) );
		self::assertNull( $schema->drop_empty( null ) );
	}

	public function test_a_post_with_a_yoast_faq_block_skips_kadences_faq(): void {
		$post = new \WP_Post( (object) array( 'ID' => 7 ) );
		Functions\when( 'get_post' )->justReturn( $post );
		Functions\expect( 'has_block' )->with( 'yoast/faq-block', $post )->andReturn( true, false );
		$schema = new KadenceFaqSchema();

		self::assertFalse( $schema->skip_beside_yoast( true ) );
		self::assertTrue( $schema->skip_beside_yoast( true ) );
		self::assertFalse( $schema->skip_beside_yoast( false ) );
	}

	public function test_no_post_leaves_kadence_as_it_is(): void {
		Functions\when( 'get_post' )->justReturn( null );

		self::assertTrue( ( new KadenceFaqSchema() )->skip_beside_yoast( true ) );
	}
}
