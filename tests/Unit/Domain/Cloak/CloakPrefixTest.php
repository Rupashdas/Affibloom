<?php
namespace Affibloom\Tests\Unit\Domain\Cloak;

use Affibloom\Domain\Cloak\CloakPrefix;
use PHPUnit\Framework\TestCase;

/**
 * Old Cloaked Link Prefixes keep answering (F24 N2; Task CL5).
 */
final class CloakPrefixTest extends TestCase {

	public function test_a_change_keeps_the_previous_prefix_once_and_never_the_current_one(): void {
		self::assertSame( array( '' ), CloakPrefix::history( array(), '', 'go' ) );
		self::assertSame( array( '', 'go' ), CloakPrefix::history( array( '' ), 'go', 'recommends' ) );
		self::assertSame( array( 'go', 'recommends' ), CloakPrefix::history( array( 'go', '' ), 'recommends', '' ) );
		self::assertSame( array( 'go' ), CloakPrefix::history( array( 'go' ), 'shop', 'shop' ) );
	}

	public function test_paths_are_tried_with_the_current_prefix_first(): void {
		self::assertSame( array( 'shop', 'go', '' ), CloakPrefix::all( 'shop', array( 'go', '', 'shop' ) ) );
	}
}
