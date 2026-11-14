<?php
namespace Affibloom\Tests\Unit\Interface\Cli;

use Affibloom\Application\Upgrade\UpgradeResult;
use Affibloom\Interface\Cli\UpgradeCommand;
use PHPUnit\Framework\TestCase;

/**
 * wp affibloom upgrade (Task TL2): what it says for each outcome, and whether that is an error.
 */
final class UpgradeCommandTest extends TestCase {
	public function test_each_outcome_has_its_words(): void {
		self::assertSame(
			array( false, 'Nothing to do: the database is up to date.' ),
			UpgradeCommand::report( UpgradeResult::nothing() )
		);
		self::assertSame(
			array( false, 'Ran 2 steps (1, 2). The database is up to date.' ),
			UpgradeCommand::report( UpgradeResult::done( array( 1, 2 ) ) )
		);
		self::assertSame(
			array( true, 'Another upgrade is running. Try again in five minutes.' ),
			UpgradeCommand::report( UpgradeResult::locked() )
		);
		self::assertSame(
			array( true, 'Step 3 failed: Table full. Steps before it are kept; the next run starts at step 3.' ),
			UpgradeCommand::report( UpgradeResult::failed( 3, 'Table full' ) )
		);
	}
}
