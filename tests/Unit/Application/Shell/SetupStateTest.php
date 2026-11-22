<?php
namespace Affibloom\Tests\Unit\Application\Shell;

use Affibloom\Application\Shell\SetupFlag;
use Affibloom\Application\Shell\SetupState;
use PHPUnit\Framework\TestCase;

/**
 * F38 N1, D13, AC10, AC11 (Tasks ON6, ON7): the wizard is due only after an activation on a site without Affibloom's
 * settings, opens by itself once, and finishing it ends it.
 */
final class SetupStateTest extends TestCase {

	private function flag( string $state ): SetupFlag {
		return new class( $state ) implements SetupFlag {
			public function __construct( public string $state ) {}
			public function get(): string {
				return $this->state;
			}
			public function set( string $state ): void {
				$this->state = $state;
			}
		};
	}

	public function test_due_only_without_settings_and_only_once(): void {
		$configured = $this->flag( '' );
		( new SetupState( $configured ) )->on_activation( true );
		self::assertSame( '', $configured->get() );

		$fresh = $this->flag( '' );
		$state = new SetupState( $fresh );
		$state->on_activation( false );
		self::assertSame( SetupState::PENDING, $fresh->get() );
		self::assertTrue( $state->take_first_visit() );
		self::assertFalse( $state->take_first_visit() );
		self::assertSame( SetupState::SHOWN, $fresh->get() );
	}

	public function test_a_second_activation_does_not_make_it_due_again_and_finishing_ends_it(): void {
		$flag  = $this->flag( SetupState::SHOWN );
		$state = new SetupState( $flag );
		$state->on_activation( false );
		self::assertFalse( $state->take_first_visit() );

		$state->finish();
		self::assertSame( SetupState::DONE, $flag->get() );
	}
}
