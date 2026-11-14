# GA4 and Amazon integration details: what the site already has (2026-11-12)

Revised the same day. The first version listed what Brave's logged-in accounts could see. This version first
inspects the site itself: the Local copy's files and database (a clone of production from 2026-11-09), production
through the read-only WordPress connector (plugins, environment, REST routes, connector integrations), and the public
pages. Nothing was changed on either site. Credentials were never read: anything secret-shaped is reported as
"present (N characters)" or "empty". Other clients' Analytics properties seen in Brave are not listed.

## Already Available

### Google Analytics 4

| Item | Value | Source |
|---|---|---|
| GA4 measurement ID | `G-K7T3M99G7Q` | Headers & Footers plugin (`wpheaderandfooter_basics`, header script); also Site Kit and Rank Math settings |
| **GA4 property ID** | **`312587692`** | Site Kit's stored settings (`googlesitekit_analytics-4_settings`, last synced 2023-05-24) |
| Web data stream ID | `3472054567` | same |
| Google tag account / container IDs | `6040625272` / `84482949` | same |
| GA account (probably) | `226977444` | Rank Math Analytics settings (with the retired Universal Analytics property `UA-226977444-1`, stream name `volleyballvault.com`) |

- **How the tag gets on the page:** Headers & Footers prints Google's own `gtag.js` snippet for `G-K7T3M99G7Q` on every
  page. That is the tag Affibloom's C3 will use; Affibloom needs **no GA ID, no API key and no client input to build C3**.
- **Lasso's own setting `371244810` matches nothing:** it is neither the property ID (`312587692`), the stream ID nor
  the measurement ID. It is a misconfiguration and goes away with Lasso. Lasso's click events still reach the site's tag
  anyway, because Lasso sends them without a `send_to` (plugin `docs/features/C3/analysis.md`).
- **Existing Google connections on the site,** none of which can report custom events without a change:
  - Rank Math Analytics: connected to Google (OAuth tokens stored, not read), set to `G-K7T3M99G7Q`; it fetches Search
    Console data (570,048 rows locally) but no GA data (its GA table is empty) and has no events report.
  - Site Kit: **not installed** on production; only its old settings and 3 users' stored tokens remain (not read, not
    usable without the plugin).
  - Easy MCP AI (the connector Claude uses): its Google Analytics provider is **not connected**.
- **Lasso's event names** are known from its code: `Lasso Display Primary Click`, `Lasso Display Secondary Click`,
  `Lasso Link Click` (plus `Internal Click`, `External Click`).

### Amazon

| Item | State | Source |
|---|---|---|
| Partner tag (tracking ID) | `vbvault-20`; whitelist `vbvault-20`; locale US | `lasso_settings` |
| Product Advertising API 5.0 Access Key ID | present (20 characters), **unusable** (see below) | `lasso_settings.amazon_access_key_id` |
| Product Advertising API 5.0 Secret Key | present (40 characters), **unusable** | `lasso_settings.amazon_secret_key` |
| Creators API Credential ID, Secret, Partner tag, Version | **empty** | `lasso_settings.amazon_creators_*`; the live Amazon settings screen agrees |
| `lasso_amazon_valid` = 1 | means only that a tracking ID is set, not that any API works | Lasso code, `validate_amazon_settings()` |

- **The PA-API 5.0 keys are leftovers.** Lasso 369's code never reads `amazon_access_key_id` or `amazon_secret_key`, and
  Lasso 370's Amazon screen shows only the Creators API fields. Lasso gets its Amazon titles and images from its own
  cloud (`lasso.link`), not from these keys.
- **Tested once, with the user's approval (2026-11-12):** one signed GetItems request for `B0F5BNQLFZ` with these keys
  and `vbvault-20` returned **HTTP 403 `AccessDenied`: "Product Advertising API is deprecated. Please migrate to
  Creators API"**. The keys cannot be reused; C1 needs Creators API credentials. (The keys were read only inside the
  test process, never printed or saved; the test script was deleted.)
- No other Amazon plugin, no Amazon constants in `wp-config.php`, and no Amazon code in the themes, must-use plugins,
  Code Snippets (13 snippets) or WP Coder.
- **Amazon's Creators API requirements** (Associates Central page): an approved Associates account, a user with Full
  access to create applications and credentials, and for product data **at least 10 qualifying sales in the past 30
  days**; up to 48 hours of review after credentials are created.

## Missing (Client Required)

1. **Amazon product data (C1):** Creators API credentials for the
   `vbvault-20` account. The client (a Full-access user) creates them in Associates Central → Tools → Creators API →
   Create App and sends **Credential ID, Secret, Version** through a secure channel (a password-manager share), and
   confirms the account has **10+ qualifying sales in the last 30 days**. Needed only when C1 is built (after Task 12).
   Without them C1 stays dormant: editors type names and choose images.
2. **GA4 reporting question (C3), optional:** whether the client's reports or key events rely on Lasso's event names.
   This needs someone with access to property `312587692`: either the client checks Reports → Engagement → Events for
   the three names above, or adds one of our Google accounts as **Viewer** on property `312587692`. It does not block
   building C3, which keeps Lasso's names by default.

Nothing else is needed from the client: the measurement ID, property ID, stream, partner tag and locale are all on the
site already.

## Unable to Verify

- **Whether Lasso's click events actually arrive in GA4,** and whether they appear in any report (no GA access; the
  site's own integrations cannot report events, see above).
- **The Associates account's eligibility** for the Creators API (10 sales in 30 days) and who holds Full access.
- **Production's server environment and `wp-config.php`** beyond what WordPress reports (WP 7.1.4, PHP 8.3.36, MariaDB
  10.11.19): there is no shell access to production. Nothing on the Local copy, which carries production's files,
  points to GA or Amazon settings outside the database.

## What this changes in the plan

- **C3:** no client input needed; Affibloom uses the site's existing `gtag` (Headers & Footers) and stores no GA ID.
  The property ID for any later check is `312587692`.
- **C1:** Creators API only; Amazon has switched off Product Advertising API 5.0 for this account (403 `AccessDenied`,
  "deprecated"). C1 stays dormant until the client sends Creators API credentials. Nothing changes before Task 12, and
  the migration (Task 26) must not import Lasso's dead PA-API keys.
