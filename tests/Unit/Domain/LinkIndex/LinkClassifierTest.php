<?php
namespace Affibloom\Tests\Unit\Domain\LinkIndex;

use Affibloom\Domain\LinkIndex\AffiliatePlatform;
use Affibloom\Domain\LinkIndex\DisplayType;
use Affibloom\Domain\LinkIndex\LinkCandidate;
use Affibloom\Domain\LinkIndex\LinkClassifier;
use Affibloom\Domain\LinkIndex\LinkLocation;
use Affibloom\Domain\LinkIndex\LocationKey;
use Affibloom\Domain\LinkIndex\SiteStripe;
use PHPUnit\Framework\TestCase;

/**
 * Lasso's classification (F21 R2, R25, D9; Task 35A).
 */
final class LinkClassifierTest extends TestCase {

	private static function row( LinkCandidate $link, int $product = 0 ): LinkLocation {
		return ( new LinkClassifier( 'https://site.test' ) )->classify( $link, $product, 7, 'https://site.test/post/', 'content', 1, '2026-11-18 10:00:00' );
	}

	public function test_a_tied_link_is_a_product_link_without_a_domain(): void {
		$row = self::row( new LinkCandidate( 'https://shop.test/item', 'nofollow', '_blank', false, ' Buy  it ' ), 42 );

		self::assertSame( array( 'product', 42, null, 'Buy it', DisplayType::TEXT ), array( $row->link_type, $row->product_id, $row->domain, $row->anchor_text, $row->display_type ) );
		self::assertSame( 'https://shop.test/item', $row->original_url );
	}

	public function test_a_link_to_the_site_is_internal_whatever_its_scheme(): void {
		self::assertSame( 'internal', self::row( new LinkCandidate( 'http://site.test/about/' ) )->link_type );
		self::assertSame( 'external', self::row( new LinkCandidate( 'https://site.test.other.com/x' ) )->link_type );
		self::assertNull( self::row( new LinkCandidate( 'http://site.test/about/' ) )->domain );
	}

	public function test_an_external_link_keeps_its_domain_without_www(): void {
		self::assertSame( 'example.org', self::row( new LinkCandidate( 'https://WWW.Example.org/x' ) )->domain );
	}

	public function test_the_flags_are_lassos_exact_comparisons(): void {
		self::assertTrue( self::row( new LinkCandidate( 'https://a.test', 'nofollow', '_blank' ) )->no_follow );
		self::assertFalse( self::row( new LinkCandidate( 'https://a.test', 'nofollow noopener', '_blank' ) )->no_follow );
		self::assertFalse( self::row( new LinkCandidate( 'https://a.test', '', '_BLANK' ) )->new_window );
	}

	public function test_an_amazon_link_gives_its_asin_tag_and_store(): void {
		$row = self::row( new LinkCandidate( 'https://www.amazon.co.uk/dp/B0F21TEST1?tag=site-21', '', '', true ) );

		self::assertSame( array( 'B0F21TEST1', 'site-21', 'gb', DisplayType::IMAGE, null ), array( $row->asin, $row->tracking_id, $row->country, $row->display_type, $row->anchor_text ) );
	}

	public function test_a_long_text_is_cut_to_1500_bytes_on_a_character_boundary(): void {
		$text = self::row( new LinkCandidate( 'https://a.test', '', '', false, str_repeat( 'é', 1000 ) ) )->anchor_text;

		self::assertSame( 1500, strlen( (string) $text ) );
		self::assertTrue( mb_check_encoding( (string) $text, 'UTF-8' ) );
	}

	public function test_the_platforms_of_r2(): void {
		self::assertSame( 'skimlinks', AffiliatePlatform::of( 'https://go.skimresources.com/?id=1' ) );
		self::assertSame( 'geni.us', AffiliatePlatform::of( 'https://geni.us/abc' ) );
		self::assertSame( 'tinyurl', AffiliatePlatform::of( 'https://tinyurl.com/x' ) );
		self::assertSame( 'go', AffiliatePlatform::of( 'https://go.brand.com/x' ) );
		self::assertNull( AffiliatePlatform::of( 'https://www.example.org/' ) );
	}

	public function test_a_location_s_md5_ignores_a_trailing_slash(): void {
		self::assertSame( LocationKey::md5( 'https://a.test/x' ), LocationKey::md5( 'https://a.test/x/' ) );
	}

	public function test_a_rescan_keeps_the_first_url_the_dismissal_and_the_assignment(): void {
		$kept = self::row( new LinkCandidate( 'https://a.test/new' ) )->keeping( 'https://a.test/first', true, 9 );

		self::assertSame( array( 'https://a.test/first', true, 9, 'https://a.test/new' ), array( $kept->original_url, $kept->is_ignored, $kept->assigned_product_id, $kept->url ) );
	}

	public function test_sitestripe_markup_names_its_amazon_product_and_is_its_own_link_type(): void {
		self::assertSame( 'https://www.amazon.com/dp/B0F21TEST1/?tag=site-20', SiteStripe::amazon_url( 'https://ws-na.amazon-adsystem.com/widgets/q?ServiceVersion=20070822&asins=B0F21TEST1&tracking_id=site-20&marketplace=amazon&region=US' ) );
		self::assertSame( 'https://www.amazon.co.uk/dp/B0F21TEST1/?tag=uk-21', SiteStripe::amazon_url( 'https://ws-eu.amazon-adsystem.com/widgets/q?asin=B0F21TEST1&amp;tag=uk-21&amp;region=UK' ) );
		self::assertNull( SiteStripe::amazon_url( 'https://example.com/i.jpg?asin=B0F21TEST1' ) );

		$row = self::row( new LinkCandidate( 'https://ws-na.amazon-adsystem.com/widgets/q?asins=B0F21TEST1&tracking_id=site-20', '', '', false, '', LinkCandidate::SITESTRIPE_IFRAME ), 5 );
		self::assertSame( array( 'sitestripe', 'Product Box', 'B0F21TEST1', 'site-20', null ), array( $row->link_type, $row->display_type, $row->asin, $row->tracking_id, $row->domain ) );
	}

	public function test_an_assignment_ties_the_row_to_its_product_on_a_rescan(): void {
		$kept = self::row( new LinkCandidate( 'https://old.test/link' ) )->keeping( 'https://old.test/link', false, 12 );

		self::assertSame( array( 12, 'product', null ), array( $kept->product_id, $kept->link_type, $kept->domain ) );
	}
}
