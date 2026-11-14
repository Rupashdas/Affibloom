# F37 Tools and diagnostics: analysis

Status: written 2026-11-14 with `requirements.md`. Gate 4 for TL1-TL7: waiting.

**Tasks:** TL1 activation and deactivation, TL2 upgrade routine, TL3 Logs, TL4 DB Status and self-repair, TL5
Uninstall card (with Task 38), TL6 Permissions, TL7 footer version. PHP compatibility: built (Task 1).

## Depends on

- `src/Plugin.php` (composition root), `AdminMenu`, `Capabilities`, the Settings band partial, F21 (posts using
  Affibloom, for TL5), the first feature with a table (F11/F12/F21) for TL4's first real table.

## Data

| Where | What (Lasso) |
|---|---|
| Option `affibloom_db_version` | schema step reached (`lasso_version`, `lasso_rc_version`) |
| Option `affibloom_version` | plugin version last seen, for the rewrite flush (`lasso_version`) |
| Option `affibloom_activated_at` | activation time (`lasso_plugin_activated_at`) |
| Option `affibloom_upgrade_lock` | expiry timestamp, autoload no (`lasso_db_migration_lock_v2`) |
| Options `affibloom_repair_count`, `affibloom_repair_next` | self-repair limiter (`lasso_recreate_table_time`, `lasso_next_time_recreate_table`) |
| Setting `enable_logs` (false), `permission` (Administrator), `delete_data_on_uninstall` (false, Task 38) | (`enable_logs`, `lasso_permission`) |
| Files `uploads/affibloom-logs/<Y_m_d>_<type>.log` | (`plugins/lasso/logs/…`) |

## Entry points and request paths

```
Activation (register_activation_hook in affibloom.php → Plugin::activate())                 TL1
  → Lifecycle::activate(): Migrator::run(), update affibloom_version/activated_at, flush_rewrite_rules()
Deactivation → Lifecycle::deactivate(): CronRegistry::hooks() each wp_clear_scheduled_hook; flush_rewrite_rules()

admin_init (not AJAX, not cron) → Upgrader::maybe_run()                                     TL2
  → lock (add_option) → Migrator::run( from affibloom_db_version ) → finally release
init:20 → VersionWatch: AFFIBLOOM_VERSION ≠ affibloom_version → flush_rewrite_rules(), store
wp affibloom upgrade → Upgrader::run()

GET admin.php?page=affibloom-logs → LogsScreen (SETTINGS) → LogReader::newest( type ) × 7     TL3
POST admin-post.php action=affibloom_save_settings tab=logs settings[enable_logs]
Daily cron affibloom_daily → LogPruner::prune( 14 days )
Logger::write( type, message, force ) ← every feature

GET admin.php?page=affibloom-db-status → DbStatusScreen (SETTINGS) → Migrator::create_all() → [ table, output ]   TL4
$wpdb error on an Affibloom table → TableRepair::maybe_repair( error ) (limiter) → Migrator::create_all()

GET admin.php?page=affibloom-settings&tab=general → Uninstall card (TL5): switch delete_data_on_uninstall,
  list from F21 LinkIndex::posts_using_affibloom() with edit links; uninstall.php (Task 38) honours the switch

Permissions select (General) → SettingsService 'permission' (administrators only) → Capabilities::for_products()/
  for_settings() → AdminMenu, every controller and REST permission_callback                  TL6

admin_footer_text on Affibloom screens → "Version <n>"                                      TL7
```

## Layers

- Domain: `Settings\Permission` (role → capability, Lasso's five roles, corrected mapping), `Schema\Step` (pure value).
- Application: `Lifecycle`, `Upgrader` (lock, steps), `Logs\LogService` (types, newest), `Schema\TableRepair` (limiter).
- Infrastructure: `Schema\Migrator` + `Schema\Table` (dbDelta, collation to `wp_posts`), `Logs\FileLogger`,
  `Logs\FileLogReader`, `WordPress\CronRegistry` (hooks list), `WordPress\OptionLock`.
- Interface: `Admin\LogsScreen`, `Admin\DbStatusScreen` (hidden submenus), `Admin\Capabilities` becomes a service
  reading the setting (constants kept as the administrator fallback), `Cli\UpgradeCommand`, footer text hook.

## Risks

- Changing `Capabilities` from constants to a setting touches every controller; TL6 keeps the method names and
  tests each route with an Editor at Editor and Administrator permission.
- dbDelta on page view is idempotent but slow on large tables; only Affibloom's tables are touched.
- Log files can hold URLs and errors: kept out of the web root's reach by `.htaccess` and `index.php`; on Nginx the
  file names carry a random suffix stored in an option (TL3 decides by test).

## Security

- Every screen and save: SETTINGS capability (after TL6, the administrator-only rule for the permission itself).
- Upgrade lock prevents concurrent schema changes; `uninstall.php` checks `WP_UNINSTALL_PLUGIN`.
- Nothing calls an outside host (licence, updates, flags, telemetry are C).

## Tests

- Integration: activation creates tables and options; deactivation leaves no `affibloom_*` cron; upgrader lock and
  step order; self-repair limiter; uninstall with the switch off and on (Task 38).
- Unit: `PermissionTest` (five roles, unknown value fails closed), `LogServiceTest` (newest file per type, pattern
  must not catch `*_build_link_database` unintentionally — decided: exact type match), `TableRepairTest`.
- E2E `F37.spec.ts`: AC3 Logs, AC5 DB Status, AC6 Uninstall card, AC7 Permissions (Editor logs in), AC9 footer.
