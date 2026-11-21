<?php
namespace Affibloom\Tests\Integration;

use Affibloom\Domain\Lifecycle\DataInventory;
use WP_UnitTestCase;

/**
 * Task 38 (F37 AC6, F9 AC16): deleting the plugin removes Affibloom's data only when an administrator opted in, and
 * then every store of DataInventory goes; no post is rewritten and other plugins' data stays. Runs only where wp-env
 * is available (progress.md, deviation 10); on this machine Task 38's step 5 ran the same by hand.
 */
final class UninstallTest extends WP_UnitTestCase {

	/**
	 * One of each store, and a post that uses a box.
	 *
	 * @return array{product: int, post: int}
	 */
	private function stores(): array {
		$product = self::factory()->post->create( array( 'post_type' => 'affibloom_product' ) );
		update_post_meta( $product, '_affibloom_url', 'https://www.example.com/item' );
		$post = self::factory()->post->create( array( 'post_content' => '<!-- wp:affibloom/product-box {"productId":' . $product . '} /-->' ) );
		wp_insert_term( 'Shoes', 'affibloom_group' );
		foreach ( DataInventory::OPTIONS as $option ) {
			update_option( $option, 'x' );
		}
		set_transient( 'affibloom_link_issue_counts', 'x' );
		set_transient( 'affibloom_c_0123456789abcdef', 1 );
		update_user_meta( 1, 'affibloom_per_page', 25 );
		wp_schedule_single_event( time() + 60, 'affibloom_bulk_add' );
		update_option( 'someone_elses_option', 'kept' );

		return array(
			'product' => $product,
			'post'    => $post,
		);
	}

	private function uninstall(): void {
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'affibloom/affibloom.php' );
		}
		include dirname( __DIR__, 2 ) . '/uninstall.php';
	}

	public function test_without_the_opt_in_nothing_is_removed(): void {
		$made = $this->stores();
		update_option( 'affibloom_settings', array( 'delete_data_on_uninstall' => false ) );

		$this->uninstall();

		self::assertNotNull( get_post( $made['product'] ) );
		self::assertNotFalse( get_option( 'affibloom_settings' ) );
		self::assertSame( 25, (int) get_user_meta( 1, 'affibloom_per_page', true ) );
	}

	public function test_with_the_opt_in_every_store_goes_and_posts_stay(): void {
		global $wpdb;

		$made = $this->stores();
		update_option( 'affibloom_settings', array( 'delete_data_on_uninstall' => true ) );
		$content = get_post_field( 'post_content', $made['post'] );

		$this->uninstall();

		self::assertNull( get_post( $made['product'] ) );
		foreach ( DataInventory::OPTIONS as $option ) {
			self::assertFalse( get_option( $option ), $option );
		}
		self::assertFalse( get_transient( 'affibloom_link_issue_counts' ) );
		self::assertFalse( get_transient( 'affibloom_c_0123456789abcdef' ) );
		self::assertSame( '', get_user_meta( 1, 'affibloom_per_page', true ) );
		self::assertFalse( wp_next_scheduled( 'affibloom_bulk_add' ) );
		self::assertSame( 0, (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE meta_key LIKE %s', $wpdb->postmeta, '\_affibloom\_%' ) ) );
		self::assertSame( array(), get_terms( array( 'taxonomy' => 'affibloom_group', 'hide_empty' => false, 'fields' => 'ids' ) ) );
		foreach ( DataInventory::TABLES as $table ) {
			self::assertNull( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . $table ) ), $table );
		}
		self::assertSame( $content, get_post_field( 'post_content', $made['post'] ) );
		self::assertSame( 'kept', get_option( 'someone_elses_option' ) );
	}
}
