<?php
namespace Affibloom\Tests\Unit\Interface\Front;

use Affibloom\Interface\Front\ClickScript;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use Affibloom\Tests\Unit\TestCase;

/**
 * Task 37D, finding C1: with LiteSpeed's "Load JS Deferred: Delayed" (production's setting) the click script waited for
 * the reader's first move, so the first click on a box, often the reader's first act, was not counted. The script and
 * its settings line are marked for the optimisers to leave them alone.
 */
final class ClickScriptTest extends TestCase {

	public function test_the_click_script_and_its_settings_are_never_delayed_or_combined(): void {
		$script = new ClickScript( 'https://example.test/', '1.0.0', new InMemorySettingsRepository() );
		$kept   = array(
			'data-no-defer'    => '1',
			'data-no-optimize' => '1',
			'data-cfasync'     => 'false',
		);

		self::assertSame( array( 'id' => 'affibloom-clicks-js', 'src' => 'x.js' ) + $kept, $script->script_attributes( array( 'id' => 'affibloom-clicks-js', 'src' => 'x.js' ) ) );
		self::assertSame( array( 'id' => 'affibloom-clicks-js-before' ) + $kept, $script->inline_attributes( array( 'id' => 'affibloom-clicks-js-before' ) ) );
	}

	public function test_other_scripts_are_left_as_they_are(): void {
		$script = new ClickScript( 'https://example.test/', '1.0.0', new InMemorySettingsRepository() );

		self::assertSame( array( 'id' => 'jquery-core-js' ), $script->script_attributes( array( 'id' => 'jquery-core-js' ) ) );
		self::assertSame( array( 'id' => 'affibloom-box-layout-js-before' ), $script->inline_attributes( array( 'id' => 'affibloom-box-layout-js-before' ) ) );
	}
}
