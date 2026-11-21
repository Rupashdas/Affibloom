<?php
namespace Affibloom\Tests\Unit\Application\Opportunities;

use Affibloom\Application\Opportunities\IntroBoxes;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * The intro boxes (F22 R6, D8; Task O1).
 */
final class IntroBoxesTest extends TestCase {

	public function test_a_closed_box_stays_closed_and_help_tips_off_hides_them_all(): void {
		$settings = new InMemorySettingsRepository( array() );
		$boxes    = new IntroBoxes( $settings );

		self::assertTrue( $boxes->shows( 'links' ) );
		$boxes->hide( 'links' );
		$boxes->hide( 'links' );
		$boxes->hide( 'keywords' );
		self::assertFalse( $boxes->shows( 'links' ) );
		self::assertTrue( $boxes->shows( 'content' ) );
		self::assertSame( array( 'links' ), $settings->get()->list( 'education_hidden' ) );

		$off = new IntroBoxes( new InMemorySettingsRepository( array( 'disable_help_tips' => true ) ) );
		self::assertFalse( $off->shows( 'content' ) );
	}
}
