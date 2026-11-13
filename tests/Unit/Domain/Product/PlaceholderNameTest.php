<?php
namespace Affibloom\Tests\Unit\Domain\Product;

use Affibloom\Domain\Product\PlaceholderName;
use PHPUnit\Framework\TestCase;

/**
 * Lasso's placeholder names (Lasso_Affiliate_Link::is_placeholder_link_title): a product named so takes the new
 * Amazon product's name when its link changes, whatever "Keep title when URL replaced" says (F1 requirements 9, S8).
 */
final class PlaceholderNameTest extends TestCase {
	public function test_lassos_placeholder_names(): void {
		foreach ( array( '', '   ', 'Amazon', 'Add a Link Title', 'Lasso', 'add-a-link-title', 'ADD-A-LINK-TITLE', ' Amazon ' ) as $name ) {
			self::assertTrue( PlaceholderName::is( $name ), "'$name' is a placeholder" );
		}
	}

	public function test_real_names_are_not(): void {
		foreach ( array( 'Mizuno Wave Momentum 3', 'amazon', 'Amazon Basics Ball', 'lasso' ) as $name ) {
			self::assertFalse( PlaceholderName::is( $name ), "'$name' is a name" );
		}
	}
}
