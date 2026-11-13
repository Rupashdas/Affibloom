<?php
namespace Affibloom\Tests\Unit\Domain\Amazon;

use Affibloom\Domain\Amazon\AmazonUrl;
use Affibloom\Domain\Amazon\TrackingIds;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Settings\Settings;
use PHPUnit\Framework\TestCase;

// F2 requirements, sections 2-3 (Lasso_Amazon_Api::get_product_id_by_url, get_country_by_url, get_amazon_product_url,
// keep_args, format_amazon_url; Lasso_Helper::is_amazon_sponsored_url).
final class AmazonUrlTest extends TestCase {
	private static function amazon( string $link ): AmazonUrl {
		$url = AmazonUrl::from( AffiliateUrl::from_string( $link ) );
		self::assertNotNull( $url, $link );
		return $url;
	}

	/** The live settings: vbvault-20, multiple IDs on, whitelist [vbvault-20]. */
	private static function live( string $site = 'vbvault-20' ): TrackingIds {
		return TrackingIds::from_settings(
			Settings::from_array(
				array(
					'tracking_id'           => $site,
					'tracking_id_whitelist' => array( 'vbvault-20' ),
				)
			)
		);
	}

	public function test_only_amazon_links_are_amazon_urls(): void {
		self::assertNull( AmazonUrl::from( AffiliateUrl::from_string( 'https://mizuno.pxf.io/c/1?u=x' ) ) );
		self::assertNull( AmazonUrl::from( AffiliateUrl::from_string( 'https://www.amazonx.com/dp/B0C1234567' ) ) );
	}

	/**
	 * @dataProvider asin_forms
	 */
	public function test_finds_the_asin_in_lasso_s_path_forms( string $link, string $asin ): void {
		self::assertSame( $asin, self::amazon( $link )->asin() );
	}

	/**
	 * @return array<string, array{string, string}>
	 */
	public static function asin_forms(): array {
		return array(
			'dp with slug and ref'   => array( 'https://www.amazon.com/Mizuno-Wave/dp/B0C1234567/ref=sr_1_3?keywords=x', 'B0C1234567' ),
			'dp, no www'             => array( 'https://amazon.com/dp/B0C1234567', 'B0C1234567' ),
			'gp/product'             => array( 'https://www.amazon.com/gp/product/B0C1234567?th=1', 'B0C1234567' ),
			'mobile gp/aw/d'         => array( 'https://www.amazon.com/gp/aw/d/B0C1234567', 'B0C1234567' ),
			'd/x/y'                  => array( 'https://www.amazon.com/d/Volleyballs/Mikasa-V200W/B07XYZ1234', 'B07XYZ1234' ),
			'clp'                    => array( 'https://www.amazon.com/clp/B0C1234567', 'B0C1234567' ),
			'ASIN'                   => array( 'https://www.amazon.com/ASIN/B0C1234567', 'B0C1234567' ),
			'video'                  => array( 'https://www.amazon.com/gp/video/detail/B0C1234567/ref=x', 'B0C1234567' ),
			'the last marker counts' => array( 'https://www.amazon.com/dp/B0AAAAAAAA/gp/product/B0BBBBBBBB', 'B0BBBBBBBB' ),
			'case kept'              => array( 'https://www.amazon.com/dp/b0c1234567', 'b0c1234567' ),
			'host case ignored'      => array( 'https://WWW.Amazon.com/dp/B0C1234567', 'B0C1234567' ),
			'uk store'               => array( 'https://www.amazon.co.uk/dp/B0C1234567', 'B0C1234567' ),
			'the live Mikasa link'   => array( 'https://www.amazon.com/Mikasa-MGV500-Weight-Volleyball-Official/dp/B00091PQ72?tag=vbvault-20&ref_=as_li_ss_tl', 'B00091PQ72' ),
		);
	}

	/**
	 * @dataProvider without_asin
	 */
	public function test_an_amazon_page_that_is_not_one_product_has_no_asin( string $link ): void {
		self::assertSame( '', self::amazon( $link )->asin() );
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function without_asin(): array {
		return array(
			'search'         => array( 'https://www.amazon.com/s?k=volleyball' ),
			'store'          => array( 'https://www.amazon.com/stores/Mikasa/page/1' ),
			'nothing after'  => array( 'https://www.amazon.com/dp/?tag=x' ),
			'query, no path' => array( 'https://www.amazon.com?u=/dp/B0C1234567' ),
			'a short link'   => array( 'https://amzn.to/3NGJEvm' ),
		);
	}

	/**
	 * @dataProvider countries
	 */
	public function test_the_country_is_the_store_s_code( string $link, string $country ): void {
		self::assertSame( $country, self::amazon( $link )->country() );
	}

	/**
	 * @return list<array{string, string}>
	 */
	public static function countries(): array {
		return array(
			array( 'https://www.amazon.com/dp/B0C1234567', 'us' ),
			array( 'https://amazon.com/dp/B0C1234567', 'us' ),
			array( 'https://www.amazon.co.uk/dp/B0C1234567', 'gb' ),
			array( 'https://www.amazon.de/dp/B0C1234567', 'de' ),
			array( 'https://www.amazon.com.au/dp/B0C1234567', 'au' ),
			array( 'https://www.amazon.ca/dp/B0C1234567', 'ca' ),
			array( 'https://www.amazon.co.jp/dp/B0C1234567', 'jp' ),
			array( 'https://smile.amazon.com/dp/B0C1234567', 'us' ),
		);
	}

	/**
	 * @dataProvider protected_links
	 */
	public function test_protected_attribution_links_are_stored_as_pasted( string $link ): void {
		self::assertTrue( self::amazon( $link )->is_protected() );
		self::assertSame( $link, self::amazon( $link )->monetized( self::live(), true )->value() );
		self::assertSame( $link, self::amazon( $link )->monetized( self::live(), false )->value() );
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function protected_links(): array {
		return array(
			'maas'                => array( 'https://www.amazon.com/dp/B0C1234567?maas=maas_adg_123&ref_=aa_maas&tag=maas' ),
			'creator connections' => array( 'https://www.amazon.com/dp/B0C1234567?campaignId=amzn1.campaign.ABC&tag=other-20' ),
			'linkCode tr1'        => array( 'https://www.amazon.com/dp/B0C1234567?linkCode=tr1&linkId=abc&tag=other-20' ),
		);
	}

	public function test_maas_without_tag_maas_is_not_protected(): void {
		self::assertFalse( self::amazon( 'https://www.amazon.com/dp/B0C1234567?maas=x&tag=other-20' )->is_protected() );
	}

	public function test_the_live_links_stay_byte_for_byte(): void {
		$link = 'https://www.amazon.com/Mikasa-MGV500-Weight-Volleyball-Official/dp/B00091PQ72?tag=vbvault-20&ref_=as_li_ss_tl';

		self::assertSame( $link, self::amazon( $link )->monetized( self::live(), true )->value() );
	}

	public function test_shortened_the_path_stays_the_tag_goes_first_and_only_lasso_s_parameters_are_kept(): void {
		$link = 'https://www.amazon.com/Mizuno-Wave/dp/B0C1234567/ref=sr_1_3?keywords=shoe&linkId=9&tag=someone-20&linkCode=ll1&th=1&ref_=x';

		self::assertSame(
			'https://www.amazon.com/Mizuno-Wave/dp/B0C1234567/ref=sr_1_3?tag=vbvault-20&ref_=x&linkCode=ll1&linkId=9',
			self::amazon( $link )->monetized( self::live(), true )->value()
		);
	}

	public function test_shortened_every_kept_parameter_in_lasso_s_order(): void {
		$link = 'https://www.amazon.com/dp/B0C1234567?linkId=l&linkCode=c&campaignId=cid&aa_adgroupid=g&aa_creativeid=r&aa_campaignid=a&s=books&ref_=f';

		self::assertSame(
			'https://www.amazon.com/dp/B0C1234567?tag=vbvault-20&ref_=f&s=books&aa_campaignid=a&aa_creativeid=r&aa_adgroupid=g&campaignId=cid&linkCode=c&linkId=l',
			self::amazon( $link )->monetized( self::live(), true )->value()
		);
	}

	public function test_shortened_maas_leaves_the_tag_out(): void {
		self::assertSame(
			'https://www.amazon.com/dp/B0C1234567?maas=x',
			self::amazon( 'https://www.amazon.com/dp/B0C1234567?maas=x&tag=other-20' )->monetized( self::live(), true )->value()
		);
	}

	public function test_shortened_with_no_tag_at_all_there_is_no_query(): void {
		self::assertSame(
			'https://www.amazon.com/dp/B0C1234567',
			self::amazon( 'https://www.amazon.com/dp/B0C1234567?th=1' )->monetized( self::live( '' ), true )->value()
		);
	}

	public function test_shortened_a_fragment_is_dropped(): void {
		self::assertSame(
			'https://www.amazon.com/dp/B0C1234567?tag=vbvault-20',
			self::amazon( 'https://www.amazon.com/dp/B0C1234567#reviews' )->monetized( self::live(), true )->value()
		);
	}

	public function test_shortened_kept_values_are_encoded_again(): void {
		self::assertSame(
			'https://www.amazon.com/dp/B0C1234567?tag=vbvault-20&ref_=a%26b',
			self::amazon( 'https://www.amazon.com/dp/B0C1234567?ref_=a%26b' )->monetized( self::live(), true )->value()
		);
	}

	public function test_not_shortened_the_whole_query_stays_with_the_tag_replaced_in_place(): void {
		self::assertSame(
			'https://www.amazon.com/dp/B0C1234567?th=1&tag=vbvault-20&psc=1',
			self::amazon( 'https://www.amazon.com/dp/B0C1234567?th=1&tag=someone-20&psc=1' )->monetized( self::live(), false )->value()
		);
	}

	public function test_not_shortened_the_tag_is_added_last_and_values_are_rawurlencoded(): void {
		self::assertSame(
			'https://www.amazon.com/dp/B0C1234567?th=1&x=a%20b&tag=vbvault-20',
			self::amazon( 'https://www.amazon.com/dp/B0C1234567?th=1&x=a+b' )->monetized( self::live(), false )->value()
		);
	}

	public function test_smile_becomes_amazon_in_a_product_link(): void {
		self::assertSame(
			'https://amazon.com/dp/B0C1234567?tag=vbvault-20',
			self::amazon( 'https://smile.amazon.com/dp/B0C1234567?tag=x-20' )->monetized( self::live(), true )->value()
		);
	}

	public function test_an_amazon_page_without_an_asin_is_not_changed_yet(): void {
		// Task 12C monetizes Amazon pages that are not one product; 12D follows short links.
		foreach ( array( 'https://www.amazon.com/s?k=volleyball&tag=someone-20', 'https://amzn.to/3NGJEvm' ) as $link ) {
			self::assertSame( $link, self::amazon( $link )->monetized( self::live(), true )->value() );
		}
	}
}
