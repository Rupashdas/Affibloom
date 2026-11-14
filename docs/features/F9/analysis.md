# F9 Settings → General: analysis

Status: rewritten 2026-11-14 with `requirements.md`. Gate 4 for Tasks 9B-9L: waiting. Task 9 (done) built four
switches; it stays as history.

**Tasks:** 9B (layout and Link Defaults), 9C (duplicate destinations off), 9D (Update Link Settings / Override All
Links), 9E (unsaved-changes guard), 9F (Google Analytics), 9G (Cloaked Link Prefix, Smart CSS Loading), 9H
(Performance), 9I (Custom Link Detection), 9J (Link Index card), 9K (Notifications), 9L (support view). Permissions and
the Uninstall card: F37 TL6, TL5.

## Verdicts, control by control

| Control | Verdict |
|---|---|
| License Key, Activate | C — never (`lasso.link/license/status`, `/server/getinfo`) |
| GA Tracking ID, Click Tracking, Pageview, IP Anonymization | control 9F; behaviour C3 (N6) |
| Advanced Click Tracking (`performance_event_tracking`) | C — never (`js.codedrink.com`, app.getlasso.co) |
| Cloaked Link Prefix | control 9G; behaviour F24 (N4) |
| Smart CSS Loading | control 9G; behaviour F5 (N5) |
| Keep Original URL (support) | control 9L; behaviour F18/F1 |
| Permissions | F37 TL6 (N3) |
| Uninstall Lasso | F37 TL5 replacement (N2) |
| Cron interval, Cronjob Manually (support) | control 9L; behaviour F11 |
| Segment Analytics (support) | C — never |
| Restrict Prefix (support) | control 9L; behaviour F24 |
| Processes Execute Limit (support) | control 9L; behaviour F11 |
| Font Awesome JS Render SVG (support) | control 9L; behaviour F28/F5 |
| Disable AAWP Description (support) | control 9L; behaviour C1 |
| Link Index stats, Start Link Indexing, Rebuild Database | card 9J (Rebuild in 9L); behaviour F21 |
| Custom Post Types, Custom Fields (ACF) | control 9I; behaviour F21 |
| Performance Mode, CPU threshold | control 9H; behaviour F11 |
| New Window / Tab, NoFollow / NoIndex, Sponsored, Show Disclosure | built (Task 9); restyled, Sponsored default fixed 9B; read by 11E, 15, 18 |
| Allow duplicate destinations | control 9B; on mode built (11B); off mode 9C |
| Link Cloaking | control 9B; behaviour F24 |
| Detect Opportunities | control 9B; behaviour F22 |
| Auto-Detect Affiliate Links | control 9B; behaviour F35/C2 |
| Override dialog | 9D (cloaking/opportunity keys added by F24/F22) |
| Three Notifications switches | control 9K (help tips hidden in 9K); notices F38 |
| Audit / Post Content History | F34 decides (cloud-flagged; absent on production) |
| Save and its feedback | built (Task 9/10F path), extended per task |
| Unsaved-changes guard | 9E |
| Settings header (General, Display, Amazon) | built (9A band) |

## Depends on

- ADR-007, ADR-008, deviation 32, Task 10F's `data-save-when-changed`, `partials/switch-row.php`, help tips in `admin.js`.
- F18 (11B lookup, `LinkResolver` for 9C), F21 (status and rebuild routes for 9J/9L), the product repository (9D).

## Data

| Where | What (Lasso key, default) |
|---|---|
| Option `affibloom_settings` | `new_tab` (open_new_tab), `nofollow` (enable_nofollow), `sponsored` (enable_sponsored, **true**), `show_disclosure`, `allow_duplicates` (check_duplicate_link, true), `link_cloaking` (true), `detect_opportunities` (true), `auto_detect_links` (auto_monetize_affiliates, true), `new_tab2`, `nofollow2` (defaults only, no control), `ga_tracking_id` (""), `ga_click_tracking`, `ga_pageview`, `ga_anonymize_ip` (false), `link_prefix` (rewrite_slug, ""), `smart_css` (false), `keep_original_domains` ([]), `scan_post_types` (cpt_support, [post, page]), `scan_custom_fields` ([]), `performance_mode` (balanced), `cpu_threshold` (65), `cron_interval` (0), `manual_process_limit` (2), `restrict_prefix`, `process_limit`, `fontawesome_svg`, `no_feature_description` (disable_aawp_description), `disable_amazon_notice`, `disable_help_tips`, `disable_import_notice` (all false) |
| Product meta (9D writes) | `_affibloom_new_tab`, `_affibloom_nofollow`, `_affibloom_sponsored`, `_affibloom_show_disclosure`; F24/F22 add theirs |
| Product meta (9C) | `_affibloom_url_plain_key` (final URL without query; Lasso's `url_without_arguments`) |

## Entry points and request paths

```
GET admin.php?page=affibloom-settings&tab=general[&support=1]                         (9B-9L)
  → AdminMenu (Capabilities::SETTINGS) → SettingsScreen::render()
       SettingsService::current(); GeneralChoices (post types, ACF fields, modes)
  ← templates/admin/settings.php (two columns of cards; support sections when support=1)

POST admin-post.php action=affibloom_save_settings tab=general settings[…] [redirect_to]
  → SettingsController::save() (capability, nonce) → SettingsService::update_tab('general', input)
  ← 302 redirect_to (validated admin URL, 9E) or the tab with affibloom_notice=saved|partly-saved[&refused=…]

POST /affibloom/v1/settings/link-defaults/apply { key, value }                        (9D)
  → SettingsRestController (SETTINGS) → LinkDefaultsService::apply_to_all → ProductRepository::set_flag_for_all
  ← 200 { updated }   422 affibloom_invalid_field

POST /affibloom/v1/products { url }  with allow_duplicates = false                     (9C)
  → ProductService::add() → DuplicateLookup (plain final key, then pasted key) → 200 { duplicate: true, redirect_to }

GET  /affibloom/v1/link-index/status    (F21; polled every 5 s by 9J)
POST /affibloom/v1/link-index/rebuild   (F21; 9L's Rebuild Database)
```

## Layers

- Domain: `Settings\Settings` (keys, defaults), `Settings\PerformanceMode` (presets), `Product\PlainUrlKey` (Lasso's exceptions).
- Application: `SettingsService` (new cleaners: slug, int ranges, choice, post types, domains),
  `Settings\LinkDefaultsService`, `Products\DuplicateLookup` off mode.
- Infrastructure: `PostTypeProductRepository::set_flag_for_all()`, `WordPress\WpGeneralChoices`.
- Interface: `SettingsScreen`, `SettingsController` (`redirect_to`), `Rest\SettingsRestController`, partials per card,
  `update-link-settings-modal.php`, `ga-pageview-modal.php`, `rebuild-index-modal.php`, `general-settings.js`,
  the guard in `admin.js` (`data-unsaved-guard`).

## Risks

- 9D changes every product at once: one meta update per key, tested on counts; DB backup before running on Local.
- 9J and 9L's rebuild need F21; until then the card shows its empty state.
- 9C needs a link's final URL (F18's resolver, a network request) only when the setting is off.

## Security

- SETTINGS capability and nonce; REST nonce via `wp.apiFetch`; `redirect_to` through `wp_validate_redirect`, admin URLs only.
- Values cleaned by type; post types and fields only from the offered lists; nothing sent to outside hosts.

## Tests

- Unit: SettingsServiceTest (types, ranges), PerformanceModeTest, PlainUrlKeyTest, LinkDefaultsServiceTest.
- Integration: set_flag_for_all across statuses.
- E2E F9.spec.ts: AC1-AC3 existing; AC4-AC15 one test each.
