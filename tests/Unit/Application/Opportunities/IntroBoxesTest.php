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

	/**
	 * F38 R35, AC9 (Task ON5): `&reseteducation=1` on an opportunities page brings every closed box back.
	 */
	public function test_reset_brings_every_closed_box_back(): void {
		$settings = new InMemorySettingsRepository( array( 'education_hidden' => array( 'links', 'content' ) ) );
		$boxes    = new IntroBoxes( $settings );

		$boxes->reset();

		self::assertTrue( $boxes->shows( 'links' ) );
		self::assertTrue( $boxes->shows( 'content' ) );
		self::assertSame( array(), $settings->get()->list( 'education_hidden' ) );
	}
}
