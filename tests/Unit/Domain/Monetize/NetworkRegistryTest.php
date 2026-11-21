<?php
namespace Affibloom\Tests\Unit\Domain\Monetize;

use Affibloom\Domain\Monetize\NetworkRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Lasso's affiliate-network registry (F35 R2-R8, D2; Task C2A): resources/networks.php read by NetworkRegistry.
 */
final class NetworkRegistryTest extends TestCase {

	private static ?NetworkRegistry $registry = null;

	/** @var array<string, mixed>|null */
	private static ?array $data = null;

	private static function data(): array {
		self::$data ??= require dirname( __DIR__, 4 ) . '/resources/networks.php';
		return self::$data;
	}

	private static function registry(): NetworkRegistry {
		self::$registry ??= NetworkRegistry::from_array( self::data() );
		return self::$registry;
	}

	public function test_the_data_holds_lassos_counts(): void {
		$data = self::data();
		self::assertCount( 37, $data['networks'] );
		self::assertCount( 32, array_filter( $data['networks'], static fn( array $entry ): bool => '' !== $entry['integration'] ) );
		self::assertCount( 805, $data['impact_hosts'] );
		self::assertCount( 21, $data['amazon_hosts'] );
	}

	/**
	 * One link of each network, in Lasso's file order.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function links(): array {
		return array(
			'awin'               => array( 'awin', 'https://www.awin1.com/cread.php?awinmid=1&awinaffid=2&ued=https%3A%2F%2Fshop.example%2F' ),
			'cj'                 => array( 'cj', 'https://www.anrdoezrs.net/click-1-2?url=https%3A%2F%2Fshop.example%2F' ),
			'financeads'         => array( 'financeads', 'https://www.financeads.net/tc.php?t=1' ),
			'impact'             => array( 'impact', 'https://goto.walmart.com/c/1/2/3?u=https%3A%2F%2Fwww.walmart.com%2F' ),
			'shareasale'         => array( 'shareasale', 'https://shareasale.com/r.cfm?b=1&u=2&m=3&urllink=shop.example' ),
			'amazon_associates'  => array( 'amazon_associates', 'https://www.amazon.com/dp/B0TEST0001' ),
			'partnerize'         => array( 'partnerize', 'https://prf.hn/click/camref:1/destination:https%3A%2F%2Fshop.example%2F' ),
			'flexoffers'         => array( 'flexoffers', 'https://track.flexlinks.com/a.ashx?url=https%3A%2F%2Fshop.example%2F' ),
			'refersion'          => array( 'refersion', 'https://shop.refersion.com/l/abc' ),
			'postaffiliatepro'   => array( 'postaffiliatepro', 'https://shop.example/p#a_aid=me' ),
			'avantlink'          => array( 'avantlink', 'https://www.avantlink.com/click.php?tt=cl&mi=1&url=x' ),
			'pepperjam'          => array( 'pepperjam', 'https://www.pjtra.com/t/abc' ),
			'everflow'           => array( 'everflow', 'https://everflow.io/abc' ),
			'tune'               => array( 'tune', 'https://go2cloud.org/aff_c?offer_id=1&aff_id=2' ),
			'partnerstack'       => array( 'partnerstack', 'https://link.technologyadvice.com/abc' ),
			'goaffpro'           => array( 'goaffpro', 'https://goaffpro.shop.com/?ref=1' ),
			'aragon'             => array( 'aragon', 'https://jwclicks.com/abc' ),
			'sovrn'              => array( 'sovrn', 'https://redirect.viglink.com/?u=https%3A%2F%2Fshop.example%2F' ),
			'rakuten'            => array( 'rakuten', 'https://click.linksynergy.com/deeplink?id=1&mid=2&murl=https%3A%2F%2Fshop.example%2F' ),
			'skimlinks'          => array( 'skimlinks', 'https://go.skimresources.com/?id=1X&xs=1&url=https%3A%2F%2Fshop.example%2F' ),
			'agoda'              => array( 'agoda', 'https://www.agoda.com/partners/partnersearch.aspx?cid=123' ),
			'bol'                => array( 'bol', 'https://partner.bol.com/click/click?p=1&url=x' ),
			'booking'            => array( 'booking', 'https://www.booking.com/hotel.html?aid=123' ),
			'cake'               => array( 'cake', 'https://shop.example/?a=1&b=2&c=3' ),
			'clickbank'          => array( 'clickbank', 'https://vendor.hop.clickbank.net/' ),
			'commission_factory' => array( 'commission_factory', 'https://t.cfjump.com/123/x' ),
			'daisycon'           => array( 'daisycon', 'https://abc.net/c/?si=123' ),
			'ebay'               => array( 'ebay', 'https://www.ebay.com/itm/1?campid=5' ),
			'firstpromoter'      => array( 'firstpromoter', 'https://shop.example/?fpr=me' ),
			'get_your_guide'     => array( 'get_your_guide', 'https://www.getyourguide.com/tour?partner_id=1' ),
			'idevaffiliate'      => array( 'idevaffiliate', 'https://shop.example/idevaffiliate.php?id=12' ),
			'linkconnector'      => array( 'linkconnector', 'https://www.linkconnector.com/ta.php?lc=1&url=x' ),
			'maxbounty'          => array( 'maxbounty', 'https://mb102.com/lnk.asp?o=1' ),
			'rentalcarscom'      => array( 'rentalcarscom', 'https://www.rentalcars.com/x?affiliateCode=abc' ),
			'tradedoubler'       => array( 'tradedoubler', 'https://clk.tradedoubler.com/click?p=1&a=2&url=x' ),
			'tradetracker'       => array( 'tradetracker', 'https://tc.tradetracker.net/?c=1&m=2&a=3' ),
			'webgains'           => array( 'webgains', 'https://track.webgains.com/click.html?wglinkid=1&wgprogramid=2&wgtarget=x' ),
		);
	}

	/** @dataProvider links */
	public function test_each_network_is_the_first_match_for_its_link( string $slug, string $url ): void {
		self::assertSame( $slug, self::registry()->match( $url )?->slug );
		self::assertNull( self::registry()->match( 'https://plain.example/page', true, $slug ), 'a plain link is not ' . $slug );
	}

	public function test_every_entry_has_a_link_in_the_provider_in_lassos_order(): void {
		self::assertSame( array_keys( self::links() ), array_map( static fn( $network ): string => $network->slug, self::registry()->all() ) );
	}

	public function test_amazon_is_left_out_when_asked_and_a_slug_narrows_the_search(): void {
		self::assertNull( self::registry()->match( 'https://www.amazon.com/dp/B0TEST0001', false ) );
		self::assertNull( self::registry()->match( 'https://goto.walmart.com/c/1/2/3', true, 'awin' ) );
		self::assertSame( 'impact', self::registry()->match( 'https://goto.walmart.com/c/1/2/3', true, 'impact' )?->slug );
	}

	public function test_impact_reads_the_host_or_the_host_without_its_first_label(): void {
		self::assertSame( 'impact', self::registry()->match( 'https://mizunousa.sjv.io/jR4ZQP' )?->slug );
		self::assertSame( 'impact', self::registry()->match( 'https://www.sjv.io/x' )?->slug );
	}

	public function test_lassos_loose_patterns_are_kept_as_written(): void {
		self::assertSame( 'cake', self::registry()->match( 'https://x.example/?a=1&x&c=2' )?->slug );
		self::assertSame( 'flexoffers', self::registry()->match( 'https://shop.example/go/fxo.co' )?->slug );
		self::assertSame( 'tradetracker', self::registry()->match( 'https://shop.example/?tt=1' )?->slug );
	}

	public function test_which_networks_lasso_detects(): void {
		$detected = array_map( static fn( $network ): string => $network->slug, array_filter( self::registry()->all(), static fn( $network ): bool => ! $network->is_detected() ) );
		self::assertSame( array( 'amazon_associates', 'everflow', 'aragon', 'commission_factory', 'firstpromoter' ), array_values( $detected ) );
	}

	public function test_lassos_base_domain(): void {
		self::assertSame( 'shop.example', NetworkRegistry::base_domain( 'www.shop.example/x' ) );
		self::assertSame( 'sjv.io', NetworkRegistry::base_domain( 'https://mizunousa.sjv.io/x', false ) );
		self::assertSame( 'sjv.io', NetworkRegistry::base_domain( 'https://sjv.io/x', false ) );
	}
}
