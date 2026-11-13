# F17 Amazon settings (Settings → Amazon): requirements

Status: **draft, gate 1**, 2026-11-13, written under the full-Lasso scope rule (`progress.md` deviation 24) before
Task 10F is built. Evidence: Lasso 369's `admin/views/settings/amazon.php`, its save handler
(`pages/settings-general/class-ajax.php` 340-500), its defaults (`admin/lasso-constant.php` 111-160), its tracking-ID
rule (`Lasso_Amazon_Api::TRACKING_ID_REGEX`), the live `lasso_settings` and `settings-amazon-1/2/3.jpg`.

**What this feature is:** the Amazon settings screen — every control, its stored key, its default and its
validation. Several settings switch behaviour that lives in other subsystems (auto-monetization, link rewriting,
price updates, stock alerts, geni.us, deep links, local stores); those behaviours are planned with their
subsystems, area by area, and listed in section 4 so none is lost.

## 1. Controls, in Lasso's order

### 1.1 Amazon Associates Accounts

Intro: "Enter your primary tracking ID and make sure your international accounts are connected with OneLink. It'll
automatically send visitors to their local store."

| Control | Lasso key → Affibloom key | Default | Live | Rule |
|---|---|---|---|---|
| Tracking ID for This Site (text, placeholder `tracking-20`) | `amazon_tracking_id` → `tracking_id` | empty | `vbvault-20` | `^[a-zA-Z0-9-_.]+-\d{2,3}$`, case-insensitive, or empty; message "This is an invalid Tracking ID" |
| Allow Multiple Tracking IDs (switch) | `amazon_multiple_tracking_id` → `multiple_tracking_ids` | on | on | — |
| Tracking ID Whitelist (tag list, disabled while multiple IDs is off) | `amazon_tracking_id_whitelist` → `tracking_id_whitelist` | empty | `[vbvault-20]` | a set: split on spaces, sorted, no duplicates; each ID by the same rule |

Removing an ID from the whitelist adds it to the **blacklist** (`amazon_tracking_id_blacklist` →
`tracking_id_blacklist`, a sorted set of every removed ID), which the link rewriter uses to replace that tag. Lasso
keeps duplicates by a slip (`Helper::update_amazon_blacklist_ids` saves the un-deduplicated list); Affibloom keeps a
set. Lasso's note "Changing or retiring this ID? Update it in your Lasso Account too" belongs to Lasso's cloud
account and has no Affibloom equivalent (audit, "things that cannot simply be rebuilt").

### 1.2 Amazon Product Data API — task C1

Default Tracking ID (locale select, `amazon_default_tracking_country`, default `us`, help "Select your Amazon
Associates locale."), the credentials intro, Credential ID, Secret, Partner tag, Version, **Validate**. Built with C1,
the first code that reads them.

### 1.3 Auto-Monetize Amazon Links

Intro: "Automatically monetize all current and future Amazon links with your Tracking ID, migrate your SiteStripe
displays, and add them to your affiliate dashboard." One switch, **Enable Amazon Auto-Monetization**
(`auto_monetize_amazon`, default off, live on).

### 1.4 Amazon Preferences

| Label | Lasso key → Affibloom key | Default | Live |
|---|---|---|---|
| Show Prime Logo In Displays | `enable_amazon_prime` → `show_prime` | on | on |
| Show Discount Pricing | `show_amazon_discount_pricing` → `show_discount_pricing` | off | off |
| Commission Upgrades | `auto_upgrade_eligible_links` → `commission_upgrades` | on | on |
| Update Amazon pricing daily | `amazon_update_pricing_hourly` → `update_pricing_daily` | **on** | off |
| Only update products with Locations | `amazon_pricing_locations_only` → `pricing_locations_only` | on | on |
| Force changes from link details | `force_change_from_link_details` → same | off | off |
| Keep Title When URL Is Replaced | `keep_title_when_url_replaced` → same | on | on |
| Shorten Amazon affiliate links | `shorten_amazon_affiliate_links` → `shorten_amazon_links` | on | on |
| Mobile Deep Linking | `amazon_mobile_deep_linking` → `mobile_deep_linking` | on | on |
| Send Readers to Their Local Store | `international_click` → same | on | on |
| Keep Geni.us Formatting | `keep_genius_formatting` → same | on | on |
| Scan SiteStripe in post content | `enable_site_stripe_post_content_scan` → `scan_sitestripe_in_post_content` | off | off |
| Days before out of stock alerts (number) | `days_before_out_of_stock_alerts` → same | 0 | 0 |

Help texts are Lasso's (`amazon.php` 220-340) with the product name removed. The out-of-stock days take **0-30**
(Lasso's `min`/`max`). Save Changes stays disabled until something changes.

## 2. Behaviours on save (Lasso's)

- **B1** Whitelist removals go to the blacklist (1.1).
- **B2** Turning Auto-Monetization **on** with a tracking ID starts the job that monetizes existing Amazon links
  (`Lasso_Process_Build_Link`). → auto-monetization subsystem.
- **B3** Changing Commission Upgrades asks whether to apply the change to every existing link (a per-link
  `boosting_enabled` override) and syncs to Lasso's cloud, reverting on failure. → link boosting: open decision.
- **B4** Turning Keep Geni.us Formatting **off** starts the job that removes geni.us formatting from links; turning it
  on stops it. → link rewriting subsystem.
- **B5** After a save with a tracking ID, Lasso validates the Amazon settings and imports Amazon Associates Link
  Builder's tracking IDs when that plugin is present. → C1 and Import.
- **B6** Lasso **saves an invalid tracking ID** and flags it. Affibloom **refuses** it, keeps the previous value and
  shows the partly-saved notice, as the approved plan says (an invalid tag costs every Amazon click its commission).
  **Confirmed by the user, 2026-11-13.** The screen takes Lasso's two-column structure, also confirmed that day.

## 3. Acceptance criteria

- **AC1** Every control in 1.1, 1.3 and 1.4 shows its stored value and saves.
- **AC2** An invalid tracking ID is refused with "This is an invalid Tracking ID"; `vbvault-20`, `my_tag.us-21` and
  `tag-123` are accepted.
- **AC3** The whitelist is a set; an invalid entry is refused; a removed ID is added to the blacklist.
- **AC4** The whitelist is disabled while Allow Multiple Tracking IDs is off, and keeps its value.
- **AC5** Days before out of stock alerts takes 0-30.
- **AC6** The fresh-install defaults are Lasso's (section 1).

## 4. Owned elsewhere, so nothing is lost

| Setting | Behaviour | Planned with |
|---|---|---|
| tracking ID, whitelist, blacklist, force changes | which tag each Amazon link carries; rewriting other tags | Amazon links (Task 12A: tracking ID, multiple IDs, whitelist), link rewriting (blacklist, force changes) |
| shorten links (stored form) | the query an Amazon product link is stored with (`get_amazon_product_url`, at add) | Task 12A (F2 requirements 3.4) |
| auto-monetize | monetizing existing and future Amazon links | auto-monetization subsystem |
| commission upgrades | link boosting | open decision (Lasso cloud) |
| pricing daily, locations only, discount pricing, out-of-stock days | price and stock updates | Amazon data (C1) |
| shorten links, mobile deep linking, local store | how a click reaches Amazon | link building, click script (Phase 7) |
| keep title when URL replaced | product page | Task 15 |
| geni.us formatting, SiteStripe scan | link detection and rewriting | link rewriting, C2 |
| show prime, discount pricing | the box | Phase 3 |
| blacklist from Lasso | the option `lasso_amazon_tracking_id_blacklist` | migration (Task 26) |
