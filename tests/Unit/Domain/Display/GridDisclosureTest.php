<?php
namespace Affibloom\Tests\Unit\Domain\Display;

use Affibloom\Domain\Display\GridDisclosure;
use PHPUnit\Framework\TestCase;

/**
 * The grid's disclosure (F30 R17, D5; Task DT2).
 */
final class GridDisclosureTest extends TestCase {

	/**
	 * Classic: `enable` or the setting (`disable` not read); customised: `enable` and `disable` win, else the setting.
	 *
	 * @dataProvider cases
	 */
	public function test_the_grid_disclosure( bool $customised, string $attribute, bool $setting, bool $shows ): void {
		self::assertSame( $shows, GridDisclosure::shows( $customised, $attribute, $setting ) );
	}

	/**
	 * @return array<string, array{bool, string, bool, bool}>
	 */
	public static function cases(): array {
		return array(
			'classic, setting on'             => array( false, '', true, true ),
			'classic, setting off'            => array( false, '', false, false ),
			'classic, enable'                 => array( false, 'enable', false, true ),
			'classic, disable is not read'    => array( false, 'disable', true, true ),
			'customised, setting on'          => array( true, '', true, true ),
			'customised, enable beats off'    => array( true, 'enable', false, true ),
			'customised, disable beats on'    => array( true, 'disable', true, false ),
			'customised, junk is the setting' => array( true, 'maybe', false, false ),
		);
	}
}
