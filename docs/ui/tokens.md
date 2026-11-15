# UI tokens (Lasso's admin, for reference)

> **Reference only since 2026-11-12 (ADR-008).** Affibloom's admin has its own design system,
> `docs/ui/design-system.md` (written and approved in plan Task 9A). The values below record Lasso's admin look;
> they are not used for Affibloom's admin screens. Tasks 8 and 9 were built with them and are restyled in Task 9A.
> The product box (Phase 3) is not affected: its measured values go in a "Product box" section of this file.

Measured from Lasso's admin stylesheet (`admin/assets/css/lasso-dashboard.css`, Lasso 369) and checked
against the live Lasso 370 screens in `tests/fixtures/baselines/admin/`. Task 8 turned these into CSS custom
properties named `--affibloom-*` in `assets/admin/admin.css` (until Task 9A).

## Colours

| Token | Value | Lasso variable | Used for |
|---|---|---|---|
| `--affibloom-purple` | `#5E36CA` | `--purple` | header bar, active tab, links, filter button |
| `--affibloom-dark-purple` | `#40258A` | `--dark-purple` | hover on purple |
| `--affibloom-light-purple` | `#B09AE5` | `--light-purple` | info icons, outlines |
| `--affibloom-tint` | `#F2EFFB` | (literal) | light purple fills: row Edit button, banners |
| `--affibloom-green` | `#22BAA0` | `--green` | primary buttons (`Add New Link`, `Save Changes`), toggles on |
| `--affibloom-dark-green` | `#1B8F7B` | (literal) | hover on green |
| `--affibloom-light-green` | `#91E0D2` | `--light-green` | disabled green |
| `--affibloom-red` | `#E06470` | `--red` | broken-link counter, `Delete This Link`, errors |
| `--affibloom-orange` | `#F6BD60` | `--orange` | out-of-stock counter, warnings |
| `--affibloom-text` | `#555555` | `--black` | body text |
| `--affibloom-muted` | `#AAAAAA` | `--dark-gray` | URLs under names, hints |
| `--affibloom-border` | `#E2E2E2` | `--gray` | table rules, card borders |
| `--affibloom-input-border` | `#CED4DA` | (Bootstrap) | inputs |
| `--affibloom-page` | `#F7F7FC` | `--light-gray` | page background |
| `--affibloom-white` | `#FFFFFF` | `--white` | cards, modal |

## Type

| Token | Value |
|---|---|
| `--affibloom-font` | `"Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif` (`--helvetica`) |
| Headings | weight 800 (`h1`–`h6`, `.font-weight-bold`) |
| Page title (`Dashboard`, `General`) | about 2.5rem, weight 800 (measured on the screenshots) |
| Section title (`License Key`, `Link Defaults`) | about 1.75rem, weight 800 |
| Body | 1rem; `.small` 0.75rem; `.large` 1.25rem |

## Shape and depth

| Token | Value |
|---|---|
| `--affibloom-radius` | `0.5rem` (cards, buttons, inputs, modal) |
| `--affibloom-radius-round` | `50%` (avatars, counters) |
| `--affibloom-shadow` | `0 3px 6px rgba(0, 0, 0, 0.2)` (raised buttons, modal) |

## Header bar

From `.lasso-header` / `.lasso-header-nav` (`lasso-dashboard.css`, Lasso 369), built in Task 8:
`--affibloom-purple` background, at least 100px tall, padding `1rem 1.5rem`, shadow `0 0.5rem 1rem rgba(0, 0, 0, 0.15)`;
tabs centred, 16px, weight 700, white, `2rem` apart; the active and hovered tab get a white glow
(`text-shadow: 0 0 10px #fff`). Checked on Local at 1920px: 100px tall, `rgb(94, 54, 202)`.

WordPress core's `.wrap h1` (23px, weight 400) outranks a single class, so page-title rules are scoped under
`.affibloom-admin`.

## Spacing and layout

- Content column about 1,050 px wide, centred under the purple header bar (measured at 1920 px).
- Cards: white, `--affibloom-radius`, about 20 px padding; on Settings, two columns side by side.
- Dashboard rows about 80 px tall; image thumbnail 40 px square.
- Bootstrap 4 spacing scale (`.p-4`, `.mb-3` …), which the plan's CSS kit reproduces with its own classes.

## Product box

The reader-facing box (Phase 3) keeps the site's current look and Lasso's markup, under Affibloom's names (ADR-6,
decision C1; deviation 132). Task 41 translates the site's `.lasso-*` Additional CSS rules with this table.

| Lasso | Affibloom | Where |
|---|---|---|
| `lasso-container` | `affibloom-box` | the outer `div` (with the anchor `id`) |
| `lasso-anchor-id-<product>-<link>` | `affibloom-anchor-id-<product>-<link or uniqid>` | the outer `div`'s `id` |
| `<!-- LASSO DISPLAY BOX (https://getlasso.co) -->` | `<!-- PRODUCT BOX -->` | first child (F5 D9) |
| `lasso-display` | `affibloom-display` | the wrapper |
| `lasso-<theme>` (`lasso-lab`, …) | `affibloom-<theme>` | the wrapper |
| `lasso-url-<slug>` | `affibloom-url-<slug>` | the wrapper |
| `lasso-ds-v2-frontend` | `affibloom-advanced` | the wrapper, advanced rendering |
| `data-v2-hide-<element>` | `data-hide-<element>` | the wrapper |
| `data-layout-*`, `data-layout-m-*`, `data-density-*` | unchanged | the wrapper |
| `--lasso-<name>` | `--affibloom-<name>` | the wrapper's `style`, `:root` |
| `lasso-badge`, `lasso-badge-text` | `affibloom-badge`, `affibloom-badge-text` | |
| `lasso-box-1` … `lasso-box-6` | `affibloom-box-1` … `affibloom-box-6` | |
| `lasso-image`, `lasso-image-no-lazy` | `affibloom-image`, `affibloom-image-no-lazy` | |
| `lasso-title` | `affibloom-title` | |
| `lasso-price`, `lasso-price-value` | `affibloom-price`, `affibloom-price-value` | |
| `discount-price`, `latest-price` | `affibloom-discount-price`, `affibloom-latest-price` | |
| `lasso-amazon-prime` | `affibloom-amazon-prime` | |
| `clear` | `affibloom-clear` | |
| `lasso-description` | `affibloom-description` | |
| `lasso-button-1`, `lasso-button-2` | `affibloom-button-1`, `affibloom-button-2` | |
| `lasso-disclosure`, `lasso-date` | `affibloom-disclosure`, `affibloom-date` | |
| `lasso-end` | `affibloom-end` | the 2-box layout's disclosure and date row (18E) |
| `data-lasso-box-trackable` | `data-affibloom-box-trackable` | links |
| `data-lasso-lid` (the product) | `data-affibloom-id` | links (F12's click key) |
| `data-lasso-id` (link location) | not printed; F21's `data-affibloom-location` | links |
| `data-lasso-name` | `data-affibloom-name` | links |
| `data-lasso-button` | `data-affibloom-btn` | buttons 2 and up (19A, F12) |
| `data-lasso-link` | not printed (Lasso's cloud, F5 D3) | |

Icon spans in button and badge texts (`amazon-icon`, `pricetag-icon`, …) are the site's own and keep their names.
Measured values (sizes, spacing) are added here by Tasks 18B-22E.
