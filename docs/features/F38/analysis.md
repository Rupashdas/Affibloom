# F38 Admin shell: analysis

Status: gate 2 written 2026-11-14 with `docs/features/F38/requirements.md`. Gate 4 for Tasks ON1-ON10: waiting.

**Tasks:** ON1 (navigation, menu, titles, footer), ON2 (notice region, foreign notices, update notice), ON3
(Automations), ON4 (link index notices), ON5 (education boxes), ON6 (wizard: trigger, Theme, Customize), ON7 (wizard:
Amazon, Analytics, Imports, Done), ON8 (Dashboard tour), ON9 (command palette), ON10 (Settings leave guard).

## Depends on

- ADR-7 (PHP screens, plain scripts, `wp.apiFetch`; no Intro.js, Bootstrap or jQuery), ADR-8 (Aurora look, Lasso's
  arrangement), `Capabilities::{PRODUCTS,SETTINGS}`.
- Built: `AdminMenu`, `AdminAssets`, `Notice`, `header.php`, `toast*.php`, `unsaved-modal.php`, `admin.js` (modal,
  menu, toast, trapTab), `BackgroundJob` / `WpCronBackgroundJob` / `BulkAddService` (F18).
- F9 (Disable Help Tooltips setting, 9K; the General save route), F16 (themes, display settings, preview 10D), C1 / F2
  (Amazon fields), C3 (GA fields), F33 (import list), F21 (index job), F19 (search box, filters panel), F23 D10
  (`GET /products?search=`), F22 / F25 / F23 (screens whose tabs join the header).

## Data

| Where | What | Lasso's key |
|---|---|---|
| option `affibloom_tour_done` ('1', not autoloaded) | tour finished or skipped, site-wide | `lasso_dashboard_tour_done` (Task 26 copies) |
| option `affibloom_setup` (`{ state: pending|done, at }`) | wizard opened once / finished | none (licence gate, N1) |
| `affibloom_settings['education_hidden']` (list of allowed box keys) | closed education boxes | settings keys `link-, content-, domain-opportunities` = 'hide' (Task 26 copies; keyword dropped) |
| `affibloom_settings['disable_tooltips']` (F9) | read by ON5 | `general_disable_tooltip` |
| localStorage `affibloom_jobs`, `affibloom_jobs_at` | Automations cross-tab reuse (15 s) | `lasso_bg_process_data`, `lasso_bg_process_time` |
| transient none | update notice reads `get_plugin_updates()` | same |
| not kept | review card options, Intercom JWT, Segment/PostHog/Sentry ids | `lasso_review_card_*`, `intercom_user_jwt` (N4, N5) |

## Entry points and request paths

```
Any Affibloom admin page (ON1, ON2)
  → AdminMenu::add_pages() (position 20; Lasso's order from AdminNavigation::items(); hidden pages removed on admin_head;
    parent_file/submenu_file filters for the highlight; admin_title for "Link Details")
  → <Screen>::render() → templates/admin/<screen>.php
       partials/header.php ← AdminNavigation::tabs( current ) (Dashboard, Opportunities, Tables, Groups, Settings —
                              only items registered by a built screen; Menu button < 992 px)
       partials/notices.php ← NoticeBoard::for_page( screen ) ← NoticeProvider[] (UpdateAvailableNotice first)
       partials/footer.php  ("Version x.y.z")
  ForeignNotices (Module): admin_head 99999 + in_admin_header on affibloom pages → remove_all_actions

GET /affibloom/v1/jobs   (ON3)                                    Capabilities::PRODUCTS; 403 affibloom_forbidden
  → JobsController → JobBoard::running() ← JobStatus providers (BulkAddJobStatus first: name "Bulk Add Links",
    completed, total)  ← { data: { running_total, items: [ { key, name, completed, total, eta? } ] } }
  assets/admin/shell.js: poll at load, every 15 s for 5 min, localStorage reuse, popover toggle

NoticeBoard providers (ON4): LinkIndexBuildingNotice, RescanAfterImportNotice ← F21 IndexJob status

POST /affibloom/v1/education/{key}/hide   (ON5)                   PRODUCTS; 404 affibloom_unknown_box
  → EducationController → EducationBoxes::hide( key ) (allow-list) → SettingsRepository
GET  <opportunities page>&reseteducation=1 → EducationBoxes::reset() (PRODUCTS)
  partials/education-box.php ← EducationBoxes::visible( key ) (false when disable_tooltips or hidden)

GET admin.php?page=affibloom-setup   (ON6, ON7)                   Capabilities::SETTINGS
  → SetupScreen::render() → templates/admin/setup.php (step bar, steps; Imports only when the import list has sources)
  activation: register_activation_hook sets affibloom_setup=pending when no affibloom_settings and no Lasso data;
              admin_init (ActivationRedirect) → redirect once, then state=opened
  saves: the existing Settings routes (POST /settings/display — F16; /settings/amazon — C1/F2; /settings/general GA —
         C3/F9) with only the step's fields; Done → POST /setup/done → affibloom_setup=done → Dashboard

Dashboard (ON8): DashboardScreen enqueues tour.js when ! TourState::done(); steps localized
POST /affibloom/v1/tour/done   PRODUCTS → TourState::finish()  ← 200 { data: { done: true } }

Every Affibloom page + post editor + other post types' editors (ON9): AdminAssets enqueues palette.js (PRODUCTS)
  → GET /affibloom/v1/products?search=<q>&per_page=10&orderby=modified (F23 D10; name and link)
    ← { items: [ { id, name, url, image_url, edit_url } ] }

Settings tabs (ON10): templates/admin/settings*.php include unsaved-modal.php; settings-guard in admin.js compares the
  form with its loaded values (the product page's 15D logic, extracted on this second use)
```

## Layers

- **Interface:** `AdminNavigation` (Lasso's tab and menu order; each screen registers its `NavItem`), `AdminMenu`
  (changed), `ForeignNotices` (Module), `NoticeBoard` + `partials/notices.php`, `JobsController`,
  `EducationController`, `SetupScreen`, `ActivationRedirect`, `TourController`, `partials/{footer,notices,
  education-box,setup-steps}.php`, `assets/admin/{shell,setup,tour,palette}.js`.
- **Application:** `Shell\NoticeProvider` (interface: `applies( Screen ): bool`, `notice(): ShellNotice`),
  `Shell\UpdateAvailableNotice`, `Shell\JobStatus` (interface), `Shell\JobBoard`, `Shell\EducationBoxes`,
  `Shell\TourState`, `Shell\SetupState`; `Products\BulkAddJobStatus` (first JobStatus).
- **Domain:** `Shell\ShellNotice` (tone, message, action label/url, dismissible), `Shell\JobProgress` (name, done,
  total, eta), `Shell\EducationKey` (allow-list).
- **Infrastructure:** `WordPress\OptionTourState`, `WordPress\OptionSetupState`, `WordPress\PluginUpdates`
  (`get_plugin_updates`, `wp_nonce_url`).

## Risks

- Removing foreign notices hides WordPress core's update nags on Affibloom pages too (Lasso's behaviour); they still
  show on every other admin page.
- The wizard depends on four features' save routes; ON6/ON7 must wait for them (F16 10D, C1, C3, F33).
- A tour on a narrow screen can point at a folded tab: steps whose target is not visible are skipped.
- The shortcut can clash with a browser or extension using Ctrl+Shift+L (Lasso's own choice, kept).

## Security

- Every route: `permission_callback` with `Capabilities::PRODUCTS` (setup: `SETTINGS`); REST nonce via `wp.apiFetch`.
- Education keys from an allow-list (Lasso writes any key, R33). Notice texts escaped; action URLs `esc_url`.
- The update link is WordPress's nonce'd `update.php` URL, shown only to users who can `update_plugins`.
- The palette shows only what the products route returns to that user.

## Tests

- **Unit:** `AdminNavigationTest` (order, current group per page), `NoticeBoardTest` (provider failure skipped),
  `UpdateAvailableNoticeTest`, `JobBoardTest` (running total, names), `EducationBoxesTest` (allow-list, tooltips off,
  reset), `TourStateTest`, `SetupStateTest` (opens once; not with existing settings).
- **Integration (WP suite):** foreign notices removed only on Affibloom pages; activation sets pending only on an
  empty site.
- **E2E `tests/E2E/acceptance/F38.spec.ts`:** AC1-AC3 (ON1), AC4-AC6 (ON2), AC7 (ON3), AC8 (ON4), AC9 (ON5), AC10
  (ON6), AC11 (ON7), AC12 (ON8), AC13 (ON9), AC14 (ON10).
