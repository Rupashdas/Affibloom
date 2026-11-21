<?php
namespace Affibloom\Tests\Unit\Application\Clicks;

use Affibloom\Application\Clicks\ClickInput;
use Affibloom\Application\Clicks\ClickTracker;
use Affibloom\Application\Clicks\PostDirectory;
use Affibloom\Application\Clicks\RepeatGuard;
use Affibloom\Domain\Clicks\Button;
use Affibloom\Domain\Clicks\ClickStore;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * A reader's click (F12 AC4, AC5, AC14, D2, D4, D9, edge cases 1, 2, 4, 6; Task 33A).
 */
final class ClickTrackerTest extends TestCase {

	private InMemoryProductRepository $products;

	/** @var list<string> */
	private array $added = array();

	private ClickTracker $tracker;

	private RepeatGuard $guard;

	private InMemorySettingsRepository $settings;

	private const READER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36';

	protected function setUp(): void {
		$this->products = new InMemoryProductRepository();
		$test           = $this;
		$store          = new class( $test ) implements ClickStore {
			public function __construct( private ClickTrackerTest $test ) {}

			public function add( string $day, int $product_id, int $post_id, int $location, Button $button ): void {
				$this->test->added( "{$day} {$product_id} {$post_id} {$location} {$button->value}" );
			}

			public function totals_since( string $day, array $product_ids ): array {
				return array();
			}

			public function total_since( string $day ): int {
				return 'x' === $day ? 0 : 41;
			}

			public function delete_before( string $day ): void {
				$this->test->added( "before {$day}" );
			}
		};
		$posts         = new class() implements PostDirectory {
			public function is_public( int $post_id ): bool {
				return 50 === $post_id;
			}
		};
		$guard         = new class() implements RepeatGuard {
			/** @var array<string, true> */
			public array $seen = array();

			public function first( string $key ): bool {
				if ( isset( $this->seen[ $key ] ) ) {
					return false;
				}
				$this->seen[ $key ] = true;
				return true;
			}
		};
		$this->guard   = $guard;
		$this->settings = new InMemorySettingsRepository( array() );
		$this->tracker  = new ClickTracker( $this->products, $posts, $store, $guard, $this->settings );
	}

	public function added( string $row ): void {
		$this->added[] = $row;
	}

	public function test_a_click_on_a_published_product_in_a_public_post_is_a_day_row(): void {
		$id  = $this->product( 'ball' );
		$now = (int) gmmktime( 23, 59, 59, 11, 21, 2026 );

		self::assertTrue( $this->tracker->track( new ClickInput( $id, 50, 7, Button::from_input( '2' ) ), $now, self::READER, '1.2.3.4' ) );
		self::assertTrue( $this->tracker->track( new ClickInput( $id, 0, 0, Button::from_input( 'link' ) ), $now + 1, self::READER, '1.2.3.4' ) );
		self::assertSame( array( "2026-11-21 {$id} 50 7 2", "2026-11-22 {$id} 0 0 link" ), $this->added );
	}

	public function test_unknown_trashed_and_hidden_post_clicks_are_ignored_and_a_repoint_counts_for_its_target(): void {
		$kept    = $this->product( 'kept' );
		$retired = $this->product( 'retired' );
		$this->products->retire( $this->products->get( $retired ), $kept );
		$button = Button::from_input( '1' );

		self::assertFalse( $this->tracker->track( new ClickInput( 999, 50, 0, $button ), 0, self::READER, 'a' ) );
		self::assertFalse( $this->tracker->track( new ClickInput( $kept, 51, 0, $button ), 0, self::READER, 'a' ) );
		self::assertTrue( $this->tracker->track( new ClickInput( $retired, 50, 0, $button ), 0, self::READER, 'a' ) );
		self::assertSame( array( "1970-01-01 {$kept} 50 0 1" ), $this->added );
	}

	/** Task 33B (F12 D5, D6, AC6, AC7). */
	public function test_machines_and_repeats_are_not_counted_but_another_reader_or_button_is(): void {
		$id    = $this->product( 'ball' );
		$click = new ClickInput( $id, 50, 0, Button::from_input( '1' ) );

		self::assertFalse( $this->tracker->track( $click, 0, 'Googlebot/2.1', 'a' ) );
		self::assertFalse( $this->tracker->track( $click, 0, '', 'a' ) );
		self::assertTrue( $this->tracker->track( $click, 0, self::READER, 'a' ) );
		self::assertFalse( $this->tracker->track( $click, 1, self::READER, 'a' ) );
		self::assertTrue( $this->tracker->track( $click, 1, self::READER, 'b' ) );
		self::assertTrue( $this->tracker->track( new ClickInput( $id, 50, 0, Button::from_input( '2' ) ), 1, self::READER, 'a' ) );
		self::assertCount( 3, $this->added );
	}

	/** Task 33D (F12 D7, AC9): off, nothing is counted; on again, counting resumes. */
	public function test_page_and_link_level_tracking_off_counts_nothing(): void {
		$id = $this->product( 'ball' );
		$this->settings->save( Settings::from_array( array( 'click_tracking' => false ) ) );

		self::assertFalse( $this->tracker->track( new ClickInput( $id, 50, 0, Button::from_input( '1' ) ), 0, self::READER, 'a' ) );
		$this->settings->save( Settings::from_array( array( 'click_tracking' => true ) ) );
		self::assertTrue( $this->tracker->track( new ClickInput( $id, 50, 0, Button::from_input( '1' ) ), 0, self::READER, 'a' ) );
	}

	public function test_the_body_is_read_strictly(): void {
		$click = ClickInput::from_body( '{"p":"12","h":5,"l":"3","b":"image"}' );

		self::assertNotNull( $click );
		self::assertSame( array( 12, 5, 3, 'image' ), array( $click->product_id, $click->post_id, $click->location, $click->button->value ) );
		self::assertNull( ClickInput::from_body( '{"p":"x","b":"1"}' ) );
		self::assertNull( ClickInput::from_body( '{"p":3,"b":"0"}' ) );
		self::assertNull( ClickInput::from_body( '{"p":3,"b":"buy"}' ) );
		self::assertNull( ClickInput::from_body( 'not json' ) );
		self::assertNull( ClickInput::from_body( '{"p":3,"b":"1","x":"' . str_repeat( 'a', 1100 ) . '"}' ) );
		self::assertSame( '99', Button::from_input( 99 )?->value );
		self::assertNull( Button::from_input( '100' ) );
	}

	private function product( string $name ): int {
		return (int) $this->products->save( Product::draft( $name, AffiliateUrl::from_string( 'https://www.example.com/' . $name ), Settings::from_array( array() ) ) )->id;
	}
}
