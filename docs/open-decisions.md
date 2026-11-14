# Open decisions for the user (from the full planning pass, 2026-11-14)

**Answered 2026-11-14** — see 'Answers' at the end; every other item took its recommendation.

Every item below would drop, replace or simplify something Lasso does, reverse an earlier decision of the user's, or
needs an action outside the code (staging writes, client input). Nothing here is decided. Each has Claude's
recommendation and the place it is written in full (`docs/features/<ID>/requirements.md`, "Needs the user"). The tasks
that depend on an answer say so in their phase file ("Pending the user").

Everything **not** listed here was settled from Lasso's source and the project records and is written as a decision
(D-row) in its feature's requirements.

## A. Content in posts (the "never edit post content" rule)

| # | Question | Recommendation | Where | Tasks affected |
|---|---|---|---|---|
| A1 | **Legacy Lasso blocks at cutover:** convert the 248 blocks in 34 posts into Affibloom blocks (one reversible, operator-run content change), or register Lasso's block name `affiliate-plugin/lasso` ourselves and convert nothing | The migration sweep recommends keeping the conversion as the one sanctioned exception; the editors sweep recommends registering the block and converting nothing. **Claude's recommendation: register the block, convert nothing** (no content change at all; ADR-3 revised) | F10 N1, F4 N2, ADR-003 | 28A-28E or ED6; F34 H1-H2 |
| A2 | Keep Lasso's 2,790 `data-lasso-*` stamps in posts after Lasso is removed (roadmap 9.6's optional clean-up) | Keep them (invisible; the link index reads them) | F21 N3, F34 N3 | F34 H3-H4, Task 42 step 4 |
| A3 | Monetize from Opportunities without rewriting the post: store "this link → this product" and print it at render time, plus "Edit in post" | Yes, both | F22 N1, F34 N4 | O10 |
| A4 | Other plugins' shortcodes and SiteStripe iframes render through Affibloom at view time instead of being rewritten into `[lasso]` | Yes | F21 N2, F30 N3, F33 N1 | 27X*, 35J |
| A5 | Links printed by other plugins' own renderers (WP Recipe Maker, Mediavine Create, TablePress, Beaver Builder, Thrive) keep their stored attributes where no output filter exists | Accept (none used on this site) | F21 N1 | — |
| A6 | Product flag and slug changes reach content links only through the render-time filter | Accept | F24 N3 | CL6 |
| A7 | Changing the cloaked prefix: old prefixes keep answering instead of rewriting every post | Yes | F24 N2, F9 N4 | CL5 |
| A8 | Don't build "Uninstall Lasso" (it rewrites posts); the Uninstall card holds Affibloom's opt-in data removal | Yes | F9 N2 | TL5, 38 |
| A9 | Repair tools that only fix Lasso's own rewrites (Fix Overwritten Links, scan-time fixes, db scripts) are not built | Accept | F34 N5 | — |
| A10 | Elementor `_elementor_data` rewriting and MCP write routes are not built | Accept | F4 N5, N6 | MCP1 |
| A11 | Amazon short links stay `amzn.to` in stored posts (readers get the full link through the render filter) | Accept | F35 N2 | AM2 |

## B. Lasso's cloud, licence and marketing (omissions need your approval)

| # | Question | Recommendation | Where |
|---|---|---|---|
| B1 | Licence key, account, Analytics, Marketplace items, update checker, Advanced Click Tracking, Segment/PostHog/Sentry/Intercom, Stop Lasso Analytics | Omit | F9 N1, F38 N2, N5 |
| B2 | Help menu, Support item, the 29 "Learn" links to Lasso's help site | Omit (boxes keep their text) | F38 N3, F23 D9 |
| B3 | The review card asking for a WordPress.org review of Lasso | Omit | F38 N4 |
| B4 | Commission Upgrades, Mobile Deep Linking, Send Readers to Their Local Store (worked only through `lasso.to`) | Keep the stored values, label "no effect", build nothing | C1 N1, N7 (F38) |
| B5 | AI Text displays and `[lasso prompt]` (Lasso's `lasso.link/chat`) | Render only text already stored; no generator unless you choose a provider and key | F30 N2, F31 N1, F4 N4 |
| B6 | Program Opportunities (a table filled from `lasso.link/data-sync`) | Not built | F22 N4 |
| B7 | Lasso's per-customer `SUPPORT_SITES` fixes | Not built | F37 N4 |
| B8 | The cloud parts of the redirect (`sid` posted to lasso.link, `lcid`, data sync); the local sid → sub-ID rewrite is kept | Omit the cloud parts | F24 N5 |
| B9 | Lasso's own "Brag" icon (its logo, linking to its affiliate sign-up) | A neutral icon, alt text "Brag" | F5 N3 |
| B10 | Tour's Chat step (Intercom) | Omit; migrate "tour done" | F19 N1 |

## C. Reversing or changing earlier decisions

| # | Question | Recommendation | Where |
|---|---|---|---|
| C1 | **ADR-6: box CSS and class names.** Keep Lasso's class names and port Lasso's CSS (GPL v2+), adding an `affibloom-box` class, so the site's 33 `.lasso-*` Additional CSS rules keep shaping the box | Yes (reverses ADR-6) | F5 N1 |
| C2 | **U1:** with all seven display types in scope, restore Lasso's "Choose a Display Type" step in the block | Withdraw U1 | F4 N1 |
| C3 | **Permissions:** build Lasso's role select (decision 1 fixed it to administrators), fixing Lasso's swapped Contributor/Subscriber mapping | Build it | F9 N3 |
| C4 | **Prime:** Lasso shows the Prime logo on every Amazon product while the setting is on; Affibloom has a per-product switch and the migration would map Lasso's cached `is_prime` (1 product), removing the logo from about 225 boxes | Lasso's rule; drop the switch or make it an opt-out defaulting on | C1 N2, F5 correction |
| C5 | **Smart CSS Loading:** default on for a fresh install and switched on by the migration (keeps "box CSS only where a box shows"), or imported as the site's value (off) | On, and the migration turns it on (F5 N2); F9 N5 recommended importing off — choose one | F5 N2, F9 N5 |
| C6 | **GA:** build Lasso's GA ID field, Pageview, IP Anonymization and the gtag.js loader in full and import Lasso's values (reverses "no GA ID"); tell the client `371244810` is wrong and Pageview should stay off | Yes; keep the "Lasso …" event names | C3 N1, N2, F9 N6 |
| C7 | Unsaved-changes guard on Display and Amazon too (Lasso has it on General only) | Yes | F9 N7 |
| C8 | `title_type="h1"` allowed (decision 7 allowed h2-h6; Lasso's help names H1) | Allow h1 | F31 N2 |
| C9 | Field values: plain text, escaped (Lasso prints raw HTML) | Plain text | F26 N1 |

## D. Lasso quirks: copy or fix

| # | Question | Recommendation | Where |
|---|---|---|---|
| D1 | `columns` has no effect on a customised grid and `compact="hide"` does nothing | Keep Lasso's behaviour for `[lasso]`; Affibloom's own shortcode does what the help says | F30 N1, F31 N3 |
| D2 | Easy Affiliate Links takeover while that plugin is active | Build as Lasso does (inactive here) | F31 N4 |
| D3 | Old product slugs keep answering (Lasso 404s them) | Yes | F24 N1 |
| D4 | Lasso turns off WordPress's `wp_old_slug_redirect` for the whole site | Don't copy | F24 N4 |
| D5 | Paths of three or more segments under the prefix | Not matched | F24 N6 |
| D6 | Slug-conflict wording "We updated the permalink" (untrue) | Truthful wording | F24 N7, F27 N1 |
| D7 | Dashboard sort defects (Alert off-click; sort dropped for one load) | One consistent sort state | F19 N2 |
| D8 | Command palette searches the Dashboard's way (name or URL), not Lasso's title-only | Yes | F19 N3 |
| D9 | Revert from the History screen refuses posts edited after the change | Yes (`--force` in WP-CLI) | F34 N6 |
| D10 | Lasso's Post Content History toggle and 14-day purge; its 18 history rows | Drop the toggle and purge; don't show or revert Lasso's rows | F34 N1, N2 |
| D11 | A field deleted while a table uses it | The delete names the tables and removes the field from them | F25 N1 |
| D12 | Footer version blanked | On Affibloom's pages only | F38 N6 |
| D13 | Setup wizard: built without the licence step, opens once on a fresh site only | Yes | F38 N1 |

## E. Data and migration

| # | Question | Recommendation | Where |
|---|---|---|---|
| E1 | Lasso's click figures (a 30-day snapshot from its cloud, 2,839 here; no history exists) | Import production's last figures as one dated row per product at cutover | F12 N1, F10 N2 |
| E2 | Bot filtering will differ from Lasso's cloud | Accept; say so in the cutover notes | F12 N2 |
| E3 | Never copy the dead PA-API keys; copy Creators credentials only if present | Yes | F10 N3 |
| E4 | The 9 records with no link | Keep skipping, list them in the report | F10 N4 |
| E5 | Migrated Amazon names, images, prices go stale until C1 credentials | Migrate as decided; say so in the admin guide | F10 N6 |
| E6 | Amazon prices shown only from Creators data younger than 24 hours | Yes (prices are hidden on this site today) | C1 N3 |
| E7 | Re-read Amazon's 24-hour rules when C1E starts (cited only from the 2026-11-12 report) | Yes | C1 N4 |
| E8 | Detected products named as Lasso names them (page title or placeholder), not from the link text | Lasso's naming (C2 AC1 corrected) | F35 N1 |
| E9 | First Amazon auto-import at cutover would create ~91 data-less "Amazon…" products | Keep it on; run on staging first and review the list | F35 N3 |
| E10 | Import from other plugins: revert deletes the imported product (refused while in use); copy, don't move | Yes | F33 N2, N3 |
| E11 | Amazon items a source never cached come in with no price | Import anyway | F33 N4 |
| E12 | Four site-specific Lasso repairs (other customers' domains) | Leave out | F33 N5 |
| E13 | The 49 ThirstyAffiliates links (`/recommends/`, post 3297): import on staging with the client's approval | Yes (closes Task 41's old content fix) | F33 N6 |
| E14 | Missed scheduled posts (a Lasso cron job, not affiliate-related) | Not built | F11 N5 |
| E15 | F11 N1-N4 (link-health details) | As recommended in F11 | F11 |
| E16 | Amazon rows in Opportunities open "Create a Link" prefilled; short links never followed | Yes | F22 N2 |
| E17 | Links tied by their own URL can be turned back to the original URL at render time only | Yes | F22 N3 |

## F. Actions outside the code

| # | Action | Who | Where |
|---|---|---|---|
| F1 | Approve one staging session to capture Lasso baselines: 7 unused themes and other states (F5), a test group and a draft page (F30), table captures plus one cloned Vertical table deleted after (F25) | the user | F5 N4, F30 N4, F25 N2 |
| F2 | Confirm Tables stays the last area | the user | F25 N3 |
| F3 | Does production (and staging) have SSH / WP-CLI? The runbook uses admin entry points either way | the user | Phase 9 |
| F4 | Production's Lasso licence end date (Lasso forces its Brag mark 48 h after it; Local's is 2026-11-23) | the user | Phase 9 |
| F5 | Creators API credentials and the 10-sales eligibility (C1) | the client | C1 |
| F6 | Whether GA4 receives the old Lasso click event (optional) | the client | C3 (moot: events renamed, see Answers) |
| F7 | `affibloom.php`: your uncommitted edit (requires WP 7.1.4 / PHP 8.3, 31 lint errors) — Local runs PHP 8.2, so the plugin would not re-activate there; TL1 needs activation hooks in that file | the user | audit 1, F37 |
| F8 | Lasso is **active** on the Local copy (`active_plugins`), though `environment.md` said inactive; its jobs stay off only because its licence status is 0 | the user (keep or deactivate) | facts recheck |
| F9 | Groups G1-G5: the Phase 2 file records gate 4 as given ("Approve, start G1"), `progress.md` said waiting — confirm with this plan | the user | Phase 2 |

## Answers (the user, 2026-11-14 walkthrough)

| # | Answer |
|---|---|
| A1 | **Register Lasso's block name, no conversion.** ED6 is built; 28A-28E and H1-H2 are dropped; ADR-3 revised. |
| C1 | **Keep ADR-6 (Affibloom's own class names and stylesheet).** The site's 33 `.lasso-*` Additional CSS rules are translated before cutover (Task 41). F5 N1's recommendation not taken. |
| C2 | **Restore Lasso's "Choose a Display Type"** (U1 withdrawn). |
| C4 | **Lasso's Prime rule:** the logo on every Amazon product while the site setting is on; the per-product switch is an opt-out defaulting on. |
| B1, B2, B3, B10 | **Leave out** every Lasso cloud, licence, account and marketing item, the Help menu and the 29 Learn links, the review card and the tour's Chat step. |
| B4 | **Keep the three switches, labelled "no effect"**; nothing built behind them. |
| B5 | **Show stored AI text only** (DT9); DT10 is not built. |
| B9 | **Leave brag mode out.** The Enable Brag Mode and Brag URL controls built in 10E are removed (new correction task); F5 22D is dropped. |
| C3 | **Build Lasso's Permissions role select** (TL6), default Administrator, fixing Lasso's Contributor/Subscriber swap; decision 1 becomes the default. |
| C5 | **Smart CSS Loading on by default and switched on by the migration** (F5 N2; F9 N5 not taken). |
| C6 | **Build Lasso's GA controls in full** (ID, Click Tracking, Pageview, IP Anonymization, gtag.js loader) and import Lasso's values; C3C (the gtag.js loader) is built; the event names were revised, see the row "C3 N2 / C6 event names". |
| C7 | **Unsaved-changes guard on every Settings tab** (9E). |
| E1 | **Start from zero: no click figures imported** (Dashboard Clicks and GA); 26J dropped. (Revised the same day: the first answer was to import them.) |
| A3 | **Monetize at render time plus "Edit in post"** (O10). |
| A2 | **Keep the `data-lasso-*` stamps**; F34 H3-H4 and Task 42 step 4 are dropped. |
| E9 | **Amazon auto-import on**, run and reviewed on staging first. |
| C3 N2 / C6 event names | **Renamed to Affibloom's own, GA starts from zero.** `affibloom_primary_click`, `affibloom_secondary_click`, `affibloom_link_click`; parameters `affibloom_title`, `affibloom_href`, `affibloom_text`, `affibloom_badge`; no backward compatibility with Lasso's GA reports. |
| F1 | **Staging capture session approved:** one session; the test group, draft page and table clone are deleted after the capture. |
| F3 | **No shell access known:** the runbook stays on admin screens. |
| F4 | **Not needed.** "We will completely remove Lasso and replace it with Affibloom… Affibloom must work independently without any Lasso dependency or branding." The licence-date and brag-48h items are removed from Phase 9. |
| F7 | **The user's `affibloom.php` change stands: PHP 8.3 and WordPress 7.1.4 are the plugin's minimums** (Task 2X0). |
| F8 | **Lasso was deactivated on the Local copy** (done 2026-11-14, after a database backup). |
| F9 | **Groups G1-G5:** gate 4 given with the whole plan. |
| Gate 4 | **Given for the whole plan**, effective once these answers were applied (2026-11-14). |
| Every other item in tables A-F without a row above | **Took its recommendation** (the user accepted every remaining recommendation, 2026-11-14). |
