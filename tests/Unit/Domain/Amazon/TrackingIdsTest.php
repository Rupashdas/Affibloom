<?php
namespace Affibloom\Tests\Unit\Domain\Amazon;

use Affibloom\Domain\Amazon\TrackingIds;
use Affibloom\Domain\Settings\Settings;
use PHPUnit\Framework\TestCase;

// Lasso_Amazon_Api::get_amazon_product_url(), the tag lines (F2 requirements, 3.3).
final class TrackingIdsTest extends TestCase {
	/**
	 * @param list<string> $whitelist
	 */
	private function ids( string $site, bool $multiple, array $whitelist ): TrackingIds {
		return TrackingIds::from_settings(
			Settings::from_array(
				array(
					'tracking_id'           => $site,
					'multiple_tracking_ids' => $multiple,
					'tracking_id_whitelist' => $whitelist,
				)
			)
		);
	}

	public function test_the_site_id_replaces_a_tag_that_is_not_allowed(): void {
		$ids = $this->ids( 'vbvault-20', true, array( 'vbvault-20', 'other-21' ) );

		self::assertSame( 'vbvault-20', $ids->tag_for( 'someone-20' ) );
		self::assertSame( 'vbvault-20', $ids->tag_for( '' ) );
	}

	public function test_a_whitelisted_tag_is_kept_while_multiple_ids_are_on(): void {
		self::assertSame( 'other-21', $this->ids( 'vbvault-20', true, array( 'vbvault-20', 'other-21' ) )->tag_for( 'other-21' ) );
	}

	public function test_the_whitelist_does_not_count_while_multiple_ids_are_off(): void {
		self::assertSame( 'vbvault-20', $this->ids( 'vbvault-20', false, array( 'vbvault-20', 'other-21' ) )->tag_for( 'other-21' ) );
	}

	public function test_maas_is_always_allowed(): void {
		self::assertSame( 'maas', $this->ids( 'vbvault-20', false, array() )->tag_for( 'maas' ) );
	}

	public function test_without_a_site_id_the_pasted_tag_stays(): void {
		$ids = $this->ids( '', true, array() );

		self::assertSame( 'someone-20', $ids->tag_for( 'someone-20' ) );
		self::assertSame( '', $ids->tag_for( '' ) );
	}

	public function test_the_site_id_is_trimmed_as_lasso_trims_it(): void {
		self::assertSame( 'vbvault-20', $this->ids( ' vbvault-20 ', true, array() )->tag_for( 'x-20' ) );
	}

	/** F27 R19 (Task V4): a tag that is any product's Amazon variant is kept as pasted. */
	public function test_an_amazon_variant_tag_is_kept(): void {
		$variants          = new \Affibloom\Tests\Unit\Fakes\InMemoryVariantRepository();
		$variants->saved[] = new \Affibloom\Domain\Variant\Variant( 1, 9, 'Instagram', 'insta-20', \Affibloom\Domain\Variant\VariantKind::AmazonTag );
		$variants->saved[] = new \Affibloom\Domain\Variant\Variant( 2, 9, 'Shoe page', 'shoe-20', \Affibloom\Domain\Variant\VariantKind::Permalink );
		$ids               = TrackingIds::from_settings( Settings::from_array( array( 'tracking_id' => 'vbvault-20' ) ), $variants );

		self::assertSame( 'insta-20', $ids->tag_for( 'insta-20' ) );
		self::assertSame( 'vbvault-20', $ids->tag_for( 'shoe-20' ) );
		self::assertSame( 'vbvault-20', $ids->tag_for( 'other-20' ) );
	}
}
