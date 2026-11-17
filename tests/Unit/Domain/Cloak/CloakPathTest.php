<?php
namespace Affibloom\Tests\Unit\Domain\Cloak;

use Affibloom\Domain\Cloak\CloakPath;
use PHPUnit\Framework\TestCase;

/**
 * Lasso's matching of a cloaked path (F24 R16, R17, D4, N6; Task CL1).
 */
final class CloakPathTest extends TestCase {

	/**
	 * @return array<string, array{0: string, 1: string, 2: string, 3: string|null}>
	 */
	public static function paths(): array {
		return array(
			'a one-part path'                 => array( '/aircat/', '', '', 'aircat' ),
			'without its trailing slash'      => array( '/aircat', '', '', 'aircat' ),
			'its query is not part of it'     => array( '/aircat/?utm_source=ig', '', '', 'aircat' ),
			'upper case is the same slug'     => array( '/AirCat/', '', '', 'aircat' ),
			'two parts without a prefix'      => array( '/go/aircat/', '', '', null ),
			'the home page'                   => array( '/', '', '', null ),
			'the prefix and the slug'         => array( '/go/aircat/', 'go', '', 'aircat' ),
			'one part with a prefix'          => array( '/aircat/', 'go', '', null ),
			'another first part'              => array( '/other/aircat/', 'go', '', null ),
			'longer paths are not matched'    => array( '/a/b/aircat/', '', '', null ),
			'the favicon'                     => array( '/favicon.ico', '', '', null ),
			'an admin path'                   => array( '/admin/', '', '', null ),
			'a site in a subdirectory'        => array( '/blog/aircat/', '', '/blog', 'aircat' ),
			'outside the site\'s directory'   => array( '/aircat/', '', '/blog', null ),
		);
	}

	/**
	 * @dataProvider paths
	 */
	public function test_the_slug_a_path_names( string $uri, string $prefix, string $home, ?string $slug ): void {
		self::assertSame( $slug, CloakPath::slug( $uri, $prefix, $home ) );
	}
}
