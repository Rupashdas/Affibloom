<?php
namespace Affibloom\Tests\Unit\Domain\Settings;

use Affibloom\Domain\Settings\Permission;
use PHPUnit\Framework\TestCase;

/**
 * F37 R31, R35, AC7; F9 R67 (Task TL6; decision C3): the minimum role and the capability that marks it, Lasso's mapping
 * with its Contributor / Subscriber swap corrected; Administrator by default, and anything unknown falls back to it.
 */
final class PermissionTest extends TestCase {

	public function test_each_role_maps_to_the_capability_that_marks_it(): void {
		self::assertSame( 'manage_options', Permission::of( 'administrator' )->capability() );
		self::assertSame( 'edit_others_posts', Permission::of( 'editor' )->capability() );
		self::assertSame( 'publish_posts', Permission::of( 'author' )->capability() );
		self::assertSame( 'edit_posts', Permission::of( 'contributor' )->capability() );
		self::assertSame( 'read', Permission::of( 'subscriber' )->capability() );
	}

	public function test_an_unknown_or_empty_value_fails_closed_to_administrators(): void {
		self::assertSame( Permission::Administrator, Permission::of( '' ) );
		self::assertSame( Permission::Administrator, Permission::of( 'manage_access' ) );
		self::assertSame( 'manage_options', Permission::of( 'superuser' )->capability() );
	}
}
