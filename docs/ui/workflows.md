# Workflows: the top tasks in Lasso, counted

> **Partly out of date (2026-11-14).** Written before the full-scope rule (2026-11-13). Where it conflicts with `docs/features/F4, F38/requirements.md` or the phase plans, those win. Known stale parts: section 4 says "Single is the only type built" and assumes U1 (all seven display types are in scope; U1 is in question, open decision C2); the section 3 wording for U2 and U4 predates the register's final decisions.

Counted on the live Lasso 370 screens (2026-11-12) and Lasso's source. A "step" is one click, one keystroke
sequence into one field, or one page load the user waits for. Affibloom keeps each workflow at the same
number of steps or fewer; any difference is a register item (U1–U7).

## 1. Add a product

1. Dashboard → `Add New Link` (opens "Add A New Link").
2. Paste the link into the field (one per line for several).
3. `Add Link` → Lasso creates the link and opens its product page.
4. Check or edit Name, image, Badge Text, Description (one step per field changed).
5. `Save Changes` → toast "Yee haw! Your … saved."

**3 steps to create, plus 1 per field and 1 to save.**

## 2. Edit a product

From the Dashboard, either:
- **Quick edit:** search (1) → `Edit` on the row (2) → change a field in the row (3) → the row's `Save Changes` (4; `Cancel` beside it); or
- **Full edit:** search (1) → click the name (2, product page loads) → change a field (3) → `Save Changes` (4).

**4 steps either way.** If the Primary Destination URL now matches another link, "This Product Already Exists"
asks whether to repoint every location (1 more step).

## 3. Find and fix a broken link

1. Dashboard → the red counter (tooltip "See broken URLs"), which filters the list.
2. Click the product name → product page.
3. Replace the Primary Destination URL.
4. `Save Changes`.
5. Wait for the next nightly check for the alert to clear.

**4 steps, then a wait of up to a day.** U4 ("Re-check now") would replace step 5 with one click; U2 would
make step 1 a labelled filter instead of a hover-only icon.

## 4. Place a box

In the post editor:
1. Add a block → `Lasso`.
2. "Choose a Display Type" → `Single`.
3. Type in "Search URLs".
4. Pick the product.
5. `Add` → the box appears in the editor.
6. Update or publish the post.

**6 steps.** U1 (open straight to search, since Single is the only type built) would remove step 2.

## 5. See where a product is used

1. Dashboard → search for the product (the Locations column already shows the count).
2. Click the name → product page.
3. `Locations` tab: one row per post, with "(n locations)".
4. Expand a post to see each location with its link context.

**3 steps to the list, 1 more per post expanded.**
