<?php
namespace Affibloom\Tests\Unit\Application;

use Affibloom\Application\Products\FetchedPage;
use Affibloom\Application\Products\LinkNormaliser;
use Affibloom\Application\Products\LinkResolver;
use Affibloom\Tests\Unit\Fakes\FakePageFetcher;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use Affibloom\Tests\Unit\TestCase;

/**
 * A button's link as Lasso stores its second button's (save_lasso_url 1152-1160, F1 requirements 9 S4; F29 D5):
 * geni.us to its destination unless kept, https added, Amazon links tagged by the site's rule, never refused.
 */
final class LinkNormaliserTest extends TestCase {
	private FakePageFetcher $fetcher;

	protected function setUp(): void {
		parent::setUp();
		$this->fetcher = new FakePageFetcher();
		// esc_url_raw as WordPress does for these inputs: a link without a scheme gets http://, spaces go.
		\Brain\Monkey\Functions\when( 'esc_url_raw' )->alias(
			static function ( $url ) {
				$url = str_replace( ' ', '', (string) $url );
				return '' === $url || preg_match( '#^[a-z][a-z0-9+.\-]*://#i', $url ) ? $url : 'http://' . $url;
			}
		);
	}

	/**
	 * @param array<string, mixed> $settings Settings changed from the defaults.
	 */
	private function links( array $settings = array() ): LinkNormaliser {
		return new LinkNormaliser( new LinkResolver( $this->fetcher ), new InMemorySettingsRepository( $settings + array( 'tracking_id' => 'vbvault-20' ) ) );
	}

	public function test_an_empty_link_stays_empty(): void {
		self::assertSame( '', $this->links()->button( '   ' ) );
	}

	public function test_a_link_without_a_scheme_gets_https(): void {
		self::assertSame( 'https://www.example.com/second', $this->links()->button( ' www.example.com/second ' ) );
	}

	public function test_an_amazon_link_gets_the_sites_tag_in_place_of_another(): void {
		// Shortened as the add flow stores it (Task 12A, Lasso's keep_args): the site's tag first, ref_ kept, psc dropped.
		self::assertSame(
			'https://www.amazon.com/dp/B0C7654321?tag=vbvault-20&ref_=x',
			$this->links()->button( 'https://www.amazon.com/dp/B0C7654321?tag=someone-20&psc=1&ref_=x' )
		);
	}

	public function test_short_and_protected_amazon_links_are_kept_as_typed(): void {
		self::assertSame( 'https://amzn.to/3abcDEF', $this->links()->button( 'https://amzn.to/3abcDEF' ) );
		$maas = 'https://www.amazon.com/dp/B0C7654321?maas=maas_adg_123&tag=maas';
		self::assertSame( $maas, $this->links()->button( $maas ) );
		self::assertSame( array(), $this->fetcher->asked ); // an Amazon short link is not followed for a button
	}

	public function test_a_geni_us_link_becomes_its_destination_unless_kept(): void {
		$this->fetcher = new FakePageFetcher( array( 'https://geni.us/abc' => new FetchedPage( 200, 'https://www.example.com/landing', '' ) ) );

		self::assertSame( 'https://www.example.com/landing', $this->links( array( 'keep_genius_formatting' => false ) )->button( 'https://geni.us/abc' ) );
		self::assertSame( 'https://geni.us/abc', $this->links( array( 'keep_genius_formatting' => true ) )->button( 'https://geni.us/abc' ) );
	}

	public function test_a_geni_us_link_that_leads_to_amazon_is_tagged(): void {
		$this->fetcher = new FakePageFetcher( array( 'https://geni.us/amz' => new FetchedPage( 200, 'https://www.amazon.com/dp/B0C7654321?tag=other-20', '' ) ) );

		self::assertSame( 'https://www.amazon.com/dp/B0C7654321?tag=vbvault-20', $this->links( array( 'keep_genius_formatting' => false ) )->button( 'https://geni.us/amz' ) );
	}

	public function test_something_that_is_not_a_link_is_cleaned_not_refused(): void {
		self::assertSame( 'http://notalink', $this->links()->button( 'not a link' ) );
	}
}
