<?php
namespace Affibloom\Tests\Unit\Docs;

use Affibloom\Domain\Lifecycle\DataInventory;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Task 39, step 1: the guides keep up with the code. `docs/developer.md` names every REST route and WP-CLI command of
 * Task 37A's inventory (`docs/reviews/entry-points.md`, which `security.spec.ts` keeps true), every stored name and cron
 * hook of Task 38's `DataInventory`, the shortcode and every hook Affibloom fires; `docs/admin-guide.md` names every
 * screen of `docs/ui/screen-inventory.md`. A route, option, hook or screen added without a line in the guide fails here.
 */
final class DocsCoverageTest extends TestCase {

	private const ROOT = __DIR__ . '/../../../';

	public function test_the_developer_guide_names_every_route_command_name_and_hook(): void {
		$guide   = self::read( 'docs/developer.md' );
		$missing = array();
		foreach ( self::wanted_in_developer_guide() as $name ) {
			if ( ! str_contains( $guide, $name ) ) {
				$missing[] = $name;
			}
		}

		self::assertSame( array(), $missing, 'Missing from docs/developer.md' );
	}

	public function test_the_admin_guide_names_every_screen(): void {
		$guide   = mb_strtolower( self::read( 'docs/admin-guide.md' ) );
		$missing = array();
		foreach ( self::screens() as $screen ) {
			if ( ! str_contains( $guide, mb_strtolower( $screen ) ) ) {
				$missing[] = $screen;
			}
		}

		self::assertNotSame( array(), self::screens(), 'No screens found in docs/ui/screen-inventory.md' );
		self::assertSame( array(), $missing, 'Missing from docs/admin-guide.md' );
	}

	/**
	 * Every name the developer guide must carry.
	 *
	 * @return list<string>
	 */
	private static function wanted_in_developer_guide(): array {
		$inventory = self::read( 'docs/reviews/entry-points.md' );

		// The REST table's rows, `| `GET` | `/products/(?P<id>\d+)` | … |`, written as `/products/{id}` in the guide.
		preg_match_all( '/^\| `[A-Z]+` \| `([^`]+)` \|/m', $inventory, $rows );
		$routes = array_map( static fn( string $route ): string => (string) preg_replace( '/\(\?P<(\w+)>[^)]*\)/', '{$1}', $route ), $rows[1] );
		self::assertGreaterThan( 50, count( $routes ), 'No REST routes found in entry-points.md' );

		// The WP-CLI section's commands, `wp affibloom …`.
		preg_match_all( '/`(wp affibloom [a-z -]+)`/', $inventory, $commands );
		self::assertNotSame( array(), $commands[1], 'No WP-CLI commands found in entry-points.md' );

		// The hooks Affibloom fires or filters, and its shortcode, from the code.
		$code = '';
		foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( self::ROOT . 'src' ) ) as $file ) {
			if ( $file->isFile() && 'php' === $file->getExtension() ) {
				$code .= (string) file_get_contents( $file->getPathname() );
			}
		}
		preg_match_all( "/(?:do_action|apply_filters)\(\s*'(affibloom_[a-z0-9_]+)'/", $code, $hooks );
		preg_match_all( "/add_shortcode\(\s*'([a-z_]+)'/", $code, $shortcodes );
		self::assertNotSame( array(), $shortcodes[1], 'No shortcode found in src/' );

		return array_values(
			array_unique(
				array_merge(
					$routes,
					$commands[1],
					$hooks[1],
					array_map( static fn( string $tag ): string => '[' . $tag, $shortcodes[1] ),
					DataInventory::OPTIONS,
					DataInventory::TRANSIENTS,
					DataInventory::TRANSIENT_PREFIXES,
					DataInventory::USER_META,
					DataInventory::POST_TYPES,
					DataInventory::TAXONOMIES,
					DataInventory::TABLES,
					DataInventory::CRON_HOOKS,
					array( DataInventory::POST_META_PREFIX, DataInventory::LOG_FOLDER )
				)
			)
		);
	}

	/**
	 * The screens of the screen inventory: its `## ` headings from "Header" on, without the capture names in brackets
	 * and without "Not captured".
	 *
	 * @return list<string>
	 */
	private static function screens(): array {
		preg_match_all( '/^## (.+)$/m', self::read( 'docs/ui/screen-inventory.md' ), $headings );
		$screens = array();
		foreach ( $headings[1] as $heading ) {
			$screen = trim( (string) preg_replace( '/\s*\(.*\)\s*$/', '', $heading ) );
			if ( str_starts_with( $screen, 'Findings' ) || 'Not captured' === $screen ) {
				continue;
			}
			$screens[] = $screen;
		}

		return $screens;
	}

	private static function read( string $path ): string {
		$file = self::ROOT . $path;
		self::assertFileExists( $file );

		return (string) file_get_contents( $file );
	}
}
