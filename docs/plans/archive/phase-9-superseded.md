# Phase 9 — superseded task text (history only)

Moved here 2026-11-14 by the full planning pass. Tasks 40-42 below were written on 2026-11-12 for the 12-feature
scope and a WP-CLI-over-SSH runbook. They are replaced by Tasks 40-42 in `../phase-9-staging-rollout-and-cutover.md`
(same numbers, rewritten for the full scope and for admin entry points). Kept word for word.

# Affibloom — Phase 9: Staging rollout and cutover — Implementation Plan

> **How to run this plan:** inline, in one working session, task by task, in the order below. Do not hand tasks to subagents. When a step is finished, change its `- [ ]` to `- [x]`.
>
> **Explain as you go:** before each task, say in two or three sentences what the task builds, why it is needed now, and how it will be checked. After the task, say what changed and what the verification showed. No vibe coding: every line written must be one you can explain.
>
> **Shared rules:** the architecture, working method, decisions, global constraints, file structure and API contract are in [`00-overview.md`](../00-overview.md). Read it first.

## Tasks in this phase

- [ ] Task 40: Staging rehearsal
- [ ] Task 41: Production cutover
- [ ] Task 42: Monitoring and Lasso removal

---


### Task 40: Staging rehearsal

**Roadmap:** 9.1–9.4

**Why:** every step of the cutover is done once on a copy of production, timed, and undone, so that cutover day contains no surprises and the way back is proven.

**Runbook.** Record each step's time and result in `docs/cutover/staging-log.md`.

- [ ] 1. **Prepare.** Make a fresh staging copy of production, made by the host. Block outgoing mail. Point the Lasso licence away from production, or block `lasso.link` on staging, so staging cannot affect the Lasso account.
- [ ] 2. **Record what staging looks like now.**
  - Run `tools/inventory.php` and Task 5's baseline capture against staging (`WP_BASE_URL=https://staging…`).
  - Products added since the local copy are now in the fixtures.
  - Commit the refreshed fixtures on a branch.
- [ ] 3. **Install and check nothing changes.**
  - Upload `affibloom.zip` and activate it beside Lasso.
  - Run the smoke test against staging. Expected: no change.
- [ ] 4. **Migrate.**
  - `wp affibloom migrate --dry-run --report=…`, and read the notes.
  - `wp affibloom migrate --settings`.
  - `wp eval-file tools/compare-migration.php`. Expected: every box matches, or only the accepted differences listed in `docs/parity.md`.
- [ ] 5. **Build the indexes.**
  - `wp affibloom usage rebuild`. The counts should match the refreshed inventory.
  - `wp affibloom check-links --all`. Read the broken list: real broken links are content work for the client, not plugin faults.
- [ ] 6. **Switch Lasso off.**
  - Deactivate Lasso and purge the cache.
  - Run the parity suite in shortcode mode against staging. Add `PARITY_REMOTE=1` to `parity.spec.ts` to skip the local reset and the migrate steps, and run `wp` through SSH instead of `wp-env`.
- [ ] 7. **Convert.**
  - `wp affibloom convert-legacy-blocks --dry-run`, then without `--dry-run`.
  - Run the parity suite again in converted mode.
- [ ] 8. **Editor acceptance.** The client's editors, on staging:
  - add an Amazon product and an Impact product;
  - edit one;
  - put a box in a draft guide, with a badge;
  - find a broken link;
  - read a product's Locations.

  Record their remarks. Fix anything that blocks the release, then build `rc.2`.
- [ ] 9. **Rehearse the way back.**
  - `wp affibloom convert-legacy-blocks --revert`, then reactivate Lasso, then purge.
  - Run the parity suite against the Lasso baselines with Lasso on. Expected: as before migration.
  - Run steps 6 and 7 again, so staging ends in the post-cutover state.
- [ ] 10. **Sign-off.** You and the client sign off in `docs/cutover/staging-log.md`. Set the cutover date and time in a low-traffic window from the analytics.

---

### Task 41: Production cutover

**Roadmap:** 9.5

The first and only task that changes production. Two people: one runs the runbook, one checks the site.

- [ ] 1. **The day before.** Freeze product edits in Lasso until the cutover is done, and tell editors. Check that the production backup is from today and that a restore has been tested by the host.
- [ ] 2. **Install.** Install and activate the signed-off release beside Lasso. Smoke-check three guides: no change.
- [ ] 3. **Migrate.**
  - `wp affibloom migrate --dry-run --report=prod-plan.csv`. The counts must match today's inventory.
  - `wp affibloom migrate --settings --report=prod-run.csv`.
  - Run `compare-migration.php`, with the fixtures refreshed from production in step 2 of Task 40, re-run today on a fresh local copy.
- [ ] 4. **Indexes.** `wp affibloom usage rebuild`, then `wp affibloom check-links --all`.
- [ ] 5. **Switch.** Deactivate Lasso, purge LiteSpeed and any CDN, and add the LiteSpeed JS exclusion if Task 37 found it was needed.
- [ ] 6. **Check.** Run the parity suite against production in remote mode, shortcode first. Then run `convert-legacy-blocks` and the parity suite in converted mode. Check by hand five high-traffic guides on a phone.
- [ ] 7. **Clicks.** Click a box button on production and see the row in the clicks table; a click from an editor's own browser is fine.
- [ ] 8. **Content fix.** Fix the `/recommends/adidas-dame-8-all-volleyball/` 404 link found in the analysis, with the client's approval, by pointing it at the product's current link.
- [ ] 9. **Tell editors** they can edit again, now in Affibloom. Send them the admin guide.

**Rollback**, if any check in steps 6–7 fails and cannot be fixed within 30 minutes:
1. `wp affibloom convert-legacy-blocks --revert`.
2. Activate Lasso.
3. Purge caches.
4. Check three guides.

Affibloom stays installed and harmless beside Lasso. Its products are kept for the next attempt.

---

### Task 42: Monitoring and Lasso removal

**Roadmap:** 9.6

- [ ] 1. **Daily for two weeks.** Record each day in `docs/cutover/monitoring.md`:
  - clicks recorded, compared with the Amazon Associates clicks report (the trend should match; absolute numbers differ, because Associates counts differently);
  - new broken links;
  - the PHP error log, searched for `Affibloom`;
  - Search Console crawl errors;
  - the page speed of three guides.
- [ ] 2. **After two clean weeks and the client's sign-off.**
  - Delete the Lasso plugin. It is deactivated already; deleting it removes its files.
  - Lasso's data stays: its tables and `lasso-urls` posts remain for 30 days as a fallback.
  - Take a database backup that is labelled as holding them.
- [ ] 3. **After 30 more days, with the client's approval.**
  - Drop Lasso's tables. The list comes from `SHOW TABLES LIKE 'wp_lasso%'`; run it on staging first.
  - Delete the `lasso-urls` posts and their meta, and the `lasso_settings` option.
  - Delete `_affibloom_pre_conversion` (`wp post meta delete --all … _affibloom_pre_conversion` per post, or the same loop the uninstall uses).
  - Record each step.
- [ ] 4. **Finish.** Tick Phase 9 in the roadmap and record the project's completion date.

---

# Dropped 2026-11-14 by the user's decision A2

Task 42 step 4 (the `data-lasso-*` stamps are kept, so nothing strips them). Word for word:

- [ ] 4. **Only if the user chose removal (F34 N3):** strip the `data-lasso-*` attributes with F34 H3 (dry run, stored
  copy, revert available), on staging first.
