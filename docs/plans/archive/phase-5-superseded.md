# Phase 5 — superseded task sections (history only)

> Archived 2026-11-14. These are the original Phase 5 task sections, moved here word for word when the planning pass replaced them. **They are history only: do not work from them.** Replaced by:
>
> - Task 25 (migration dry run) → **25A, 25B, 25C, 25D** (products, settings, every other family and the left-behind list, the report on an admin page).
> - Task 26 (running the migration) → **26A-26P**: 26A products, 26B `--update`, 26C General and Amazon settings, 26D Display settings, 26E custom fields, 26F groups, 26G cloaked addresses, 26H URL variants, 26L Amazon data, 26M saved views, 26O run from the admin, 26P the check against the baselines; 26I, 26J, 26K and 26N move to Phases 7, 7, 6 and 7.
> - Task 27 (`[lasso]` compatibility with Lasso off) → **27A-27I** (the `[lasso]` alias and its forms) and **27X1-27X8** (the competitors' shortcodes), on top of Phase 3's `[affibloom]` tasks 18S1-18S14.
> - Task 28 (`wp affibloom convert-legacy-blocks`) → **28A-28E**, only if the user keeps the conversion (F10 "Needs the user" 1 / F4 N2).
> - Task 29 (parity run) → **29A, 29B, 29D, 29E** here; 29C moves to Phase 7.
>
> Still true and kept in the live file: the Task 6 decision bullets and the F29 any-number-of-buttons bullet (the "Decisions" block of the live file).

---

### Task 25: Migration dry run — `wp affibloom migrate --dry-run`

**Roadmap:** 5.3 · **Feature:** F10 (AC1)

**Why:** before anything is written, a report must account for every one of the 263 records: what each will become, and anything that needs a person's eye.

**User action:** a developer runs `wp affibloom migrate --dry-run` on the local copy. They see a table with one row per Lasso record (Lasso ID, name, type, action `create`, and notes), a summary such as `263 records: 263 to create, 0 already migrated, 0 errors`, and a CSV copy of the report. Nothing in the database changes.

**Request path:**
```
wp affibloom migrate --dry-run [--report=migration.csv]
  → cli_init: CliCommands registers "affibloom migrate" → MigrateCommand::__invoke()
  → MigrationService::plan()
       LassoSource::records()       read-only SELECTs: lasso-urls posts + meta, wp_lasso_url_details,
                                    wp_lasso_amazon_products, wp_lasso_field_mapping, thumbnails
       LassoIdMap::product_for()    already migrated?
       LassoRecordMapper::map()     Lasso's rules → field values + notes
       ProductInput::changes()      the same validation as the product page (second caller)
  ← MigrationReport → table + summary + CSV
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Cli\CliCommands` (module), `MigrateCommand` | `cli_init` |
| `Infrastructure\Lasso\LassoSource` | `MigrationService` |
| `Infrastructure\Lasso\LassoIdMap::product_for()` | `MigrationService` (and later the shortcode, clicks and usage) |
| `Application\Migration\{LassoRecordMapper, MigrationService, MigrationReport}` | `MigrateCommand` |
| `php-stubs/wp-cli-stubs` for PHPStan | `composer analyse` |

**The mapping rules.** They are taken from `Lasso_Affiliate_Link::get_lasso_url()` and `get_lasso_thumbnail()`. Each one has a unit test with a record from `tests/fixtures/inventory/products.json`.

| Product field | From Lasso | Rule |
|---|---|---|
| name | `post_title` | HTML entities decoded. If it is "Amazon" and an Amazon row exists, `default_product_name` is used instead. |
| url, type, asin | `lasso_amazon_products.monetized_url` (or `base_url`) when the record has an Amazon row; otherwise `lasso_url_details.redirect_url`, then `lasso_custom_redirect`, then `lasso_final_url` | Through `AmazonLinkService`, so Amazon links get the site's tag. |
| button_text | `buy_btn_text` | '' means the Display default. |
| url2, button_text2 | `second_btn_url` (trimmed), `second_btn_text` | Through `AmazonLinkService`. |
| new_tab, nofollow, sponsored, new_tab2, nofollow2 | `open_new_tab`, `enable_nofollow`, `enable_sponsored`, `open_new_tab2`, `enable_nofollow2` | `1 === (int) value`. Missing means off, exactly as Lasso reads them. |
| image_url | `lasso_custom_thumbnail`; else the post thumbnail (large); else `lasso_thumbnail_url`; and, for Amazon records with neither of the first two, `default_image` | Lasso's "no thumbnail" placeholder is dropped. |
| description | `affiliate_desc` | Hidden when `show_description` is `0` and the text is 10 characters or fewer, as Lasso does. An empty description on an Amazon record gets a note. |
| badge | `badge_text` | |
| disclosure, show_disclosure | `disclosure_text`, `show_disclosure` | '' text means the default. A missing toggle takes the site setting, with a note so Task 26 can check it against the baseline. |
| rating | `lasso_field_mapping` row with `field_id = 1` | `field_value`. |
| prime | `lasso_amazon_products.is_prime` | |
| lasso_id | the record's post ID | stored as `_affibloom_lasso_id` |

**Files:**
- Create:
  - `src/Interface/Cli/{CliCommands,MigrateCommand}.php`
  - `src/Infrastructure/Lasso/{LassoSource,LassoIdMap}.php`
  - `src/Application/Migration/{LassoRecordMapper,MappedRecord,MigrationService,MigrationReport}.php`
  - `tests/Unit/Application/LassoRecordMapperTest.php`
- Modify: `composer.json`, `phpstan.neon.dist`, `src/Plugin.php`

- [ ] **Step 1: Failing tests**

`tests/Unit/Application/LassoRecordMapperTest.php` loads records from the inventory fixture, so the tests run against the site's real data shapes:

```php
<?php
namespace Affibloom\Tests\Unit\Application;

use PHPUnit\Framework\TestCase;
use Affibloom\Application\Migration\LassoRecordMapper;

final class LassoRecordMapperTest extends TestCase {
	private static array $records;

	public static function setUpBeforeClass(): void {
		self::$records = json_decode( file_get_contents( dirname( __DIR__, 2 ) . '/fixtures/inventory/products.json' ), true );
	}

	private function first( callable $match ): array {
		foreach ( self::$records as $record ) {
			if ( 'publish' === $record['status'] && $match( $record ) ) {
				return $record;
			}
		}
		self::markTestSkipped( 'No such record on the site.' );
	}

	public function test_an_amazon_record_uses_its_monetized_url(): void {
		$record = $this->first( static fn( $r ) => ! empty( $r['amazon']['monetized_url'] ) );
		$mapped = ( new LassoRecordMapper() )->map( $record );

		self::assertSame( $record['amazon']['monetized_url'], $mapped->fields['url'] );
	}

	public function test_a_second_button_is_carried_over(): void {
		$record = $this->first( static fn( $r ) => ! empty( $r['meta']['second_btn_url'] ) );
		$mapped = ( new LassoRecordMapper() )->map( $record );

		self::assertSame( trim( $record['meta']['second_btn_url'] ), $mapped->fields['url2'] );
	}

	public function test_missing_toggles_mean_off_as_lasso_reads_them(): void {
		$record                       = $this->first( static fn( $r ) => true );
		$record['meta']['open_new_tab'] = '';
		self::assertFalse( ( new LassoRecordMapper() )->map( $record )->fields['new_tab'] );
	}

	public function test_a_rating_comes_from_field_one(): void {
		$record = $this->first( static fn( $r ) => in_array( '1', array_column( $r['fields'], 'field_id' ), true ) );
		self::assertNotSame( '', ( new LassoRecordMapper() )->map( $record )->fields['rating'] );
	}
}
```

```bash
composer test:unit
```

- [ ] **Step 2: Command (the entry point)**

```bash
composer require --dev php-stubs/wp-cli-stubs
```

`phpstan.neon.dist`, under `parameters`:

```neon
	scanFiles:
		- vendor/php-stubs/wp-cli-stubs/wp-cli-stubs.php
```

`src/Interface/Cli/CliCommands.php`:

```php
<?php
/**
 * Registers the "wp affibloom" commands, only when WP-CLI runs.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Cli;

use Affibloom\Module;
use WP_CLI;

/**
 * Registers the "wp affibloom" commands, only when WP-CLI runs.
 */
final class CliCommands implements Module {

	/**
	 * Builds the module.
	 *
	 * @param array<string, object> $commands Command name ("affibloom migrate") => invokable command.
	 */
	public function __construct( private array $commands ) {}

	/**
	 * Hooks into WP-CLI's start.
	 */
	public function register(): void {
		add_action( 'cli_init', array( $this, 'add_commands' ) );
	}

	/**
	 * Adds every command.
	 */
	public function add_commands(): void {
		foreach ( $this->commands as $name => $command ) {
			WP_CLI::add_command( $name, $command );
		}
	}
}
```

`src/Interface/Cli/MigrateCommand.php`:

```php
<?php
/**
 * The wp affibloom migrate command.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Cli;

use Affibloom\Application\Migration\MigrationReport;
use Affibloom\Application\Migration\MigrationService;
use WP_CLI;

/**
 * The wp affibloom migrate command.
 */
final class MigrateCommand {

	/**
	 * Builds the command.
	 *
	 * @param MigrationService $migration Migration use cases.
	 */
	public function __construct( private MigrationService $migration ) {}

	/**
	 * Copies Lasso's records into Affibloom products. Lasso's data is never changed.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Report what would happen; write nothing.
	 *
	 * [--update]
	 * : Also update products migrated before (otherwise they are skipped).
	 *
	 * [--report=<file>]
	 * : Also write the report as CSV.
	 *
	 * ## EXAMPLES
	 *
	 *     wp affibloom migrate --dry-run --report=migration.csv
	 *
	 * @param list<string>          $args       Positional arguments (none).
	 * @param array<string, string> $assoc_args Flags.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		if ( ! $this->migration->source_available() ) {
			WP_CLI::error( 'No Lasso data found (wp_lasso_url_details is missing).' );
		}

		$dry_run = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$report  = $dry_run
			? $this->migration->plan( (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'update', false ) )
			: $this->migration->run( (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'update', false ) );

		$this->print( $report );

		if ( isset( $assoc_args['report'] ) ) {
			$report->write_csv( $assoc_args['report'] );
			WP_CLI::log( 'Report written to ' . $assoc_args['report'] );
		}

		$summary = $report->summary();
		$line    = sprintf(
			'%d records: %d to create, %d to update, %d already migrated, %d errors%s.',
			$summary['total'],
			$summary['create'],
			$summary['update'],
			$summary['skip'],
			$summary['error'],
			$dry_run ? ' (dry run, nothing written)' : ''
		);
		$summary['error'] ? WP_CLI::warning( $line ) : WP_CLI::success( $line );
	}

	/**
	 * The report as a table.
	 *
	 * @param MigrationReport $report Report.
	 */
	private function print( MigrationReport $report ): void {
		WP_CLI\Utils\format_items( 'table', $report->rows(), array( 'lasso_id', 'name', 'type', 'action', 'product_id', 'notes' ) );
	}
}
```

The command's `run()` path is written now, because it shares everything with `plan()`. It is first run, and checked, in Task 26.

- [ ] **Step 3: Service and report**

`src/Application/Migration/MigrationReport.php`:

```php
<?php
/**
 * One row per Lasso record: what the migration did or would do.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Migration;

/**
 * One row per Lasso record: what the migration did or would do.
 */
final class MigrationReport {

	/**
	 * Rows so far.
	 *
	 * @var list<array{lasso_id: int, name: string, type: string, action: string, product_id: string, notes: string}>
	 */
	private array $rows = array();

	/**
	 * Adds a row.
	 *
	 * @param int          $lasso_id   Lasso post ID.
	 * @param string       $name       Product name.
	 * @param string       $type       "amazon" or "other".
	 * @param string       $action     create, update, skip or error.
	 * @param int|null     $product_id Affibloom product ID, when there is one.
	 * @param list<string> $notes      Things for a person to check.
	 */
	public function add( int $lasso_id, string $name, string $type, string $action, ?int $product_id, array $notes ): void {
		$this->rows[] = array(
			'lasso_id'   => $lasso_id,
			'name'       => $name,
			'type'       => $type,
			'action'     => $action,
			'product_id' => null === $product_id ? '' : (string) $product_id,
			'notes'      => implode( '; ', $notes ),
		);
	}

	/**
	 * Every row.
	 *
	 * @return list<array{lasso_id: int, name: string, type: string, action: string, product_id: string, notes: string}>
	 */
	public function rows(): array {
		return $this->rows;
	}

	/**
	 * Counts by action.
	 *
	 * @return array{total: int, create: int, update: int, skip: int, error: int}
	 */
	public function summary(): array {
		$count = array_count_values( array_column( $this->rows, 'action' ) );
		return array(
			'total'  => count( $this->rows ),
			'create' => $count['create'] ?? 0,
			'update' => $count['update'] ?? 0,
			'skip'   => $count['skip'] ?? 0,
			'error'  => $count['error'] ?? 0,
		);
	}

	/**
	 * Writes the rows as CSV.
	 *
	 * @param string $path File path.
	 */
	public function write_csv( string $path ): void {
		$handle = fopen( $path, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- CLI report file.
		if ( false === $handle ) {
			return;
		}
		fputcsv( $handle, array( 'lasso_id', 'name', 'type', 'action', 'product_id', 'notes' ) );
		foreach ( $this->rows as $row ) {
			fputcsv( $handle, $row );
		}
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	}
}
```

`src/Application/Migration/MigrationService.php`:

```php
<?php
/**
 * Lasso records → products. Lasso's own data is only read.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Migration;

use Throwable;
use Affibloom\Application\Products\ProductInput;
use Affibloom\Application\Products\ValidationFailed;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Infrastructure\Lasso\LassoIdMap;
use Affibloom\Infrastructure\Lasso\LassoSource;

/**
 * Lasso records → products. Lasso's own data is only read.
 */
final class MigrationService {

	/**
	 * Builds the service.
	 *
	 * @param LassoSource        $source   Read-only Lasso data.
	 * @param LassoIdMap         $map      Lasso ID → product ID.
	 * @param LassoRecordMapper  $mapper   Lasso's rules.
	 * @param ProductInput       $input    The product field rules.
	 * @param ProductRepository  $products Product storage.
	 * @param SettingsRepository $settings Link defaults for new products.
	 */
	public function __construct(
		private LassoSource $source,
		private LassoIdMap $map,
		private LassoRecordMapper $mapper,
		private ProductInput $input,
		private ProductRepository $products,
		private SettingsRepository $settings,
	) {}

	/**
	 * Whether Lasso's tables are present.
	 */
	public function source_available(): bool {
		return $this->source->is_available();
	}

	/**
	 * What would happen; writes nothing.
	 *
	 * @param bool $update Also update products migrated before.
	 */
	public function plan( bool $update ): MigrationReport {
		return $this->process( $update, false );
	}

	/**
	 * Creates (and with $update, updates) products.
	 *
	 * @param bool $update Also update products migrated before.
	 */
	public function run( bool $update ): MigrationReport {
		$report = $this->process( $update, true );
		$this->map->flush();
		return $report;
	}

	/**
	 * One pass over every record.
	 *
	 * @param bool $update Update existing products.
	 * @param bool $write  Save, or only report.
	 */
	private function process( bool $update, bool $write ): MigrationReport {
		$report = new MigrationReport();

		foreach ( $this->source->records() as $record ) {
			$lasso_id = (int) $record['id'];
			$existing = $this->map->product_for( $lasso_id );
			$mapped   = $this->mapper->map( $record );

			if ( null !== $existing && ! $update ) {
				$report->add( $lasso_id, $mapped->name(), $mapped->type(), 'skip', $existing, array() );
				continue;
			}

			try {
				$changes = $this->input->changes( $mapped->fields );
				$product = $this->product_for( $existing, $changes, $mapped );
				$action  = null === $existing ? 'create' : 'update';
				$id      = $write ? (int) $this->products->save( $product )->id : $existing;
				$report->add( $lasso_id, $product->name, $product->type->value, $action, $id, $mapped->notes );
			} catch ( ValidationFailed $error ) {
				$report->add( $lasso_id, $mapped->name(), $mapped->type(), 'error', $existing, array_merge( $mapped->notes, array( 'invalid: ' . implode( ', ', array_map( static fn( $f, $r ) => "$f ($r)", array_keys( $error->errors ), $error->errors ) ) ) ) );
			} catch ( Throwable $error ) {
				$report->add( $lasso_id, $mapped->name(), $mapped->type(), 'error', $existing, array_merge( $mapped->notes, array( $error->getMessage() ) ) );
			}
		}

		return $report;
	}

	/**
	 * The product to save: a new draft, or the existing product, with the mapped values.
	 *
	 * @param int|null             $existing Existing product ID.
	 * @param array<string, mixed> $changes  Validated values.
	 * @param MappedRecord         $mapped   Mapped record (for values ProductInput does not own).
	 */
	private function product_for( ?int $existing, array $changes, MappedRecord $mapped ): Product {
		$base = null !== $existing ? $this->products->get( $existing ) : null;
		$base = $base ?? Product::draft( $changes['name'], $changes['url'], $changes['type'] ?? ProductType::Other, $this->settings->get() );

		return $base->with(
			$changes + array(
				'sponsored' => $mapped->sponsored,
				'lasso_id'  => $mapped->lasso_id,
			)
		);
	}
}
```

`sponsored` and `lasso_id` are not product page fields, so `ProductInput` does not own them. The migration sets them directly.

`Product` gets a last constructor parameter, `public readonly ?int $lasso_id = null,`. The repository stores it as `_affibloom_lasso_id` (only when not null) and reads it back. Those are two lines each in `to_meta()` and `hydrate()`.

- [ ] **Step 4: The mapper**

`src/Application/Migration/MappedRecord.php`:

```php
<?php
/**
 * A Lasso record mapped to product field values, plus notes for a person.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Migration;

/**
 * A Lasso record mapped to product field values, plus notes for a person.
 */
final class MappedRecord {

	/**
	 * Builds the result.
	 *
	 * @param int                  $lasso_id  Lasso post ID.
	 * @param array<string, mixed> $fields    Values for ProductInput::changes().
	 * @param bool                 $sponsored The record's sponsored flag.
	 * @param list<string>         $notes     Things to check by hand.
	 */
	public function __construct(
		public readonly int $lasso_id,
		public readonly array $fields,
		public readonly bool $sponsored,
		public readonly array $notes,
	) {}

	/**
	 * The name, for the report.
	 */
	public function name(): string {
		return (string) ( $this->fields['name'] ?? '' );
	}

	/**
	 * "amazon" or "other", for the report.
	 */
	public function type(): string {
		return str_contains( (string) ( $this->fields['url'] ?? '' ), 'amazon.' ) ? 'amazon' : 'other';
	}
}
```

`src/Application/Migration/LassoRecordMapper.php`:

```php
<?php
/**
 * Lasso's rules for turning a record into what its box shows (get_lasso_url, get_lasso_thumbnail).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Migration;

/**
 * Lasso's rules for turning a record into what its box shows (get_lasso_url, get_lasso_thumbnail).
 */
final class LassoRecordMapper {

	/** Lasso's own "no image" placeholder, which must not become a product image. */
	private const PLACEHOLDER = 'lasso-no-thumbnail';

	/**
	 * Maps one record in the shape LassoSource::records() returns.
	 *
	 * @param array<string, mixed> $record Record.
	 */
	public function map( array $record ): MappedRecord {
		$meta    = (array) ( $record['meta'] ?? array() );
		$amazon  = is_array( $record['amazon'] ?? null ) ? $record['amazon'] : null;
		$details = is_array( $record['url_details'] ?? null ) ? $record['url_details'] : array();
		$text    = static fn( string $key ): string => trim( (string) ( $meta[ $key ] ?? '' ) );
		$on      = static fn( string $key ): bool => 1 === (int) ( $meta[ $key ] ?? 0 );
		$notes   = array();

		$name = html_entity_decode( (string) $record['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		if ( 'Amazon' === $name && $amazon && '' !== (string) $amazon['default_product_name'] ) {
			$name = (string) $amazon['default_product_name'];
		}

		if ( $amazon ) {
			$url = '' !== (string) $amazon['monetized_url'] ? (string) $amazon['monetized_url'] : (string) $amazon['base_url'];
		} else {
			$url = (string) ( $details['redirect_url'] ?? '' );
			$url = '' !== $url ? $url : $text( 'lasso_custom_redirect' );
			$url = '' !== $url ? $url : $text( 'lasso_final_url' );
		}

		$description = (string) ( $meta['affiliate_desc'] ?? '' );
		if ( '0' === (string) ( $meta['show_description'] ?? '' ) && strlen( $description ) <= 10 ) {
			$description = '';
		}
		$description = str_replace( '<p></p>', '', $description );
		if ( '' === trim( $description ) && $amazon ) {
			$notes[] = 'empty description';
		}

		$show_disclosure = $meta['show_disclosure'] ?? null;
		if ( null === $show_disclosure || '' === $show_disclosure ) {
			$notes[] = 'disclosure toggle missing: site default used';
		}

		$rating = '';
		foreach ( (array) ( $record['fields'] ?? array() ) as $field ) {
			if ( '1' === (string) ( $field['field_id'] ?? '' ) ) {
				$rating = (string) $field['field_value'];
			}
		}

		$fields = array(
			'name'         => $name,
			'url'          => $url,
			'button_text'  => $text( 'buy_btn_text' ),
			'url2'         => $text( 'second_btn_url' ),
			'button_text2' => $text( 'second_btn_text' ),
			'new_tab'      => $on( 'open_new_tab' ),
			'nofollow'     => $on( 'enable_nofollow' ),
			'new_tab2'     => $on( 'open_new_tab2' ),
			'nofollow2'    => $on( 'enable_nofollow2' ),
			'image_url'    => $this->image( $record, $meta, $amazon ),
			'badge'        => $text( 'badge_text' ),
			'description'  => $description,
			'rating'       => $rating,
			'disclosure'   => $text( 'disclosure_text' ),
			'prime'        => $amazon && 1 === (int) $amazon['is_prime'],
		);
		if ( null !== $show_disclosure && '' !== $show_disclosure ) {
			$fields['show_disclosure'] = 1 === (int) $show_disclosure;
		}

		return new MappedRecord( (int) $record['id'], $fields, $on( 'enable_sponsored' ), $notes );
	}

	/**
	 * Lasso's thumbnail order.
	 *
	 * @param array<string, mixed>      $record Record.
	 * @param array<string, mixed>      $meta   Its meta.
	 * @param array<string, mixed>|null $amazon Its Amazon row.
	 */
	private function image( array $record, array $meta, ?array $amazon ): string {
		$custom = trim( (string) ( $meta['lasso_custom_thumbnail'] ?? '' ) );
		if ( '' !== $custom ) {
			$image = $custom;
		} elseif ( '' !== (string) ( $record['thumbnail_url'] ?? '' ) ) {
			$image = (string) $record['thumbnail_url'];
		} else {
			$image = trim( (string) ( $meta['lasso_thumbnail_url'] ?? '' ) );
			if ( $amazon && '' !== (string) $amazon['default_image'] ) {
				$image = (string) $amazon['default_image'];
			}
		}
		return str_contains( $image, self::PLACEHOLDER ) ? '' : $image;
	}
}
```

The test asserts `$mapped->fields['url']` before normalisation. `ProductInput` applies the tag afterwards, and Task 26 checks the result against the baseline.

- [ ] **Step 5: Infrastructure: reading Lasso, and the ID map**

`src/Infrastructure/Lasso/LassoSource.php`. The same shape as `tools/inventory.php`, so the mapper is tested on fixtures and run on live data:

```php
<?php
/**
 * Reads Lasso's records and settings. SELECT only; Lasso may be active or not.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\Lasso;

use wpdb;

/**
 * Reads Lasso's records and settings. SELECT only; Lasso may be active or not.
 */
final class LassoSource {

	/**
	 * Builds the source.
	 *
	 * @param wpdb $db Database.
	 */
	public function __construct( private wpdb $db ) {}

	/**
	 * Whether Lasso's details table exists.
	 */
	public function is_available(): bool {
		$table = $this->db->prefix . 'lasso_url_details';
		return $table === $this->db->get_var( $this->db->prepare( 'SHOW TABLES LIKE %s', $this->db->esc_like( $table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Every published Lasso record with what its box is built from.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function records(): array {
		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- read-only access to Lasso's tables.
		$posts = $this->db->get_results(
			$this->db->prepare( "SELECT ID, post_title, post_name, post_status FROM {$this->db->posts} WHERE post_type = %s AND post_status = %s ORDER BY ID", 'lasso-urls', 'publish' ),
			ARRAY_A
		);
		$ids   = array_map( 'intval', array_column( $posts, 'ID' ) );
		if ( ! $ids ) {
			return array();
		}
		update_meta_cache( 'post', $ids );

		$in      = implode( ',', $ids );
		$details = $this->by_key( $this->db->get_results( "SELECT * FROM {$this->db->prefix}lasso_url_details WHERE lasso_id IN ($in)", ARRAY_A ), 'lasso_id' );
		$fields  = $this->db->get_results( "SELECT * FROM {$this->db->prefix}lasso_field_mapping WHERE lasso_id IN ($in)", ARRAY_A );
		$amazon  = array();
		foreach ( $this->db->get_results( "SELECT * FROM {$this->db->prefix}lasso_amazon_products", ARRAY_A ) as $row ) {
			$amazon[ $row['amazon_id'] . '|' . $row['country'] ] = $row;
		}
		// phpcs:enable

		$records = array();
		foreach ( $posts as $post ) {
			$id   = (int) $post['ID'];
			$meta = array();
			foreach ( get_post_meta( $id ) as $key => $values ) {
				$meta[ $key ] = maybe_unserialize( $values[0] );
			}
			$detail    = $details[ $id ] ?? null;
			$amazon_id = $detail && ! empty( $detail['product_id'] ) ? $detail['product_id'] . '|' . ( $detail['country'] ?? 'us' ) : '';
			$thumbnail = get_the_post_thumbnail_url( $id, 'large' );

			$records[] = array(
				'id'            => $id,
				'title'         => $post['post_title'],
				'slug'          => $post['post_name'],
				'status'        => $post['post_status'],
				'thumbnail_url' => $thumbnail ? $thumbnail : '',
				'meta'          => $meta,
				'url_details'   => $detail,
				'amazon'        => '' !== $amazon_id ? ( $amazon[ $amazon_id ] ?? null ) : null,
				'fields'        => array_values( array_filter( $fields, static fn( $f ) => (int) $f['lasso_id'] === $id ) ),
			);
		}
		return $records;
	}

	/**
	 * Lasso's settings option (read only).
	 *
	 * @return array<string, mixed>
	 */
	public function settings(): array {
		return (array) get_option( 'lasso_settings', array() );
	}

	/**
	 * Rows keyed by a column.
	 *
	 * @param list<array<string, mixed>> $rows Rows.
	 * @param string                     $key  Column.
	 * @return array<int, array<string, mixed>>
	 */
	private function by_key( array $rows, string $key ): array {
		$out = array();
		foreach ( $rows as $row ) {
			$out[ (int) $row[ $key ] ] = $row;
		}
		return $out;
	}
}
```

`src/Infrastructure/Lasso/LassoIdMap.php`:

```php
<?php
/**
 * Lasso record ID → Affibloom product ID, from _affibloom_lasso_id. Loaded once, cached.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\Lasso;

use Affibloom\Infrastructure\WordPress\ProductPostType;
use wpdb;

/**
 * Lasso record ID → Affibloom product ID, from _affibloom_lasso_id. Loaded once, cached.
 */
final class LassoIdMap {

	private const CACHE_KEY   = 'lasso_map';
	private const CACHE_GROUP = 'affibloom';

	/**
	 * The map, once loaded.
	 *
	 * @var array<int, int>|null
	 */
	private ?array $map = null;

	/**
	 * Builds the map.
	 *
	 * @param wpdb $db Database.
	 */
	public function __construct( private wpdb $db ) {}

	/**
	 * The product migrated from a Lasso record, or null.
	 *
	 * @param int $lasso_id Lasso post ID.
	 */
	public function product_for( int $lasso_id ): ?int {
		return $this->load()[ $lasso_id ] ?? null;
	}

	/**
	 * Forgets the map after products changed.
	 */
	public function flush(): void {
		$this->map = null;
		wp_cache_delete( self::CACHE_KEY, self::CACHE_GROUP );
	}

	/**
	 * Loads every pair in one query (a few hundred rows).
	 *
	 * @return array<int, int>
	 */
	private function load(): array {
		if ( null !== $this->map ) {
			return $this->map;
		}
		$cached = wp_cache_get( self::CACHE_KEY, self::CACHE_GROUP );
		if ( is_array( $cached ) ) {
			$this->map = $cached;
			return $this->map;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- one indexed read, cached below.
		$rows = $this->db->get_results(
			$this->db->prepare(
				"SELECT pm.meta_value AS lasso_id, pm.post_id FROM {$this->db->postmeta} pm
				 INNER JOIN {$this->db->posts} p ON p.ID = pm.post_id
				 WHERE pm.meta_key = %s AND p.post_type = %s AND p.post_status = 'publish'",
				'_affibloom_lasso_id',
				ProductPostType::NAME
			),
			ARRAY_A
		);

		$this->map = array();
		foreach ( $rows as $row ) {
			$this->map[ (int) $row['lasso_id'] ] = (int) $row['post_id'];
		}
		wp_cache_set( self::CACHE_KEY, $this->map, self::CACHE_GROUP, HOUR_IN_SECONDS );
		return $this->map;
	}
}
```

`Plugin`: add a `lasso_map()` builder (shared from Task 27 on), and:

```php
			new CliCommands(
				array(
					'affibloom migrate' => new MigrateCommand(
						new MigrationService( new LassoSource( $GLOBALS['wpdb'] ), $this->lasso_map(), new LassoRecordMapper(), $product_input, $this->product_repository(), $this->settings_repository() )
					),
				)
			),
```

where `$product_input` is the `ProductInput` already built for the controller.

The commands are built on every request, but they are cheap objects, and `CliCommands` only hands them to WP-CLI on `cli_init`. If profiling in Task 37 shows a cost, wrap the list in a closure.

- [ ] **Step 6: Run the dry run**

```bash
composer lint && composer analyse && composer test:unit
bash tools/reset-local.sh
npm run wp -- affibloom migrate --dry-run --report=wp-content/plugins/affibloom/.local/migration-plan.csv
```
Expected: `Success: 263 records: 263 to create, 0 to update, 0 already migrated, 0 errors (dry run, nothing written).`

Read every note in the CSV. Each "empty description" and "disclosure toggle missing" case is checked against `boxes.json` in Task 26. Then confirm that nothing was written:

```bash
npm run wp -- post list --post_type=affibloom_product --format=count
```
Expected: `0`.

- [ ] **Step 7: Commit**

```bash
git add -A && git commit -m "Add the Lasso migration dry run

wp affibloom migrate --dry-run reports what each of the 263 Lasso records
would become, using Lasso's own rules and the same field validation as
the product page. Nothing is written."
```

---

### Task 26: Running the migration — `wp affibloom migrate`

> **From the Task 15 sweep (2026-11-13, F1 D20):** Affibloom stores no `show_description`; a Lasso record whose
> `show_description` is 0 is migrated with an empty description, which is what Lasso shows. A record with
> `lasso_repointed_to_lasso_id` is migrated retired, with `_affibloom_repointed_to` mapped to the new ID (F1 D14).
> **From the Groups sweep (2026-11-14, F23 section 5):** Lasso's `lasso-cat` terms become `affibloom_group` terms
> (name, slug, description) with each product's memberships, and `lasso_category_order` becomes each group's
> `_affibloom_order` (IDs mapped). This copy has one empty group.
> **From the Task 17 sweep (2026-11-13, F1 D43):** "retired" is the status `affibloom_retired` (Lasso's `lasso_delete`,
> 1 record here); a Lasso record in the trash (1 here) is migrated with the status `trash` set through
> `wp_update_post`, as Lasso's bulk delete leaves it, so add can bring it back (17D).

**Roadmap:** 5.3 · **Feature:** F10 (AC1)

**Why:** this turns the plan into products and imports the site's settings. The task is not done until every migrated product produces the same box values as Lasso's baseline: name, badge, description, image, every button's link, text, `rel` and `target`, disclosure and Prime.

**User action:** the developer runs `wp affibloom migrate --settings`. 263 products are created, and the site's Lasso settings are imported:
- link defaults;
- button texts and disclosure;
- colours;
- tracking ID;
- the Prime toggle.

Running it again creates nothing. `tools/compare-migration.php` then compares every box in `boxes.json` with the migrated product and lists any difference.

**Request path:**
```
wp affibloom migrate --settings [--update]
  → MigrationService::run()              → ProductRepository::save() ×263 (with _affibloom_lasso_id)
  → MigrationService::import_settings()  LassoSource::settings() → SettingsService::update_tab() ×3 (second caller)
wp eval-file tools/compare-migration.php
  → boxes.json entry → LassoIdMap → product → BoxBuilder::build() → compare field by field
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `MigrationService::import_settings()` and the settings map | `MigrateCommand --settings` |
| `--settings` flag | the developer |
| `tools/compare-migration.php` | the developer |

**Settings map** (Lasso `lasso_settings` key → Affibloom key). Check each key's real value in `tests/fixtures/inventory/settings.json` first.

| Lasso | Affibloom | Tab |
|---|---|---|
| `open_new_tab`, `enable_nofollow`, `enable_sponsored`, `show_disclosure` | `new_tab`, `nofollow`, `sponsored`, `show_disclosure` | general |
| `check_duplicate_link` | `allow_duplicates` (inverted, if the inventory confirms that `check_duplicate_link` on means "refuse duplicates") | general |
| `primary_button_text`, `secondary_button_text`, `disclosure_text`, `link_from_display_title` | same names | display |
| `display_color_main`, `display_color_title`, `display_color_background`, `display_color_button`, `display_color_secondary_button`, `display_color_button_text` | `color_main` … `color_button_text` | display |
| `amazon_tracking_id`, `enable_amazon_prime` | `tracking_id`, `show_prime` | amazon |

**Files:**
- Create: `tools/compare-migration.php`
- Modify: `MigrationService.php`, `MigrateCommand.php`, `Plugin.php`, `SettingsService.php`

- [ ] **Step 1: Check that real booleans are read correctly**

Lasso stores booleans as `true`/`false`, not as the `"1"` a checkbox sends. Task 9's `bool` cleaner accepts only `true`, `1`, `"1"`, `"on"`, `"true"` and `"yes"`, so `false` means off. Add this test to prove it, before relying on it:

```php
	public function test_false_turns_a_setting_off(): void {
		$repo = $this->repository();
		( new SettingsService( $repo ) )->update_tab( 'general', array( 'new_tab' => false, 'nofollow' => true ) );
		self::assertFalse( $repo->get()->bool( 'new_tab' ) );
		self::assertTrue( $repo->get()->bool( 'nofollow' ) );
	}
```

Expected: it passes without code changes. If it fails, fix the cleaner before going on.

- [ ] **Step 2: Settings import**

`MigrationService` takes `private SettingsService $settings_service`. Add:

```php
	/** Lasso setting → [tab, our key, invert?]. */
	private const SETTINGS = array(
		'open_new_tab'                   => array( 'general', 'new_tab', false ),
		'enable_nofollow'                => array( 'general', 'nofollow', false ),
		'enable_sponsored'               => array( 'general', 'sponsored', false ),
		'show_disclosure'                => array( 'general', 'show_disclosure', false ),
		'check_duplicate_link'           => array( 'general', 'allow_duplicates', true ),
		'primary_button_text'            => array( 'display', 'primary_button_text', false ),
		'secondary_button_text'          => array( 'display', 'secondary_button_text', false ),
		'disclosure_text'                => array( 'display', 'disclosure_text', false ),
		'link_from_display_title'        => array( 'display', 'link_from_display_title', false ),
		'display_color_main'             => array( 'display', 'color_main', false ),
		'display_color_title'            => array( 'display', 'color_title', false ),
		'display_color_background'       => array( 'display', 'color_background', false ),
		'display_color_button'           => array( 'display', 'color_button', false ),
		'display_color_secondary_button' => array( 'display', 'color_secondary_button', false ),
		'display_color_button_text'      => array( 'display', 'color_button_text', false ),
		'amazon_tracking_id'             => array( 'amazon', 'tracking_id', false ),
		'enable_amazon_prime'            => array( 'amazon', 'show_prime', false ),
	);

	/**
	 * Copies Lasso's settings into ours, through the same validation as the Settings screen.
	 * Settings Lasso does not have keep their current value.
	 *
	 * @return list<string> Keys that were refused.
	 */
	public function import_settings(): array {
		$lasso   = $this->source->settings();
		$current = $this->settings->get()->to_array();
		$by_tab  = array();

		foreach ( self::SETTINGS as $lasso_key => [ $tab, $key, $invert ] ) {
			$by_tab[ $tab ] ??= array_intersect_key( $current, $this->settings_service->fields( $tab ) );
			if ( array_key_exists( $lasso_key, $lasso ) ) {
				$value                  = $lasso[ $lasso_key ];
				$by_tab[ $tab ][ $key ] = $invert ? ! filter_var( $value, FILTER_VALIDATE_BOOLEAN ) : $value;
			}
		}

		$refused = array();
		foreach ( $by_tab as $tab => $input ) {
			$refused = array_merge( $refused, $this->settings_service->update_tab( $tab, $input ) );
		}
		return $refused;
	}
```

Each tab is sent complete, built from the current values, because `update_tab()` treats a missing boolean as "off". That is right for a form, and wrong here.

`SettingsService`: add the public `fields( string $tab ): array`, which returns `self::FIELDS[ $tab ]`. Its first caller is this method.

`MigrateCommand`: a `[--settings]` flag. With it, after `run()`, call `import_settings()` and print the refused keys as warnings. With `--dry-run`, print the settings that would be imported without saving them: add `plan_settings()`, the same loop that returns `$by_tab` instead of saving.

- [ ] **Step 3: Run it**

```bash
bash tools/reset-local.sh
npm run wp -- affibloom migrate --settings --report=wp-content/plugins/affibloom/.local/migration-run.csv
npm run wp -- affibloom migrate
```
Expected:
- the first run reports `263 records: 263 to create, …, 0 errors.` and no refused settings;
- the second run reports `263 already migrated`;
- `wp option get affibloom_settings` shows `tracking_id: vbvault-20` and the site's colours.

- [ ] **Step 4: Compare every box with the baseline**

`tools/compare-migration.php`:

```php
<?php
/**
 * Compares every box in boxes.json with what the migrated product would render.
 * Run: npm run wp -- eval-file wp-content/plugins/affibloom/tools/compare-migration.php
 *
 * @package Affibloom
 */

( static function () {
	$boxes = json_decode( (string) file_get_contents( dirname( __DIR__ ) . '/tests/fixtures/baselines/boxes.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$plugin = $GLOBALS['affibloom_plugin'] ?? null; // set in Plugin::boot() only under WP_CLI (see below)
	if ( ! $plugin ) {
		WP_CLI::error( 'Run with Affibloom active.' );
	}
	[ $map, $products, $builder ] = $plugin->for_tools();

	$problems = 0;
	foreach ( $boxes as $box ) {
		$product_id = $map->product_for( (int) $box['lasso_id'] );
		$product    = $product_id ? $products->get( $product_id ) : null;
		if ( ! $product ) {
			WP_CLI::warning( "{$box['guide']} #{$box['index']}: no product for Lasso {$box['lasso_id']}" );
			++$problems;
			continue;
		}
		// A box's own badge or description comes from its block (Task 27), so only product values are compared here.
		$view   = $builder->build( $product, \Affibloom\Application\Box\BoxOptions::none() );
		$checks = array(
			'button1.href'   => array( $box['button1']['href'] ?? '', $view->button1->url ),
			'button1.text'   => array( $box['button1']['text'] ?? '', $view->button1->text ),
			'button1.rel'    => array( (string) ( $box['button1']['rel'] ?? '' ), $view->button1->rel ),
			'button1.target' => array( (string) ( $box['button1']['target'] ?? '' ), $view->button1->target ),
			'button2.href'   => array( $box['button2']['href'] ?? '', $view->button2->url ?? '' ),
			'button2.text'   => array( $box['button2']['text'] ?? '', $view->button2->text ?? '' ),
			'image'          => array( $box['image'], $view->image_url ),
			'prime'          => array( $box['prime'] ? 'yes' : 'no', $view->prime ? 'yes' : 'no' ),
			'disclosure'     => array( $box['disclosure'], $view->disclosure ),
			'rating'         => array( $box['rating'], $view->rating ),
		);
		foreach ( $checks as $what => [ $lasso, $ours ] ) {
			if ( trim( (string) $lasso ) !== trim( (string) $ours ) ) {
				WP_CLI::log( "{$box['guide']} #{$box['index']} (Lasso {$box['lasso_id']}) $what: Lasso \"$lasso\" vs \"$ours\"" );
				++$problems;
			}
		}
	}
	$problems ? WP_CLI::warning( "$problems differences." ) : WP_CLI::success( 'Every box matches.' );
} )();
```

`Plugin::boot()`: when `defined( 'WP_CLI' ) && WP_CLI`, keep the instance in `$GLOBALS['affibloom_plugin']`. Add a public `for_tools(): array` that returns `array( $this->lasso_map(), $this->product_repository(), new BoxBuilder( $this->settings_repository() ) )`. It exists only for the tools scripts. Its docblock says so, and it is never called by plugin code.

```bash
npm run wp -- eval-file wp-content/plugins/affibloom/tools/compare-migration.php
```

Fix every difference by changing the mapper rule that caused it, with a unit test. Then reset, migrate and compare again, until the result is `Success: Every box matches.` or every remaining difference is recorded in `docs/parity.md`, with the reason and your sign-off. Expected kinds of difference:
- a button `href` that Lasso cloaked or kept as a short link;
- a description that Lasso took from Amazon data;
- Amazon links where Lasso kept extra parameters (`th`, `psc`, `linkCode`) that the clean `/dp/<ASIN>?tag=` form drops.

The last case leads to the same product with the same tag, but `th=1` can preselect a size or colour. Decide with the client. Either accept it (record it in `docs/parity.md`), or add a `keep_parameters` flag to `AmazonLinkService::normalize()`, used only by the migration, which retags Lasso's URL with `AmazonUrl::retag()` instead of rebuilding it. If you add the flag, add it with a unit test in this step, and pass it through `ProductInput::changes( $fields, true )` from `MigrationService`.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "Run the migration and import Lasso's settings

263 products with their Lasso IDs, idempotent on reruns; settings go
through the Settings screen's validation. Every migrated box is
compared field by field with Lasso's baseline."
```

---

### Task 27: A guide renders with Lasso switched off — `[lasso]` compatibility

> **From Task 15C (2026-11-13, F1 D14):** `[lasso id=…]` of a retired product renders the product it points to,
> by Task 18's rule.

**Roadmap:** 5.2 · **Feature:** F10 (AC2)

**Why:** the 250 existing boxes are saved as `[lasso id="…" …]` inside Lasso's block. With Lasso off, WordPress renders an unregistered block's saved content, which is the shortcode. Something must then answer `[lasso]`.

**User action:** with Lasso deactivated and the migration done, a reader opens any of the 34 guides and sees every box as before. Each box keeps the badge and description set on it, and an anchor id when one was set.

**Request path:**
```
the_content: do_blocks → affiliate-plugin/lasso is not registered → its saved HTML "[lasso id=… badge=…]"
            → do_shortcode → LassoShortcode::render( atts )   (registered only when Lasso is inactive)
                 type set and not "single" → '' (logged)
                 LassoIdMap::product_for( id ) → ProductRepository::get()  → missing → '' (logged)
                 LegacyBoxOptions::from_atts( atts ) → BoxOptions
                 BoxHtml::render( product, options )
wp_enqueue_scripts: BoxAssets::has_box() also sees legacy blocks and [lasso]
```

**Attribute map.** Start from `tests/fixtures/inventory/attributes.json`, and add any attribute found there that this table lacks.

| `[lasso]` attribute | Effect |
|---|---|
| `id` | the Lasso record, mapped to the product |
| `link_id`, `ref`, `theme` | ignored (location and cloaking ids; one theme in use) |
| `badge` | text overrides; `hide` hides |
| `description` | text overrides (Lasso's `lasso-br-code` → `<br>`); `hide` hides |
| `title` | text overrides the name; `hide` hides the title |
| `title_url` | overrides the link of the title and the image; Lasso then leaves out `target` (`single.php` lines 202–203). Used on 4 boxes, all on `best-womens-volleyball-shoes`, each equal to that box's `primary_url`. *Added in Task 4 from the inventory.* |
| `title_type` | Lasso wraps the title in `<value>…</value>` (`single.php` lines 221–223), meant for a heading tag. The one use on the site is `title_type="Best Lightweight"` (`best-volleyball-shoes-for-setters`), which makes invalid markup: accept `h2`–`h6`, ignore anything else and log it. Ask at gate 4 whether that box's text should become its badge. *Added in Task 4 from the inventory.* |
| `rating` | number overrides; `hide` hides |
| `primary_url`, `primary_text` | override the primary button's link and label |
| `secondary_url`, `secondary_text` | override the second button's link and label |
| `image_url`, `image_alt` | override the image and its alt text |
| `disclosure_text` | overrides the text and shows the disclosure |
| `anchor_id` | `id` on the container |
| `type` | '' or `single` render; anything else renders nothing and is logged (none expected on the site) |

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Legacy\LassoShortcode` | `init` (priority 20), `do_shortcode` |
| `Application\Box\LegacyBoxOptions::from_atts()` | `LassoShortcode` |
| `BoxOptions` fields `title`, `rating`, `primary_url`, `primary_text`, `secondary_url`, `secondary_text`, `image_url`, `image_alt`, `disclosure`, `anchor_id` | `BoxBuilder` |
| `BoxView::$anchor_id`, title-hidden handling | `templates/box.php` |
| legacy detection in `BoxAssets::has_box()` | `wp_enqueue_scripts` |

**Files:**
- Create: `src/Interface/Legacy/LassoShortcode.php`, `src/Application/Box/LegacyBoxOptions.php`, `tests/Unit/Application/LegacyBoxOptionsTest.php`, `tests/Integration/LassoShortcodeTest.php`
- Modify: `BoxOptions.php`, `BoxBuilder.php`, `BoxView.php`, `templates/box.php`, `BoxAssets.php`, `Plugin.php`

- [ ] **Step 1: Failing tests**

`tests/Integration/LassoShortcodeTest.php`:

```php
<?php
namespace Affibloom\Tests\Integration;

use WP_UnitTestCase;

final class LassoShortcodeTest extends WP_UnitTestCase {
	public function test_a_lasso_box_renders_its_migrated_product_with_its_own_badge(): void {
		$product = self::factory()->post->create(
			array(
				'post_type'  => 'affibloom_product',
				'post_title' => 'Mizuno Wave Momentum 3',
				'meta_input' => array(
					'_affibloom_url'      => 'https://www.amazon.com/dp/B0C1234567?tag=vbvault-20',
					'_affibloom_lasso_id' => '4321',
					'_affibloom_badge'    => 'Product badge',
				),
			)
		);
		wp_cache_flush();

		$html = do_blocks( '<!-- wp:affiliate-plugin/lasso -->[lasso id="4321" link_id="77" badge="Best Overall"]<!-- /wp:affiliate-plugin/lasso -->' );
		$html = do_shortcode( $html );

		self::assertStringContainsString( 'Mizuno Wave Momentum 3', $html );
		self::assertStringContainsString( '>Best Overall<', $html );
		self::assertStringNotContainsString( 'Product badge', $html );
		self::assertStringContainsString( 'data-affibloom-id="' . $product . '"', $html );
	}

	public function test_an_unknown_record_renders_nothing(): void {
		self::assertSame( '', trim( do_shortcode( '[lasso id="999999"]' ) ) );
	}
}
```

`npm run test:integration` fails: the shortcode is printed as text.

- [ ] **Step 2: Entry point: the shortcode, only without Lasso**

`src/Interface/Legacy/LassoShortcode.php`:

```php
<?php
/**
 * Answers [lasso …] when Lasso is not active, so the 250 existing boxes keep rendering.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Legacy;

use Affibloom\Application\Box\LegacyBoxOptions;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Infrastructure\Lasso\LassoIdMap;
use Affibloom\Interface\Front\BoxHtml;
use Affibloom\Module;

/**
 * Answers [lasso …] when Lasso is not active, so the 250 existing boxes keep rendering.
 */
final class LassoShortcode implements Module {

	/**
	 * Builds the shortcode.
	 *
	 * @param LassoIdMap        $map      Lasso ID → product ID.
	 * @param ProductRepository $products Product storage.
	 * @param BoxHtml           $html     Box renderer.
	 */
	public function __construct(
		private LassoIdMap $map,
		private ProductRepository $products,
		private BoxHtml $html,
	) {}

	/**
	 * After plugins have registered their shortcodes.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'maybe_register' ), 20 );
	}

	/**
	 * Takes over [lasso] only when Lasso itself is not running.
	 */
	public function maybe_register(): void {
		if ( defined( 'LASSO_PLUGIN_PATH' ) || shortcode_exists( 'lasso' ) ) {
			return;
		}
		add_shortcode( 'lasso', array( $this, 'render' ) );
	}

	/**
	 * Renders one [lasso] box.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes ('' when there are none).
	 */
	public function render( $atts ): string {
		$atts = is_array( $atts ) ? $atts : array();
		// Lasso's own repair: attributes saved as &quot;value&quot;.
		$atts = array_map( static fn( $value ): string => (string) preg_replace( '/^&quot;|&quot;$/', '', (string) $value ), $atts );

		$type = strtolower( $atts['type'] ?? '' );
		if ( '' !== $type && 'single' !== $type ) {
			$this->log( 'unsupported type "' . $type . '"', $atts );
			return '';
		}

		$product_id = $this->map->product_for( absint( $atts['id'] ?? 0 ) );
		$product    = null !== $product_id ? $this->products->get( $product_id ) : null;
		if ( null === $product ) {
			$this->log( 'no product for this Lasso record', $atts );
			return '';
		}

		return $this->html->render( $product, LegacyBoxOptions::from_atts( $atts ) );
	}

	/**
	 * A box that could not render: logged where WordPress logs errors, never shown to readers.
	 *
	 * @param string                $why  Reason.
	 * @param array<string, string> $atts Attributes.
	 */
	private function log( string $why, array $atts ): void {
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( 'Affibloom [lasso]: ' . $why . ' ' . wp_json_encode( $atts ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
		do_action( 'affibloom_legacy_box_failed', $why, $atts );
	}
}
```

- [ ] **Step 3: Options from attributes**

`BoxOptions`: add the fields, all `?string` and all `null` by default, after `description`: `title`, `rating`, `primary_url`, `primary_text`, `secondary_url`, `secondary_text`, `image_url`, `image_alt`, `disclosure`, `anchor_id`. Each has a `@param` line.

`src/Application/Box/LegacyBoxOptions.php`:

```php
<?php
/**
 * [lasso] attributes → BoxOptions, with Lasso's "hide" and empty rules.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Box;

/**
 * [lasso] attributes → BoxOptions, with Lasso's "hide" and empty rules.
 */
final class LegacyBoxOptions {

	/**
	 * Maps the attributes the site uses (tests/fixtures/inventory/attributes.json).
	 *
	 * @param array<string, string> $atts Shortcode attributes.
	 */
	public static function from_atts( array $atts ): BoxOptions {
		$hideable = static function ( string $key ) use ( $atts ): ?string {
			$value = trim( $atts[ $key ] ?? '' );
			if ( 'hide' === strtolower( $value ) ) {
				return '';
			}
			return '' === $value ? null : $value;
		};
		$text     = static function ( string $key ) use ( $atts ): ?string {
			$value = trim( $atts[ $key ] ?? '' );
			return '' === $value ? null : $value;
		};

		$description = $hideable( 'description' );
		if ( null !== $description ) {
			$description = str_replace( 'lasso-br-code', '<br>', $description );
		}

		return new BoxOptions(
			badge: $hideable( 'badge' ),
			description: $description,
			title: $hideable( 'title' ),
			rating: $hideable( 'rating' ),
			primary_url: $text( 'primary_url' ),
			primary_text: $text( 'primary_text' ),
			secondary_url: $text( 'secondary_url' ),
			secondary_text: $text( 'secondary_text' ),
			image_url: $text( 'image_url' ),
			image_alt: $text( 'image_alt' ),
			disclosure: $text( 'disclosure_text' ),
			anchor_id: $text( 'anchor_id' ),
		);
	}
}
```

`tests/Unit/Application/LegacyBoxOptionsTest.php` gives one test per row of the attribute map. Include `badge="hide"`, `description` with `lasso-br-code`, and an empty `title=""` (which means "use the product").

- [ ] **Step 4: The builder applies the new options**

In `BoxBuilder::build()`, apply each override on top of the product's value:

```php
		$name = $options->title ?? $product->name;
		$button1 = new BoxButton(
			$options->primary_url ?? $product->url->value(),
			$options->primary_text ?? ( '' !== $product->button_text ? $product->button_text : $settings->string( 'primary_button_text' ) ),
			$link->rel,
			$link->target
		);
		// …the same for button2 with secondary_url / secondary_text (an override URL creates button2 if the product has none)…

		if ( null !== $options->disclosure ) {
			$disclosure = $options->disclosure; // Lasso shows the disclosure whenever the attribute is set.
		}
```

and pass:
- `name: $name`;
- `image_url: $options->image_url ?? $product->image_url`;
- `image_alt: $options->image_alt ?? $name`;
- `rating: $options->rating ?? ( $product->rating ? (string) $product->rating : '' )`;
- `anchor_id: $options->anchor_id ?? ''`.

`BoxView` gets `public readonly string $anchor_id = '',`. The template treats an empty `name` as "no title":

```php
<div class="affibloom-container"<?php echo '' !== $box->anchor_id ? ' id="' . esc_attr( $box->anchor_id ) . '"' : ''; ?>>
```

```php
			<?php if ( '' !== $box->name && $box->title_link ) : ?>
				…link…
			<?php elseif ( '' !== $box->name ) : ?>
				…span…
			<?php endif; ?>
```

`BoxBuilderTest`: add one test per override.

- [ ] **Step 5: CSS on legacy pages**

`BoxAssets::has_box()`:

```php
		return has_block( ProductBoxBlock::NAME, $post )
			|| has_block( 'affiliate-plugin/lasso', $post )
			|| has_shortcode( $post->post_content, 'lasso' );
```

`Plugin`: add `new LassoShortcode( $this->lasso_map(), $this->product_repository(), $box_html ),` to the modules.

- [ ] **Step 6: Run and look**

```bash
composer lint && composer analyse && composer test:unit && npm run test:integration
bash tools/reset-local.sh && npm run wp -- affibloom migrate --settings
npm run wp -- plugin deactivate lasso
```
Open `/best-volleyball-shoes-for-hitters/` and compare it with its baseline screenshots. The full automated check is Task 29. Then reactivate Lasso:

```bash
npm run wp -- plugin activate lasso
```
Expected: Lasso renders its boxes again. Affibloom stepped aside, because `LASSO_PLUGIN_PATH` is defined.

- [ ] **Step 7: Commit**

```bash
git add -A && git commit -m "Render [lasso] boxes from migrated products when Lasso is off

Every attribute the site uses is mapped. Affibloom steps aside
whenever Lasso is active, so switching back is instant."
```

---

### Task 28: Converting legacy blocks — `wp affibloom convert-legacy-blocks`

**Roadmap:** 5.5 · **Feature:** F10 (AC3) · **ADR-3**

**Why:** with Lasso off, the editor shows each of the 250 Lasso blocks as "unsupported". Readers are fine (Task 27), but editors could not change a box. Converting them to the new block, with a stored copy of each post for revert, removes that problem at cutover, without hand editing.

**User action:** the developer runs `wp affibloom convert-legacy-blocks --dry-run`. A report lists, per post, the boxes to convert and any that must stay as a shortcode (with the reason). Without `--dry-run`, every post's content is converted; each post keeps a revision and a stored original. `--revert` puts the originals back.

**Request path:**
```
wp affibloom convert-legacy-blocks [--dry-run] [--post=<id>] [--revert]
  → ConvertBlocksCommand → LegacyBlockConverter::convert( content )
       parse_blocks() → each affiliate-plugin/lasso block → its [lasso] attributes
         only id/link_id/ref/theme/type/badge/description/anchor_id → affibloom/product-box { productId, badge, hideBadge, description, hideDescription }
         any other attribute → core/shortcode with the same [lasso …] (still rendered by Task 27)
       serialize_blocks()
  → write: update_post_meta( _affibloom_pre_conversion, original ) once; wp_update_post( content ) (revision)
  → revert: content ← _affibloom_pre_conversion; meta deleted
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\Migration\{LegacyBlockConverter, ConversionResult}` | `ConvertBlocksCommand` |
| `Interface\Cli\ConvertBlocksCommand` | `CliCommands` |
| post meta `_affibloom_pre_conversion` | the command's write and revert paths |

**Files:**
- Create: `src/Application/Migration/{LegacyBlockConverter,ConversionResult}.php`, `src/Interface/Cli/ConvertBlocksCommand.php`, `tests/Integration/LegacyBlockConverterTest.php`
- Modify: `src/Plugin.php`

The new block keeps only the badge and description (F7), so anchor ids are lost. If Task 4 found `anchor_id` in use (for example as the target of an in-page link), add an `anchorId` attribute to the block in this task, rather than leaving those boxes as shortcodes.

- [ ] **Step 1: Failing test**

`tests/Integration/LegacyBlockConverterTest.php` covers three cases:
1. A block with `id`, `link_id` and `badge` becomes `<!-- wp:affibloom/product-box {"productId":N,"badge":"Best Overall"} /-->`.
2. A block with `primary_text` becomes a `core/shortcode` block holding the same shortcode, and is reported.
3. A block whose `id` has no product is left unchanged, and is reported.

- [ ] **Step 2: Converter**

`src/Application/Migration/ConversionResult.php`:

```php
<?php
/**
 * Converted content and what happened to each legacy box.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Migration;

/**
 * Converted content and what happened to each legacy box.
 */
final class ConversionResult {

	/**
	 * Builds the result.
	 *
	 * @param string       $content   New post content.
	 * @param int          $converted Boxes now product-box blocks.
	 * @param list<string> $kept      Boxes kept as [lasso] shortcodes, with the reason.
	 */
	public function __construct(
		public readonly string $content,
		public readonly int $converted,
		public readonly array $kept,
	) {}
}
```

`src/Application/Migration/LegacyBlockConverter.php`:

```php
<?php
/**
 * Converts affiliate-plugin/lasso blocks to affibloom/product-box blocks, where nothing would be lost.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Migration;

use Affibloom\Infrastructure\Lasso\LassoIdMap;

/**
 * Converts affiliate-plugin/lasso blocks to affibloom/product-box blocks, where nothing would be lost.
 */
final class LegacyBlockConverter {

	/** Attributes the new block carries, or that do not affect rendering. */
	private const CONVERTIBLE = array( 'id', 'link_id', 'ref', 'theme', 'type', 'badge', 'description' );

	/**
	 * Builds the converter.
	 *
	 * @param LassoIdMap $map Lasso ID → product ID.
	 */
	public function __construct( private LassoIdMap $map ) {}

	/**
	 * Converts a post's content.
	 *
	 * @param string $content Post content.
	 */
	public function convert( string $content ): ConversionResult {
		$converted = 0;
		$kept      = array();
		$blocks    = $this->walk( parse_blocks( $content ), $converted, $kept );

		return new ConversionResult( serialize_blocks( $blocks ), $converted, $kept );
	}

	/**
	 * Replaces legacy blocks, also inside groups and columns.
	 *
	 * @param list<array<string, mixed>> $blocks    Parsed blocks.
	 * @param int                        $converted Counter.
	 * @param list<string>               $kept      Reasons.
	 * @return list<array<string, mixed>>
	 */
	private function walk( array $blocks, int &$converted, array &$kept ): array {
		foreach ( $blocks as $i => $block ) {
			if ( ! empty( $block['innerBlocks'] ) ) {
				$blocks[ $i ]['innerBlocks'] = $this->walk( $block['innerBlocks'], $converted, $kept );
			}
			if ( 'affiliate-plugin/lasso' !== $block['blockName'] ) {
				continue;
			}

			$shortcode = trim( (string) $block['innerHTML'] );
			$atts      = $this->atts( $shortcode );
			$product   = $this->map->product_for( absint( $atts['id'] ?? 0 ) );
			$extra     = array_diff( array_keys( $atts ), self::CONVERTIBLE );
			$type      = strtolower( $atts['type'] ?? '' );

			if ( null === $product ) {
				$kept[] = "Lasso {$shortcode}: no migrated product; left unchanged";
				continue;
			}
			if ( $extra || ( '' !== $type && 'single' !== $type ) ) {
				$kept[]       = "{$shortcode}: kept as a shortcode because of " . implode( ', ', $extra ? $extra : array( 'type' ) );
				$blocks[ $i ] = array(
					'blockName'    => 'core/shortcode',
					'attrs'        => array(),
					'innerBlocks'  => array(),
					'innerHTML'    => $shortcode,
					'innerContent' => array( $shortcode ),
				);
				continue;
			}

			$attrs = array( 'productId' => $product );
			$badge = trim( $atts['badge'] ?? '' );
			if ( 'hide' === strtolower( $badge ) ) {
				$attrs['hideBadge'] = true;
			} elseif ( '' !== $badge ) {
				$attrs['badge'] = $badge;
			}
			$description = trim( $atts['description'] ?? '' );
			if ( 'hide' === strtolower( $description ) ) {
				$attrs['hideDescription'] = true;
			} elseif ( '' !== $description ) {
				$attrs['description'] = str_replace( 'lasso-br-code', "\n", $description );
			}

			$blocks[ $i ] = array(
				'blockName'    => 'affibloom/product-box',
				'attrs'        => $attrs,
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			);
			++$converted;
		}
		return $blocks;
	}

	/**
	 * The [lasso] shortcode's attributes.
	 *
	 * @param string $shortcode Shortcode text.
	 * @return array<string, string>
	 */
	private function atts( string $shortcode ): array {
		if ( ! preg_match( '/' . get_shortcode_regex( array( 'lasso' ) ) . '/', $shortcode, $match ) ) {
			return array();
		}
		$atts = shortcode_parse_atts( $match[3] );
		return is_array( $atts ) ? array_map( 'strval', $atts ) : array();
	}
}
```

- [ ] **Step 3: Command**

`src/Interface/Cli/ConvertBlocksCommand.php`:

```php
<?php
/**
 * The wp affibloom convert-legacy-blocks command.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Cli;

use Affibloom\Application\Migration\LegacyBlockConverter;
use WP_CLI;
use wpdb;

/**
 * The wp affibloom convert-legacy-blocks command.
 */
final class ConvertBlocksCommand {

	/** The post's content before its first conversion, for --revert. */
	public const META = '_affibloom_pre_conversion';

	/**
	 * Builds the command.
	 *
	 * @param LegacyBlockConverter $converter Block conversion.
	 * @param wpdb                 $db        Database (finding posts by content).
	 */
	public function __construct( private LegacyBlockConverter $converter, private wpdb $db ) {}

	/**
	 * Converts Lasso blocks in published posts and pages to product-box blocks.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Report only.
	 *
	 * [--post=<id>]
	 * : Only this post.
	 *
	 * [--revert]
	 * : Put back the content stored before the first conversion.
	 *
	 * ## EXAMPLES
	 *
	 *     wp affibloom convert-legacy-blocks --dry-run --user=admin
	 *
	 * @param list<string>          $args       Positional arguments (none).
	 * @param array<string, string> $assoc_args Flags.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		// Without unfiltered_html, wp_update_post() would run kses over the whole post and could strip embeds.
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			WP_CLI::error( 'Run as an administrator (--user=<login>), so WordPress does not filter the post HTML.' );
		}

		$dry  = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$only = isset( $assoc_args['post'] ) ? absint( $assoc_args['post'] ) : 0;

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'revert', false ) ) {
			$this->revert( $only, $dry );
			return;
		}

		$rows      = array();
		$converted = 0;
		$kept      = 0;
		foreach ( $this->posts_with_lasso_blocks( $only ) as $id ) {
			$post   = get_post( $id );
			$result = $this->converter->convert( $post->post_content );
			$rows[] = array(
				'post'      => $id,
				'title'     => $post->post_title,
				'converted' => $result->converted,
				'kept'      => count( $result->kept ),
			);

			$converted += $result->converted;
			$kept      += count( $result->kept );
			foreach ( $result->kept as $reason ) {
				WP_CLI::warning( "Post $id: $reason" );
			}

			if ( $dry || 0 === $result->converted ) {
				continue;
			}
			if ( '' === (string) get_post_meta( $id, self::META, true ) ) {
				add_post_meta( $id, self::META, wp_slash( $post->post_content ), true );
			}
			$updated = wp_update_post(
				array(
					'ID'           => $id,
					'post_content' => wp_slash( $result->content ),
				),
				true
			);
			if ( is_wp_error( $updated ) ) {
				WP_CLI::warning( "Post $id: " . $updated->get_error_message() );
			}
		}

		WP_CLI\Utils\format_items( 'table', $rows, array( 'post', 'title', 'converted', 'kept' ) );
		WP_CLI::success(
			sprintf( '%d posts: %d boxes %s, %d kept as shortcodes%s.', count( $rows ), $converted, $dry ? 'to convert' : 'converted', $kept, $dry ? ' (dry run)' : '' )
		);
	}

	/**
	 * Published posts and pages whose content holds a Lasso block.
	 *
	 * @param int $only One post ID, or 0 for all.
	 * @return list<int>
	 */
	private function posts_with_lasso_blocks( int $only ): array {
		if ( $only ) {
			return array( $only );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- one-off CLI scan; WP_Query cannot search block comments.
		$ids = $this->db->get_col(
			$this->db->prepare(
				"SELECT ID FROM {$this->db->posts} WHERE post_status = 'publish' AND post_type IN ('post','page') AND post_content LIKE %s ORDER BY ID",
				'%' . $this->db->esc_like( 'wp:affiliate-plugin/lasso' ) . '%'
			)
		);
		return array_map( 'intval', $ids );
	}

	/**
	 * Restores stored originals.
	 *
	 * @param int  $only One post ID, or 0 for all.
	 * @param bool $dry  Report only.
	 */
	private function revert( int $only, bool $dry ): void {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$ids = $only ? array( $only ) : array_map( 'intval', $this->db->get_col( $this->db->prepare( "SELECT post_id FROM {$this->db->postmeta} WHERE meta_key = %s", self::META ) ) );

		$restored = 0;
		foreach ( $ids as $id ) {
			$original = (string) get_post_meta( $id, self::META, true );
			if ( '' === $original ) {
				continue;
			}
			if ( ! $dry ) {
				wp_update_post(
					array(
						'ID'           => $id,
						'post_content' => wp_slash( $original ),
					)
				);
				delete_post_meta( $id, self::META );
			}
			++$restored;
			WP_CLI::log( "Post $id restored" . ( $dry ? ' (dry run)' : '' ) );
		}
		WP_CLI::success( "$restored posts restored." );
	}
}
```

Register it in `Plugin` under `'affibloom convert-legacy-blocks'`, with `new ConvertBlocksCommand( new LegacyBlockConverter( $this->lasso_map() ), $GLOBALS['wpdb'] )`. Each `wp affibloom convert-legacy-blocks` call in this plan runs with `--user=<an administrator>`. Add `--user=vvadmin` locally, and the real login on staging and production.

- [ ] **Step 4: Run, revert, run**

```bash
bash tools/reset-local.sh && npm run wp -- affibloom migrate --settings
npm run wp -- affibloom convert-legacy-blocks --dry-run --user=vvadmin
```
Expected: `34 posts: 250 boxes to convert, 0 kept`, or a short, explained list of boxes that are kept.

```bash
npm run wp -- affibloom convert-legacy-blocks --user=vvadmin
npm run wp -- plugin deactivate lasso
```
Open a converted guide in the editor. Expected: the boxes are product-box blocks, and selecting one shows its badge in the sidebar.

```bash
npm run wp -- affibloom convert-legacy-blocks --revert --user=vvadmin
```
Expected: the content is byte-for-byte the original. Check with `npm run wp -- post get <id> --field=post_content | md5sum`, before conversion and after revert.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "Convert Lasso blocks to product-box blocks, with revert

Only boxes that lose nothing are converted; the rest stay as [lasso]
shortcodes, which Task 27 renders. The original content is stored
once per post for --revert."
```

---

### Task 29: Parity run — all 34 guides with Lasso off

**Roadmap:** 5.6 (exit) · **Feature:** F10 (AC2)

**Why:** this is the proof for F10. Switching Lasso off must change nothing a reader sees, or clicks, on any guide.

**User action:** the developer runs the parity suite. It resets the local copy, migrates, switches Lasso off (optionally converting blocks first), and checks every guide against its baseline: the full-page screenshot, every box's values, and every link `href` in the article. Then it repeats with the blocks converted.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `tests/Visual/parity.spec.ts` (`@local`) | `npm run test:e2e -- parity`; again on staging in Task 40 |
| `docs/parity.md` | you: accepted differences and sign-off |

**Files:** create the two above.

- [ ] **Step 1: The suite**

`tests/Visual/parity.spec.ts`:

```ts
import { test, expect } from '@playwright/test';
import { execSync } from 'node:child_process';
import { readFileSync, mkdirSync } from 'node:fs';
import { wp } from '../E2E/utils';
import { diffRatio } from './compare';

const BASE = 'tests/fixtures/baselines';
const boxes = JSON.parse( readFileSync( `${ BASE }/boxes.json`, 'utf8' ) );
const guides: string[] = [ ...new Set< string >( boxes.map( ( b ) => b.guide ) ) ];
const PAGE_TOLERANCE = 0.02; // whole pages: agreed in Task 6
const MODE = process.env.PARITY_MODE ?? 'shortcode'; // "shortcode" or "converted"

mkdirSync( 'test-results/parity', { recursive: true } );

test.beforeAll( () => {
	execSync( 'bash tools/reset-local.sh', { stdio: 'inherit' } );
	wp( 'affibloom migrate --settings' );
	if ( MODE === 'converted' ) {
		wp( 'affibloom convert-legacy-blocks --user=vvadmin' );
	}
	wp( 'plugin deactivate lasso' );
} );

for ( const guide of guides ) {
	test( `@local ${ guide } matches Lasso with Lasso off (${ MODE })`, async ( { page } ) => {
		const external: string[] = [];
		page.on( 'request', ( r ) => /codedrink|lasso\.link|getlasso/.test( r.url() ) && external.push( r.url() ) );

		await page.setViewportSize( { width: 1280, height: 900 } );
		await page.goto( `/${ guide }/` );
		await page.waitForLoadState( 'networkidle' );

		// 1. Every box's values, in page order.
		const expected = boxes.filter( ( b ) => b.guide === guide );
		const actual = await page.locator( '.affibloom-container' ).evaluateAll( ( containers ) =>
			containers.map( ( c ) => {
				const link = ( sel: string ) => {
					const a = c.querySelector< HTMLAnchorElement >( sel );
					return a ? { href: a.getAttribute( 'href' ), text: a.textContent?.trim(), rel: a.getAttribute( 'rel' ), target: a.getAttribute( 'target' ) } : null;
				};
				return {
					title: c.querySelector( '.affibloom-title' )?.textContent?.trim() ?? '',
					badge: c.querySelector( '.affibloom-badge-text' )?.textContent?.trim() ?? '',
					description: c.querySelector( '.affibloom-description' )?.textContent?.trim() ?? '',
					image: c.querySelector( '.affibloom-image img' )?.getAttribute( 'src' ) ?? '',
					prime: !! c.querySelector( '.affibloom-prime' ),
					disclosure: c.querySelector( '.affibloom-disclosure' )?.textContent?.trim() ?? '',
					button1: link( '.affibloom-button-1' ),
					button2: link( '.affibloom-button-2' ),
				};
			} )
		);
		expect( actual.length, 'number of boxes' ).toBe( expected.length );
		expected.forEach( ( lasso, i ) => {
			const ours = actual[ i ];
			expect.soft( ours.title, `box ${ i } title` ).toBe( lasso.title );
			expect.soft( ours.badge, `box ${ i } badge` ).toBe( lasso.badge );
			expect.soft( ours.description, `box ${ i } description` ).toBe( lasso.description );
			expect.soft( ours.image, `box ${ i } image` ).toBe( lasso.image );
			expect.soft( ours.prime, `box ${ i } prime` ).toBe( lasso.prime );
			expect.soft( ours.disclosure, `box ${ i } disclosure` ).toBe( lasso.disclosure );
			expect.soft( ours.button1, `box ${ i } button 1` ).toEqual( lasso.button1 );
			expect.soft( ours.button2, `box ${ i } button 2` ).toEqual( lasso.button2 );
		} );

		// 2. The whole page, desktop and mobile.
		for ( const [ size, width ] of [ [ 'desktop', 1280 ], [ 'mobile', 390 ] ] as const ) {
			await page.setViewportSize( { width, height: 900 } );
			await page.goto( `/${ guide }/` );
			await page.waitForLoadState( 'networkidle' );
			const shot = await page.screenshot( { fullPage: true } );
			const ratio = diffRatio( shot, `${ BASE }/guides/${ guide }-${ size }.png`, `test-results/parity/${ guide }-${ size }-diff.png` );
			expect.soft( ratio, `${ size } page diff` ).toBeLessThanOrEqual( PAGE_TOLERANCE );
		}

		// 3. No request to Lasso's services.
		expect( external ).toEqual( [] );
	} );
}
```

Two differences are expected and allowed:
- Lasso's own tracking script is gone, by design.
- `rel` and `target` on the title and image links are not compared, because the baseline records the button only.

Record both in `docs/parity.md`.

- [ ] **Step 2: Run both modes**

```bash
npm run test:e2e -- parity
PARITY_MODE=converted npm run test:e2e -- parity
```
For every failure, open the diff image or the value message, fix the cause (mapper, attribute map, CSS), add a test at the right level for it, and run again. Repeat until both modes pass. A difference that should stay is written in `docs/parity.md`, with a screenshot and your sign-off.

- [ ] **Step 3: Commit**

```bash
git add -A && git commit -m "Prove all 34 guides match Lasso with Lasso switched off

Every box's values and link targets, every page on desktop and mobile,
and no request to Lasso's services, with and without converted
blocks."
```

**Phase 5 exit check:** both parity modes pass. The migration report accounts for all 263 records. Revert restores the original content exactly.

---

# Dropped 2026-11-14 by the user's decision A1

No conversion of legacy Lasso blocks (Tasks 28A-28E), the History screen that listed those changes (H1-H2) and the parity run for the converted state (29D). Word for word.

## Tasks: F10 — converting legacy blocks (only if the user keeps the conversion: F10 N1 / F4 N2)

### Task 28A: Conversion dry run — single boxes

> **Only if the user keeps the conversion (F10 N1 / F4 N2).** Tasks 28A-28E are the one operator-run, reversible exception to "Affibloom never edits post content". If the user instead chooses F4 N2 (Affibloom registers `affiliate-plugin/lasso` itself and converts nothing; Task ED6, now in this phase after 27A), 28A-28E and F10 AC3, AC25-AC28 are dropped.

**Feature:** F10 (D2, D16; AC25) · needs the user's answer to "Needs the user" 1

**Why:** the 248 legacy blocks must be listed, with the reason each one can or cannot be converted, before any post is touched.

**Depends on:** Tasks 23A-23G (the block), 27A (the `[lasso]` alias), 26A.

**User action:** `wp affibloom convert-legacy-blocks --dry-run [--post=<id>]`: per post (published, draft, private,
future, pending; not revisions), the boxes to convert and the boxes that stay with the reason (an attribute the
block cannot hold, an unknown product, a display type not built). Nothing is written.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\Migration\{LegacyBlockConverter, ConversionResult}`, `Domain\Migration\LegacyBox` | `ConvertBlocksCommand` |
| `Interface\Cli\ConvertBlocksCommand` | WP-CLI |

**Files:** the classes; `tests/Unit/Application/LegacyBlockConverterTest.php` (fixtures from `boxes.json`: the 11
attributes in use); F10 spec AC25.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Converter.**
- [ ] **Step 3: Command.**
- [ ] **Step 4: Run and look** (34 posts, 248 blocks listed).
- [ ] **Step 5: Commit** — "Report which Lasso blocks can be converted".

---

### Task 28B: Convert, keep a copy, revert

> **Only if the user keeps the conversion (F10 N1 / F4 N2).**

**Feature:** F10 (D16, D17; AC3)

**Why:** the conversion is the one content change Affibloom makes (ADR-3, D17), so it keeps a copy of every post it changes and can put it back exactly.

**Depends on:** 28A.

**User action:** without `--dry-run` each post's convertible boxes become `affibloom/product-box`; the post keeps
`_affibloom_pre_conversion` and a revision. `--revert` restores the stored content exactly and deletes the copy; a
post edited since its conversion is refused unless `--force`.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `ConvertBlocksCommand` writing, `--revert`, `--force`; `LegacyBlockConverter::revert()` | WP-CLI; Task 28E; F34 H2 |
| `ContentChangeWriter` (Infrastructure: `wp_update_post`, a revision, `_affibloom_pre_conversion` and `_affibloom_conversion`; `restore()`; F34 requirements section 5) | `LegacyBlockConverter`, `ContentHistoryService::revert()` (F34 H1-H2) |

**Files:** `ConvertBlocksCommand.php`, `LegacyBlockConverter.php`; `tests/Integration/LegacyBlockConversionTest.php`;
F10 spec AC3 (exists). Create `src/Infrastructure/WordPress/ContentChangeWriter.php` (F34 relies on it).

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Write and revert.**
- [ ] **Step 3: Run and look** (one guide in the editor before and after; the front page unchanged).
- [ ] **Step 4: Commit** — "Convert Lasso blocks, with a copy to go back to".

---

### Task 28C: Bare shortcodes

> **Only if the user keeps the conversion (F10 N1 / F4 N2).**

**Feature:** F10 (AC26)

**Why:** bare `[lasso ...]` shortcodes (2 on this site) sit in content outside a block and are handled by the same rules.

**Depends on:** 28B.

**User action:** a `[lasso …]` in a shortcode block, a paragraph or classic content is converted by the same rules
(a shortcode block becomes the product box; inside other text it stays and is reported).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the shortcode-block and bare-shortcode rule in `LegacyBlockConverter` | `ConvertBlocksCommand` |

**Files:** the converter and its tests; F10 spec AC26.

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: The rule.**
- [ ] **Step 3: Run and look** (the 2 on this site).
- [ ] **Step 4: Commit** — "Convert bare Lasso shortcodes".

---

### Task 28D: The other display types

> **Only if the user keeps the conversion (F10 N1 / F4 N2).**

**Feature:** F10 (AC27; its table branch is built in 27E, Phase 7)

**Why:** boxes of the other display types (button, image, grid, list, gallery) stand in the same posts and must convert too.

**Depends on:** 28B; the Affibloom blocks for button, image, grid, list, gallery (Phase
3-4 displays, F23's group displays).

**User action:** boxes of `type="button|image|grid|list|gallery"` convert to their block; while a block does
not exist they stay, reported "waiting for <feature>". One sub-step and one test per display type. The table branch is built in 27E (Phase 7).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| one mapping per display type in `LegacyBlockConverter` | `ConvertBlocksCommand` |

**Files:** the converter (one mapping per type), tests, F10 spec AC27.

- [ ] **Step 1: Failing tests, per type.**
- [ ] **Step 2: The mappings.**
- [ ] **Step 3: Run and look** (the guides with these types).
- [ ] **Step 4: Commit** — "Convert Lasso's other display types".

---

### Task 28E: Conversion from the admin page

> **Only if the user keeps the conversion (F10 N1 / F4 N2).**

**Feature:** F10 (AC28)

**Why:** staging has no SSH (F10 D13): the conversion and its revert must also run from wp-admin.

**Depends on:** 26O, 28B.

**User action:** on Migrate from Lasso, a "Convert legacy blocks" card: the dry-run list, a backup tick, **Convert**
and **Revert**, one post per request, with progress, behind the migration's lock.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `POST /migration/convert` in `MigrationController` | `migrate.js` |
| the "Convert legacy blocks" card in `templates/admin/migrate.php`; the run in `assets/admin/migrate.js` | the Migrate from Lasso page |

**Files:** `MigrationController` (`POST /migration/convert`), `migrate.js`, `migrate.php`; F10 spec AC28.

- [ ] **Step 1: Failing test.**
- [ ] **Step 2: Route and card.**
- [ ] **Step 3: Run and look.**
- [ ] **Step 4: Commit** — "Convert and revert Lasso blocks from the admin".

---

## Tasks: F34 — Content changes, history and repair tools

### Task H1: The History list

**Feature:** F34 (R1-R5, R27-R36, D2-D7, D12, D13; AC1, AC5, AC8)

**Why:** the legacy block conversion is the one content change Affibloom makes; Lasso shows such changes on a History
screen, and the site owner must see every post Affibloom changed before trusting the change.

**Depends on:** F10 28B (writes `_affibloom_pre_conversion` **and** `_affibloom_conversion` through
`ContentChangeWriter`; requirements section 5); `Capabilities::SETTINGS`; F23 G1's in-place list pattern.

**User action:** Affibloom's admin menu gains **History** (after Settings). It opens "Post Content History": "Search
History", the table (Post ID, Post Name with edit and view icons and a line "Legacy blocks converted (N)", Post Type,
Modified date on two lines, Action: **View**, **Revert**), ten a page, newest first; each heading sorts; search and
page links redraw in place; with no changes "Looks like we're coming up empty."; a page past the last shows page 1.
Revert is wired in H2 (until then the button opens the detail). Lasso's 18 history rows never appear.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Content\{ContentChange, ChangeKind, HistoryQuery, HistoryPage, ContentChangeRepository}` | the service, the repository |
| `Application\Content\ContentHistoryService::page()` | `HistoryScreen`, `HistoryController` |
| `Infrastructure\WordPress\PostMetaContentChangeRepository::page()` (SQL over the two meta keys, joined to existing posts) | the service |
| `Interface\Admin\HistoryScreen::render()`, the submenu in `AdminMenu` | WordPress |
| `GET /affibloom/v1/history` in `Rest\HistoryController` | `history.js` |
| `templates/admin/history.php`, partial `history-row.php`, `assets/admin/history.js` | the screen, the route |
| `tests/Unit/Architecture/NoOtherContentWriterTest.php` (AC8) | PHPUnit |

**Files:** create the above, `tests/E2E/acceptance/F34.spec.ts` (AC1, AC5, AC8 by its unit test),
`tests/Unit/Application/ContentHistoryServiceTest.php`, `tests/Unit/Domain/HistoryQueryTest.php`; modify
`src/Plugin.php`, `src/Interface/Admin/{AdminMenu,AdminAssets}.php`, `assets/admin/admin.css`,
`docs/ui/design-system.md` (History row).

- [ ] **Step 1: Failing tests.** E2E AC1: two fixture posts converted with `wp affibloom convert-legacy-blocks --post=…` (28B) → both listed, order, columns, search ("best shoes" as wildcards), sort by Post Type, empty state after reverting both by CLI; AC5: the Lasso table's row count and checksum unchanged after a visit. Unit: query wildcards, order allow-list, page fallback; AC8's scan. Expected red: no History menu.
- [ ] **Step 2: Domain, repository, service.**
- [ ] **Step 3: Screen, route, templates, script.**
- [ ] **Step 4: Run and look.** Checks; F34 AC1, AC5, AC8; by hand: list, search, sorts, empty state, 375 px.
- [ ] **Step 5: Commit** — "List Affibloom's content changes on a History screen, as Lasso does".

---

### Task H2: A change's detail — the diff and Revert

**Feature:** F34 (R37-R43, D8-D11, D5; AC2-AC4)

**Why:** Lasso's History shows what a change did and puts the post back; Affibloom's must, with refusals where Lasso
would silently overwrite later edits.

**Depends on:** H1; F10 Task 28B (`LegacyBlockConverter::revert()`), 28E (the same revert from the migration page);
`ContentChangeWriter::restore()` (28B); F21 35C (re-indexing on `save_post`); the shared delete-dialog partial.

**User action:** **View** on a row opens "View Post Content History": Post Name (edit and view links, new tab), Post
ID, Post Type, Modified Date, "Content changed" with the before/now difference; an unknown post or kind opens the list.
**Revert** (row or detail) asks "Revert this post?" / "The post's content goes back to what it was before this change.
A revision of the current content is kept." / Cancel, **Revert** → "Saving your changes." with the bar → "Content
reverted for “<post name>”."; the row leaves the list. A post edited after the change: "“<post name>” was edited after
this change. Revert it from WP-CLI with --force, or edit it by hand." and nothing changes; a later clean-up on the same
post: "Revert the later change of this post first."; a vanished record: "This post has no change to revert.".

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `ContentHistoryService::get(), revert()` with `NoChange`, `EditedSince`, `LaterChange` | `HistoryScreen`, `HistoryController` |
| `POST /affibloom/v1/history/{post}/revert` (SETTINGS, `edit_post`, `unfiltered_html`) | `history.js` |
| `HistoryScreen::render_detail()`, `templates/admin/history-detail.php` (`wp_text_diff()`) | WordPress |
| the revert dialog and handling in `history.js`; `Messages` codes `affibloom_no_change`, `affibloom_edited_since`, `affibloom_later_change` | the list rows, the detail |

**Files:** the above; `templates/admin/partials/history-row.php` (Revert wired), `templates/admin/partials/delete-modal.php`
(unchanged markup, another user); tests F34 **AC2-AC4**, `tests/Integration/HistoryRevertTest.php`, unit tests for the
refusals.

- [ ] **Step 1: Failing tests.** AC2 (detail of a converted fixture; unknown ID → list); AC3 (revert from the row and from the detail; content byte-equal to the stored copy; a revision exists; the row gone); AC4 (edit the post through WP-CLI, then revert → refusal, content unchanged). Expected red: no detail page.
- [ ] **Step 2: Service refusals and the route.**
- [ ] **Step 3: Detail template, diff, dialog.**
- [ ] **Step 4: Run and look** (a 340 KB guide's diff loads in under a second; keyboard-only revert; 375 px).
- [ ] **Step 5: Commit** — "Show and revert a content change from History".

---

### Task 29D: Parity — converted, and reverted

> The `converted` and `reverted` runs exist only if the conversion is kept (28A-28E); the 29C part runs when 29C is built (Phase 7).

**Feature:** F10 (AC31)

**Why:** the conversion must not change what readers see, and reverting it must give the original pages back.

**Depends on:** 28B-28D, 29A, 29B (29C's part runs when 29C is built, Phase 7).

**User action:** `PARITY_MODE=converted npm run test:e2e -- parity` and `PARITY_MODE=reverted npm run test:e2e -- parity` run 29A-29B (and, later, 29C) after converting every post and after reverting them.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| the `PARITY_MODE` switch in `tests/Visual/parity.spec.ts` (convert or revert through the command first) | `npm run test:e2e -- parity` |

**Files:** `tests/Visual/parity.spec.ts`, `docs/parity.md`.

- [ ] **Step 1: The two modes, red.**
- [ ] **Step 2: Fix causes, or record the difference in `docs/parity.md`.**
- [ ] **Step 3: Run and look.** Both modes pass on Local.
- [ ] **Step 4: Commit** — "Prove parity survives conversion and revert".

---

---

# Dropped 2026-11-14 by the user's decision A2

The `data-lasso-*` stamps stay in posts, so there is nothing to strip (Tasks H3-H4). Word for word.

### Task H3 (only with N3): Remove Lasso's stamps from content — WP-CLI

> **Only if the user approves F34 N3** (remove Lasso's stamps at retirement). With N3 refused this task is dropped and F21 N3 (keep the stamps) stands.

**Feature:** F34 (R18 in part, D2, D4, D14; AC6)

**Why:** roadmap 9.6's optional clean-up, if the user wants it: Lasso's 2,791 `data-lasso-id` and 698 `data-lasso-name`
/ `data-lasso-lid` attributes in 180 posts, removed by an operator, with a copy to go back to.

**Depends on:** H2 (History lists and reverts the change); F10 26A-26O finished (the ID map and dismissals no longer
need the stamps) and its lock; F21 D4 (`occurrence` identity); `ContentChangeWriter` (28B).

**User action:** `wp affibloom lasso-attributes strip --dry-run` prints one line per post (removed counts per attribute,
JSON-encoded stamps counted, skipped posts with reasons) and a summary; without `--dry-run` it changes those posts
(each keeps `_affibloom_pre_cleanup` and a revision; History lists "Lasso attributes removed (N)"); before the
migration has finished it refuses with "Run the Lasso migration first."; `--revert [--post=<id>] [--force]` puts posts
back (the same refusals as H2 without `--force`).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Content\LassoAttributes` (counts and the cleaned HTML; pure) | the cleaner |
| `Application\Content\LassoAttributeCleaner::plan(), run(), revert()` | the command, `ContentHistoryService::revert()` (kind cleanup) |
| `Interface\Cli\LassoAttributesCommand` (`wp affibloom lasso-attributes strip`) | WP-CLI |
| meta `_affibloom_pre_cleanup`, `_affibloom_cleanup` in `PostMetaContentChangeRepository` | the cleaner, H1's list |

**Files:** the above; `src/Interface/Cli/CliCommands.php` (registration); tests F34 **AC6**,
`tests/Unit/Domain/LassoAttributesTest.php`, `tests/Integration/LassoAttributeCleanerTest.php`.

- [ ] **Step 1: Failing tests.** Unit: exactly the three names removed, every other byte equal (fixtures from three of this site's guides: GenerateBlocks buttons, reusable block, inline SVG); JSON-encoded stamps untouched. E2E AC6: dry run, run, History row, revert byte-equal; refusal before migration. Expected red: unknown command.
- [ ] **Step 2: Domain and cleaner.**
- [ ] **Step 3: The command.**
- [ ] **Step 4: Run and look** (staging copy only; Local first; a backup before; the front end of three guides unchanged, F21's index unchanged in count).
- [ ] **Step 5: Commit** — "Remove Lasso's leftover attributes from posts, with a copy to go back to".

---

### Task H4 (only with N3): The clean-up from the admin

> **Only if the user approves F34 N3.**

**Feature:** F34 (R18 in part, D14; AC7)

**Why:** staging has no SSH (F10 25D), so the clean-up must also run from wp-admin, as 28E does for the conversion.

**Depends on:** H3; F10 26O's lock and batch pattern (`affibloom_migration_lock`).

**User action:** History gains a **Remove Lasso attributes** card (only after the migration has finished): the dry-run
figures ("N posts, N attributes"), a required tick "I have a database backup from today", **Remove**, then progress one
post per request ("N of M posts"), behind the migration lock ("Another migration or conversion is running." when
held); the cleaned posts appear in the list; each is reverted from its row (H2).

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `POST /affibloom/v1/history/cleanup` (`cursor`, `backup_confirmed`) and `LassoAttributeCleaner::batch()` | `history.js` |
| partial `history-cleanup-card.php`; the run in `history.js` | `HistoryScreen` |

**Files:** the above; `templates/admin/history.php`; tests F34 **AC7**.

- [ ] **Step 1: Failing test** (AC7: card hidden before migration; tick required; run; rows listed; lock refusal).
- [ ] **Step 2: Route and batch.**
- [ ] **Step 3: The card and the run.**
- [ ] **Step 4: Run and look** (375 px; reload mid-run resumes from the cursor).
- [ ] **Step 5: Commit** — "Remove Lasso's attributes from the History screen".

---
