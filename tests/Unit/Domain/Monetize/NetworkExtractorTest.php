<?php
namespace Affibloom\Tests\Unit\Domain\Monetize;

use Affibloom\Domain\Monetize\NetworkExtractor;
use Affibloom\Domain\Monetize\NetworkRegistry;
use PHPUnit\Framework\TestCase;

/**
 * What a network link carries (F35 R7, R9, Appendix B; Task C2A).
 */
final class NetworkExtractorTest extends TestCase {

	private static ?NetworkRegistry $registry = null;

	private static function read( string $url ): array {
		self::$registry ??= NetworkRegistry::from_array( require dirname( __DIR__, 4 ) . '/resources/networks.php' );
		$network = self::$registry->match( $url, false );
		self::assertNotNull( $network, $url );
		$link = NetworkExtractor::read( $network, $url );

		return array( $link->network->integration, $link->affiliate_id, $link->advertiser_id, $link->deep_link, $link->domain );
	}

	public function test_impact_reads_both_path_shapes_and_its_u(): void {
		self::assertSame( array( 'Impact', '3512787', '9383', 'https://www.walmart.com/ip/1', 'walmart.com' ), self::read( 'https://goto.walmart.com/c/3512787/565706/9383?u=https%3A%2F%2Fwww.walmart.com%2Fip%2F1' ) );
		self::assertSame( array( 'Impact', 'jR4ZQP', '', '', '' ), self::read( 'https://mizunousa.sjv.io/jR4ZQP' ) );
	}

	public function test_ids_are_read_case_insensitively_cut_at_u0026_and_with_amp(): void {
		self::assertSame( array( 'ShareASale', '2', '3', 'shop.example', 'shop.example' ), self::read( 'https://shareasale.com/r.cfm?b=1&amp;U=2u0026x&amp;merchantID=3&amp;urllink=shop.example' ) );
		self::assertSame( array( 'Awin', '567', '1234', 'https://www.shop.example/x', 'shop.example' ), self::read( 'https://www.awin1.com/cread.php?awinmid=1234&awinaffid=567&ued=https%3A%2F%2Fwww.shop.example%2Fx' ) );
	}

	public function test_partnerize_takes_everything_after_destination(): void {
		self::assertSame( array( 'Partnerize', '', '', 'https://shop.example/a?b=1', 'shop.example' ), self::read( 'https://prf.hn/click/camref:1/destination:https%3A%2F%2Fshop.example%2Fa%3Fb%3D1' ) );
	}

	public function test_the_other_rules_of_appendix_b(): void {
		self::assertSame( array( 'Rakuten', '1', '2', 'https://shop.example/', 'shop.example' ), self::read( 'https://click.linksynergy.com/deeplink?id=1&mid=2&murl=https%3A%2F%2Fshop.example%2F' ) );
		self::assertSame( array( 'Webgains', '1', '2', 'x', 'x' ), self::read( 'https://track.webgains.com/click.html?wglinkid=1&wgprogramid=2&wgtarget=x' ) );
		self::assertSame( array( 'Tradetracker', '3', '2', '', '' ), self::read( 'https://tc.tradetracker.net/?c=1&m=2&a=3' ) );
		self::assertSame( array( 'TUNE (HasOffers)', '2', '', '', '' ), self::read( 'https://go2cloud.org/aff_c?offer_id=1&aff_id=2' ) );
		self::assertSame( array( 'AvantLink', '', '1', 'x', 'x' ), self::read( 'https://www.avantlink.com/click.php?tt=cl&merchant_id=1&url=x' ) );
		self::assertSame( array( 'MaxbBunty', '', '', '', '' ), self::read( 'https://mb102.com/lnk.asp?o=1' ) );
	}
}
