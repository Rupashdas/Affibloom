# Environment

> **Partly out of date (2026-11-14).** Written before the full-scope rule (2026-11-13). Where it conflicts with `docs/features/F37/requirements.md` or the phase plans, those win. Known stale parts: the Lasso lines saying "inactive on this copy" (it is listed active in this copy's `active_plugins`, licence status 0; facts recheck 2026-11-14); "Safety state" (rewritten below on 2026-11-14); "Branch protection on `main` is set up in Task 3" (the simple workflow of 2026-11-12 skips branches, pull requests and CI); the `wp db export ../...` advice that stood in Safety state contradicts the gotcha above it (use `bash tools/local-wp.sh db export <absolute path>`).

## Machine and site

- Windows, user `Rupash`. Site root: `C:\Users\Rupash\Local Sites\volleyballvault\app\public`
  (Local by Flywheel). Plugin repo: `...\app\public\wp-content\plugins\affibloom`.
- Local site: id `XMaGBQZ-W`, URL `http://volleyballvault.local` (`siteurl` confirmed 2026-11-12).
  Table prefix `wp_`. `WP_ENVIRONMENT_TYPE` is already `local` in `wp-config.php`.
- Local's versions (2026-11-14): WordPress 7.1.4, **PHP 8.3.36** (switched from 8.2.29 for Task 2X0), MySQL 8.4.0, nginx 1.26.1, Mailpit 1.24.1.
  Production runs WP 7.1.4, **PHP 8.3, MariaDB 10.11**, so only the database differs here. MariaDB
  10.11.18 is installed in Local, but Local can't switch an existing site's database. CI (wp-env) tests
  the production versions.
- WP-CLI: Local's site shell (Local → the site → "Open site shell"), or from any terminal with Local's PHP,
  the site's php.ini and the bundled WP-CLI (read-only call checked 2026-11-12):
  ```bash
  export PHPRC="$APPDATA/Local/run/XMaGBQZ-W/conf/php"
  "$APPDATA/Local/lightning-services/php-8.3.37+4/bin/win64/php.exe" \
    "/c/Program Files (x86)/Local/resources/extraResources/bin/wp-cli/wp-cli.phar" <command>
  ```
  (the folder changes when Local updates PHP: 8.3.36+0 became 8.3.37+4 on 2026-11-22; `tools/local-wp.sh` picks the newest
  `php-8.3.*` itself).
  **Use `bash tools/local-wp.sh <command>` from the plugin folder** (Task 2): it does the above, puts PHP's
  `ImageMagick` folder on PATH (otherwise a `php_imagick.dll` warning lands on stdout and breaks JSON
  output), adds Local's `mysql` client, and sets `MYSQL_TCP_PORT` (WP-CLI runs mysql with
  `--no-defaults` and `DB_HOST` is `localhost`, so it would otherwise try port 3306). It reads the port from
  Local's `run/XMaGBQZ-W/conf/mysql/my.cnf` on every run, because **Local can change it when the site
  restarts** (10005 → 10006 on 2026-11-12, which made `reset-local.sh` fail with "Can't connect to MySQL
  server on 'localhost:10005'"). `LOCAL_MYSQL_PORT=<n>` still overrides it.
- **Gotcha: `bash` in the user's PowerShell.** Git Bash is installed (`C:\Program Files\Git\bin\bash.exe`)
  but not on PowerShell's PATH, so `bash tools/…`, `npm run reset-local` and the smoke test fail there with
  "The term 'bash' is not recognized". Per terminal: `$env:Path = "C:\Program Files\Git\bin;" + $env:Path`
  (or add that folder to the user PATH once, the user's choice). Claude Code's own shell already has it.
  **The Playwright tests no longer need this** (2026-11-13): `tests/E2E/utils.ts` finds Git Bash itself on
  Windows (`ProgramW6432`, `ProgramFiles` or `%LOCALAPPDATA%\Programs`, then `Git\bin\bash.exe`), before a
  `bash` on PATH, which may be WSL's; `AFFIBLOOM_BASH` still overrides it. Before that, every test that checks
  the database through `wp()` failed with `spawnSync bash ENOENT`. Running `bash tools/reset-local.sh` or
  `npm run reset-local` from PowerShell still needs the PATH line, or `& "C:\Program Files\Git\bin\bash.exe" tools/reset-local.sh`.
- **Gotcha: Playwright browsers.** Claude Code's sandboxed shell does not write to the user's real
  `%LOCALAPPDATA%\ms-playwright`; browsers it installs are invisible to the user's terminal. Install them
  from the user's own terminal (`npx.cmd playwright install chromium`, done 2026-11-12).
- **Gotcha: `npm` in the user's PowerShell.** The execution policy blocks `npm.ps1` ("running scripts is
  disabled on this system"). Call `npm.cmd …` / `npx.cmd …` instead; that needs no policy change.
- **Gotcha: `%` in `wp db query` on Windows.** WP-CLI runs the mysql client through `cmd.exe`, which reads
  `%…%` as an environment variable, so `LIKE '%x%'` queries silently return wrong results. Use
  `wp eval` with `$wpdb->prepare()` instead, or pipe SQL on stdin.
- **Gotcha: Git Bash rewrites `/…` arguments.** When Git Bash starts a native program (`php.exe`), an argument that
  starts with `/`, or `--x=/…`, becomes a Windows path: `/wp-json/x` arrives as `C:/Program Files/Git/wp-json/x`
  (checked 2026-11-12). JSON, block markup, quotes and `&` pass unchanged. `tools/local-wp.sh` relies on the
  rewriting for its own paths, so it stays on; `tests/E2E/utils.ts` `wp()` refuses such arguments instead.
- **Gotcha: `wp db export` paths are relative to where `local-wp.sh` is run.** From the plugin folder,
  `../affibloom-backup-….sql` lands in `wp-content/plugins/`, inside the web root (Tasks 12A and 12B did this;
  the files were moved to `app/`). Give the absolute path, e.g.
  `bash tools/local-wp.sh db export "C:/Users/Rupash/Local Sites/volleyballvault/app/affibloom-backup-<date>.sql"`.
- **A Playwright run no longer saves or restores the Local copy (deviation 129):** the Local database is dummy data,
  so runs leave what they made. `globalSetup` only clears Affibloom's background events (`tests/E2E/quiet-jobs.ts`).
  The old save/restore stays as an opt-in tool, described next. **Earlier (deviation 111):** `tests/E2E/run-state.ts`
  (first in `globalSetup`) saves the state to `.local/e2e-run-state.json`; `run-state-teardown.ts` puts it back
  after the run, failed or not: posts and terms the run made are deleted, `affibloom*` options, products' status
  and slug and the active plugins are restored, and it prints what it did ("run-state: deleted N posts …"). No
  backup, credentials copy or `reset-local.sh` is needed around a run. If a run is killed before its teardown, the
  next run restores the leftover first; by hand: `bash tools/local-wp.sh eval-file tests/E2E/fixtures/run-state.php restore`.
  Not restored: edits to posts that existed before the run, users, comments. Before 2026-11-15 the suite left its
  data (a run on 2026-11-12 left 37 products, 26 posts and a test secret). Since C1C (deviation 113) it also puts back
  the rows of Affibloom's own tables (`{prefix}affibloom_*`) and leaves `affibloom_db_version` and
  `affibloom_upgrade_lock` alone (the schema follows the code, not the run). Never run two Playwright runs at once:
  they share `test-results/` and break each other's traces.
- **Running tests (deviation 111):** from the plugin folder,
  `node node_modules/@playwright/test/cli.js test -c .local/no-login.config.ts <spec> -g "<AC or task>" -x --reporter=line`
  (`-g` picks the task's tests, `-x` stops at the first failure). Looks: `node tools/shot.cjs "wp-admin/admin.php?page=…"`
  (no leading `/`: Git Bash rewrites it), 1280 and 375 px by default, into `.local/shots/`.
- **A long burst of requests stops Local's PHP workers** (seen 2026-11-22, Task 37A: ~800 REST calls in a row): nginx
  answers 502 "no live upstreams" until the site is stopped and started again in Local. Keep test walks to a few
  hundred requests.
- **Local runs only two PHP workers:** on Windows it starts two `php-cgi.exe` processes (ports 10002, 10003), each
  serving one request at a time; `pm.max_children` in `www.conf` does not apply. The count is hard-coded in Local's
  `lightning-services/php-*/lib/PhpService.js` (`requiredPorts`: 2 on Windows), not a setting (checked 2026-11-15,
  deviation 115). A page load sometimes waits ~16 s behind another request, which is why the tests have no page-load
  limit of their own.
- **`tools/local-wp.sh` keeps compiled PHP in `.local/opcache`** (2.3 s → 1.3 s a call). If WP-CLI ever acts on old
  code, delete that folder, or run with `OPCACHE_FILE_CACHE=` (empty) to turn it off.
- **Backslashes in a `wp()` argument are lost** on the way from Node through Git Bash to Windows PHP (`x \ y`
  arrives as `x  y`); keep test data free of them, or pass them another way.
- Mail: PHP sends to `localhost:10001`, which is Local's **Mailpit**, and no SMTP plugin is active, so
  `wp_mail` does not reach real people (checked 2026-11-12).
- **Lasso v369 is installed and inactive on this copy** (deactivated 2026-11-14 at the user's request, after the backup
  `app/affibloom-backup-2026-11-14-before-lasso-deactivate.sql`); its data is present (265 `lasso-urls` posts, its 29
  tables). The parity tasks switch it on and off themselves; the safety constants (Task 2) are in place.
- Active plugins (2026-11-12; Lasso inactive since 2026-11-14; EWWW is inactive) include Affibloom, Rank Math, GeneratePress/GP Premium, GenerateBlocks, LiteSpeed Cache,
  Mediavine Control Panel, EWWW/ShortPixel image optimisers and others (see `wp plugin list`).
  Lasso's files are at `wp-content/plugins/lasso` — **never edit them**.

## Safety state of this Local copy

Updated 2026-11-14 (facts recheck of that date). Task 2's safety constants are in place: `wp-config.php` lines 98-101
set `WP_HTTP_BLOCK_EXTERNAL` true, `DISABLE_WP_CRON` true, an empty `SENTRY_DSN` and an empty `LASSO_POSTHOG_API_KEY`
(confirmed at runtime). Lasso v369 is **inactive** (deactivated 2026-11-14); while it was active its licence status 0
(`lasso_license_status`) kept its cloud jobs off. The copy still
holds real user emails and Lasso's production data.
- Do not run Lasso jobs on purpose; prefer read-only WP-CLI.
- The Local database is dummy data (the user, 2026-11-15, deviation 129): writes need no backup. If a backup is wanted
  anyway: `bash tools/local-wp.sh db export "<absolute path outside the web root>"`.

**The WordPress MCP connector available to Claude Code is connected to production**
(`https://volleyballvault.com`, checked 2026-11-12), not to this Local copy. Use it for reads only, never
for writes, and never as a stand-in for checking the Local site.

## Tooling commands (run in the plugin folder)

```
composer install          # dev tools (see Known issues if it times out)
composer test:unit        # PHPUnit unit suite (Brain Monkey, no WordPress)
composer lint             # PHPCS, WordPress Coding Standards
composer lint:fix         # PHPCBF, fixes spacing
composer analyse          # PHPStan level 6
npm install               # @wordpress/env (and Playwright from Task 2)
npm run env:start         # wp-env (Docker) — see decision D1
```

## GitHub

- Remote `origin` = https://github.com/Rupashdas/Affibloom (private). `main` tracks `origin/main`.
- Commit identity is the user's own; no co-author trailers (see `CLAUDE.md`).
- Branch protection on `main` is set up in Task 3, Step 4.

## Known issues

- **`composer install` times out on repo.packagist.org** (curl error 28 after 10 s, seen 2026-11-12).
  Try in order, in PowerShell in the plugin folder:
  1. `$env:COMPOSER_IPRESOLVE = "4"; composer install` (forces IPv4; Composer ≥ 2.8 has this).
  2. `composer diagnose`; turn off VPN/proxy if it reports one.
  3. Project-only mirror: `composer config repos.packagist composer https://mirrors.tencent.com/composer/`
     then `composer install`. **Undo before committing:** `composer config --unset repos.packagist`
     (CI must keep using Packagist). A `composer.lock` made through a mirror records the mirror's download
     URLs, so do not commit it; leave it uncommitted and note that in `progress.md`.
  Resolved for now: `composer install` succeeded on 2026-11-12 and the committed `composer.lock`
  (`8732ff4`) has Packagist's normal URLs.
- **Docker / wp-env:** `docker` is not on the PATH on this machine (checked 2026-11-12); see decision D1.
- **Machine CLI versions (2026-11-12):** PHP 8.5.1, Composer 2.9.5, Node 22.23.2, npm 10.9.8. `wp` is not
  on the PATH; use Local's site shell.
