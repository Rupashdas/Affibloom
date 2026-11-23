# Going back to Lasso

Task 29E (F10 D17, AC32). For the people running the cutover (Tasks 40-41) on staging and production. Lasso's data is
never written by the migration (its fingerprint is checked on every run), so going back is two steps, **in this
order**: put the content back, then switch the plugins.

## When

Any time after the cutover, while Lasso and its data are still on the site (before Task LM10's Finish and removal).
After LM10, going back means restoring the database backup taken before the cutover.

## 1. Put the content back

The conversion changed posts, widgets, Elementor data and the Additional CSS; each place kept a copy of what it was.

- **From wp-admin** (staging has no SSH): Affibloom → Migrate from Lasso → **Content** → **Revert All**, and confirm.
  Each place is put back one per request; the list empties as it goes. A place someone edited after the conversion is
  kept and named in the status line: revert it with the command below and `--force`, or by hand.
- **With WP-CLI**, in this order (the reverse of the conversion):

  ```bash
  wp affibloom-lasso blocks --revert
  wp affibloom-lasso stamps --revert
  wp affibloom-lasso convert --revert
  wp affibloom-lasso css --revert
  ```

  Each ends with "N reverted, N edited since (kept), N with nothing to revert, N failed." A post edited since is kept:
  add `--force` to put it back anyway (its edit is lost).

## 2. Switch the plugins

Plugins → deactivate **Affibloom Lasso Migrator**, then **Affibloom**; activate **Lasso**. Purge the page cache
(LiteSpeed). Affibloom's own data (its products, settings, link index) stays in the database, unused, so switching back
to Affibloom later needs no new migration.

## 3. Check

- Open three guides signed out: Lasso's boxes, buttons and links are back.
- Where WP-CLI is available, Lasso's fingerprint equals the one taken before the migration:

  ```bash
  wp eval-file wp-content/plugins/affibloom-lasso-migrator/tools/lasso-fingerprint.php > lasso-after.json
  ```

  compared with `lasso-before.json` from before the cutover (Task 40's checklist takes it).

## The drill on Local

`bash tools/rollback-drill.sh` (from the Affibloom folder) takes Lasso's and the content's fingerprints
(`tools/content-fingerprint.php`), migrates and converts with Lasso off, goes back by the steps above (WP-CLI), and fails
when either fingerprint differs. Then the guides are compared with Lasso's pages:

```bash
PARITY_MODE=lasso npx playwright test tests/Visual/parity.spec.ts --config=.local/no-login.config.ts
```

Afterwards switch Affibloom and the migrator on again (Local only).
