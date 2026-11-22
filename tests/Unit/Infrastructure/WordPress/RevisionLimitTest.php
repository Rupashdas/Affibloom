<?php
namespace Affibloom\Tests\Unit\Infrastructure\WordPress;

use Affibloom\Infrastructure\WordPress\RevisionLimit;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

/**
 * Lasso's revision limit (pages/class-hook.php 322-330, LASSO_REVISIONS_TO_KEEP; Task GF3): eight revisions for the
 * post types it scans, every other type as WordPress or the site says.
 */
final class RevisionLimitTest extends TestCase {

	private function limit( string $type, int $num, array $chosen = array() ): int {
		Functions\when( 'get_post_type' )->justReturn( $type );

		return ( new RevisionLimit( new InMemorySettingsRepository( array( 'scan_post_types' => $chosen ) ) ) )->keep( $num, new \stdClass() );
	}

	public function test_posts_and_pages_keep_eight(): void {
		self::assertSame( 8, $this->limit( 'post', -1 ) );
		self::assertSame( 8, $this->limit( 'page', 25 ) );
	}

	public function test_a_chosen_type_keeps_eight_too(): void {
		self::assertSame( 8, $this->limit( 'recipe', -1, array( 'recipe' ) ) );
	}

	public function test_other_types_keep_what_they_had(): void {
		self::assertSame( -1, $this->limit( 'recipe', -1 ) );
		self::assertSame( 3, $this->limit( 'affibloom_product', 3 ) );
	}
}
