# Security review (Task 37A, roadmap 8.1)

Reviewed 2026-11-22, on the Local copy, Phases 2-7 as built (Phase 7 signed off the same day). The inventory every
verdict below refers to is [`entry-points.md`](entry-points.md). Tools: `tests/E2E/security.spec.ts` (6 tests, green),
Plugin Check 2.2.0 (installed for the run, removed after), and a by-hand read of the code, item by item.

## Findings

| # | Finding | Severity | Verdict |
|---|---|---|---|
| S1 | Products (`affibloom_product`) and tables (`affibloom_table`) used the plain `post` capabilities, so an **Editor** passed `edit_post` and `delete_post` on them (probed: `user_can( editor, 'edit_post', product )` was true) and could rename, unpublish or trash them through WordPress's own paths that never ask Affibloom: Quick Edit's `admin-ajax.php?action=inline-save` (its nonce is on every Posts screen) and XML-RPC's `wp.editPost` / `wp.deletePost`. | High | **Fixed.** `PostTypeCapabilities::only()` maps every primitive capability of both types to `Capabilities::PRODUCTS`, passed in by `Plugin` (TL6 will pass the Permissions setting). Tests: `PostTypeCapabilitiesTest` (unit), and `security.spec.ts` "an Editor cannot change a product or a table through WordPress's own Quick Edit" (core answers "Sorry, you are not allowed to edit this post." and the title stays). |
| S2 | The Elementor widget printed its shortcode field as typed (`do_shortcode()` leaves the text around a shortcode as it is), so an Author without `unfiltered_html` could put a `<script>` on a page through it, which WordPress keeps out of their post content. | Medium (Elementor is not active on the site) | **Fixed.** The field goes through `wp_kses_post()` before its shortcodes run. Test: `ElementorDisplayWidgetTest::test_the_field_is_kept_to_post_html_before_its_shortcodes_run`. |
| S3 | `WpPageFetcher` (the page behind a link an editor adds, and the Amazon short links the import finds in post content, which any Author writes) used `wp_remote_get()`, which also reaches private and local addresses (a server-side request forgery). The link checker already used `wp_safe_remote_get()`. | Medium | **Fixed.** Every hop goes through `wp_safe_remote_get()`. Test: `WpPageFetcherTest`. Local's test stub (`tests/E2E/link-stub.php`) already lets the site's own host through for the F11 tests. |
| S4 | `LinkIndexCron::catch_up()` runs on `admin_init`, which WordPress also fires for a **visitor's** `admin-ajax.php` and `admin-post.php`. With WP-Cron off (production's setting is `DISABLE_WP_CRON`, Task 35F), any anonymous request ran up to three due link-index batches of 100 posts. | Medium (load only, no data) | **Fixed.** It returns first unless the user has `Capabilities::PRODUCTS` (it is the administrator's page load moving their rebuild on, 35F). Test: `LinkIndexCronTest`. |
| S5 | The inventory differs from the plan's list (compiled from the analyses): no `admin_post_*` actions exist (saves are REST, deviation 143); `/import/*`, `/history/*`, `/migration/*`, `affibloom_import_batch` and `[lasso]` do not exist (deviation 146); `/dashboard/tour`, `/tour/done`, `/setup/done`, `/education/{key}/hide` come with ON5-ON8; `/ai-text` was never a route (F30's texts are stored and drawn, no service is asked). New in the code and not in the list: `/opportunities/*` detail routes, `/tables/*` (T1-T17), `/locations/{id}/preview`, `/products/{id}/alerts/dismiss`, `/amazon/search`, `/editor/schema*`, `/editor/shortcode`, `/notices/sitestripe/dismiss`. | Info | Recorded; every route of the code is in `entry-points.md` and walked by the spec. |
| S6 | `PUT`/`PATCH /products/{id}/fields` is registered with `EDITABLE`, which includes `POST`; `POST` is taken by the route's first handler (attach a field), so the second handler's `POST` is never reached. | Info | Stands: not a security issue, and the product page sends `PUT`. |
| S7 | The render-time content filters (`LinkAttributesFilter`, `DisplayRenderFilter`) also run where `the_content` runs outside the page: feeds, the REST `content.rendered`, excerpts. | Info | Stands, by design: they only rewrite links and SiteStripe tags already in the post, as Lasso's filters do everywhere; they write nothing (checked: no insert, update, transient or schedule call), and the rewritten markup goes through `WP_HTML_Tag_Processor`. |
| S8 | `POST /clicks` is public and cannot tell a reader from a script, so counts can be inflated by anyone who sends clicks; the location ID is taken as sent. | Info | Stands (Lasso the same): the click must name a published product (or a repointed one), a public post and an allowed button; the body is at most 1 KB of JSON; one count per reader, product, post and button per 30 s; bots are filtered by user agent; nothing about the reader is kept (the guard's key is an HMAC with the site's salt); the answer is always an empty 204. |

Nothing is left for the user: S1-S4 are fixed, S5-S8 stand for the reasons given.

## Plugin Check 2.2.0

Run on the working folder, so it also read `.local/`, `tests/`, `tools/` and the config files, which never ship
(4,680 of its 4,684 files; Task 39's `.distignore` and `files` list keep them out of the zip). On the files that ship
(`affibloom.php`, `src/`, `templates/`, `assets/`, `build/`, `resources/`, `data/`) it found four:

| Check | File | Verdict |
|---|---|---|
| `no_plugin_readme` | `readme.txt` | Task 39 writes it. |
| `plugin_header_nonexistent_domain_path` | `affibloom.php` (`Domain Path: /languages`) | Task 39 creates `languages/affibloom.pot`. |
| `missing_direct_file_access_protection` | `resources/networks.php` | False positive: its guard is `defined( 'ABSPATH' ) \|\| defined( 'AFFIBLOOM_VERSION' ) \|\| exit;` (the unit tests load the file without WordPress), a form the check does not recognise. |
| `library_core_files` | `assets/front/copy-clipboard.js` | False positive: Affibloom's own 1.3 KB script (F5 R36, Task 21C), matched by its name against core's `clipboard.js`. |

~~No escaping, SQL, nonce or capability finding in the shipped code.~~ **Corrected 2026-11-22 (Task 39):** that line was
wrong. The run did report code-sniff findings in shipped files too; they are the same as the release's below and are
triaged there.

### Re-run on the release, 1.0.0-rc.1 (Task 39, 2026-11-22)

Plugin Check 2.2.0 on the installed zip on the empty test site (only what ships). `no_plugin_readme`,
`plugin_header_nonexistent_domain_path` and `missing_composer_json_file` are fixed (`readme.txt`, `languages/`, and
`composer.json` now ships beside `vendor/`). What remains, with the verdict:

| Finding | Count | Where | Verdict |
|---|---|---|---|
| `NonPrefixedVariableFound` (warning) | 672 | `templates/` | False positive: templates are included inside `Template::render()`, so their variables are local to that method, not globals. |
| `ExceptionNotEscaped` (error) | 34 | domain and application exceptions (`AffiliateUrl`, `Palette`, `AmazonDataService`, …) | False positive: an exception's message is never echoed raw; the REST controllers return it as JSON in a `WP_Error`, and screens escape it. |
| `UnescapedDBParameter` (warning) | 26 | the custom-table repositories (`TableOpportunityRepository`, `TableLinkIssueRepository`, `TableLinkLocationRepository`, `TermGroupRepository`, `IndexPostsUsingAffibloom`, `PostMetaDueAmazonData`, `PostMetaPendingAmazonSync`) | Checked line by line: each interpolated fragment (`$where`, `$from`, `$group`, `$uses`, `covered()`, `per_product_sql()`) is fixed SQL built in code with `%s`/`%d`/`%i` placeholders, its values passed to `prepare()`; sorting comes from a whitelist (`SORT`, `match`). Each carries a `phpcs:disable` with that reason. |
| `SuppressFilters_suppress_filters` (error) | 8 | `PostTypeProductRepository`, the slug guards | Deliberate: Affibloom's own post types read without other plugins' query filters. |
| `PostNotIn_exclude` (warning) | 1 | `FieldsController` | Deliberate: excludes the products already shown, a short list. |
| `load_plugin_textdomainFound` (warning) | 1 | `Plugin.php` | Needed: Affibloom is not on WordPress.org, so WordPress does not load its translations by itself. |
| `missing_direct_file_access_protection`, `library_core_files` | 1 each | as above | False positives, as above. |

No nonce or capability finding. Plugin Check was installed for the run and deleted after.

## By hand, per item of the plan's step 4

1. **Every write has a capability and a nonce.** Every REST route but `POST /clicks` has a `permission_callback`
   with a capability (table in `entry-points.md`); WordPress's cookie authentication refuses a wrong nonce with
   `rest_cookie_invalid_nonce` (403) before any route runs, and treats a missing one as no sign-in (401). Proven for
   all 99 route and method pairs by the spec (visitor 401, Author 403 except the six `edit_posts` routes, wrong nonce
   403); the 10 admin pages refuse an Author (403). There are no `admin-post` or `admin-ajax` handlers of Affibloom's
   own. The routes that act on a post also check `edit_post` on it (`/editor/schema` with `post`,
   `/opportunities/locations/{row}/*`, `/products/{id}/alerts/dismiss`). The wizard, tour and education routes come
   with ON5-ON8 and get the same walk (the spec reads the route table at run time).
2. **Public routes.** `POST /clicks`: S8. The cloaked link: the slug is looked up among published (and retired,
   repointed) products only, under the current and old prefixes, never over a post or a term's page
   (`WpPostSlugGuard`); the destination is the product's stored link (`AffiliateUrl`: absolute `http`/`https` only),
   and `RedirectQuery` only adds query parameters, so the host is never the request's (no open redirect); with no link
   it goes home. The shortcode and the block: `DisplayRequest` reads the attributes; only published products and
   tables are drawn (`PostTypeProductRepository::get`, `PostTypeTableRepository::get` check `publish`); every value is
   escaped by the box templates. The content filters: S7.
3. **Input.** `ProductInput` (URLs through `AffiliateUrl`, prices and badges through `wp_kses` with Lasso's allowed
   tags, descriptions through `wp_kses_post`), `SettingsService` (typed per key), group, field and table inputs, the
   REST `args` (types, enums, minimums), `ClickInput` (strict JSON, 1 KB). No file upload exists anywhere
   (`$_FILES`, `move_uploaded_file`: none).
4. **Output.** No `echo $…` without an escape in `src/` or `templates/`; 43 `phpcs:ignore` for escaping, each with its
   reason, read one by one: values escaped a line above or by their template, fixed attributes, the plugin's own CSS,
   `CustomCss::clean()` (no `<`, so no `</style>`), and the Elementor shortcode (S2).
5. **SQL.** Every query is `$wpdb->prepare()`d with placeholders (`%i` for tables); `ORDER BY` comes from allow-lists
   (`TableOpportunityRepository::SORT`, `content_order()`); ID lists are placeholders built from counts; every `LIKE`
   value goes through `$wpdb->esc_like()`. The ignores name their reason.
6. **Stored HTML.** Descriptions, AI texts, variant names, table cells and button texts are `wp_kses_post` /
   `wp_kses` on save and on output; custom CSS is set only with `SETTINGS` and cleaned; block attributes and per-box
   texts are escaped by the box templates.
7. **Outbound requests.** Only Amazon's Creators API (`CreatorsApiCatalog`, fixed hosts), the product and merchant
   links the link check and the fetcher read (both `wp_safe_remote_get` since S3), and the site's own `wp-cron.php`
   (`WpCronHealth`, the WP-Cron fallback of Phase 6). Nothing to Lasso's cloud: `grep getlasso|lasso.link|lasso.to|lasso.io` over `src`,
   `templates` and `assets` finds nothing.
8. **Secrets.** The Creators API credentials are their own option, `affibloom_amazon_credentials`, stored with
   autoload off (`update_option( …, false )`); the Settings screen gets only `has_secret` (the field is always empty,
   `autocomplete="new-password"` with password managers' ignore hints; a stored value shows as a fixed 12-dot mask
   placeholder, not its length; 2026-11-22); no REST response, log line or export carries them. There is no AI key (F30 asks no
   service). Logs come with TL3 and Task 38's export with its own check.
9. **Cron jobs.** No hook is started by a visitor (S4 closed the one path); `POST /jobs/run-due` needs `PRODUCTS`;
   batches hold locks (`UpgradeLock`, `JobSlots`, the CPU guard); the `init` handlers only add a missing event.
10. **Files.** The CSV exports are built in `php://temp` and streamed (`CsvResponse`), never stored. The log folder is
    TL3's.
11. **Without the capability, 403.** The spec's Author walk is the proof; the per-post cases are listed in item 1.
    With TL6, the spec runs again with the role set to Author (TL6's step 1).

## Runs

- `tests/E2E/security.spec.ts`: 6 passed (2026-11-22). The first run, before the walk was trimmed, sent ~800
  requests in a row and Local's PHP workers stopped answering (nginx "no live upstreams"); the user restarted the site.
  The walk now sends ~300 and gives each request 30 s (Local's slow worker, `environment.md`).
- Unit: `PostTypeCapabilitiesTest`, `ElementorDisplayWidgetTest`, `WpPageFetcherTest`, `LinkIndexCronTest`.
