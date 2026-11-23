<?php
namespace Affibloom\Tests\Unit\Domain\Lifecycle;

use Affibloom\Domain\Lifecycle\DataInventory;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Task 38, the drift test: every `affibloom_…` name in `src/` is either stored data that DataInventory lists (so the
 * opt-in removal deletes it), or a name that is not stored. A new option, table, transient, user meta or cron hook
 * that is in neither fails here until it is classified.
 *
 * Not stored, by the line it is on: a WP_Error code, a hook fired, filtered or listened to (do_action, apply_filters,
 * add_action, add_filter: cron hooks are registered through their modules' HOOKS constants, never by a literal here),
 * a query variable (`…_VAR = `), a URL argument ($_GET, add_query_arg, remove_query_arg). The rest that is not stored
 * is listed below by name.
 */
final class DataInventoryCoverageTest extends TestCase {

	/** Names that are not stored data and that the line rules above do not recognise. */
	private const NOT_STORED = array(
		// A shortcode tag that carries a drawn box past wptexturize while a page renders (ProductBoxBlock, Task 29A).
		'affibloom_box_html',
		// Error codes in arrays of codes (ProductsController, AmazonController, OpportunitiesController).
		'affibloom_amazon_missing',
		'affibloom_group_in_use',
		'affibloom_invalid',
		'affibloom_invalid_field',
		'affibloom_duplicate',
		'affibloom_in_use',
		'affibloom_invalid_product',
		'affibloom_not_found',
		'affibloom_product_not_found',
		'affibloom_location_gone',
		'affibloom_location_not_found',
		'affibloom_table_changed',
		// The TinyMCE ids of the product page's rich fields (RichEditorSettings).
		'affibloom_description',
		'affibloom_price',
		'affibloom_table_description',
		// The Elementor widget's control names, kept in Elementor's own data.
		'affibloom_shortcode',
		'affibloom_type',
		// Affibloom's capability (Task TL6), granted per request, never stored.
		'affibloom_products',
		// WP-Cron's five-minute schedule name, not an event.
		'affibloom_five_minutes',
		// The products' retired post status: its posts are products, removed with their post type.
		'affibloom_retired',
		// The Settings page's notice argument, in an array of URL arguments (SettingsRestController).
		'affibloom_notice',
	);

	public function test_every_stored_name_in_src_is_in_the_inventory(): void {
		$stored = array_merge(
			DataInventory::OPTIONS,
			DataInventory::TRANSIENTS,
			DataInventory::USER_META,
			DataInventory::POST_TYPES,
			DataInventory::TAXONOMIES,
			DataInventory::TABLES,
			DataInventory::CRON_HOOKS
		);
		$unknown = array();
		$src     = dirname( __DIR__, 4 ) . '/src';
		foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $src ) ) as $file ) {
			if ( ! $file->isFile() || 'php' !== $file->getExtension() ) {
				continue;
			}
			foreach ( (array) file( $file->getPathname() ) as $number => $line ) {
				if ( ! preg_match_all( "/'(affibloom_[a-z0-9_]+)'/", (string) $line, $names ) || self::not_stored_line( (string) $line ) ) {
					continue;
				}
				foreach ( $names[1] as $name ) {
					$prefixed = array_filter( DataInventory::TRANSIENT_PREFIXES, static fn( string $prefix ): bool => str_starts_with( $name, $prefix ) || $name === $prefix );
					if ( ! in_array( $name, $stored, true ) && array() === $prefixed && ! in_array( $name, self::NOT_STORED, true ) ) {
						$unknown[] = $name . ' (' . basename( $file->getPathname() ) . ':' . ( $number + 1 ) . ')';
					}
				}
			}
		}

		self::assertSame( array(), $unknown, 'Add each to DataInventory if it is stored, or to NOT_STORED here if it is not.' );
	}

	/**
	 * Whether the line names something that is not stored: an error code, a hook, a query variable, a URL argument.
	 *
	 * @param string $line A line of PHP.
	 */
	private static function not_stored_line( string $line ): bool {
		return 1 === preg_match( '/WP_Error\(|do_action\(|apply_filters\(|add_action\(|add_filter\(|_VAR\s*=|\$_GET\[|add_query_arg\(|remove_query_arg\(/', $line );
	}
}
