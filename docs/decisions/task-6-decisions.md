# Task 6 decisions

Date: 2026-11-12. Status: **Decided.** The user delegated these decisions with an order of evidence: (1) the
project's records (feature plan, inventory, analysis, plan), (2) research into Lasso and how the live site uses it,
(3) for UI/UX, the documented requirements and Lasso's behaviour, with sensible improvements where they conflict
with neither. Reasons and sources are given for each. The user can overrule any of them; the tasks named carry the
change.

## Questions from `docs/spec/interface.md`, section 7

### 1. Who may manage products: Administrators only

- **Evidence.** Lasso's `Permissions` on the live site is `Administrator` (`settings-general-3.jpg`); Lasso maps it to
  an administrator-only capability (`Lasso_Setting::get_lasso_permission_level()`). Lasso's block search
  (`lasso_report_urls`) needs only `edit_posts`. The live site has 4 administrators and no editors, authors or
  contributors (read-only user count, 2026-11-12).
- **Decision.** `Capabilities::PRODUCTS = 'manage_options'`, `SETTINGS = 'manage_options'`,
  `INSERT_BOXES = 'edit_posts'`. This follows the plan's own rule ("if it allows a role other than Editors, change
  `Capabilities::PRODUCTS` to match") and changes nothing for anyone who uses the site today.
- **Changes:** Task 8 (`Capabilities.php`); every task that cites `PRODUCTS` keeps the constant.

### 2. Duplicate links: no setting; the same link is always refused

- **Evidence.** The plan's `allow_duplicates` turns the duplicate check off, so the identical link could be added
  twice. Lasso never allows that. Its `Allow duplicate destinations` (stored as `check_duplicate_link`, on live)
  only permits *different* links that lead to the same product ("links from different networks to the same
  product"; `class-lasso-affiliate-link.php` lines 765–770). Affibloom keeps other links as pasted and does not
  follow them to their destination, so different links are always separate products.
- **Decision.** Remove the `allow_duplicates` setting and its toggle. A link with the same key (`amazon:<ASIN>`, or the
  same link) is always refused with "This Product Already Exists"; different links are always allowed. That is
  Lasso's behaviour with the live setting. `check_duplicate_link` is not imported.
- **Changes:** Tasks 9 (no toggle; `Settings::DEFAULTS` without the key), 11 and 15 (no bypass), 26 (no mapping row).

### 3. Sponsored per product: yes

- **Evidence.** Feature plan F9: "an optional 'sponsored' tag, set site-wide or per product". Lasso's product page
  has a `Sponsored` toggle; the plan already stores `_affibloom_sponsored`.
- **Decision.** Add `Sponsored` (`#affibloom-sponsored`) to the Details form and to `ProductInput`'s booleans.
- **Changes:** Tasks 14, 15.

### 4. Delete on the product page, as in Lasso

- **Evidence.** Lasso's Dashboard rows offer `Edit` (quick edit) and `Shortcode`; `Delete This Link` is on the
  product page, with the "Delete This Link?" dialog (Task 6 audit).
- **Decision.** Follow Lasso: delete from the product page. Dashboard row action: `Edit` only (`Shortcode` is left
  out: Affibloom has no shortcode of its own). A product in use is still refused (F12 AC3), which is stricter than
  Lasso's "Hold Up" warning, as the roadmap decided.
- **Changes:** Tasks 13, 16, 17.

### 5. Icon markup in button and badge text: kept

- **Evidence.** Live button texts and badges carry `<span class="amazon-icon">`, `<span class="medal-icon">` and
  similar; the icons come from the site's Additional CSS, and readers see them (`variants/second-button-desktop.png`).
  The plan's `sanitize_text_field` would strip them.
- **Decision.** `button_text`, `button_text2`, `badge` and the two Display button texts accept `<span>` with a
  `class` attribute only (`wp_kses` allowlist); the box prints them through the same allowlist.
- **Changes:** Tasks 10, 15, 18, 20, 26.

## Other open points

### 6. Drafts count as locations, as in Lasso

- **Evidence.** Lasso's Locations tab lists drafts (the live draft "sweet new post", `?p=9492`, has 3 locations). The
  plan's usage index (Task 35) covered published posts only, so a product used only in a draft could be deleted and
  that draft would publish with an empty box.
- **Decision.** Index posts with status `publish`, `future`, `draft`, `pending` and `private`; the Locations tab shows
  the status of any post that is not published. A product used only in a draft counts as in use. The comparison
  with the Phase 1 inventory (676 links in 39 published posts) uses the published rows.
- **Changes:** Tasks 35, 36.

### 7. `title_type="Best Lightweight"`: ignored

- **Evidence.** Lasso prints `<Best Lightweight>` around the title, which browsers treat as an unknown element:
  readers see the title "ASICS Gel-Rocket 12" and the badge "Budget Frontcourt", never "Best Lightweight"
  (live Local page and `boxes.json`, 2026-11-12).
- **Decision.** `title_type` accepts `h2`–`h6`; anything else is ignored and logged. It is not turned into a badge,
  which would change what readers see.
- **Changes:** Task 27's attribute map.

### 8. Affiliate auto-detection (C2): the networks the site uses

- **Evidence.** All 16 links Lasso detected on the site are Impact (Walmart `goto.walmart.com/c/…`, Mizuno
  `mizunousa.sjv.io/…`) or Post Affiliate Pro (`?a_aid=…`) (`wp_lasso_auto_monetize`).
- **Decision.** C2 detects those two networks, by host and path; the patterns sit in one list so another network is
  one entry plus its test. Lasso's other 31 network classes are not ported.
- **Changes:** task C2.

### 9. Visual match tolerance

- **Decision.** `maxDiffPixelRatio` 0.01 (already in `playwright.config.ts` and ADR-006). Differences above it are
  reviewed against the baseline, not accepted blindly.

### 10. Lasso Groups: stay out

- **Evidence.** `lasso-cat` has 1 term and 0 assignments on the site (Task 6 check). The roadmap brings Groups back
  only if the site uses them.

## UI/UX improvements register (U1–U8)

| ID | Decision | Reason |
|---|---|---|
| U1 Block opens straight to product search | **Approved** | Single is the only display type built, so "Choose a Display Type" would offer one choice; one step fewer, no requirement against it. |
| U2 Text label on the Dashboard filter | **Approved** | The broken-link filter is the only one built; a visible "Broken links (3)" is clearer than a hover-only icon and works for screen readers. |
| U3 Help text by keyboard and screen reader | **Approved** | The audit confirmed Lasso's tooltips never take focus; accessibility is reviewed in Task 37 anyway. |
| U4 "Re-check now" on a broken product | **Rejected** | Not in the requirements (F11: a scheduled check). The nightly check and `wp affibloom check-links --product=<id>` cover it; no extra route or UI. |
| U5 7/30/90-day Clicks period | **Rejected** | Not in the requirements (F12: counts per product). Lasso's Dashboard shows a fixed 30 days, which is kept. |
| U6 Plain success wording | **Approved** | "Yee haw!" is Lasso branding; "Product saved." changes wording only, with the same toast. |
| U7 Unsaved-changes warning | **Parity** | Lasso already warns; built as Lasso has it. |
| U8 Ask before adding detected links | **Rejected** | Lasso adds them automatically, and on the live site all 16 detected links became products the site uses; the post content is never changed either way. |

Approved items change these tasks: U1 Task 23 (and F4 AC1), U2 Task 31, U3 Tasks 8–17 (help text partial) and 37,
U6 Tasks 11 and 15 (toast text).
