# F28 Button icons: analysis

Status: gate 2 written 2026-11-13. Gate 4 given by the user 2026-11-13.

**Tasks:** 14F (the icon set), 14G (the picker). Then 14B and 16 use the picker for their fields.

## Depends on

- Task 6 decision 5: button and badge texts accept `<span class="…">` (`DisplaySettingsService::clean_label`,
  `ICON_MARKUP`); Task 15 applies the same rule to the product's fields.
- ADR-6: the reader-facing box keeps its look; ADR-8: the admin follows the design system (Menu component).

## Data

| Data | Shape |
|---|---|
| option `affibloom_icons` (autoloaded) | `list<{class: string, name: string, url: string, attachment: int, imported: bool}>`, in display order |

Absent until first read; the first read imports the site's CSS rules and stores the result, so a later change of
the Additional CSS does not silently change the list (an editor adds the new icon in Settings).

## Request paths

```
Settings → Display (admin.php?page=affibloom-settings&tab=display)
  → DisplayScreen::render() → IconService::all() → OptionIconRepository (import on first read: SiteCssIcons)
  ← templates/admin/partials/icon-set.php (a card under the split card: the list, Add icon, Remove)

POST admin-post.php?action=affibloom_add_icon { name, attachment }     → IconController → IconService::add()
POST admin-post.php?action=affibloom_remove_icon { class }             → IconService::remove()  (Affibloom's only)
  nonce, Capabilities::SETTINGS, back to the tab with a toast

wp_enqueue_scripts / admin_enqueue_scripts → IconStyles: inline CSS for the added (not imported) icons, only when
  there is one

Every page with a picker field → wp_localize/inline data `affibloomIcons = [{class, name, url}]`
  → assets/admin/icon-picker.js enhances `input[data-icon-picker]`
```

## Layers

- Domain: `Icons\Icon` (class rule `^[a-z0-9]+(-[a-z0-9]+)*-icon$`, name 1-40), `Icons\IconSet` (add with a unique
  class, remove an added one, find by class), `Icons\IconText` (split `<span class="x"></span> text` / compose, B5).
- Application: `IconService` (all, add, remove; import through a `SiteIcons` port).
- Infrastructure: `OptionIconRepository`; `SiteCssIcons` (reads `wp_get_custom_css()`, matches
  `span\.([a-z0-9-]+-icon)\s*\{[^}]*?content:\s*url\(\s*['"]?([^'")]+)`, resolves the attachment with
  `attachment_url_to_postid()`).
- Interface: `IconController` (admin-post), `IconStyles` (front and admin inline CSS), the template partial, the
  script and its data.

## Risks

- **The site's CSS uses nested `@media` inside a rule** (CSS nesting). The importer reads only `content: url()` from
  each rule's text up to its first `}`, which the nesting does not affect for the `content` line; checked against the
  live CSS in 14F.
- **`content: url()` on an element** is how the site draws icons; Affibloom's added icons use the same mechanism so they
  behave the same in every browser the site already supports.
- A class removed from the set while texts still use it shows "Unknown icon" (D2); nothing is rewritten.

## Security

- Capability `SETTINGS` and nonces on add and remove; the attachment must be an image the user can read
  (`wp_attachment_is_image`); the printed CSS uses the class rule above and `esc_url_raw` for the URL, so a name can
  never inject CSS.
- The picker composes only `<span class="<known or kept class>"></span>`; the server cleaners still decide what is
  stored.
