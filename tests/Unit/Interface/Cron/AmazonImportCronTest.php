<?php
namespace Affibloom\Tests\Unit\Interface\Cron;

use Affibloom\Application\Monetize\AmazonImport;
use Affibloom\Application\Products\LinkNormaliser;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Application\Products\ProductInput;
use Affibloom\Application\Products\ProductService;
use Affibloom\Application\Upgrade\UpgradeLock;
use Affibloom\Infrastructure\WordPress\AmazonImportSchedule;
use Affibloom\Interface\Cron\AmazonImportCron;
use Affibloom\Tests\Unit\Fakes\FakePageFetcher;
use Affibloom\Tests\Unit\Fakes\InMemoryAmazonProducts;
use Affibloom\Tests\Unit\Fakes\InMemoryLinkLocations;
use Affibloom\Tests\Unit\Fakes\InMemoryProductRepository;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

/**
 * Saving the Auto-Monetization switch (F35 R23, D18, D23; F35 AC5, AC8; Task AM3): on starts the import at once, off
 * stops it, anything else leaves it.
 */
final class AmazonImportCronTest extends TestCase {

	private function cron(): AmazonImportCron {
		$products = new InMemoryProductRepository();
		$resolver = new LinkResolver( new FakePageFetcher() );
		$settings = new InMemorySettingsRepository();
		$links    = new LinkNormaliser( $resolver, $settings );
		$service  = new ProductService( $products, $resolver, $settings, new ProductInput( $links ), $links, InMemoryAmazonProducts::without_amazon() );

		return new AmazonImportCron( new AmazonImport( new InMemoryLinkLocations(), $service, $products ), new AmazonImportSchedule(), $this->createStub( UpgradeLock::class ), $settings );
	}

	public function test_switched_on_starts_the_import_now(): void {
		Functions\expect( 'wp_clear_scheduled_hook' )->once()->with( 'affibloom_amazon_import' );
		Functions\expect( 'wp_schedule_event' )->once()->with( \Mockery::on( static fn( $at ): bool => abs( $at - time() ) < 5 ), 'affibloom_five_minutes', 'affibloom_amazon_import' );

		$this->cron()->switched( array( 'auto_monetize_amazon' => false ), array( 'auto_monetize_amazon' => true ) );
	}

	public function test_switched_off_stops_it(): void {
		Functions\expect( 'wp_clear_scheduled_hook' )->once()->with( 'affibloom_amazon_import' );
		Functions\expect( 'wp_schedule_event' )->never();

		$this->cron()->switched( array( 'auto_monetize_amazon' => true ), array( 'auto_monetize_amazon' => false ) );
	}

	public function test_a_save_that_leaves_it_on_changes_nothing(): void {
		Functions\expect( 'wp_clear_scheduled_hook' )->never();
		Functions\expect( 'wp_schedule_event' )->never();

		$this->cron()->switched( array( 'auto_monetize_amazon' => true ), array( 'auto_monetize_amazon' => true, 'tracking_id' => 'x-20' ) );
	}
}
