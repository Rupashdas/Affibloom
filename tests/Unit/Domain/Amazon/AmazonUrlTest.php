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

	/** Lasso's get_amazon_tracking_id_by_url: the link's "tag" parameter, '' when it has none. */
	public function test_the_links_tag_is_read_as_written(): void {
		self::assertSame( 'vbvault-20', self::amazon( 'https://www.amazon.com/dp/B0CHK12B01?tag=vbvault-20&th=1' )->tag() );
		self::assertSame( 'other-21', self::amazon( 'https://www.amazon.com/dp/B0CHK12B01?th=1&amp;tag=other-21' )->tag() );
		self::assertSame( '', self::amazon( 'https://www.amazon.com/dp/B0CHK12B01' )->tag() );
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

	public function test_a_short_link_is_not_changed_here(): void {
		// Task 12D follows short links first.
		self::assertSame( 'https://amzn.to/3NGJEvm', self::amazon( 'https://amzn.to/3NGJEvm' )->monetized( self::live(), true )->value() );
	}

	/**
	 * The four live search pages (tests/fixtures/inventory/products.json) stay byte for byte, shortened or not.
	 *
	 * @dataProvider live_searches
	 */
	public function test_the_live_search_pages_stay_byte_for_byte( string $link ): void {
		self::assertSame( $link, self::amazon( $link )->monetized( self::live(), true )->value() );
		self::assertSame( $link, self::amazon( $link )->monetized( self::live(), false )->value() );
	}

	/**
	 * @return list<array{string}>
	 */
	public static function live_searches(): array {
		return array(
			array( 'https://www.amazon.com/s?k=Nike%20KD14&linkCode=sl2&tag=vbvault-20&linkId=52794cc6e0eebddc6c2cbd97cce68d79&language=en_US&ref_=as_li_ss_tl' ),
			array( 'https://www.amazon.com/s?k=adidas%20stabil%20next%20gen&crid=2VBFORMERNLYM&sprefix=adidas%20stabil%20next%20%2Caps%2C434&linkCode=sl2&tag=vbvault-20&linkId=8a2a5e139b897dd3a48f24e051e32e2d&language=en_US&ref_=as_li_ss_tl' ),
			array( 'https://www.amazon.com/s?k=asics%20sky%20elite%20ff%20mens&crid=3PUOMEHYRLU2K&sprefix=asics%20sky%20elite%20ff%20mens%2Caps%2C369&linkCode=sl2&tag=vbvault-20&linkId=877ee10fbd25f7f6a1b4ac31c947d79f&language=en_US&ref_=as_li_ss_tl' ),
			array( 'https://www.amazon.com/s?k=nike%20react%20hyperset&crid=39BUAJWFO0GLP&sprefix=nike%20rehyperset%2Caps%2C536&linkCode=sl2&tag=vbvault-20&linkId=d9f1941855068e2f01ec70ffeb029cf1&language=en_US&ref_=as_li_ss_tl' ),
		);
	}

	public function test_a_page_keeps_its_whole_query_with_the_tag_set_where_it_is(): void {
		self::assertSame(
			'https://www.amazon.com/s?k=nike%20kd14&crid=X1&tag=vbvault-20&sprefix=a',
			self::amazon( 'https://www.amazon.com/s?k=nike+kd14&crid=X1&tag=someone-20&sprefix=a' )->monetized( self::live(), true )->value()
		);
	}

	public function test_a_page_without_a_query_gets_the_tag(): void {
		self::assertSame(
			'https://www.amazon.com/stores/Mikasa/page/1?tag=vbvault-20',
			self::amazon( 'https://www.amazon.com/stores/Mikasa/page/1' )->monetized( self::live(), true )->value()
		);
	}

	public function test_a_page_with_no_tag_to_set_stays_as_it_is(): void {
		$link = 'https://www.amazon.com/s?k=nike+kd14';

		self::assertSame( $link, self::amazon( $link )->monetized( self::live( '' ), true )->value() );
	}

	/**
	 * @dataProvider searches
	 */
	public function test_a_search_page_s_keywords( string $link, string $keywords ): void {
		self::assertSame( $keywords, self::amazon( $link )->search_keywords() );
	}

	/**
	 * @return array<string, array{string, string}>
	 */
	public static function searches(): array {
		return array(
			'k'                     => array( 'https://www.amazon.com/s?k=nike+kd14', 'nike kd14' ),
			'field-keywords'        => array( 'https://www.amazon.com/s?field-keywords=pads', 'pads' ),
			'keywords'              => array( 'https://www.amazon.com/s?keywords=pads', 'pads' ),
			'k before the others'   => array( 'https://www.amazon.com/s?keywords=b&field-keywords=c&k=a', 'a' ),
			'/s/ with a ref'        => array( 'https://www.amazon.com/s/ref=nb_sb_noss?k=pads', 'pads' ),
			'/s/ with a slash'      => array( 'https://www.amazon.com/s/?k=pads', 'pads' ),
			'slugged search'        => array( 'https://www.amazon.com/curry-flow-10/s?k=curry+flow+10&tag=x', 'curry flow 10' ),
			'no keywords'           => array( 'https://www.amazon.com/s?i=sporting', '' ),
			'not a search path'     => array( 'https://www.amazon.com/stores/x?k=pads', '' ),
			'a product link with k' => array( 'https://www.amazon.com/dp/B0C1234567?k=pads', '' ),
		);
	}

	public function test_the_search_key_is_the_host_without_www_and_the_normalised_keywords(): void {
		self::assertSame( 'amazon.com|Nike KD14', self::amazon( 'https://www.amazon.com/s?k=%20Nike++KD14%20' )->search_key() );
		self::assertSame( 'amazon.co.uk|a b', self::amazon( 'https://www.amazon.co.uk/s?k=a%2520b' )->search_key() );
		self::assertSame( 'amazon.com|tom & jerry', self::amazon( 'https://www.amazon.com/s?k=tom+%26amp%3B+jerry' )->search_key() );
		self::assertSame( '', self::amazon( 'https://www.amazon.com/stores/x' )->search_key() );
	}

	/**
	 * @dataProvider bare_asins
	 */
	public function test_a_bare_asin_becomes_the_store_link( string $input, string $link ): void {
		self::assertSame( $link, AmazonUrl::from_bare_asin( $input )?->value() );
	}

	/**
	 * @return array<string, array{string, string}>
	 */
	public static function bare_asins(): array {
		return array(
			'B0 ASIN'          => array( 'B0C1234567', 'https://www.amazon.com/dp/B0C1234567' ),
			'lower case'       => array( 'b0c1234567', 'https://www.amazon.com/dp/B0C1234567' ),
			'spaces around'    => array( "  B0C1234567 \n", 'https://www.amazon.com/dp/B0C1234567' ),
			'ISBN-10'          => array( '0140449132', 'https://www.amazon.com/dp/0140449132' ),
			'ISBN-10 ending X' => array( '014044913x', 'https://www.amazon.com/dp/014044913X' ),
		);
	}

	/**
	 * @dataProvider not_bare_asins
	 */
	public function test_other_input_is_not_a_bare_asin( string $input ): void {
		self::assertNull( AmazonUrl::from_bare_asin( $input ) );
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function not_bare_asins(): array {
		return array(
			'a word'        => array( 'BESTSHOE10' ),
			'too long'      => array( 'B0C12345678' ),
			'too short'     => array( 'B0C123456' ),
			'a link'        => array( 'https://www.amazon.com/dp/B0C1234567' ),
			'nine digits X' => array( '12345678XX' ),
		);
	}

	/**
	 * @dataProvider starting_names
	 */
	public function test_the_starting_name( string $link, string $name ): void {
		self::assertSame( $name, self::amazon( $link )->starting_name() );
	}

	/**
	 * @return array<string, array{string, string}>
	 */
	public static function starting_names(): array {
		return array(
			'slug'               => array( 'https://www.amazon.com/Mizuno-Wave-Momentum-3/dp/B0C1234567?tag=x', 'Mizuno Wave Momentum 3' ),
			'the live Mikasa'    => array( 'https://www.amazon.com/Mikasa-MGV500-Weight-Volleyball-Official/dp/B00091PQ72?tag=vbvault-20', 'Mikasa MGV500 Weight Volleyball Official' ),
			'plus and decoding'  => array( 'https://www.amazon.com/Knee+Pads%2C-Volleyball/dp/B0C1234567', 'Knee Pads, Volleyball' ),
			'spaces collapsed'   => array( 'https://www.amazon.com/-Mizuno--Wave-/dp/B0C1234567', 'Mizuno Wave' ),
			'no slug'            => array( 'https://www.amazon.com/dp/B0C1234567', 'Amazon' ),
			'gp/product'         => array( 'https://www.amazon.com/gp/product/B0C1234567', 'Amazon' ),
			'slug before gp'     => array( 'https://www.amazon.com/Mizuno/gp/product/B0C1234567', 'Amazon' ),
			'a search page'      => array( 'https://www.amazon.com/s?k=nike+kd14', 'Amazon: nike kd14' ),
			'a store page'       => array( 'https://www.amazon.com/stores/Mikasa/page/1', 'Amazon' ),
			'a short link'       => array( 'https://amzn.to/3NGJEvm', 'Amazon' ),
		);
	}

	public function test_the_search_page_title(): void {
		self::assertSame( 'Amazon: nike kd14', self::amazon( 'https://www.amazon.com/s?k=nike+kd14' )->search_page_title() );
		self::assertSame( 'Amazon', self::amazon( 'https://www.amazon.com/dp/B0C1234567' )->search_page_title() );
	}
}
