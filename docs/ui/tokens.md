# UI tokens

Measured from Lasso's admin stylesheet (`admin/assets/css/lasso-dashboard.css`, Lasso 369) and checked
against the live Lasso 370 screens in `tests/fixtures/baselines/admin/`. Task 8 turns these into CSS custom
properties named `--affibloom-*` in `assets/admin/admin.css`.

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

## Spacing and layout

- Content column about 1,050 px wide, centred under the purple header bar (measured at 1920 px).
- Cards: white, `--affibloom-radius`, about 20 px padding; on Settings, two columns side by side.
- Dashboard rows about 80 px tall; image thumbnail 40 px square.
- Bootstrap 4 spacing scale (`.p-4`, `.mb-3` …), which the plan's CSS kit reproduces with its own classes.
