<?php
namespace Affibloom\Tests\Unit\Domain\Link;

use Affibloom\Domain\Link\LinkAttributes;
use PHPUnit\Framework\TestCase;

/**
 * Lasso's link attributes in a box (F5 R38, R39; Lasso_URL::get_attrs, Lasso_Affiliate_Link 442-458), under
 * Affibloom's names (ADR-6).
 */
final class LinkAttributesTest extends TestCase {

	public function test_the_attributes_come_in_lassos_order(): void {
		$attributes = ( new LinkAttributes( 14727, 'adidas Ligra 8 Kids' ) )->primary( 'https://www.amazon.com/dp/B0D2JFK15W?tag=vbvault-20', true, true, false );

		self::assertSame(
			array(
				'target'                       => '_blank',
				'href'                         => 'https://www.amazon.com/dp/B0D2JFK15W?tag=vbvault-20',
				'data-affibloom-box-trackable' => 'true',
				'data-affibloom-id'            => '14727',
				'data-affibloom-name'          => 'adidas Ligra 8 Kids',
				'title'                        => 'adidas Ligra 8 Kids',
				'rel'                          => 'nofollow noopener',
			),
			$attributes
		);
	}

	/**
	 * Lasso's primary table: nofollow, then noopener for a new window, then sponsored; _blank or _self.
	 *
	 * @dataProvider primary_cases
	 */
	public function test_the_primary_rel_and_target_follow_lassos_table( bool $new_tab, bool $nofollow, bool $sponsored, string $rel, string $target ): void {
		$attributes = ( new LinkAttributes( 1, 'Shoe' ) )->primary( 'https://www.example.com/', $new_tab, $nofollow, $sponsored );

		self::assertSame( $rel, $attributes['rel'] );
		self::assertSame( $target, $attributes['target'] );
	}

	/**
	 * Every combination of the three switches.
	 *
	 * @return array<string, array{bool, bool, bool, string, string}>
	 */
	public static function primary_cases(): array {
		return array(
			'all on'              => array( true, true, true, 'nofollow noopener sponsored', '_blank' ),
			'site default here'   => array( true, true, false, 'nofollow noopener', '_blank' ),
			'nofollow sponsored'  => array( false, true, true, 'nofollow sponsored', '_self' ),
			'noopener sponsored'  => array( true, false, true, 'noopener sponsored', '_blank' ),
			'sponsored only'      => array( false, false, true, 'sponsored', '_self' ),
			'new window only'     => array( true, false, false, 'noopener', '_blank' ),
			'nofollow only'       => array( false, true, false, 'nofollow', '_self' ),
			'all off: rel stays'  => array( false, false, false, '', '_self' ),
		);
	}

	/**
	 * The secondary button carries sponsored only when the product is sponsored and its own NoFollow 2 is on.
	 */
	public function test_a_secondary_link_is_sponsored_only_with_its_own_nofollow(): void {
		$links = new LinkAttributes( 1, 'Shoe' );

		self::assertSame( 'nofollow noopener sponsored', $links->secondary( 'https://www.example.com/2', 'Check Price', true, true, true )['rel'] );
		self::assertSame( 'noopener', $links->secondary( 'https://www.example.com/2', 'Check Price', true, false, true )['rel'] );
		self::assertSame( '_self', $links->secondary( 'https://www.example.com/2', 'Check Price', false, false, true )['target'] );
	}

	/**
	 * Lasso prints a secondary button without text as a button with neither href nor target.
	 */
	public function test_a_secondary_link_without_text_has_no_href_or_target(): void {
		$attributes = ( new LinkAttributes( 1, 'Shoe' ) )->secondary( 'https://www.example.com/2', ' ', true, true, false );

		self::assertArrayNotHasKey( 'href', $attributes );
		self::assertArrayNotHasKey( 'target', $attributes );
		self::assertSame( 'nofollow noopener', $attributes['rel'] );
	}

	/**
	 * An empty name: data-…-name takes the link's domain label, capitalised (Lasso_Helper::get_name_of_domain); the
	 * title stays empty, as Lasso's does.
	 */
	public function test_an_empty_name_is_named_after_the_links_domain(): void {
		$attributes = ( new LinkAttributes( 1, ' ' ) )->primary( 'https://www.amazon.com/dp/B0D2JFK15W', true, true, false );

		self::assertSame( 'Amazon', $attributes['data-affibloom-name'] );
		self::assertSame( ' ', $attributes['title'] );
		self::assertSame( 'Amzn', ( new LinkAttributes( 1, '' ) )->primary( 'https://amzn.to/3puu6RU', true, true, false )['data-affibloom-name'] );
	}
}
