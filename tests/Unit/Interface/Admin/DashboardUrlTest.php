<?php
declare( strict_types=1 );

namespace Affibloom\Tests\Unit\Interface\Admin;

use Affibloom\Domain\Product\LinkKind;
use Affibloom\Interface\Admin\DashboardUrl;
use PHPUnit\Framework\TestCase;

/**
 * The list's arguments (Task 13F): what the address and the GET forms' hidden inputs carry.
 */
final class DashboardUrlTest extends TestCase {

	public function test_defaults_carry_nothing(): void {
		self::assertSame( array(), DashboardUrl::args( '', LinkKind::All, 1, 10 ) );
	}

	public function test_every_argument_away_from_its_default_is_carried_as_a_string(): void {
		self::assertSame(
			array(
				's'         => 'wave',
				'link_type' => 'amazon',
				'group'     => '-1',
				'post_id'   => '123',
				'per_page'  => '25',
				'paged'     => '3',
			),
			DashboardUrl::args( 'wave', LinkKind::Amazon, 3, 25, -1, 123 )
		);
	}

	public function test_a_post_id_of_zero_is_no_filter(): void {
		self::assertArrayNotHasKey( 'post_id', DashboardUrl::args( '', LinkKind::All, 1, 10, 0, 0 ) );
	}
}
