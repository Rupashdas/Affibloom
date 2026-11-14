# Phase 6 — superseded tasks (history only)

These are the original Phase 6 task sections (Tasks 30, 31 and 32), moved here word for word on 2026-11-14 when the
planning pass rewrote Phase 6 from the full-scope feature sweeps. They are history only: do not work from them. The
new tasks that replace them are in `phase-6-link-health.md`: Task 30 (nightly check, with its test stub and the
`Migrator`) is replaced by TL1, TL2, 30A, 30B and 30C; Task 31 (Alert column, filter, issue panel) by 31A, 31B, 31C
and 31D; Task 32 (`wp affibloom check-links`, broken-links notice, and the unapproved "Re-check now") by 32A and
32B, with 32C-32E added from the Lasso sweep. The old `affibloom_link_checks` table is replaced by F11's
`affibloom_link_issues`. The still-valid decision bullets of the old file were kept in the new file.

---

## Tasks in this phase

- [ ] Task 30: Nightly link check — the `affibloom_daily` cron event
- [ ] Task 31: Dashboard Alert column, broken-link filter, issue panel
- [ ] Task 32: `wp affibloom check-links` and the broken-links notice

---


### Task 30: Nightly link check — the `affibloom_daily` cron event

**Roadmap:** 6.1, 6.3, 6.4 · **Feature:** F11 (AC1, AC2)

**Why:** Lasso checks product links every night. Without that check, a product that disappears from Amazon or a merchant stays in a guide, as a dead button, until a reader notices.

**User action:** nobody acts. Each night the site checks every product's links, in small batches. Link checks the site makes:
- A link that answers 404 or 410 twice in a row becomes "broken".
- A first 404 is only "suspect", so one bad night is not reported.
- A blocked or unreachable check (Amazon's 503, a timeout) is "inconclusive", never "broken".

**Request path:**
```
WP-Cron "affibloom_daily" (03:00 site time)       → LinkCheckCron::start()  → schedules "affibloom_link_check_batch" now
WP-Cron "affibloom_link_check_batch"              → LinkCheckCron::batch()
     transient lock (10 min) → LinkHealthService::run_batch( 25 )
          LinkCheckTable::due_product_ids( 25, now − 20 h )
          for each product, button 1 (url) and button 2 (url2):
             LinkChecker::check( url )           → WpLinkChecker: wp_safe_remote_get, 10 s, 5 redirects
             LinkCheckClassifier::classify()     → ok / suspect / broken / inconclusive
             LinkCheckTable::save()
     more due → wp_schedule_single_event( +60 s, "affibloom_link_check_batch" )
plugins_loaded → Migrator::maybe_migrate()      → dbDelta( affibloom_link_checks ) when affibloom_db_version is behind
activation      → Migrator::migrate(), DailyCron::schedule()
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Infrastructure\Schema\{Migrator, Table}` (version 1), activation hook | `plugins_loaded`, activation |
| `Infrastructure\Tables\LinkCheckTable` | `Migrator`, `LinkHealthService` |
| `Domain\LinkHealth\{LinkStatus, LinkCheckResult, LinkCheckClassifier}` | `LinkHealthService` |
| `Application\LinkHealth\{LinkHealthService, LinkChecker, LinkCheckResponse}` | `LinkCheckCron` |
| `Infrastructure\Http\WpLinkChecker` | `LinkHealthService` through `LinkChecker` |
| `Interface\Cron\{DailyCron, LinkCheckCron}` | WP-Cron |
| `tests/E2E/stub/affibloom-stub.php` (a must-use plugin, test environments only) | F11 acceptance tests |

**Files:** create every file above, plus `tests/Unit/Domain/LinkCheckClassifierTest.php` and `tests/Unit/Application/LinkHealthServiceTest.php`. Modify `src/Plugin.php` and `.wp-env.json`.

- [ ] **Step 1: Failing tests**

`tests/Unit/Domain/LinkCheckClassifierTest.php`:

```php
<?php
namespace Affibloom\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Affibloom\Domain\LinkHealth\LinkCheckClassifier;
use Affibloom\Domain\LinkHealth\LinkStatus;

final class LinkCheckClassifierTest extends TestCase {
	/** @dataProvider cases */
	public function test_classifies( ?int $code, string $body, bool $amazon, int $before, LinkStatus $status, int $after ): void {
		$result = ( new LinkCheckClassifier() )->classify( $code, $body, $amazon, $before );
		self::assertSame( array( $status, $after ), array( $result->status, $result->broken_count ) );
	}

	public static function cases(): array {
		return array(
			'ok resets the count'       => array( 200, '<html>', false, 1, LinkStatus::Ok, 0 ),
			'first 404 is suspect'      => array( 404, '', false, 0, LinkStatus::Suspect, 1 ),
			'second 404 is broken'      => array( 404, '', false, 1, LinkStatus::Broken, 2 ),
			'410 counts like 404'       => array( 410, '', false, 1, LinkStatus::Broken, 2 ),
			'amazon 503 is blocked'     => array( 503, '', true, 1, LinkStatus::Inconclusive, 1 ),
			'amazon captcha page'       => array( 200, 'To discuss automated access to Amazon data please contact api-services-support@amazon.com', true, 0, LinkStatus::Inconclusive, 0 ),
			'403 is blocked'            => array( 403, '', false, 0, LinkStatus::Inconclusive, 0 ),
			'no response is unknown'    => array( null, '', false, 1, LinkStatus::Inconclusive, 1 ),
		);
	}
}
```

```bash
composer test:unit
```

- [ ] **Step 2: Entry point: cron**

`src/Interface/Cron/DailyCron.php`:

```php
<?php
/**
 * One daily event that the plugin's nightly jobs hang from.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Cron;

use Affibloom\Module;

/**
 * One daily event that the plugin's nightly jobs hang from.
 */
final class DailyCron implements Module {

	public const HOOK = 'affibloom_daily';

	/**
	 * Hooks into WordPress; puts the event back if something removed it.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'schedule' ) );
	}

	/**
	 * Schedules the event at 03:00 site time, if it is not scheduled yet.
	 */
	public function schedule(): void {
		if ( wp_next_scheduled( self::HOOK ) ) {
			return;
		}
		$next = new \DateTimeImmutable( 'tomorrow 03:00', wp_timezone() );
		wp_schedule_event( $next->getTimestamp(), 'daily', self::HOOK );
	}

	/**
	 * Removes the plugin's events (deactivation).
	 */
	public static function unschedule(): void {
		wp_clear_scheduled_hook( self::HOOK );
		wp_clear_scheduled_hook( LinkCheckCron::BATCH_HOOK );
	}
}
```

`src/Interface/Cron/LinkCheckCron.php`:

```php
<?php
/**
 * Runs the link check in batches, one batch per cron call, chained until done.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Cron;

use Affibloom\Application\LinkHealth\LinkHealthService;
use Affibloom\Module;

/**
 * Runs the link check in batches, one batch per cron call, chained until done.
 */
final class LinkCheckCron implements Module {

	public const BATCH_HOOK = 'affibloom_link_check_batch';
	private const LOCK      = 'affibloom_link_check_lock';
	private const BATCH     = 25;

	/**
	 * Builds the module.
	 *
	 * @param LinkHealthService $health Link checking.
	 */
	public function __construct( private LinkHealthService $health ) {}

	/**
	 * Hooks into WP-Cron.
	 */
	public function register(): void {
		add_action( DailyCron::HOOK, array( $this, 'start' ) );
		add_action( self::BATCH_HOOK, array( $this, 'batch' ) );
	}

	/**
	 * Nightly: starts the first batch.
	 */
	public function start(): void {
		if ( ! wp_next_scheduled( self::BATCH_HOOK ) ) {
			wp_schedule_single_event( time(), self::BATCH_HOOK );
		}
	}

	/**
	 * One batch; schedules the next while products are due. Two runs never overlap.
	 */
	public function batch(): void {
		if ( get_transient( self::LOCK ) ) {
			return;
		}
		set_transient( self::LOCK, 1, 10 * MINUTE_IN_SECONDS );

		try {
			$remaining = $this->health->run_batch( self::BATCH );
		} finally {
			delete_transient( self::LOCK );
		}

		if ( $remaining > 0 ) {
			wp_schedule_single_event( time() + MINUTE_IN_SECONDS, self::BATCH_HOOK );
		}
	}
}
```

- [ ] **Step 3: Application**

`src/Application/LinkHealth/LinkCheckResponse.php` holds an HTTP answer. Its fields are `?int $code`, `string $body` (at most 32 KB) and `string $error`. All are `public readonly` and set in the constructor.

`src/Application/LinkHealth/LinkChecker.php`:

```php
<?php
/**
 * Fetches a link the way a visitor would, without following it forever.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\LinkHealth;

/**
 * Fetches a link the way a visitor would, without following it forever.
 */
interface LinkChecker {

	/**
	 * The answer for one URL.
	 *
	 * @param string $url URL.
	 */
	public function check( string $url ): LinkCheckResponse;
}
```

`src/Application/LinkHealth/LinkHealthService.php`:

```php
<?php
/**
 * Checks product links and keeps their latest result.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\LinkHealth;

use Affibloom\Domain\Amazon\AmazonUrl;
use Affibloom\Domain\LinkHealth\LinkCheckClassifier;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Infrastructure\Tables\LinkCheckTable;

/**
 * Checks product links and keeps their latest result.
 */
final class LinkHealthService {

	/** A product is checked again once its last check is older than this: 20 hours, in seconds. */
	private const INTERVAL = 72000;

	/**
	 * Builds the service.
	 *
	 * @param ProductRepository   $products   Product storage.
	 * @param LinkChecker         $checker    HTTP fetches.
	 * @param LinkCheckClassifier $classifier Rules for results.
	 * @param LinkCheckTable      $table      Latest results.
	 */
	public function __construct(
		private ProductRepository $products,
		private LinkChecker $checker,
		private LinkCheckClassifier $classifier,
		private LinkCheckTable $table,
	) {}

	/**
	 * Checks up to $limit products that are due.
	 *
	 * @param int $limit Products in this batch.
	 * @return int Products still due afterwards.
	 */
	public function run_batch( int $limit ): int {
		$before = gmdate( 'Y-m-d H:i:s', time() - self::INTERVAL );
		foreach ( $this->table->due_product_ids( $limit, $before ) as $id ) {
			$product = $this->products->get( $id );
			if ( null !== $product ) {
				$this->check_product( $product );
			}
		}
		return $this->table->count_due( $before );
	}

	/**
	 * Checks both of a product's links now.
	 *
	 * @param Product $product Product.
	 */
	public function check_product( Product $product ): void {
		$this->check_button( $product, 1, $product->url->value() );
		if ( null !== $product->url2 ) {
			$this->check_button( $product, 2, $product->url2->value() );
		} else {
			$this->table->remove( (int) $product->id, 2 );
		}
	}

	/**
	 * Checks one link and stores the result.
	 *
	 * @param Product $product Product.
	 * @param int     $button  1 or 2.
	 * @param string  $url     Link.
	 */
	private function check_button( Product $product, int $button, string $url ): void {
		$previous = $this->table->get( (int) $product->id, $button );
		$same_url = null !== $previous && $previous['url'] === $url;
		$response = $this->checker->check( $url );
		$amazon   = AmazonUrl::is_amazon_host( (string) parse_url( $url, PHP_URL_HOST ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- keeps the service testable without WordPress.
		$result   = $this->classifier->classify(
			$response->code,
			$response->body,
			$amazon,
			$same_url ? (int) $previous['broken_count'] : 0
		);
		$this->table->save( (int) $product->id, $button, $url, $result, $response->error );
	}
}
```

Here the Application layer depends directly on `LinkCheckTable`. A link-check results table has exactly one implementation and no domain meaning beyond storage, so an interface would add nothing. The Code review checklist accepts this when it is written down; this paragraph is that record.

- [ ] **Step 4: Domain**

`src/Domain/LinkHealth/LinkStatus.php`:

```php
<?php
/**
 * The state of one product link.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkHealth;

/**
 * The state of one product link.
 */
enum LinkStatus: string {
	case Ok           = 'ok';
	case Suspect      = 'suspect';      // One 404 or 410; not shown as an alert yet.
	case Broken       = 'broken';       // Two in a row: shown in the Alert column.
	case Inconclusive = 'inconclusive'; // Blocked, timed out or server error: never an alert.
}
```

`src/Domain/LinkHealth/LinkCheckResult.php` holds `LinkStatus $status`, `?int $http_code` and `int $broken_count`, all `public readonly`.

`src/Domain/LinkHealth/LinkCheckClassifier.php`:

```php
<?php
/**
 * Turns an HTTP answer into a link status, without false alarms.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkHealth;

/**
 * Turns an HTTP answer into a link status, without false alarms.
 */
final class LinkCheckClassifier {

	/** Amazon's robot-check page answers 200 with this text. */
	private const AMAZON_BLOCK = 'api-services-support@amazon.com';

	/**
	 * Classifies one answer.
	 *
	 * @param int|null $code          HTTP status; null when there was no answer.
	 * @param string   $body          Start of the body.
	 * @param bool     $amazon        Whether the link is on Amazon.
	 * @param int      $broken_before 404/410 answers in a row before this one.
	 */
	public function classify( ?int $code, string $body, bool $amazon, int $broken_before ): LinkCheckResult {
		if ( null === $code ) {
			return new LinkCheckResult( LinkStatus::Inconclusive, null, $broken_before );
		}
		if ( 404 === $code || 410 === $code ) {
			$count = $broken_before + 1;
			return new LinkCheckResult( $count >= 2 ? LinkStatus::Broken : LinkStatus::Suspect, $code, $count );
		}
		if ( $code >= 200 && $code < 400 ) {
			if ( $amazon && str_contains( $body, self::AMAZON_BLOCK ) ) {
				return new LinkCheckResult( LinkStatus::Inconclusive, $code, $broken_before );
			}
			return new LinkCheckResult( LinkStatus::Ok, $code, 0 );
		}
		// 401, 403, 405, 429 and 5xx: the site refused a robot or is down for a moment.
		return new LinkCheckResult( LinkStatus::Inconclusive, $code, $broken_before );
	}
}
```

- [ ] **Step 5: Infrastructure: table, migrator, HTTP**

`src/Infrastructure/Tables/LinkCheckTable.php`:

```php
<?php
/**
 * {prefix}affibloom_link_checks: the latest check of each product link.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\Tables;

use Affibloom\Domain\LinkHealth\LinkCheckResult;
use Affibloom\Infrastructure\Schema\Table;
use Affibloom\Infrastructure\WordPress\ProductPostType;
use wpdb;

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- the plugin's own table; reads are per batch or per screen.

/**
 * {prefix}affibloom_link_checks: the latest check of each product link.
 */
final class LinkCheckTable implements Table {

	/**
	 * Builds the table.
	 *
	 * @param wpdb $db Database.
	 */
	public function __construct( private wpdb $db ) {}

	/**
	 * Table name.
	 */
	public function name(): string {
		return $this->db->prefix . 'affibloom_link_checks';
	}

	/**
	 * CREATE TABLE for dbDelta (two spaces after PRIMARY KEY, as dbDelta needs).
	 */
	public function schema(): string {
		return "CREATE TABLE {$this->name()} (
			product_id bigint(20) unsigned NOT NULL,
			button tinyint(1) unsigned NOT NULL,
			url varchar(2000) NOT NULL,
			status varchar(20) NOT NULL,
			http_code smallint(5) unsigned NOT NULL DEFAULT 0,
			detail varchar(255) NOT NULL DEFAULT '',
			broken_count tinyint(3) unsigned NOT NULL DEFAULT 0,
			checked_at datetime NOT NULL,
			PRIMARY KEY  (product_id,button),
			KEY status (status),
			KEY checked_at (checked_at)
		) {$this->db->get_charset_collate()};";
	}

	/**
	 * Published products whose primary link was never checked, or not since $before.
	 *
	 * @param int    $limit  Maximum IDs.
	 * @param string $before GMT datetime.
	 * @return list<int>
	 */
	public function due_product_ids( int $limit, string $before ): array {
		return array_map(
			'intval',
			$this->db->get_col(
				$this->db->prepare(
					"SELECT p.ID FROM {$this->db->posts} p
					 LEFT JOIN {$this->name()} c ON c.product_id = p.ID AND c.button = 1
					 WHERE p.post_type = %s AND p.post_status = 'publish' AND ( c.checked_at IS NULL OR c.checked_at < %s )
					 ORDER BY c.checked_at ASC, p.ID ASC LIMIT %d",
					ProductPostType::NAME,
					$before,
					$limit
				)
			)
		);
	}

	/**
	 * How many products are due.
	 *
	 * @param string $before GMT datetime.
	 */
	public function count_due( string $before ): int {
		return (int) $this->db->get_var(
			$this->db->prepare(
				"SELECT COUNT(*) FROM {$this->db->posts} p
				 LEFT JOIN {$this->name()} c ON c.product_id = p.ID AND c.button = 1
				 WHERE p.post_type = %s AND p.post_status = 'publish' AND ( c.checked_at IS NULL OR c.checked_at < %s )",
				ProductPostType::NAME,
				$before
			)
		);
	}

	/**
	 * The stored row for one link, or null.
	 *
	 * @param int $product_id Product.
	 * @param int $button     1 or 2.
	 * @return array<string, string>|null
	 */
	public function get( int $product_id, int $button ): ?array {
		$row = $this->db->get_row( $this->db->prepare( "SELECT * FROM {$this->name()} WHERE product_id = %d AND button = %d", $product_id, $button ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Stores the latest result.
	 *
	 * @param int             $product_id Product.
	 * @param int             $button     1 or 2.
	 * @param string          $url        The link checked.
	 * @param LinkCheckResult $result     Result.
	 * @param string          $detail     Error text, if any.
	 */
	public function save( int $product_id, int $button, string $url, LinkCheckResult $result, string $detail ): void {
		$this->db->replace(
			$this->name(),
			array(
				'product_id'   => $product_id,
				'button'       => $button,
				'url'          => $url,
				'status'       => $result->status->value,
				'http_code'    => (int) $result->http_code,
				'detail'       => substr( $detail, 0, 255 ),
				'broken_count' => $result->broken_count,
				'checked_at'   => gmdate( 'Y-m-d H:i:s' ),
			),
			array( '%d', '%d', '%s', '%s', '%d', '%s', '%d', '%s' )
		);
	}

	/**
	 * Forgets a link (a second button that was removed).
	 *
	 * @param int $product_id Product.
	 * @param int $button     1 or 2.
	 */
	public function remove( int $product_id, int $button ): void {
		$this->db->delete(
			$this->name(),
			array(
				'product_id' => $product_id,
				'button'     => $button,
			),
			array( '%d', '%d' )
		);
	}
}
```

`src/Infrastructure/Schema/Table.php`:

```php
<?php
/**
 * A plugin table the Migrator creates and upgrades.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\Schema;

/**
 * A plugin table the Migrator creates and upgrades.
 */
interface Table {

	/**
	 * The table's current CREATE TABLE statement, for dbDelta.
	 */
	public function schema(): string;
}
```

`ClickTable` (Task 33) and `UsageTable` (Task 35) implement it too.

`src/Infrastructure/Schema/Migrator.php`:

```php
<?php
/**
 * Creates and upgrades the plugin's tables with dbDelta.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\Schema;

use Affibloom\Module;

/**
 * Creates and upgrades the plugin's tables with dbDelta.
 */
final class Migrator implements Module {

	/** Raise by one with every schema change; each table's schema() holds its latest form. */
	public const VERSION = 1;
	public const OPTION  = 'affibloom_db_version';

	/**
	 * Builds the migrator.
	 *
	 * @param list<Table> $tables The plugin's tables.
	 */
	public function __construct( private array $tables ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'plugins_loaded', array( $this, 'maybe_migrate' ) );
	}

	/**
	 * Migrates when the stored version is behind (after an update, too).
	 */
	public function maybe_migrate(): void {
		if ( (int) get_option( self::OPTION, 0 ) < self::VERSION ) {
			$this->migrate();
		}
	}

	/**
	 * Applies every table's current schema.
	 */
	public function migrate(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( $this->tables as $table ) {
			dbDelta( $table->schema() );
		}
		update_option( self::OPTION, self::VERSION, false );
	}
}
```

`src/Infrastructure/Http/WpLinkChecker.php`:

```php
<?php
/**
 * GET with a short timeout, honest user agent, and only the start of the body.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\Http;

use Affibloom\Application\LinkHealth\LinkChecker;
use Affibloom\Application\LinkHealth\LinkCheckResponse;

/**
 * GET with a short timeout, honest user agent, and only the start of the body.
 */
final class WpLinkChecker implements LinkChecker {

	/**
	 * Fetches the URL.
	 *
	 * @param string $url URL.
	 */
	public function check( string $url ): LinkCheckResponse {
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => 10,
				'redirection'         => 5,
				'limit_response_size' => 32768,
				'user-agent'          => 'Mozilla/5.0 (compatible; Affibloom-LinkCheck/1.0; +' . home_url( '/' ) . ')',
				'headers'             => array( 'Accept' => 'text/html' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return new LinkCheckResponse( null, '', $response->get_error_message() );
		}
		return new LinkCheckResponse( (int) wp_remote_retrieve_response_code( $response ), (string) wp_remote_retrieve_body( $response ), '' );
	}
}
```

The user agent says what it is, rather than pretending to be a browser. Amazon will often answer it with 503, and those answers count as inconclusive. For Amazon products, the check therefore mostly catches product pages that answer 404. Write this down in `docs/admin-guide.md` (Task 39), so nobody expects more.

- [ ] **Step 6: Wire up, activation, and the test stub**

`Plugin`:
- In `register()`: `register_activation_hook( $this->file, array( $this, 'activate' ) );` and `register_deactivation_hook( $this->file, array( DailyCron::class, 'unschedule' ) );`.
- `public function activate(): void { $this->migrator()->migrate(); ( new DailyCron() )->schedule(); }`
- A `migrator()` builder: `new Migrator( array( $this->link_check_table() ) )`.
- Modules: `$this->migrator()`, `new DailyCron()`, `new LinkCheckCron( $link_health )`.
- Build `$link_health = new LinkHealthService( $this->product_repository(), new WpLinkChecker(), new LinkCheckClassifier(), $this->link_check_table() )` in `modules()`.

`tests/E2E/stub/affibloom-stub.php` is a must-use plugin, mapped into both wp-env sites through `.wp-env.json` `"mappings": { "wp-content/mu-plugins/affibloom-stub.php": "./tests/E2E/stub/affibloom-stub.php" }`. It never ships.

```php
<?php
/**
 * Test-only: answers /wp-json/affibloom-stub/v1/status/<code> with that status,
 * and lets the link checker reach localhost.
 *
 * @package Affibloom
 */

add_filter( 'http_request_host_is_external', static fn( $external, $host ) => 'localhost' === $host ? true : $external, 10, 2 );

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'affibloom-stub/v1',
			'/status/(?P<code>\d{3})',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => static fn( WP_REST_Request $r ) => new WP_REST_Response( array( 'stub' => true ), (int) $r['code'] ),
			)
		);
	}
);
```

The F11 acceptance tests (Task 7) use `http://localhost/wp-json/affibloom-stub/v1/status/404` and `/503` as product links, run `wp cron event run affibloom_link_check_batch` twice, and then check the result.

`tests/Unit/Application/LinkHealthServiceTest.php` uses a fake checker that returns 404 twice for the same URL. It expects `broken` after the second run. When the URL changes in between, it expects `suspect`, because the count starts again for a new link.

- [ ] **Step 7: Run and see it work**

```bash
composer lint && composer analyse && composer test:unit && npm run test:integration
npm run wp -- plugin deactivate affibloom && npm run wp -- plugin activate affibloom
npm run wp -- db query "SHOW TABLES LIKE 'wp_affibloom_link_checks'"
npm run wp -- cron event list --fields=hook,next_run_relative | grep affibloom
```
Expected: the table exists, and `affibloom_daily` is scheduled.

Run the F11 tests:

```bash
npm run test:e2e -- acceptance/F11
```

- [ ] **Step 8: Commit**

```bash
git add -A && git commit -m "Check product links nightly, in batches, without false alarms

Two 404s in a row make a link broken; blocked, timed-out and server
errors stay inconclusive. Results go in a table created by the
plugin's first schema migration."
```

---

### Task 31: Dashboard Alert column, broken-link filter, issue panel

**Roadmap:** 6.2 · **Feature:** F11 (AC1)

**Why:** in Lasso, a broken link shows in the Dashboard's Alert column, behind its "See broken URLs" filter, and on the product's page. Editors already look there.

**User action:**
1. On the Dashboard, an editor sees a warning icon in the Alert column of each product with a broken link, labelled "Broken link (404)".
2. They click the "See broken URLs" filter icon, which shows a count. Only those products remain in the list.
3. On a product's page, a panel at the top of Details says "The primary link returned 404 when checked on 10 Nov 2026."

**Request path:**
```
GET admin.php?page=affibloom[&filter=broken]
  → DashboardScreen → LinkHealthService::broken_ids() → ProductService::list( term, page, only_ids )
                    → LinkHealthService::alerts_for( page ids ) → Alert column
GET admin.php?page=affibloom-product&id=…
  → ProductScreen → LinkHealthService::issues_for( id ) → issue panel
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `LinkHealthService::broken_ids()`, `alerts_for()`, `issues_for()` | `DashboardScreen`, `ProductScreen` |
| `LinkCheckTable::ids_with_status()`, `for_products()` | those methods |
| `ProductRepository::search()` optional `$only_ids` | `ProductService::list()` |
| Alert column, filter icon, issue panel templates | the screens |

**Files:** modify `LinkHealthService.php`, `LinkCheckTable.php`, `ProductRepository.php`, `PostTypeProductRepository.php`, `ProductService.php`, `DashboardScreen.php`, `ProductScreen.php`, `dashboard.php`, `product-row.php`, `product.php`, `admin.css`, `Plugin.php`; create `tests/Integration/BrokenFilterTest.php`.

- [ ] **Step 1: Failing test:** `npm run test:e2e -- acceptance/F11 -g AC1`. It fails at the Alert column.

- [ ] **Step 2: Application and storage**

`LinkHealthService`:

```php
	/**
	 * Products with at least one broken link.
	 *
	 * @return list<int>
	 */
	public function broken_ids(): array {
		return $this->table->ids_with_status( LinkStatus::Broken );
	}

	/**
	 * Broken links per product, for a page of the Dashboard.
	 *
	 * @param list<int> $ids Product IDs.
	 * @return array<int, list<array{button: int, http_code: int}>>
	 */
	public function alerts_for( array $ids ): array {
		$alerts = array();
		foreach ( $this->table->for_products( $ids ) as $row ) {
			if ( LinkStatus::Broken->value === $row['status'] ) {
				$alerts[ (int) $row['product_id'] ][] = array(
					'button'    => (int) $row['button'],
					'http_code' => (int) $row['http_code'],
				);
			}
		}
		return $alerts;
	}

	/**
	 * Every stored check for one product, for its issue panel.
	 *
	 * @param int $id Product ID.
	 * @return list<array<string, string>>
	 */
	public function issues_for( int $id ): array {
		return array_values( array_filter( $this->table->for_products( array( $id ) ), static fn( $r ) => LinkStatus::Broken->value === $r['status'] ) );
	}
```

`LinkCheckTable`:

```php
	/**
	 * Products with any link in a status.
	 *
	 * @param LinkStatus $status Status.
	 * @return list<int>
	 */
	public function ids_with_status( LinkStatus $status ): array {
		return array_map( 'intval', $this->db->get_col( $this->db->prepare( "SELECT DISTINCT product_id FROM {$this->name()} WHERE status = %s", $status->value ) ) );
	}

	/**
	 * Rows for some products.
	 *
	 * @param list<int> $ids Product IDs.
	 * @return list<array<string, string>>
	 */
	public function for_products( array $ids ): array {
		$ids = array_filter( array_map( 'intval', $ids ) );
		if ( ! $ids ) {
			return array();
		}
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		return $this->db->get_results( $this->db->prepare( "SELECT * FROM {$this->name()} WHERE product_id IN ($placeholders)", $ids ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	}
```

`ProductRepository::search( string $term, int $page, int $per_page, ?array $only_ids = null )`. In the implementation, when `$only_ids` is given, add `'post__in' => $only_ids ?: array( 0 )`. `array( 0 )` returns no rows rather than all of them. `ProductService::list( string $term, int $page, ?array $only_ids = null )` passes it through.

- [ ] **Step 3: Screens**

`DashboardScreen` takes `LinkHealthService $health`. In `render()`:

```php
		$filter = isset( $_GET['filter'] ) ? sanitize_key( wp_unslash( $_GET['filter'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$broken = $this->health->broken_ids();
		$page   = $this->products->list( $term, $paged, 'broken' === $filter ? $broken : null );
		$ids    = array_map( static fn( $p ) => (int) $p->id, $page->items );
```

It passes `'alerts' => $this->health->alerts_for( $ids ), 'broken_count' => count( $broken ), 'filter' => $filter` to the template.

`dashboard.php`, in the toolbar after the search box. The filter is an icon with Lasso's tooltip text; U2, if approved, adds a visible text label.

```php
			<a class="affibloom-filter affibloom-filter-broken<?php echo 'broken' === $view['filter'] ? ' is-active' : ''; ?>"
				href="<?php echo esc_url( add_query_arg( array( 'page' => 'affibloom', 'filter' => 'broken' === $view['filter'] ? false : 'broken' ), admin_url( 'admin.php' ) ) ); ?>"
				data-tooltip="<?php esc_attr_e( 'See broken URLs', 'affibloom' ); ?>"
				aria-label="<?php echo esc_attr( sprintf( /* translators: %d: number of products. */ __( 'See broken URLs (%d)', 'affibloom' ), $view['broken_count'] ) ); ?>"
				<?php echo 'broken' === $view['filter'] ? 'aria-current="true"' : ''; ?>>
				<span class="dashicons dashicons-editor-unlink" aria-hidden="true"></span>
				<span class="affibloom-filter__count"><?php echo esc_html( (string) $view['broken_count'] ); ?></span>
			</a>
```

Add the header cell `<th scope="col" class="affibloom-col-alert">Alert</th>` after Link Name. In `product-row.php`, the Alert cell (the row now receives `'alerts' => $view['alerts'][ $product->id ] ?? array()`):

```php
	<td class="affibloom-col-alert">
		<?php foreach ( $view['alerts'] as $alert ) : ?>
			<span class="affibloom-alert" data-tooltip="<?php echo esc_attr( sprintf( /* translators: 1: button number, 2: HTTP code. */ __( 'Button %1$d link is broken (%2$d)', 'affibloom' ), $alert['button'], $alert['http_code'] ) ); ?>">
				<span class="dashicons dashicons-warning" aria-hidden="true"></span>
				<span class="screen-reader-text"><?php echo esc_html( sprintf( /* translators: %d: HTTP code. */ __( 'Broken link (%d)', 'affibloom' ), $alert['http_code'] ) ); ?></span>
			</span>
		<?php endforeach; ?>
	</td>
```

`ProductScreen` takes `LinkHealthService $health` and passes `'issues' => $this->health->issues_for( $id )`. `product.php`, before the form:

```php
		<?php foreach ( $view['issues'] as $issue ) : ?>
			<div class="affibloom-issue" role="alert">
				<span class="dashicons dashicons-warning" aria-hidden="true"></span>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: "primary" or "secondary", 2: HTTP code, 3: date. */
						__( 'The %1$s link returned %2$d when checked on %3$s.', 'affibloom' ),
						'1' === (string) $issue['button'] ? __( 'primary', 'affibloom' ) : __( 'secondary', 'affibloom' ),
						(int) $issue['http_code'],
						wp_date( get_option( 'date_format' ), strtotime( $issue['checked_at'] . ' UTC' ) )
					)
				);
				?>
			</div>
		<?php endforeach; ?>
```

`admin.css`:

```css
.affibloom-filter { position: relative; display: inline-flex; align-items: center; gap: 4px; padding: 6px 10px; border-radius: 999px; color: var(--affibloom-muted); text-decoration: none; }
.affibloom-filter:hover, .affibloom-filter.is-active { background: var(--affibloom-purple-tint); color: var(--affibloom-purple); }
.affibloom-filter[data-tooltip]:hover::after, .affibloom-filter[data-tooltip]:focus::after,
.affibloom-alert[data-tooltip]:hover::after { content: attr(data-tooltip); position: absolute; top: 100%; left: 50%; transform: translateX(-50%); margin-top: 6px; padding: 6px 8px; border-radius: 6px; background: var(--affibloom-ink); color: #fff; font-size: 12px; white-space: nowrap; z-index: 10; }
.affibloom-col-alert { width: 72px; }
.affibloom-alert { position: relative; color: #d63638; }
.affibloom-issue { display: flex; gap: 8px; align-items: center; margin: 0 0 16px; padding: 12px 16px; border-left: 4px solid #d63638; border-radius: 6px; background: #fcf0f1; }
```

- [ ] **Step 4: Run, compare with `baselines/admin/dashboard.png` (Alert column and filter: place and labels; looks per the design system, ADR-8), commit**

```bash
npm run test:integration && npm run test:e2e -- acceptance/F11
git add -A && git commit -m "Show broken links in the Dashboard's Alert column and filter

As in Lasso: the Alert column, a \"See broken URLs\" filter with its
count, and an issue panel on the product's page."
```

---

### Task 32: `wp affibloom check-links` and the broken-links notice

> **From the Task 15 sweep (2026-11-13, F1 D16):** Lasso also re-checks a link when its product is saved with a
> changed URL, or when it was last saved more than an hour ago, and files the issue (`save_lasso_url` 1305-1335,
> 1830-1842); this task adds that check to `ProductService::update()` for non-Amazon links (Amazon: C1).

**Roadmap:** 6.2 · **Feature:** F11

**Why:** a developer needs to run the check on demand, for example right after the migration. Editors also need a nudge on the WordPress Dashboard when links break, because they may not open Affibloom every day.

**User action:**
1. `wp affibloom check-links --all` checks every product now and prints a table: product, button, status, HTTP code.
2. `--product=123` checks one product.
3. Editors see a notice on the WordPress Dashboard, and on the Affibloom screens: "3 product links look broken. See broken URLs".

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Interface\Cli\CheckLinksCommand` | `CliCommands` |
| `Interface\Admin\BrokenLinksNotice` (module) | `admin_notices` |
| `LinkHealthService::check_all()` | the command |

**Files:** create the two classes; modify `LinkHealthService.php`, `Plugin.php`.

- [ ] **Step 1:** `CheckLinksCommand::__invoke()`:
- `--product=<id>` → `check_product()` on that product;
- `--all` → `check_all()`, which loops `run_batch( 25 )` with an interval of 0 until nothing is due. Give `run_batch()` a second parameter, `int $interval = self::INTERVAL`, for this.
- Afterwards it prints `format_items( 'table', … )` from `LinkCheckTable::for_products()`.

- [ ] **Step 2:** `BrokenLinksNotice::render()` hooks `admin_notices`. It shows only to users with `Capabilities::PRODUCTS`, only on `index.php` and on `affibloom` screens, and only when `broken_ids()` is not empty. The message is a `notice notice-warning`, with a link to `admin.php?page=affibloom&filter=broken`. The count comes from one query (`ids_with_status`), already cheap; cache it in a 1-hour transient, which `LinkCheckTable::save()` deletes.

- [ ] **Step 3:** run `npm run wp -- affibloom check-links --all` on the local copy, after the migration. With `WP_HTTP_BLOCK_EXTERNAL` on, every external link comes back inconclusive. That proves the "no false alarm" rule on 263 real links. Then commit:

```bash
git add -A && git commit -m "Add wp affibloom check-links and a broken-links notice for editors"
```

**U4 (only if approved): "Re-check now".** Add `POST /affibloom/v1/products/{id}/check` (permission `PRODUCTS`), which runs `check_product()` and returns the new issues. Add a "Re-check now" button to the issue panel, which calls the route and reloads the panel. Add an E2E test with the stub, changing the link's answer from 404 to 200 between the two calls.
