<?php
namespace Affibloom\Tests\Unit\Interface\Admin;

use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Admin\PermissionCaps;
use Affibloom\Tests\Unit\Fakes\InMemorySettingsRepository;
use Affibloom\Tests\Unit\TestCase;

/**
 * F37 AC7 (Task TL6): a user with the chosen role's capability gets Affibloom's two; anyone else gets nothing.
 */
final class PermissionCapsTest extends TestCase {

	private function caps( string $permission ): PermissionCaps {
		return new PermissionCaps( new InMemorySettingsRepository( array( 'permission' => $permission ) ) );
	}

	public function test_the_chosen_role_and_above_get_affibloom_s_capabilities(): void {
		$editor = array( 'edit_posts' => true, 'publish_posts' => true, 'edit_others_posts' => true );

		$granted = $this->caps( 'editor' )->grant( $editor );

		self::assertTrue( $granted[ Capabilities::PRODUCTS ] );
		self::assertTrue( $granted[ Capabilities::SETTINGS ] );
	}

	public function test_a_lower_role_and_an_unknown_setting_get_nothing(): void {
		$author = array( 'edit_posts' => true, 'publish_posts' => true );

		self::assertSame( $author, $this->caps( 'editor' )->grant( $author ) );
		self::assertSame( $author, $this->caps( 'nonsense' )->grant( $author ) );
	}
}
