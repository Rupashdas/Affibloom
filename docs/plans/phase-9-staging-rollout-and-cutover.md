# Affibloom — Phase 9: Staging rollout and cutover — Implementation Plan

> **How to run this plan:** inline, in one working session, task by task, in the order below. Do not hand tasks to subagents. When a step is finished, change its `- [ ]` to `- [x]`.
>
> **Explain as you go:** before each task, say in two or three sentences what the task builds, why it is needed now, and how it will be checked. After the task, say what changed and what the verification showed. No vibe coding: every line written must be one you can explain.
>
> **Shared rules:** the architecture, working method, decisions, global constraints, file structure and API contract are in [`00-overview.md`](00-overview.md). Read it first.

## Status

Not started. Rewritten 2026-11-14 in the full planning pass for the full Lasso scope. The 2026-11-12 text is in
[`archive/phase-9-superseded.md`](archive/phase-9-superseded.md). Planned; gate 4 given by the user 2026-11-14 for every open task.

## Scope

No new feature. This phase moves every data family and every reader-facing behaviour planned in Phases 2-8 from
Lasso to Affibloom on staging, then on production, with a proven way back. Production is read only until Task 41.

## Decisions that change this phase

- **No SSH on staging is known** (the user, 2026-11-13; memory "staging site"). Every step uses an **admin entry
  point**: the migration screen (F10 25D, 26O), Settings → General
  "Link Index" rebuild (F21 35G / F9 9J), the link check from the Dashboard (F11 32A's admin twin, 31A), the import
  screen (F33). WP-CLI (`wp affibloom …`) is used only where the host gives shell access; ask the user before Task 40
  whether production has it.
- **Decided (the user, 2026-11-14) — legacy blocks (A1):** Affibloom registers `affiliate-plugin/lasso` itself
  (Task ED6) and `[lasso]` keeps rendering (Task 27A); the 248 blocks are not converted and no post content is edited.
  Tasks 28A-28E are dropped (`archive/phase-5-superseded.md`).
- **Decided (the user, 2026-11-14) — `data-lasso-*` attributes (A2):** kept. Nothing strips them; Task 42's old step 4
  and F34 H3-H4 are dropped.
- **Decided (the user, 2026-11-14) — click figures (E1, revised): start from zero.** No click figures are imported from
  Lasso (Task 26J is dropped). The Dashboard's Clicks count and Google Analytics both start from zero at cutover; there
  is no dependency on Lasso's historical analytics.
- **Decided (the user, 2026-11-14) — GA event names (C3 N2 / C6, revised):** Affibloom's own names
  (`affibloom_primary_click`, `affibloom_secondary_click`, `affibloom_link_click`; parameters `affibloom_title`,
  `affibloom_href`, `affibloom_text`, `affibloom_badge`); no backward compatibility with Lasso's GA reports. The client
  is told that GA events start from zero and that their reports and conversions must use the new names.
- **Decided (the user accepted the recommendation, 2026-11-14) — F3:** no shell access is known on production or
  staging; the runbook stays on admin screens.
- **Affibloom works with no Lasso dependency and no Lasso branding (the user, 2026-11-14).** Lasso is removed
  completely and replaced; no Lasso licence date or Lasso "Brag" mark matters to this plan (decisions F4, B9).
- **Cloaked links are a cutover blocker** (F24, F10): 7 cloaked links to 4 addresses in 3 posts must keep answering;
  CL1 and CL10 must be done and checked (Task 40 step 6).
- **ThirstyAffiliates `/recommends/` links** (post 3297 and six drafts, F33 N6): imported on staging through F33 IM2
  with the client's approval; this replaces the old "content fix" step.
- **Production's Additional CSS** carries `.lasso-*` rules (33, F5 N1) and needs the two icon links and the SVG
  (deviation 71, F28 D4). ADR-6 stands (the user, 2026-11-14): the box keeps Affibloom's own class names and stylesheet, so the cutover
  translates those 33 `.lasso-*` rules to Affibloom's classes (Task 41 step 5; the list is prepared in Task 40 step 2)
  and adds the icon links.
- Lasso's tables and posts are archived for **90 days** after removal (roadmap 9.6). The old Task 42 said 30; the
  roadmap's figure is the approved one.

## Tasks in this phase

- [ ] Task 40: Staging rehearsal
- [ ] Task 41: Production cutover
- [ ] Task 42: Monitoring and Lasso removal

## Cross-phase notes

- Needs every task of Phases 2-8 done, Phase 8's release candidate (Task 39) and the user's sign-off of Phase 8.
- Uses: F10 25A-25D (dry run and report), 26A-26P (every data family; 26O the admin run), 29A-29E (parity), F31 27A-27X8
  (`[lasso]` and other plugins' shortcodes with Lasso off), F24 CL1-CL11, F21 35A-35J (index and render-time links),
  F11 30A-32E, F12 33A-34C, C3A-C3C, F33 IM1-IM20, F4 ED6.
- C1 (Amazon data) ships dormant unless the client has sent Creators API credentials; if they arrive before Task 41,
  C1A's card is filled and validated on staging first.

---

### Task 40: Staging rehearsal

**Feature:** all · **Depends on:** Phase 8 done and signed off; the release candidate (39); the user's answers in `open-decisions.md` · **Files:** `docs/cutover/staging-log.md` (new), refreshed `tests/fixtures/inventory/` and `tests/fixtures/baselines/`; commit: "Record the staging rehearsal".

**Roadmap:** 9.1-9.2 · **Why:** every step of the cutover is done once on a copy of production, timed and undone, so
cutover day holds no surprises and the way back is proven.

**Runbook.** Record each step's time and result in `docs/cutover/staging-log.md`.

- [ ] 1. **Prepare.** A fresh staging copy of production, made by the host (staging already holds the one-time import
  of 2026-11-13: delete those products first, or start from a fresh copy). Block outgoing mail. Check that Lasso on
  staging cannot reach production's Lasso account (licence moved away or `lasso.link` blocked).
- [ ] 2. **Record what staging looks like now.** Run the inventory (`tools/inventory.php`) and the baseline capture
  (Task 5) against staging, including the captures F5 18D, F30 DT1 and F25's tables need (approved by the user 2026-11-14, F1: one staging
  session; the test group, draft page and table clone are deleted after the capture). Copy the site's Additional CSS
  and list its `.lasso-*` rules (33 on the Local copy) with the Affibloom class each becomes: Task 41 step 5 uses the
  list. Commit the refreshed fixtures. Products added since the Local copy are now in them.
- [ ] 3. **Install and check nothing changes.** Upload the release zip in wp-admin (forward-slash paths, `--no-dev`
  autoloader; deviation 87) and activate it beside Lasso. Smoke test against staging: no visible change, no notice.
- [ ] 4. **Migrate.** On the migration screen: dry run (25A-25C) and read the report, including the left-behind list
  (the 9 records with no link, Lasso-cloud data). Then run every family (26O). Compare (26P): every box matches, or only
  the accepted differences in `docs/parity.md`. Import the other plugins' links (F33; ThirstyAffiliates for
  `/recommends/`, with the client's approval).
- [ ] 5. **Build the indexes.** Settings → General → Link Index → Rebuild (F21); the counts match the refreshed
  inventory (676 tied links in content). Run the link check (F11): real broken links are content work for the client.
- [ ] 6. **Switch Lasso off.** Deactivate Lasso, purge the cache. Parity suite against staging in shortcode mode
  (29A, 29B), every link (29C: cloaked addresses answer, Amazon links carry the right tag, no request to Lasso's
  hosts), clicks counted (33A), GA events (C3B) with a stub or the site's tag.
- [ ] 7. **Legacy blocks.** Check that the registered legacy block opens in the editor without a warning (ED6) and that
  `[lasso]` shortcodes render (27A). Nothing is converted.
- [ ] 8. **Editor acceptance.** The client's editors on staging: add an Amazon product and an Impact product, edit one,
  put a box in a draft guide with a badge, find a broken link, read a product's Locations, use Groups and a table.
  Record remarks; fix what blocks the release, build the next RC.
- [ ] 9. **Rehearse the way back.** Reactivate Lasso, purge. Parity
  against Lasso's baselines with Lasso on: as before migration (29E rollback drill). Then run steps 6-7 again, so
  staging ends in the post-cutover state.
- [ ] 10. **Sign-off.** The user and the client sign off in `docs/cutover/staging-log.md`; set the cutover date in a
  low-traffic window from the analytics.

---

### Task 41: Production cutover

**Feature:** all · **Depends on:** Task 40 signed off · **Files:** `docs/cutover/production-log.md` (new); commit: "Record the production cutover".

**Roadmap:** 9.3-9.4 · The first and only task that changes production. Two people: one runs the runbook, one checks
the site. Every step is the staging step that was rehearsed.

- [ ] 1. **The day before.** Freeze product edits in Lasso and tell editors. Check that today's production backup
  exists and a restore has been tested by the host.
- [ ] 2. **Install.** Upload and activate the signed-off release beside Lasso. Smoke-check three guides: no change.
- [ ] 3. **Migrate.** Dry run; the counts match today's inventory. Run every family; compare (26P) with fixtures
  refreshed from production. Import the other plugins' links.
- [ ] 4. **Indexes.** Rebuild the link index; run the link check.
- [ ] 5. **Switch.** Deactivate Lasso; purge LiteSpeed and any CDN; add the LiteSpeed JS-delay exclusion if Task 37D
  found it was needed; add the icon links to Additional CSS, and
  translate the site's Additional CSS `.lasso-*` rules to Affibloom's classes (list prepared in Task 40 step 2).
- [ ] 6. **Check.** Parity suite against production in remote mode (29A-29C); then the legacy-block step as on staging
  (the editor check, ED6). Five high-traffic guides by hand on a phone. The cloaked addresses answer.
- [ ] 7. **Clicks and events.** Click a box button on production: the click is recorded (33A) and the GA event
  `affibloom_primary_click` reaches the site's tag (C3B, checked in GA4 DebugView by the client if they have access); no
  event named "Lasso …" is sent.
- [ ] 8. **Tell editors** they can edit again, now in Affibloom. Send them the admin guide (Task 39).

**Rollback**, if any check in steps 6-7 fails and cannot be fixed within 30 minutes: activate Lasso, purge caches, check three guides. Affibloom stays installed and harmless beside Lasso; its products are
kept for the next attempt. Lasso's data was never changed (ADR-2), so reactivating it restores the old state.

---

### Task 42: Monitoring and Lasso removal

**Feature:** all · **Depends on:** Task 41 · **Files:** `docs/cutover/monitoring.md` (new); commit: "Record monitoring and Lasso's removal".

**Roadmap:** 9.5-9.6

- [ ] 1. **Daily for two weeks.** Record in `docs/cutover/monitoring.md`: clicks recorded against the Amazon Associates
  clicks report (the trend should match; Lasso's cloud figures were filtered differently, F12 N2); new broken links;
  the PHP error log searched for `Affibloom`; Search Console crawl errors (cloaked and `/recommends/` addresses); the
  page speed of three guides. Click counts and GA events start at cutover (decisions E1, C6): write the cutover date at the top
  of `monitoring.md`, and compare against the Amazon Associates report from that date, not against Lasso's figures.
- [ ] 2. **After two clean weeks and the client's sign-off:** delete the Lasso plugin (it is deactivated; deleting it
  removes its files; Lasso has no uninstall routine, so its data stays). Take a database backup labelled as holding
  Lasso's data.
- [ ] 3. **After 90 days, with the client's approval:** drop Lasso's tables (`SHOW TABLES LIKE 'wp_lasso%'`, run on
  staging first), delete the `lasso-urls` posts and their meta and the `lasso_*` options. Record each step. Affibloom's `LassoIdMap` (`_affibloom_lasso_id`) stays: `[lasso]` shortcodes still use it.
- [ ] 4. **Finish.** The client cancels the Lasso subscription. Tick Phase 9 in the roadmap and record the completion
  date.

## Exit check

Lasso removed from production; two weeks of monitoring with no regressions; every parity check (29A-29C, 29E) green on
production; the client's written sign-off.
