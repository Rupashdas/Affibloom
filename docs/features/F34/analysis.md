# F34 Content changes, history and repair tools: analysis

Status: gate 2 written 2026-11-14 with `requirements.md`. Gate 4 for Tasks H1-H4: waiting (H3-H4 only with N3).

**Tasks:** H1 (the History list), H2 (a change's detail: diff and revert), H3 (only with N3: `wp affibloom
lasso-attributes strip`, dry run, copy, revert), H4 (only with N3: the clean-up card on History). They follow Task
28B (the first content change and the first caller of the writer) and 28E (the admin conversion), in Phase 5.

## Depends on

- ADR-003 (legacy blocks converted at cutover, reversible), ADR-007 (PHP-rendered screens, plain scripts), ADR-008
  (Aurora's look, Lasso's arrangement).
- F10: Task 28B (`LegacyBlockConverter`, `_affibloom_pre_conversion`, **and** `_affibloom_conversion` and
  `ContentChangeWriter`, asked in requirements section 5), 28E (the admin page and its lock), 26 (the migration's
  finish, which H3 checks), the migration lock option `affibloom_migration_lock`.
- F21: on-save indexing (35C) rebuilds a reverted post's rows; D4's occurrence identity (so H3 can drop the stamps).
- `Capabilities::SETTINGS`; the shared delete-dialog partial; the in-place list pattern of F23 D13.

## Data

| Where | What | Lasso key |
|---|---|---|
| Post meta `_affibloom_pre_conversion` (F10, 28B) | the post's content before the conversion, stored once | `lasso_post_content_history.old_value` (first change only) |
| Post meta `_affibloom_conversion` (new, written by 28B) | `{ at: UTC ISO, by: user ID, after_md5, boxes: N }` | `updated_date`; no `new_value` copy is kept (the live content is the "after" until edited) |
| Post meta `_affibloom_pre_cleanup`, `_affibloom_cleanup` (H3, only with N3) | the content before the clean-up; `{ at, by, after_md5, removed: { id, name, lid } }` | R18 keeps no copy |
| Option `affibloom_migration_lock` (F10 26O) | taken by H4's run as by 28E | — |
| Lasso's `lasso_post_content_history`, `lasso_revert`, `lasso_link_updates` | **read by nothing** in F34 (D3); dropped at 9.6 | — |
| Not created | no table, no `enable_history` option (D13), no purge cron (N1) | `enable_history`, the daily delete |

The detail needs "after" text for the diff: the current content when its md5 equals `after_md5`; otherwise the diff
shows stored copy → current content with the note "This post was edited after the change." (D10's state).

## Entry points and request paths

```
GET admin.php?page=affibloom-history[&s=…&orderby=post_id|post_title|post_type|changed&order=asc|desc&paged=N]   (H1)
  → AdminMenu (Capabilities::SETTINGS; submenu "History" after Settings) → HistoryScreen::render()
  → ContentHistoryService::page( HistoryQuery ) → ContentChangeRepository::page()
       WP_Query-free SQL over postmeta (meta_key IN _affibloom_conversion, _affibloom_cleanup) JOIN posts
       (posts that exist; trash marked), title LIKE with spaces as wildcards, ORDER BY the column, LIMIT 10
       page past the last → page 1 (R33)
  ← templates/admin/history.php (band "Post Content History", search, table or "Looks like we're coming up empty.",
    pager); partial history-row.php
GET /affibloom/v1/history?search=&orderby=&order=&page=   → { html, url }   (the same partial; history.js, D6)

GET admin.php?page=affibloom-history&post=N&kind=conversion|cleanup                                            (H2)
  → HistoryScreen::render_detail() → ContentHistoryService::get( post, kind ) → ContentChange | null (null → list)
  ← templates/admin/history-detail.php: "View Post Content History", Post Name + edit/view links, Post ID, Post Type,
    Modified Date, "Content changed" = wp_text_diff( stored copy, current, { title_left: "Before", title_right:
    "Now" } ), Revert (red), the confirm dialog

POST /affibloom/v1/history/{post}/revert { kind }                                                              (H2)
  → HistoryController (SETTINGS + edit_post(post) + unfiltered_html; REST nonce)
  → ContentHistoryService::revert( post, kind )
       record found? else 404 affibloom_no_change
       later change of the other kind? → 409 affibloom_later_change
       md5(current) == after_md5? else 409 affibloom_edited_since
       kind conversion → LegacyBlockConverter::revert( post )  (28B's; the CLI's and 28E's code)
       kind cleanup   → LassoAttributeCleaner::revert( post )  (H3)
         → ContentChangeWriter::restore( post, stored copy, expected current )  wp_update_post (revision)
         → delete the two meta
  ← 200 { post, message: "Content reverted for “<title>”." }

wp affibloom lasso-attributes strip [--dry-run] [--post=<id>] [--revert] [--force]                             (H3)
  → LassoAttributesCommand → LassoAttributeCleaner::plan() / run( post ) / revert( post )
       refuses before the migration finished (option affibloom_migration.finished), and while the lock is held
       WP_HTML_Tag_Processor over <a>: remove data-lasso-id, data-lasso-name, data-lasso-lid; count JSON-encoded ones
       → ContentChangeWriter::replace( post, new, expected old ) ; meta _affibloom_pre_cleanup (once), _affibloom_cleanup
  ← one line per post (removed counts or skip reason), a summary, exit 1 on any error

POST /affibloom/v1/history/cleanup { cursor, backup_confirmed }                                                (H4)
  → HistoryController → LassoAttributeCleaner::batch( cursor, 1 ) behind affibloom_migration_lock
  ← 200 { cursor | null, done, total, line }   409 affibloom_migration_running   422 backup_confirmed required
```

## Layers

- **Domain** `Content\ContentChange` (post ID, kind, at, by, after_md5, summary counts), `Content\ChangeKind` (enum
  conversion, cleanup), `Content\HistoryQuery` (search term with Lasso's wildcard rule, order, page),
  `Content\HistoryPage`, `Content\ContentChangeRepository` (interface), `Content\LassoAttributes` (pure: given HTML,
  the counts and the cleaned HTML through an injected tag walker — kept pure by passing the processor's results;
  unit-tested with fixtures). No WordPress calls.
- **Application** `Content\ContentHistoryService` (page, get, revert with the three refusals), `Content\NoChange`,
  `Content\EditedSince`, `Content\LaterChange`; `Content\LassoAttributeCleaner` (H3: plan, run, revert, batch);
  `Migration\LegacyBlockConverter::revert()` (F10, reused).
- **Infrastructure** `WordPress\ContentChangeWriter` (D2: the only `post_content` writer; created by 28B),
  `WordPress\PostMetaContentChangeRepository` (the two meta pairs; the list SQL).
- **Interface** `Admin\HistoryScreen` (list and detail), the submenu in `AdminMenu`, `Rest\HistoryController`,
  `Cli\LassoAttributesCommand` (H3), templates `history.php`, `history-detail.php`, partials `history-row.php`,
  `history-cleanup-card.php` (H4), `assets/admin/history.js` (list in place, revert dialog, the clean-up run),
  `Messages` codes `affibloom_no_change`, `affibloom_edited_since`, `affibloom_later_change`.

## Risks

- **The diff of a 340 KB post** (this site's largest guides): `wp_text_diff()` is line-based; Gutenberg content has
  long lines, so a single changed attribute shows its whole line. Acceptable (Lasso's diff is character-level);
  checked by hand on post 9119-sized content for speed (< 1 s).
- **28B must write `_affibloom_conversion`.** If F10's task lands without it, H1 has nothing to list: H1's Step 1
  test fails on that dependency, and the request is in requirements section 5.
- **Revert through `wp_update_post` runs kses** for users without `unfiltered_html`: the route requires it (edge case
  4), so a byte-for-byte restore is guaranteed.
- **H3 against the index:** stripping stamps must not orphan F21 rows keyed by `lasso_location_id`; H3 runs only after
  the migration (dismissals mapped) and F21 D4 uses `occurrence` afterwards.

## Security

- Screens: `Capabilities::SETTINGS`; revert: SETTINGS, `edit_post` on the post (Lasso's R40 check) and
  `unfiltered_html`; REST nonce through `wp.apiFetch`; IDs `absint`, `kind` from the enum only.
- Titles and the diff are escaped (`wp_text_diff` escapes its input); search `sanitize_text_field`, `esc_like`.
- No Lasso cloud: the cloud flag (R31) is not read; nothing calls `lasso.link`.
- `post_content` is written only by `ContentChangeWriter` (AC8's static test over `src/`).

## Tests

- **Unit** `ContentHistoryServiceTest` (the three refusals, the order of kinds), `HistoryQueryTest` (wildcards,
  order allow-list, page fallback), `LassoAttributesTest` (exactly three attribute names removed, other bytes equal,
  JSON-encoded stamps counted, not touched), `NoOtherContentWriterTest` (AC8: scans `src/` for `post_content` writes
  outside `ContentChangeWriter`).
- **Integration** (WordPress test suite) `ContentChangeWriterTest` (revision made, refusal when the row changed),
  `HistoryRevertTest` (converted fixture post → revert → byte-equal content, meta gone).
- **E2E acceptance** `tests/E2E/acceptance/F34.spec.ts` (`@local`): AC1, AC5, AC8 (H1); AC2-AC4 (H2); AC6 (H3,
  WP-CLI through `tools/local-wp.sh`); AC7 (H4). Fixtures: two posts converted by 28B's CLI on a copy of a guide.
