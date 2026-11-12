# Screen inventory (UI audit, roadmap 1.7)

Every element on Lasso's admin screens, with its help text (the hover tooltip, `data-tooltip` in
`admin/views/**`), marked **Kept**, **Left out** (with the excluded feature it belongs to) or **Changed** (with
its register item or the spec change in `docs/spec/interface.md`). Screens: the live Lasso 370 captures in
`tests/fixtures/baselines/admin/` (2026-11-12, 1920 px wide, view only). Help text: Lasso 369 source.

## Findings that answer Task 6's questions

- **Unsaved changes (U7):** Lasso already warns, on the product page and on Settings (browser prompt, plus
  its own `#unsaved-changes` dialog when a link is clicked). Building it is parity, not an improvement.
- **Tooltips by keyboard (U3):** no. The CSS shows them on `:hover` and `:focus`, but the elements carrying
  them are `<label>`s and `<a>`s without `href` and without `tabindex`, so they never take focus. Touch and
  screen readers miss them too.
- **Toast:** `Yee haw! Your <name> saved.` (`admin/views/notifications/save-success.php`). U6 would change it.
- **Confirmations:** `Delete This Link?` / `If deleted, you won't be able to get it back.` / `Cancel`,
  `Delete`. In use: `Hold Up` / `This URL has active links and/or displays. Deleting this link will: …
  This can't be undone. Do you want to proceed?` / `Keep This Link`, `Delete This Link`.
- **Dashboard page size:** 10 rows, with a per-page selector (`10 per page`, `1-10 of 263`).
- **Permissions:** `lasso_permission` is `Administrator` (Settings → General → Permissions). The plan assumed
  Editors; see `interface.md` section 7, question 1.
- **Version:** production runs Lasso 370; the plan and the source were 369. 370 adds the product page's
  Share kit and Variants tab and turns Display settings into a visual editor.

## Header (every screen)

| Element | Help text | Status |
|---|---|---|
| Lasso logo | — | Changed: Affibloom name |
| Tabs: Dashboard | — | Kept |
| Tabs: Opportunities, Tables, Groups, Analytics, Marketplace | — | Left out (excluded: opportunities, tables, groups, cloud analytics, marketplace) |
| Settings | (in the WordPress menu) | Changed: a header tab, as the plan's screen map has it |
| Sync icon (with count) | — | Left out (Lasso cloud sync) |
| Help `?` | — | Left out (Lasso support) |
| `Add New Link` + caret (`Bulk Add Links`) | — | Kept; the caret is left out (one link per line covers it) |
| Intercom chat bubble | — | Left out (Lasso support) |

## Dashboard (`dashboard-1.jpg`, `dashboard-2.jpg`)

| Element | Help text | Status |
|---|---|---|
| Banner `Bulk Amazon sync · 3 Amazon links are ready to sync product data` | — | Left out (Amazon data sync; client point 1) |
| Title `Dashboard` + `Learn` | — | Kept; `Learn` left out (Lasso docs) |
| Red counter (broken) | `See broken URLs` | Kept (F11); U2 adds a text label |
| Orange counter (out of stock) | `See out-of-stock products` | Left out (no Amazon data refresh) |
| Green counter (opportunities) | `See opportunities` | Left out (opportunities) |
| Search `Search all 263 links…` + `Ctrl+Shift+L` | — | Kept; shortcut left out |
| `SAVED VIEW` `All links`, `Filters` | — | Left out (saved views and filters by group/network); the broken filter covers F11 |
| Columns Image, Link Name, Alert, Locations, Clicks, Actions | — | Kept |
| Column Groups | — | Left out (Groups unused) |
| Row: thumbnail, name, URL under it | — | Kept |
| Row `Edit` (title `Quick Edit`) | — | Kept (F1, Task 16) |
| Row `Shortcode` | — | Left out (no Affibloom shortcode; the block places boxes) |
| `10 per page`, `1-10 of 263`, pages | — | Kept |
| `Version 370` | — | Changed: Affibloom version |
| Empty state | (not seen: the site has links) | Kept as Lasso's wording when found in Task 13 |

## Add A New Link (`add-link-modal.jpg`)

| Element | Help text | Status |
|---|---|---|
| `Add A New Link` / `Enter the affiliate link you would like to track.` | — | Kept |
| Tabs `URL` / `Marketplace` | — | Marketplace left out, so no tabs |
| Field, placeholder `https://www.example.com/affiliate-id` | `Enter each new link on a separate line (Shift + Enter)` | Kept |
| `Add Link` | — | Kept |

## Product page, Details (`product-details-1…3.jpg`)

| Element | Help text | Status |
|---|---|---|
| Notice `This product may be out of stock.` | — | Left out (no Amazon data refresh) |
| Name + URL link | — | Kept |
| Share kit (Copy shareable URL, destination URL, disclosure, shortcode, block) | — | Left out (new in 370, not in the feature plan) |
| Tabs `Details`, `Locations 17` | — | Kept |
| Tabs `Opportunities 0`, `Variants 0` | — | Left out (excluded) |
| `Name` | This title will only be shown in displays | Kept |
| `Primary Destination URL` | The actual URL you want people to go to when they click a link | Kept |
| `Button Text` | This text will appear in the primary button | Kept |
| `New Window / Tab` | When enabled, users who click this link will have it loaded in a new tab. | Kept |
| `NoFollow / NoIndex` | When enabled, this link will be set to nofollow. This indicates to Google that it's an affiliate link. | Kept |
| `Secondary Destination URL` | A secondary URL you want people to go to when they click an optional second button in displays | Kept (F6) |
| `Button Text` (secondary) | This text will appear in the optional secondary button | Kept |
| `New Window / Tab`, `NoFollow / NoIndex` (secondary) | as above | Kept |
| `Groups` | Group this URL for better organization and to use in group displays | Left out (Groups unused) |
| `Primary Rating` card (toggle, number, stars) | Rating: Appears on your displays. Can be edited in Settings > Fields. | Kept (F8), label changed to `Primary Rating` |
| `Add Custom Field`, `Learn` | — | Left out (custom fields beyond the rating) |
| Box preview (right column) | — | Kept (Task 22) |
| `Display Theme` | Choose the default display theme for this link. | Left out (one theme) |
| `Amazon Image` + `Refresh` | (product image tooltip) | `Update Image` (media library) for every product; `Refresh` kept for Amazon products (C1) |
| `Badge Text` | This text will appear as a badge in displays for this URL. | Kept (F7) |
| `Price` + toggle | This price can be any text or amount … | Left out (price not shown on the site) |
| `Description` (rich text) | Share more details about the product or service you're linking to. A short paragraph of 1-4 sentences typically performs best. | Kept (F7) |
| `Disclosure` | This disclosure will only be shown in displays | Kept |
| `Sponsored` | When enabled, this link will be set to sponsored. | Kept: added to the plan's form (interface.md) |
| `Show Disclosure` | Turn this on to show the disclosure in Displays for this Lasso Link. | Kept |
| `Detect Opportunities` | When disabled, Link Opportunities will not show for this Lasso Link. | Left out (opportunities) |
| `Commission Upgrades` | For this link only: when disabled, Lasso will not auto-upgrade this link … | Left out (Lasso brand deals) |
| `Delete This Link` | — | Kept (moved here from the plan's Dashboard row) |
| `Learn About This Page` | — | Left out (Lasso docs) |
| `Save Changes` | — | Kept |

## Product page, Locations (`product-locations-1…2.jpg`)

| Element | Help text | Status |
|---|---|---|
| `Search` | — | Kept |
| Columns `Content` (sortable), `Link with Context`, `Type`, `Clicks` | Link with Context, Clicks: info icons | Kept (F12) |
| Rows: post title, URL, `(n locations)`, `Expand to view each location` | — | Kept |
| `1-5 of 5` | — | Kept |

Note: the list includes `sweet new post` (`?p=9492`, 3 locations), which looks like an unpublished test post.
The usage index (Task 35) should state whether drafts count.

## Settings → General (`settings-general-1…3.jpg`)

| Element | Help text | Status |
|---|---|---|
| `License Key` + `Activate` | — | Left out (licence) |
| `Google Analytics` (ID, Click Tracking, Pageview, IP Anonymization) | Enter a Tracking ID (UA-) or a Product ID (G-) …; Enable to begin tracking Lasso Link clicks in Google Analytics. | `Click Tracking` kept (C3); ID, Pageview and IP Anonymization left out (the site's own GA tag does that) |
| `Advanced Click Tracking` | When enabled, Lasso can help you understand what pages and links drive conversions on your site. | Left out (cloud analytics) |
| `Cloaked Link Prefix` | For no prefix leave this field empty. | Left out (cloaking) |
| `Smart CSS Loading` | — | Left out: Affibloom always loads box CSS only where a box is |
| `Permissions` (`Administrator`) | — | Left out as a setting; fixed capability (interface.md, question 1) |
| `Uninstall Lasso` | — | Changed: `delete_data_on_uninstall` opt-in (Task 38) |
| `Link Index` (Last Updated, Links Indexed 3,015) | — | Left out (Lasso link index) |
| `Custom Link Detection` | Additional locations where Lasso will discovering links. | Left out |
| `Performance` (CPU mode) | Lasso will wait until your CPU drops below this level to continue with its updates. | Left out |
| `Link Defaults`: `New Window / Tab`, `NoFollow / NoIndex`, `Sponsored`, `Show Disclosure` | as on the product page; Show Disclosure: When enabled, this link will show the disclosure. | Kept (F9) |
| `Allow duplicate destinations` | Allows you to have links from different networks to the same product. | Kept |
| `Link Cloaking` | Toggle on to brand your link with your domain … | Left out (cloaking) |
| `Detect Opportunities` | When off, Link Opportunities won't show for affected links … | Left out |
| `Auto-Detect Affiliate Links` (on) | Add all detected affiliate links to your dashboard as individual products. | Kept (C2) |
| `Notifications` (three toggles) | — | Left out |
| `Save Changes` | — | Kept |

## Settings → Display (`settings-display-*.jpg`)

| Element | Help text | Status |
|---|---|---|
| `Single` / `Grid` / `List`, desktop / mobile preview | — | Left out: Single only; a static preview |
| `Theme` (`Lab`) | — | Left out (one theme) |
| `Layout & spacing` (density, elements, layout, spacing, typography, image) | — | Left out (Lasso 370 editor; the box design is fixed to the baseline) |
| `Colors & labels`: `Badge` | This is the color of your badge background. | Kept (`color_main`) |
| `Title` | This is the color of the title text of your display. | Kept |
| `Background` | This is the color of the inside of your display. | Kept |
| `Button + Badge Text` | This is text color for your badges and buttons. | Kept |
| `Pros`, `Cons` | This is the main color of the Pros / Cons Field. | Left out (pros/cons not used) |
| `Primary button text` | If you leave your display button text blank, this is what it will default to. | Kept |
| `Primary button color` | This is the color of your display's main CTA button. | Kept |
| `Secondary button text` | If you set a secondary button for your display and leave it blank, this is what it will default to. | Kept |
| `Secondary button color` | This is the color of your display's secondary CTA button. | Kept |
| `Disclosure text` | This is the default disclosure text used with your displays. | Kept |
| `Show Price` | Turn this on to show the price in Displays by default … | Left out |
| `Disclosure in Single Display` | Turn this on to show the disclosure in Displays by default … | Kept as `show_disclosure` (General) |
| `Disclosure in Grids`, `Disclosure in Lists & Tables` | … | Left out (Single only) |
| `Link from Display Title` | When enabled, your Display titles will be your clickable Lasso Link … | Kept |
| `Keep Site Stripe UI`, `Brag mode URL`, `Enable Brag Mode` | … | Left out |
| `Custom CSS` | — | Left out |
| `Reset Settings`, `Save Changes` | — | `Save Changes` kept; Reset left out |
| `Need More Customization?` | — | Left out |

## Settings → Amazon (`settings-amazon-1…3.jpg`)

| Element | Help text | Status |
|---|---|---|
| `Tracking ID for This Site` (`vbvault-20`) | WordPress and your Lasso Account each store this ID … | Kept (F2) |
| `Allow Multiple Tracking IDs`, `Tracking ID Whitelist` | — | Left out: one tag per link, the site's (global constraint) |
| `Amazon Product Data API` (locale, Credential ID, Secret, Partner tag, Version, `Validate`) | Select your Amazon Associates locale. | Kept (C1) |
| `Auto-Monetize Amazon Links` | — | Left out |
| `Show Prime Logo In Displays` | Show the blue Prime logo beside the price for all eligible Amazon products in your Lasso Displays. | Kept (`show_prime`) |
| `Show Discount Pricing`, `Commission Upgrades`, `Update Amazon pricing daily`, `Only update products with Locations`, `Force changes from link details`, `Keep Title When URL Is Replaced`, `Shorten Amazon affiliate links`, `Mobile Deep Linking`, `Send Readers to Their Local Store`, `Keep Geni.us Formatting`, `Scan SiteStripe in post content`, `Days before out of stock alerts` | one tooltip each (see `admin/views/settings/amazon.php`) | Left out (Amazon data refresh, deep linking, internationalisation: excluded) |
| `Save Changes` | — | Kept |

## Not captured

The block editor's `Choose a Display Type` modal and product search, the save toast, and `This Product Already
Exists`: each needs a write on production, and Lasso is unlicensed on the Local copy. Their wording is taken
from Lasso's source (`admin/views/modals/display-add.php`, `save-success.php`, `url-save.php`) and listed
above and in `interface.md`.
