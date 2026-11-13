# F29 Any number of buttons: analysis

Status: gate 2 written 2026-11-13. Waiting for the user's gate 4 on Task 14H.

**Tasks:** 14H (the product and its page), then the parts in later tasks: 15 (save), 18 and the box tasks (render,
Phase 3), 33-34 and C3 (clicks and GA), 26 (migration), the seed tool.

## Data

| Before (14A) | After (14H) |
|---|---|
| `_affibloom_url2`, `_affibloom_button_text2`, `_affibloom_new_tab2`, `_affibloom_nofollow2` | `_affibloom_buttons`: `list<{url: string, text: string, new_tab: bool, nofollow: bool}>`, buttons 2 and up in order |
| `Product::$url2`, `$button_text2`, `$new_tab2`, `$nofollow2` | `Product::$buttons` (`Domain\Product\Buttons`, a list of `Domain\Product\Button`) |

`hydrate()` reads `_affibloom_buttons`; a product with only the old four keys (written by the seed tool before 14H)
reads them as one button, so nothing on the Local copy is lost before the seed is re-run. The seed tool writes the new
key from 14H on.

## Request path (14H)

```
GET admin.php?page=affibloom-product&id=N
  → ProductScreen::render() → templates/admin/product.php
      → partials/product-button.php for the secondary (Lasso's labels) and each additional button
      → one empty additional block (no JavaScript: always; with it, a <template> for "Add Another Button")
  ← assets/admin/product-buttons.js: add, move up/down, remove (confirm when its URL is filled), renumbering the
     field names buttons[<i>][url|text|new_tab|nofollow] so the order posted is the order shown
```

## Layers

- Domain: `Button` (url as stored, text, new_tab, nofollow; `is_empty()`), `Buttons` (ordered; `from_array`,
  `to_array`, `secondary()`, `additional()`).
- Infrastructure: `hydrate()` and the seed tool.
- Interface: the template partial, the script, `IconChoices::field()` for each button's text.

## Risks

- **Pixel parity (ADR-6):** a product with no additional button must render byte-for-byte as today; Phase 3's box
  tests already compare against Lasso's baselines, and AC5 makes it explicit.
- **Field names that move:** reordering in the page must renumber `buttons[i]`; the script does it on every change,
  and the server reads the list in posted order (Task 15).
- **Search** over `_affibloom_buttons` uses `LIKE` on the serialized meta, which finds a URL substring as it does
  today for `_affibloom_url2` (F19).

## Security

- Unchanged from the product page: `Capabilities::PRODUCTS`, everything escaped; Task 15 validates each button's URL
  and text with the secondary's rules.
