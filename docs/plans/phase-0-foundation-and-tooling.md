# Affibloom — Phase 0: Foundation and tooling — Implementation Plan

> **How to run this plan:** inline, in one working session, task by task, in the order below. Do not hand tasks to subagents. When a step is finished, change its `- [ ]` to `- [x]`.
>
> **Explain as you go:** before each task, say in two or three sentences what the task builds, why it is needed now, and how it will be checked. After the task, say what changed and what the verification showed. No vibe coding: every line written must be one you can explain.
>
> **Shared rules:** the architecture, working method, decisions, global constraints, file structure and API contract are in [`00-overview.md`](00-overview.md). Read it first.

## Tasks in this phase

- [x] Task 1: Plugin skeleton and tooling — activating Affibloom
- [x] Task 2: Local copy of production with Lasso active
- [x] Task 3: Continuous integration on every pull request (CI run pending, see the task's note)

---


### Task 1: Plugin skeleton and tooling — activating Affibloom

> **Status (2026-11-12):** Steps 1–6 and 8 done; commit `c7b808f` pushed to `origin/main`. Step 7 is open: activate Affibloom on the Local site and confirm no notice appears. See `docs/progress.md` for the deviations from this text (repo location, PHPStan fix, wp-env, composer.lock).

**Roadmap:** 0.1, 0.3, 0.5

**Why:** the plugin starts from a clean repository in which standards, static analysis and tests run before any feature code exists, so every later task can begin with a failing test.

**User action:** an administrator activates "Affibloom" under Plugins. Nothing visible happens, and nothing breaks.

**Request path:**
```
Plugins → Activate
  → WordPress loads affibloom.php
       requirement check (PHP 8.1, WordPress 6.5) → admin notice and stop if not met
       vendor/autoload.php
       Affibloom\Plugin::boot( __FILE__ )
         → add_action( 'init', load_textdomain )
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `affibloom.php` (header, constants, checks) | WordPress |
| `affibloom_requirements_notice()` | `admin_notices`, only when the requirements fail |
| `Plugin::boot()`, `Plugin::load_textdomain()` | `affibloom.php`, `init` |
| Composer, PHPCS, PHPStan, PHPUnit and wp-env configuration | the developer, and CI from Task 3 |
| `tests/Unit/TestCase.php` (Brain Monkey) | the first unit test, in this task |

**Not yet, and why:** there is no container, no module list, no activation hook and no table. Each is created by the first task that needs it (Tasks 8 and 30).

**Files:**
- Create:
  - `affibloom.php`, `src/Plugin.php`
  - `composer.json`, `phpcs.xml.dist`, `phpstan.neon.dist`, `phpunit.xml.dist`
  - `package.json`, `.wp-env.json`
  - `.gitignore`, `.editorconfig`, `CHANGELOG.md`
  - `tests/Unit/bootstrap.php`, `tests/Unit/TestCase.php`, `tests/Unit/PluginTest.php`

- [x] **Step 1: Repository**

```bash
mkdir /d/affibloom && cd /d/affibloom
git init -b main
```

`.gitignore`:

```gitignore
/vendor/
/node_modules/
/build/
/.local/
/test-results/
/playwright-report/
/tests/E2E/.auth/
.wp-env.override.json
.phpunit.result.cache
```

`.editorconfig`:

```ini
root = true

[*]
charset = utf-8
end_of_line = lf
insert_final_newline = true
trim_trailing_whitespace = true
indent_style = tab

[*.{json,yml,md}]
indent_style = space
indent_size = 2
```

`CHANGELOG.md`:

```markdown
# Changelog

All notable changes to this plugin are recorded here. Versions follow semantic versioning.

## [Unreleased]
```

- [x] **Step 2: Composer and the tools**

`composer.json`:

```json
{
  "name": "volleyballvault/affibloom",
  "description": "Affiliate product library and product boxes for VolleyballVault.",
  "type": "wordpress-plugin",
  "license": "GPL-2.0-or-later",
  "require": {
    "php": ">=8.1"
  },
  "require-dev": {
    "brain/monkey": "^2.6",
    "dealerdirect/phpcodesniffer-composer-installer": "^1.0",
    "phpcompatibility/phpcompatibility-wp": "^2.1",
    "phpstan/phpstan": "^2.1",
    "phpunit/phpunit": "^9.6",
    "squizlabs/php_codesniffer": "^3.10",
    "szepeviktor/phpstan-wordpress": "^2.0",
    "wp-coding-standards/wpcs": "^3.1",
    "yoast/phpunit-polyfills": "^2.0"
  },
  "autoload": {
    "psr-4": { "Affibloom\\": "src/" }
  },
  "autoload-dev": {
    "psr-4": { "Affibloom\\Tests\\": "tests/" }
  },
  "scripts": {
    "lint": "phpcs",
    "lint:fix": "phpcbf",
    "analyse": "phpstan analyse --memory-limit=1G",
    "test:unit": "phpunit -c phpunit.xml.dist"
  },
  "config": {
    "platform": { "php": "8.1.0" },
    "allow-plugins": { "dealerdirect/phpcodesniffer-composer-installer": true },
    "sort-packages": true
  }
}
```

`config.platform.php` is set to 8.1 so Composer never installs a package that needs a newer PHP than the plugin promises. PHPUnit stays on 9.6 because the WordPress test suite (Task 3) runs on it.

`phpcs.xml.dist`:

```xml
<?xml version="1.0"?>
<ruleset name="Affibloom">
	<description>WordPress Coding Standards for Affibloom.</description>

	<file>affibloom.php</file>
	<file>src</file>

	<arg name="extensions" value="php"/>
	<arg name="colors"/>
	<arg value="sp"/>

	<config name="testVersion" value="8.1-"/>
	<config name="minimum_wp_version" value="6.5"/>

	<rule ref="WordPress">
		<!-- PSR-4 file names (Plugin.php), not class-plugin.php. -->
		<exclude name="WordPress.Files.FileName"/>
		<!-- @param list<string> and similar PHPStan types are more exact than the PHP type "array". -->
		<exclude name="Squiz.Commenting.FunctionComment.IncorrectTypeHint"/>
	</rule>

	<!-- Domain and Application exceptions carry codes, never shown to users; the Interface layer escapes what it prints. -->
	<rule ref="WordPress.Security.EscapeOutput.ExceptionNotEscaped">
		<exclude-pattern>src/Domain/*</exclude-pattern>
		<exclude-pattern>src/Application/*</exclude-pattern>
	</rule>
	<rule ref="PHPCompatibilityWP"/>

	<rule ref="WordPress.WP.I18n">
		<properties>
			<property name="text_domain" type="array">
				<element value="affibloom"/>
			</property>
		</properties>
	</rule>

	<rule ref="WordPress.NamingConventions.PrefixAllGlobals">
		<properties>
			<property name="prefixes" type="array">
				<element value="affibloom"/>
			</property>
		</properties>
	</rule>
</ruleset>
```

The `templates` and `tools` folders are added to this file by the tasks that create them (Tasks 4 and 8).

`phpstan.neon.dist`:

```neon
includes:
	- vendor/szepeviktor/phpstan-wordpress/extension.neon

parameters:
	level: 6
	paths:
		- affibloom.php
		- src
```

`phpunit.xml.dist` (unit tests only, with no WordPress loaded):

```xml
<?xml version="1.0"?>
<phpunit
	bootstrap="tests/Unit/bootstrap.php"
	colors="true"
	beStrictAboutOutputDuringTests="true"
	failOnRisky="true"
	failOnWarning="true">
	<testsuites>
		<testsuite name="unit">
			<directory>tests/Unit</directory>
		</testsuite>
	</testsuites>
	<coverage>
		<include>
			<directory suffix=".php">src</directory>
		</include>
	</coverage>
</phpunit>
```

```bash
composer install
```

- [x] **Step 3: Failing test (outside first)**

`tests/Unit/bootstrap.php`:

```php
<?php
/**
 * Unit test bootstrap: Composer only, no WordPress.
 *
 * @package Affibloom
 */

require dirname( __DIR__, 2 ) . '/vendor/autoload.php';
```

`tests/Unit/TestCase.php`:

```php
<?php
/**
 * Base class for unit tests that touch WordPress functions.
 *
 * @package Affibloom
 */

namespace Affibloom\Tests\Unit;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base class for unit tests that touch WordPress functions.
 */
abstract class TestCase extends PHPUnitTestCase {
	use MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
}
```

`tests/Unit/PluginTest.php`:

```php
<?php
namespace Affibloom\Tests\Unit;

use Affibloom\Plugin;

final class PluginTest extends TestCase {
	public function test_boot_loads_translations_on_init(): void {
		Plugin::boot( '/plugins/affibloom/affibloom.php' );

		self::assertNotFalse( has_action( 'init', 'Affibloom\Plugin->load_textdomain()' ) );
	}
}
```

```bash
composer test:unit
```
Expected: an error that `Affibloom\Plugin` is not found.

- [x] **Step 4: Plugin file (WordPress enters here)**

`affibloom.php`:

```php
<?php
/**
 * Plugin Name:       Affibloom
 * Description:       Affiliate product library and product boxes for VolleyballVault.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            VolleyballVault
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       affibloom
 * Domain Path:       /languages
 *
 * @package Affibloom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AFFIBLOOM_VERSION', '0.1.0' );
define( 'AFFIBLOOM_FILE', __FILE__ );

/**
 * Explains why the plugin did not start. Shown only when the checks below fail.
 */
function affibloom_requirements_notice() {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'Affibloom needs PHP 8.1 and WordPress 6.5 or newer. It has not started.', 'affibloom' )
	);
}

// WordPress checks the header's requirements before activation, but not after
// a server is downgraded, so they are checked again on every load.
if ( version_compare( PHP_VERSION, '8.1', '<' ) || version_compare( get_bloginfo( 'version' ), '6.5', '<' ) ) {
	add_action( 'admin_notices', 'affibloom_requirements_notice' );
	return;
}

if ( ! is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	add_action( 'admin_notices', 'affibloom_requirements_notice' );
	return;
}

require __DIR__ . '/vendor/autoload.php';

Affibloom\Plugin::boot( __FILE__ );
```

This file uses no PHP 8 syntax, so an older PHP can still read it and show the notice instead of a fatal error.

- [x] **Step 5: Composition root (what the plugin file calls)**

`src/Plugin.php`:

```php
<?php
/**
 * Composition root: builds the plugin's objects and hands them to WordPress.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom;

/**
 * Starts the plugin once WordPress has loaded it.
 */
final class Plugin {

	/**
	 * Absolute path of affibloom.php.
	 *
	 * @var string
	 */
	private string $file;

	/**
	 * Use Plugin::boot().
	 *
	 * @param string $file Absolute path of affibloom.php.
	 */
	private function __construct( string $file ) {
		$this->file = $file;
	}

	/**
	 * Entry point, called once from affibloom.php.
	 *
	 * @param string $file Absolute path of affibloom.php.
	 */
	public static function boot( string $file ): void {
		( new self( $file ) )->register();
	}

	/**
	 * Hooks the plugin into WordPress.
	 */
	private function register(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Loads translations from /languages.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'affibloom', false, dirname( plugin_basename( $this->file ) ) . '/languages' );
	}
}
```

```bash
composer test:unit
```
Expected: `OK (1 test, 1 assertion)`.

- [x] **Step 6: Standards and static analysis**

```bash
composer lint
composer analyse
```
Expected: no PHPCS errors and `[OK] No errors` from PHPStan. Fix anything reported before going on. `composer lint:fix` corrects spacing automatically.

- [x] **Step 7: Local WordPress**

> **D1 = b (2026-11-12):** on this machine the check is done on the Local site, not wp-env. Affibloom is
> already active there (`active 0.1.0`, seen with WP-CLI); the check by hand happens in Task 2, Step 3,
> once the copy is safe to load. The wp-env files below stay as written, for CI (Task 3).

`package.json`:

```json
{
  "name": "affibloom",
  "private": true,
  "license": "GPL-2.0-or-later",
  "scripts": {
    "env:start": "wp-env start",
    "env:stop": "wp-env stop",
    "wp": "wp-env run cli wp"
  },
  "devDependencies": {
    "@wordpress/env": "^10.0.0"
  }
}
```

`.wp-env.json`:

```json
{
  "core": "https://wordpress.org/wordpress-7.1.4.zip",
  "phpVersion": "8.3",
  "plugins": [ "." ],
  "env": {
    "development": { "port": 8888 },
    "tests": { "port": 8889 }
  }
}
```

```bash
npm install
npm run env:start
npm run wp -- plugin activate affibloom
npm run wp -- plugin list --name=affibloom --fields=name,status,version
```
Expected: `affibloom  active  0.1.0`. Open http://localhost:8888/wp-admin/plugins.php (user `admin`, password `password`). There should be no notice, and the front end should look unchanged.

- [x] **Step 8: Commit**

```bash
git add -A
git commit -m "Start the Affibloom plugin with its tooling

An empty plugin that activates cleanly, with PHPCS, PHPStan, PHPUnit
and wp-env in place so every later task can start from a failing test."
```

---

### Task 2: Local copy of production with Lasso active

> **Adapted to Local (decision D1 = b, approved by the user on 2026-11-12).** The original text built a
> wp-env (Docker) copy from a production backup in `D:\vv-dev\`. This machine already has the production
> copy in Local (`http://volleyballvault.local`), so this task makes that copy safe and resettable instead.
> The goals are unchanged: no calls to Lasso's cloud, no cron, no real people's data, one-command reset, and
> a smoke test. CI (Task 3) still uses wp-env on GitHub. Facts this relies on (checked 2026-11-12): Local site
> id `XMaGBQZ-W`, prefix `wp_`, PHP 8.2.29, MySQL 8.4.0, Mailpit catches all mail, Lasso v369 installed but
> inactive. See `docs/environment.md`.

**Roadmap:** 0.2, 0.6

**Why:** every comparison in this plan is made against the real site as it is today, with Lasso running. The copy must also be safe: it must not send anything to Lasso's cloud or to readers.

**User action:** a developer runs one command and gets the local VolleyballVault back to a clean state, with Lasso and the real content, and Affibloom active beside it with no visible change.

**Request path:**
```
bash tools/reset-local.sh
  → refuses to run unless wp-config.php has the safety constants
  → tools/local-wp.sh (Local's PHP + the site's php.ini + Local's mysql + Local's WP-CLI)
  → wp db reset → import .local/snapshot.sql (the cleaned copy made in Step 6)
  → deactivate the plugins listed in tools/local-deactivate.txt (ads, caching)
  → activate lasso and affibloom
npm run test:e2e -- smoke
  → tests/E2E/smoke.spec.ts against http://volleyballvault.local:
    a guide with Affibloom off and on gives the same page
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `tools/local-wp.sh` | `reset-local.sh`, the smoke test, the developer (any terminal) |
| Safety constants in the site's `wp-config.php` (outside the repo) | WordPress and Lasso on every load; checked by `reset-local.sh` |
| `.local/snapshot.sql` (gitignored) | `reset-local.sh` |
| `tools/reset-local.sh`, `tools/local-deactivate.txt` | the developer, and every visual test run |
| `playwright.config.ts`, `tests/E2E/global-setup.ts` | `npm run test:e2e` |
| `tests/E2E/smoke.spec.ts` | `npm run test:e2e` |

**Not built, and why:** `tools/make-wp-env-override.mjs` (Local needs no folder mappings) and the search-replace from `https://volleyballvault.com` (the Local database already uses the local domain; Step 6 checks this with a dry run).

**Files:**
- Create: `tools/local-wp.sh`, `tools/reset-local.sh`, `tools/local-deactivate.txt`, `playwright.config.ts`, `tests/E2E/global-setup.ts`, `tests/E2E/smoke.spec.ts`
- Modify: `package.json`
- Outside the repo: the site's `wp-config.php` (Step 2)

- [x] **Step 1: WP-CLI for Local from any terminal**

Every later step talks to the Local site through WP-CLI. Local's own "Open site shell" works, but scripts and Playwright cannot use it, so this wrapper does what that shell does.

`tools/local-wp.sh`:

```bash
#!/usr/bin/env bash
# Runs WP-CLI against the Local (Local by Flywheel) copy of the site from any terminal,
# the way Local's "Open site shell" does: Local's PHP with the site's php.ini, Local's
# mysql client, and the WP-CLI that ships with Local. Override the paths with the
# variables below if Local is installed elsewhere or the versions change.
set -euo pipefail

LOCAL_DATA="${LOCAL_DATA:-$APPDATA/Local}"
LOCAL_SITE_ID="${LOCAL_SITE_ID:-XMaGBQZ-W}"
LOCAL_PHP_DIR="${LOCAL_PHP_DIR:-$LOCAL_DATA/lightning-services/php-8.2.29+0/bin/win64}"
LOCAL_MYSQL_DIR="${LOCAL_MYSQL_DIR:-$LOCAL_DATA/lightning-services/mysql-8.4.0+2/bin/win64/bin}"
LOCAL_WP_CLI="${LOCAL_WP_CLI:-/c/Program Files (x86)/Local/resources/extraResources/bin/wp-cli/wp-cli.phar}"
# The site's MySQL port, from Local's run/<site id>/conf/mysql/my.cnf.
LOCAL_MYSQL_PORT="${LOCAL_MYSQL_PORT:-10005}"

# The WordPress root is four levels up from this file: wp-content/plugins/affibloom/tools.
SITE_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../../.." && pwd)"

export PHPRC="$LOCAL_DATA/run/$LOCAL_SITE_ID/conf/php"
# PHP's folder and its ImageMagick folder hold the DLLs that php_imagick.dll needs. Without
# them PHP prints a startup warning on stdout, which breaks --format=json output.
export PATH="$LOCAL_PHP_DIR/ImageMagick:$LOCAL_PHP_DIR:$LOCAL_MYSQL_DIR:$PATH"
# PHP gets the port from php.ini, but `wp db …` runs the mysql client with --no-defaults
# and DB_HOST "localhost", so without this it tries port 3306.
export MYSQL_TCP_PORT="$LOCAL_MYSQL_PORT"

exec "$LOCAL_PHP_DIR/php.exe" "$LOCAL_WP_CLI" --path="$SITE_ROOT" "$@"
```

```bash
bash tools/local-wp.sh core version
bash tools/local-wp.sh option get siteurl
bash tools/local-wp.sh plugin list --name=lasso --format=json
```
Expected: `7.1.4`, `http://volleyballvault.local`, and valid JSON for Lasso, with no PHP warning in any output. Start Local's site first if the database is not reachable.

> **Done 2026-11-12.** Output as expected, but Lasso showed `active`: it had been switched on since the
> earlier check, before the safety constants existed. The first `db export` failed (mysql tried port 3306),
> so the wrapper gained `MYSQL_TCP_PORT` (shown above).

- [x] **Step 2: Make the copy safe (before any page is loaded on purpose)**

Back up the database, outside the web root:

```bash
bash tools/local-wp.sh db export "C:/Users/Rupash/Local Sites/volleyballvault/affibloom-backup-2026-11-12.sql"
```

In the site's `wp-config.php` (not in the repo), add above `/* That's all, stop editing! */`:

```php
// Affibloom Task 2: this Local copy must not reach Lasso's cloud, Sentry or PostHog,
// and must not run scheduled jobs. Never copy these lines to a live site.
define( 'WP_HTTP_BLOCK_EXTERNAL', true );
define( 'DISABLE_WP_CRON', true );
define( 'SENTRY_DSN', '' );
define( 'LASSO_POSTHOG_API_KEY', '' );
```

Why each line:
- `WP_HTTP_BLOCK_EXTERNAL` blocks every `wp_remote_*` request except to this site's own host and localhost, which includes Lasso's licence, sync and Amazon calls. The link checker in Task 30 is tested against a local stub, never the real internet, on this copy.
- `DISABLE_WP_CRON` stops page loads from starting WP-Cron. Lasso's background-processing loopback to this site's own `admin-ajax.php` can still run, but everything it then requests from outside is blocked by the line above.
- `SENTRY_DSN` empty: Lasso's bundled Sentry SDK sends errors and traces over its own curl client, which `WP_HTTP_BLOCK_EXTERNAL` does not cover. Lasso defines the DSN only if it is not defined yet (`admin/lasso-constant.php`), and the SDK treats an empty DSN as none and uses its `NullTransport`.
- `LASSO_POSTHOG_API_KEY` empty: Lasso's admin analytics prints no PostHog loader when the key is empty (`classes/class-product-analytics.php`).

Mail needs no change: PHP sends to Local's Mailpit (`localhost:10001`), and no SMTP plugin is active.

```bash
bash tools/local-wp.sh eval '$r = wp_remote_get( "https://app.getlasso.co/" ); echo is_wp_error( $r ) ? $r->get_error_message() : "NOT BLOCKED";'
bash tools/local-wp.sh config get DISABLE_WP_CRON
bash tools/local-wp.sh config list SENTRY_DSN LASSO_POSTHOG_API_KEY --strict --format=json
```
Expected: `User has blocked requests through HTTP.`, `1`, and both constants listed with empty values.

> **Done 2026-11-12.** Backup 153 MB at the path above. WP 7.1 words the message as `User has blocked
> requests through HTTP to the URL: https://app.getlasso.co/.`; requests to the site's own host still
> return 200.

What this cannot stop: scripts that Lasso's own admin screens load in the browser (Sentry's browser bundle from its CDN, Segment). Do not open Lasso's admin screens on this copy unless needed, or block those hosts in the machine's hosts file (the user's decision).

- [x] **Step 3: Finish Task 1, Step 7 (check by hand)**

Affibloom is already active (`bash tools/local-wp.sh plugin list --name=affibloom` → `active 0.1.0`). Open `http://volleyballvault.local/wp-admin/plugins.php`: no Affibloom notice. Open a guide: it looks as before. Then tick Task 1, Step 7 and Task 1.

- [x] **Step 4: No real people's data**

```bash
WP="bash tools/local-wp.sh"
$WP db query "UPDATE wp_users SET user_email = CONCAT('user', ID, '@example.test'), user_pass = ''"
$WP db query "DELETE FROM wp_usermeta WHERE meta_key = 'session_tokens'"
$WP db query "UPDATE wp_comments SET comment_author_email = '', comment_author_IP = ''"
$WP user create vvadmin vvadmin@example.test --role=administrator --user_pass=password --quiet
```
After this, every real account on this copy has no password; log in as `vvadmin` / `password`. Expected: `$WP user list --fields=user_login,user_email` shows only `@example.test` addresses.

- [x] **Step 5: Lasso on, noisy plugins off**

> **Done 2026-11-12.** Lasso was already active (see Step 1). `tools/local-deactivate.txt` =
> `mediavine-control-panel`, `litespeed-cache`; both deactivated. The guide shows 10 `lasso-container` boxes.

`tools/local-deactivate.txt` lists plugins that make screenshots unstable or need a production server. Fill it in after looking at `bash tools/local-wp.sh plugin list --status=active`, for example:

```
mediavine-control-panel
litespeed-cache
```

```bash
while read -r plugin; do [ -n "$plugin" ] && bash tools/local-wp.sh plugin deactivate "$plugin" --quiet || true; done < tools/local-deactivate.txt
bash tools/local-wp.sh plugin activate lasso
curl -s http://volleyballvault.local/best-volleyball-shoes-for-hitters/ | grep -c 'lasso-container'
```
Expected: Lasso active, and a count above 0. Then log in at `http://volleyballvault.local/wp-admin` as `vvadmin`: Lasso's menu is there.

- [x] **Step 6: Snapshot and one-command reset**

> **Done 2026-11-12.** Snapshot 153 MB, taken after the scrub and after `vvadmin` existed. With no snapshot
> the script refused and changed nothing; a real run took 74 s and printed `Local copy reset.`; afterwards
> Lasso and Affibloom active, the two listed plugins inactive, `vvadmin` present, 10 boxes on the guide.

```bash
bash tools/local-wp.sh search-replace 'https://volleyballvault.com' 'http://volleyballvault.local' --all-tables --skip-columns=guid --dry-run --report-changed-only
bash tools/local-wp.sh db export .local/snapshot.sql
```
The dry run is expected to report few or no changes (the database already uses the local domain). If it reports content rows, look at them with the user before deciding whether to run it for real. `.local/` is gitignored, so the snapshot never reaches git.

> **2026-11-12:** the dry run found 1 replacement, in plugin options only (Rank Math's Search Console map
> `https://volleyballvault.com/ → http://volleyballvault.local/`, and an old ShortPixel notice); no content.
> Not run. Take the snapshot only after `vvadmin` exists (Step 4), or every reset removes it.

`tools/reset-local.sh`:

```bash
#!/usr/bin/env bash
# Returns the Local copy to the cleaned snapshot in .local/snapshot.sql. Safe to run any time.
# The safety constants live in wp-config.php, outside the database, so a reset keeps them.
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."

WP="bash tools/local-wp.sh"

# The snapshot carries Lasso's production licence, so never load it into a site that
# could reach the internet or run Lasso's jobs.
for constant in WP_HTTP_BLOCK_EXTERNAL DISABLE_WP_CRON SENTRY_DSN LASSO_POSTHOG_API_KEY; do
	if ! $WP config has "$constant" --type=constant; then
		echo "wp-config.php is missing $constant (Task 2, Step 2). Nothing was changed." >&2
		exit 1
	fi
done

if [ ! -f .local/snapshot.sql ]; then
	echo "No .local/snapshot.sql yet (Task 2, Step 6). Nothing was changed." >&2
	exit 1
fi

$WP db reset --yes
$WP db import .local/snapshot.sql

while read -r plugin; do
	[ -n "$plugin" ] && $WP plugin deactivate "$plugin" --quiet || true
done < tools/local-deactivate.txt

$WP plugin activate lasso affibloom --quiet
$WP rewrite flush --quiet
$WP cache flush --quiet
echo "Local copy reset."
```

```bash
bash tools/reset-local.sh
```
Expected: `Local copy reset.`, and the Step 5 `curl` count is unchanged.

- [x] **Step 7: Playwright**

> **Done 2026-11-12.** `@playwright/test` 1.65.0 and Chromium installed; Node's `bash` is Git Bash, so
> `AFFIBLOOM_BASH` is not needed on this machine.

```bash
npm install --save-dev @playwright/test
npx playwright install chromium
```

`package.json`, `scripts`, add:

```json
    "test:e2e": "playwright test",
    "reset-local": "bash tools/reset-local.sh"
```

`playwright.config.ts`:

```ts
import { defineConfig, devices } from '@playwright/test';

export default defineConfig( {
	testDir: './tests',
	testMatch: [ 'E2E/**/*.spec.ts', 'Visual/**/*.spec.ts' ],
	timeout: 60_000,
	fullyParallel: false, // Tests switch plugins on the shared local site.
	workers: 1,
	reporter: [ [ 'list' ], [ 'html', { open: 'never' } ] ],
	globalSetup: './tests/E2E/global-setup.ts',
	use: {
		// Local by default; CI (Task 3) sets WP_BASE_URL to its wp-env site.
		baseURL: process.env.WP_BASE_URL ?? 'http://volleyballvault.local',
		storageState: 'tests/E2E/.auth/admin.json',
		trace: 'retain-on-failure',
	},
	expect: {
		toHaveScreenshot: { maxDiffPixelRatio: 0.01, animations: 'disabled' },
	},
	projects: [
		{ name: 'desktop', use: { ...devices[ 'Desktop Chrome' ], viewport: { width: 1280, height: 900 } } },
	],
} );
```

`tests/E2E/global-setup.ts`:

```ts
import { chromium, type FullConfig } from '@playwright/test';

// Logs in once and saves the cookies, so each test starts signed in.
export default async function globalSetup( config: FullConfig ) {
	const baseURL = config.projects[ 0 ].use.baseURL as string;
	const user = process.env.WP_USER ?? 'vvadmin';
	const password = process.env.WP_PASSWORD ?? 'password';

	const browser = await chromium.launch();
	const page = await browser.newPage();
	await page.goto( `${ baseURL }/wp-login.php` );
	await page.fill( '#user_login', user );
	await page.fill( '#user_pass', password );
	await page.click( '#wp-submit' );
	await page.waitForURL( /wp-admin/ );
	await page.context().storageState( { path: 'tests/E2E/.auth/admin.json' } );
	await browser.close();
}
```

- [x] **Step 8: Smoke test: Affibloom beside Lasso changes nothing**

`tests/E2E/smoke.spec.ts`:

```ts
import { test, expect, type Page } from '@playwright/test';
import { execFileSync } from 'node:child_process';

// Git Bash runs tools/local-wp.sh. If `bash` on PATH is WSL's, set AFFIBLOOM_BASH to Git Bash's bash.exe.
const bash = process.env.AFFIBLOOM_BASH ?? 'bash';
const wp = ( ...args: string[] ) =>
	execFileSync( bash, [ 'tools/local-wp.sh', ...args ], { stdio: 'pipe' } ).toString();

// What a reader gets from a guide: its text, its boxes and every link target.
// Lasso gives each box a random anchor id, so ids are not compared.
async function snapshot( page: Page ) {
	await page.goto( '/best-volleyball-shoes-for-hitters/' );
	const article = page.locator( 'article' ).first();
	return {
		text: ( await article.innerText() ).replace( /\s+/g, ' ' ),
		boxes: await page.locator( '.lasso-container' ).count(),
		links: await article.locator( 'a' ).evaluateAll( ( links ) => links.map( ( a ) => a.getAttribute( 'href' ) ) ),
	};
}

test( '@local Affibloom active beside Lasso changes nothing on a guide', async ( { page } ) => {
	wp( 'plugin', 'deactivate', 'affibloom' );
	const before = await snapshot( page );

	wp( 'plugin', 'activate', 'affibloom' );
	const after = await snapshot( page );

	expect( before.boxes ).toBeGreaterThan( 0 );
	expect( after ).toEqual( before );
} );
```

The `@local` tag marks tests that need the production copy. CI skips them (Task 3).

```bash
npm run test:e2e -- smoke
```
Expected: `1 passed`.

> **Done 2026-11-12.** `1 passed`, run by the user in their own PowerShell (it signs in on a `.local`
> host); Playwright's browsers had to be installed from that terminal too (see environment.md).

- [x] **Step 9: Commit**

```bash
git add -A
git commit -m "Add a safe Local copy of production and a smoke test

The Local site blocks outgoing requests, cron, Sentry and PostHog so
Lasso cannot reach its cloud, has no real user data, and resets from a
cleaned snapshot with one command. The smoke test proves activating
Affibloom beside Lasso changes nothing."
```

> **Done 2026-11-12:** `84b7ff9`, plus `196f0f1` "Keep LF line endings in every checkout" (`.gitattributes`,
> see progress.md deviation 8). Not pushed yet.

---


### Task 3: Continuous integration on every pull request

**Roadmap:** 0.1, 0.4, 0.6

**Why:** standards, static analysis and all test levels must run on every change without anyone having to remember them.

**User action:** a developer opens a pull request. GitHub runs the checks, and the pull request shows the code review checklist.

**Request path:**
```
git push → pull request
  → .github/workflows/ci.yml
       lint:        composer lint, composer analyse
       unit:        composer test:unit
       integration: wp-env start → phpunit -c phpunit-integration.xml.dist (tests container)
       e2e:         wp-env start → playwright test --grep-invert @local
  → .github/pull_request_template.md (gate 7 checklist)
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `.github/workflows/ci.yml` | GitHub, on every pull request and push to `main` |
| `.github/pull_request_template.md` | GitHub, on every new pull request |
| `phpunit-integration.xml.dist`, `tests/Integration/bootstrap.php` | the CI integration job, `npm run test:integration` |
| `tests/Integration/PluginLoadsTest.php` | the integration suite |

**Files:**
- Create: the four files above, plus `tests/Integration/PluginLoadsTest.php`
- Modify: `package.json`

- [x] **Step 1: Failing integration test**

> **Written, not run (2026-11-12).** The files are in place, but `npm run test:integration` needs wp-env
> (Docker, not on this machine) and GitHub Actions is blocked by the account's billing. `OK (2 tests, …)` has
> not been seen yet.

`phpunit-integration.xml.dist`:

```xml
<?xml version="1.0"?>
<phpunit
	bootstrap="tests/Integration/bootstrap.php"
	colors="true"
	failOnRisky="true"
	failOnWarning="true">
	<testsuites>
		<testsuite name="integration">
			<directory>tests/Integration</directory>
		</testsuite>
	</testsuites>
</phpunit>
```

`tests/Integration/bootstrap.php`:

```php
<?php
/**
 * Integration test bootstrap: the WordPress test suite inside wp-env.
 *
 * @package Affibloom
 */

require dirname( __DIR__, 2 ) . '/vendor/autoload.php';

$affibloom_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : '/wordpress-phpunit';

require_once $affibloom_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () {
		require dirname( __DIR__, 2 ) . '/affibloom.php';
	}
);

require $affibloom_tests_dir . '/includes/bootstrap.php';
```

`tests/Integration/PluginLoadsTest.php`:

```php
<?php
namespace Affibloom\Tests\Integration;

use WP_UnitTestCase;

final class PluginLoadsTest extends WP_UnitTestCase {
	public function test_plugin_boots_inside_wordpress(): void {
		self::assertTrue( defined( 'AFFIBLOOM_VERSION' ) );
		self::assertTrue( class_exists( \Affibloom\Plugin::class ) );
	}

	public function test_deactivating_and_activating_raises_no_errors(): void {
		deactivate_plugins( 'affibloom/affibloom.php' );
		$result = activate_plugin( 'affibloom/affibloom.php' );

		self::assertNull( $result );
	}
}
```

`package.json`, `scripts`, add:

```json
    "test:integration": "wp-env run tests-cli --env-cwd=wp-content/plugins/affibloom vendor/bin/phpunit -c phpunit-integration.xml.dist"
```

```bash
npm run test:integration
```
Expected: `OK (2 tests, …)`. The test passes straight away, because it checks Task 1's code inside WordPress. If it fails, Task 1 is not done.

- [x] **Step 2: Workflow**

`.github/workflows/ci.yml`:

```yaml
name: CI

on:
  pull_request:
  push:
    branches: [ main ]

jobs:
  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.1', tools: composer }
      - run: composer install --no-progress
      - run: composer lint
      - run: composer analyse

  unit:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: [ '8.1', '8.3', '8.4' ]
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '${{ matrix.php }}', tools: composer, coverage: none }
      - run: composer install --no-progress
      - run: composer test:unit

  integration-and-e2e:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.1', tools: composer }
      - uses: actions/setup-node@v4
        with: { node-version: '22', cache: npm }
      - run: composer install --no-progress
      - run: npm ci
      - run: npx wp-env start
      - run: npm run test:integration
      - run: npx playwright install --with-deps chromium
      - run: npx playwright test --grep-invert @local
        env:
          WP_BASE_URL: http://localhost:8888 # Task 2 (adapted) defaults Playwright to the Local site.
          WP_USER: admin
          WP_PASSWORD: password
      - uses: actions/upload-artifact@v4
        if: failure()
        with: { name: playwright-report, path: playwright-report }
```

CI never sees production data: there is no `.local/snapshot.sql` there, and the Local site's `wp-config.php` is not in the repo. Tests tagged `@local` compare against the production copy and run only on a developer's machine. CI runs on a clean WordPress, where tests create their own products.

- [x] **Step 3: Pull request template (gate 7)**

`.github/pull_request_template.md`:

```markdown
## What and why

<!-- One paragraph. Link the task number from the implementation plan. -->

## Checks

- [ ] Correctness: meets every acceptance criterion; edge cases from the analysis are handled and tested.
- [ ] Architecture: code sits in the right layer; dependencies point inward; no business logic in templates or hooks.
- [ ] Security: capability checks and nonces on writes; input sanitised, output escaped; queries prepared; REST routes have permission callbacks.
- [ ] Performance: no queries inside loops; results cached where reused; assets load only where needed; background work batched.
- [ ] Consistency: naming, prefixes and file layout match the standards; no duplicated logic.
- [ ] UI/UX: matches the approved mockup and Lasso's layout, labels and help text; built from the shared UI kit; any difference is an approved register item.
- [ ] Maintainability: small focused classes, typed signatures, docblocks on public methods; no dead code.
- [ ] Tests: new code covered at the right level; tests are readable and independent.
- [ ] Roadmap: checklist updated, decisions recorded, anything deferred noted with a reason.

## Verified by hand

<!-- What you ran and what you saw. -->
```

- [x] **Step 4: Protect `main`**

> **Replaced 2026-11-12 (decision D3 = c).** GitHub refuses branch protection and rulesets for a private
> repo on a Free account. `main` is not protected on GitHub; the rule is kept by discipline: every task on
> its own branch with a pull request, merged only with green CI and the user's approval, no direct pushes.

Create the repository on GitHub, push, then go to Settings → Branches → add a rule for `main`: require a pull request, require the `lint`, `unit` and `integration-and-e2e` checks, and allow no direct pushes. From here on every task runs on its own branch: `git switch -c task-04-inventory`.

- [x] **Step 5: Run it**

> **Done without CI (2026-11-12, the user's decision).** PR #1 opened; both runs ended `startup_failure`
> because GitHub billing blocks Actions on this account ("recent account payments have failed or your
> spending limit needs to be increased"). The user chose to merge on the local checks (lint, analyse, unit,
> smoke all green): `main` fast-forwarded to `77492e2`, PR #1 shows merged. CI stays pending, and from here
> on the user does not want further CI or GitHub Actions work (progress.md, deviation 10).

```bash
git switch -c task-03-ci
git add -A
git commit -m "Run standards, analysis and all test levels on every pull request

Production data never reaches CI: tests that need the local copy are
tagged @local and skipped there."
git push -u origin task-03-ci
```
Open the pull request. Expected: all three jobs green, and the description pre-filled with the checklist. Merge.
