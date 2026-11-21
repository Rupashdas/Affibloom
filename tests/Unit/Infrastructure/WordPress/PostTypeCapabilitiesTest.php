<?php
namespace Affibloom\Tests\Unit\Infrastructure\WordPress;

use Affibloom\Infrastructure\WordPress\PostTypeCapabilities;
use Affibloom\Infrastructure\WordPress\ProductPostType;
use Affibloom\Infrastructure\WordPress\TablePostType;
use Affibloom\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

/**
 * Task 37A, finding S1: products and tables are Affibloom's, not posts. With the post capabilities an Editor could
 * change or trash them through WordPress's own Quick Edit or XML-RPC although Affibloom is theirs to use only with
 * its capability.
 */
final class PostTypeCapabilitiesTest extends TestCase {

	public function test_every_capability_of_the_type_is_the_one_given(): void {
		$caps = PostTypeCapabilities::only( 'manage_options' );

		foreach ( array( 'edit_posts', 'edit_others_posts', 'edit_published_posts', 'edit_private_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_others_posts', 'delete_published_posts', 'delete_private_posts', 'create_posts' ) as $primitive ) {
			self::assertSame( 'manage_options', $caps[ $primitive ] ?? null, $primitive );
		}
	}

	/**
	 * @return array<string, array{0: callable(string): object, 1: string}>
	 */
	public static function types(): array {
		return array(
			'products' => array( static fn( string $cap ): object => new ProductPostType( $cap ), ProductPostType::NAME ),
			'tables'   => array( static fn( string $cap ): object => new TablePostType( $cap ), TablePostType::NAME ),
		);
	}

	/**
	 * @dataProvider types
	 *
	 * @param callable(string): object $make The post type, given its capability.
	 * @param string                   $name The post type's name.
	 */
	public function test_the_post_type_maps_its_capabilities_to_affibloom_s( callable $make, string $name ): void {
		Functions\when( '__' )->returnArg();
		$registered = null;
		Functions\expect( 'register_post_type' )->once()->andReturnUsing(
			static function ( string $type, array $args ) use ( &$registered ): void {
				$registered = array( $type, $args );
			}
		);

		$make( 'manage_options' )->register_type();

		self::assertSame( $name, $registered[0] );
		self::assertTrue( $registered[1]['map_meta_cap'] );
		self::assertSame( PostTypeCapabilities::only( 'manage_options' ), $registered[1]['capabilities'] );
	}
}
