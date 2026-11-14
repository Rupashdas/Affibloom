# F23 Groups: requirements

Status: gates 1-2 written 2026-11-14 from a sweep of Lasso 369's Groups (area 0 of
`affibloom-docs/plans/archive/dashboard-completion-plan.md`; progress deviation 88). Gate 4 for Tasks G1-G5: waiting.

## 1. User stories and acceptance criteria

- As an editor, I put products into named groups, so I can find related products and (later) show a group as a list,
  grid or gallery.

- **AC1** Affibloom → **Groups** lists the groups, newest first, ten to a page: Name, Description (237 characters, then
  "..."), Links (published products in it); "Search Groups" finds a group by name, spaces as wildcards; with no groups,
  Lasso's empty state. A row opens the group's Links tab. (G1, R1-R4)
- **AC2** **Add New Group** opens "Add a New Group" (Name, Description); Save Changes with a name shows "Updating Group",
  creates the group and opens its Details tab; an empty Name is refused with "Name is required."; a name another group
  has is refused with "A group with this name already exists." (G1, R5-R7, D3)
- **AC3** A group's **Details** tab shows Name, Slug (read only) and Description; a save keeps the slug; **Delete This
  Group** asks "Delete This Group?" ("If deleted, you won't be able to get it back."), deletes and lands on the Groups
  list with "Successfully deleted the Group."; a group with products shows "Hold Up" ("You can't delete a Group if it
  has associated Links. Remove all Links using this Group first.") and deletes nothing. (G2, R8-R11)
- **AC4** On a product's page, **Groups** offers the existing groups and takes a new name typed there; after Save
  Changes and a reload the product is in exactly the chosen groups, a new name is a new group, and the Dashboard's
  Groups column names them. (G3, R13-R16)
- **AC5** A group's **Links** tab lists its products in the group's order (Image, Link Name, Description or "(blank)",
  Remove); a new member comes last; dragging a row, or its Move up / Move down buttons, keeps the new order after a
  reload. (G4, R17, R19, D7)
- **AC6** **Remove** on a row asks "Remove This Link From Group?"; ticking rows and the bar's Remove asks "Remove
  Selected Links From Group?"; Remove takes them out of the group only (the products stay), and the Links count falls.
  (G4, R18)
- **AC7** **Add a Link** on the Links tab opens "Add to Group": "Search URLs" finds products, ten to a page; **Add**
  puts one in the group (last) and shows it at once; a product already in it says "Link already exists."; **Done**
  closes. (G4, R20)
- **AC8** In the Dashboard's quick edit, **Groups** is live: chosen and new groups are saved with the row. (G5, R21)
- **AC9** The filters panel's **Group** filter (All groups, Not in Groups, each group) narrows the list, counts as a
  filter and is kept in a saved view. (G5, R22)
- **AC10** Ticking rows and **Add to Group** shows a group picker (existing groups and new names) with Save Changes;
  the ticked products join those groups and keep the ones they had; with none chosen, "Select at least one group.".
  (G5, R23)

## 2. Evidence

All read: `admin/views/groups/{groups,header,group-details,group-urls}.php`; `admin/views/rows/{group-row,
group-url-row,group-url-row-2}.php`; `admin/views/modals/{group-delete,link-monetize}.php`;
`admin/views/education/groups.php`; `pages/groups/class-ajax.php` (`lasso_store_category`, `lasso_get_groups`,
`lasso_add_lasso_to_group`, `lasso_remove_lassos_from_group`); `pages/group-urls/class-ajax.php`
(`lasso_save_category_positions`, `lasso_delete_category`); `pages/class-ajax.php` 1986-2125 (bulk Add to Group,
`resolve_dashboard_group_values_to_term_ids`, `append_groups_to_link`); `pages/class-hook.php` 1115-1135 (the Links
tab's words); `admin/assets/js/group-urls.js`; `admin/assets/js/settings.js` 3100-3190 (group save and delete);
`admin/assets/js/view-js.php` (the Groups column 365, the filter 1200-1310, the bulk editor 456-575, the quick edit's
`categories` 811); `admin/views/dashboard/dashboard.php` 20-35, 93-117, 158-165; `classes/class-post-type.php` 133-146
(the taxonomy); `classes/class-group.php`, `classes/class-category-order.php`; `Lasso_DB::get_groups_query()`
(1702-1745); `Lasso_Affiliate_Link::save_lasso_url()` 1184-1200 and 1744-1790 (groups on save);
`admin/views/header-new.php` 58-170 (the nav).

**This copy, 2026-11-14:** one Lasso group, "best volyball shoe" (`best-volyball-shoe`, no description), with **no
products**; no `lasso_category_order` rows; no post uses a group display (`[lasso … category=…]`). Groups are unused
on this site; they are built in full because everything Lasso does is in scope (deviation 24).

## 3. What Lasso does

| # | Rule | Source |
|---|---|---|
| R1 | **Storage:** the taxonomy `lasso-cat` on Lasso's links, not hierarchical, not public; a group is a term (name, slug, description) | class-post-type.php 133-146 |
| R2 | **Groups list** (`page=groups`): title "Groups", "Learn" (Lasso's help site), **Add New Group**; "Search Groups" (`t.name LIKE`, spaces → `%`); columns **Name, Description, Links**; ten a page, `term_id DESC`; the description cut at 237 characters plus "..."; Links counts **published** links only; each row links to the group's Links tab | groups.php; group-row.php; get_groups_query; lasso_get_groups |
| R3 | **Empty list:** "Create Multi-Product Displays & Organize Your Links" — "When you add links into groups, you can display them across your site as Lists, Grids, or Galleries. You can also use groups to categorize similar links making them easier to find." and a help link | education/groups.php |
| R4 | **Nav:** Dashboard, Opportunities, Tables, **Groups**, then Analytics and Marketplace (Lasso's cloud) | header-new.php 155-171 |
| R5 | **A group's page:** heading = its name ("Add a New Group" when new); tabs **Links `<n>`** and **Details**; on the Links tab **Add a Link**; right, **Copy Shortcode** (`[lasso type="grid" category="<slug>"]`, "Copy this Display to your clipboard.", "Copied!") | groups/header.php |
| R6 | **Details form:** Name (placeholder "Group Name Goes Here"), Slug (read only, shown once the group exists), Description (textarea); footer: Save Changes right, **Delete This Group** left (existing groups) | group-details.php |
| R7 | **Save:** "Updating Group" / "Saving your changes now." with a bar; an empty name → "Name is required." (before sending); new → `wp_insert_term( name, { description } )`, existing → `wp_update_term( name, description )` (the slug is not touched); then the group's Details page opens; a refusal (a name WordPress already has) → the generic failure bar | settings.js 3100-3145; lasso_store_category |
| R8 | **Delete This Group:** with links → "Hold Up" / "You can't delete a Group if it has associated Links. Remove all Links using this Group first." / Ok; without → "Delete This Group?" / "If deleted, you won't be able to get it back." / Cancel, Delete → "Deleting Group" → `wp_delete_term` → "Successfully deleted the Group." → the Groups list after 1.2 s | group-details.php; group-delete.php; settings.js 3149-3185 |
| R9 | The "has links" check reads the count the page was opened with (a query argument), not the server | settings.js 3151 |
| R10 | The Links tab has "Delete This Group" too, with the same rule | group-urls.php |
| R11 | Capability for every group action: `edit_posts` | pages/groups/class-ajax.php |
| R12 | **Order:** table `lasso_category_order (item_id, parent_slug, term_order)`; a link added to a group is placed after the group's last; removing a link deletes its row; a drag rewrites the whole group's order | class-category-order.php; lasso_save_category_positions |
| R13 | **Product page "Groups":** select2, several, placeholder "Select a group", a typed new name becomes a group (`tags: true`); help "Group this URL for better organization and to use in group displays" | url-details.php (F1 requirements 4) |
| R14 | **On save** the field's values are term IDs or names: a name finds the group by name or creates it (`wp_insert_term`); the product's groups become exactly that list (`wp_set_object_terms`, replace); order rows are added for new groups and deleted for groups left | save_lasso_url 1184-1200, 1744-1790 |
| R15 | **Quick edit** sends `categories` with the row and saves them the same way (F1 Q2, Q6) | view-js.php 811 |
| R16 | **Dashboard Groups column:** the product's group names, comma-separated, the full list as the cell's title | view-js.php 365-366 |
| R17 | **Links tab:** every link of the group in one request (no pages), in the group's order; rows: grip, Image (50 px), Link Name (to the link's page), Description (plain text, whitespace collapsed; "(blank)" when empty), **Remove**; a selection bar (the Dashboard's: "N link(s) selected", Select All, Clear Selection, **Remove**) | group-urls.php; group-url-row*.php; view-js.php 2196-2240 |
| R18 | **Remove:** one row → "Remove This Link From Group?" / "This will remove the link from this group, but the link will remain in Lasso and in other Groups (if applicable)."; selected → "Remove Selected Links From Group?" / "This will remove the selected links from this group, but the links will remain in Lasso and other Groups (if applicable)."; button "Remove"; then `wp_remove_object_terms`, the order row deleted, **and a link scan that rewrites posts** (`scan_post_page`); the count badge updates; failure "Could not remove links from this group." | class-hook.php 1125-1133; group-urls.js; lasso_remove_lassos_from_group |
| R19 | **Reorder:** drag a row (jQuery UI sortable; not from the checkbox or Remove); on drop the whole order is saved | group-urls.js 140-175 |
| R20 | **Add a Link** (`link-monetize` in its "Add to Group" mode): "Search URLs", ten a page with page links, each result with an add button; adding puts the link last in the group and draws its row at once; one already in it → 409 "Link already exists."; "Done" closes | link-monetize.php; group-urls.js 1-14; lasso_add_lasso_to_group |
| R21 | (= R15) the quick edit's Groups field | |
| R22 | **Group filter** in the filters panel: "All groups", "Not in Groups", then every group; kept in saved views (`Dashboard_Saved_Views::GROUP_TERM_NOT_IN`) | dashboard.php 158-165; view-js.php 1200-1310 |
| R23 | **Bulk Add to Group:** the bar's "Add to Group" (behind the quick edit's guard) turns into a select2 (several, new names) and "Save Changes"; with none chosen "Select at least one group."; values resolved as R14; groups are **appended** (existing ones kept), order rows added; then the current page reloads. Errors "No links selected." / "Could not add links to groups. Please try again." | view-js.php 456-575; pages/class-ajax.php 1986-2125 |
| R24 | **Elsewhere:** group displays on the front end (`[lasso type="grid|list|gallery" category="<slug>"]`, in group order); the post editor's display modal creates groups and picks one (`display-add.php`) | class-lasso-shortcode.php 261, 984; display-add.php |

## 4. Decisions

Settled by Claude from Lasso and the records (none drops anything Lasso does or reverses a user decision):

- **D1 Storage.** A taxonomy `affibloom_group` on `affibloom_product` (not public, not hierarchical, no core UI), as
  Lasso's. A group's order is one term meta, `_affibloom_order` (product IDs in order), instead of Lasso's table: it is
  always read and written whole, per group, and the site has none. How it is read: D12.
- **D2 Who:** `Capabilities::PRODUCTS` (decision 1), not Lasso's `edit_posts`.
- **D3 A duplicate name** gets its own sentence, "A group with this name already exists.", under Name; Lasso shows its
  generic failure bar for WordPress's refusal. Names are compared as WordPress compares them (`term_exists`).
- **D4 The delete rule is checked by the server** (the group's published products at the moment of the click), not by
  a count carried in the address (R9), which can be stale.
- **D5 Copy Shortcode is "Coming soon"** until Affibloom's shortcode exists (Task 18S), as the product page's Share kit
  (F1 D4); then it copies Affibloom's group shortcode, and `[lasso … category=…]` is Task 27's compatibility. **Group
  displays** (R24) get a home in Phase 3 (see section 5).
- **D6 Removing from a group rewrites no post** (R18's link scan is not built): deviation 52's rule, as F1 D13.
- **D7 Reorder by keyboard too:** each row has Move up / Move down buttons beside the grip (drag needs a mouse;
  WCAG 2.1.1). The order is saved after each move, as after each drop.
- **D8 The picker without select2:** Affibloom loads no jQuery UI or select2 (ADR-7: plain scripts). One shared
  component, the **group picker**: chips for the chosen groups, a text box that filters the site's groups in a
  listbox, Enter or "Create “<name>”" for a new name, Backspace removes the last chip. It serves the product page, the
  quick edit and the bulk Add to Group; the design system gets its row.
- **D9 Pages and nav:** `admin.php?page=affibloom-groups` (the list) and `admin.php?page=affibloom-group&id=N[&tab=
  details]` (a group; without `id`, "Add a New Group"); the header gains **Groups** after Dashboard (Lasso's order;
  Opportunities and Tables come with their areas). "Learn" (Lasso's help site) is left out (deviation 52).
- **D10 The product search for "Add to Group"** is the planned `GET /affibloom/v1/products?search=` (the overview's API
  table, the block picker's), here with pages of ten and an `in_group` flag; G4 is its first caller, Phase 4's picker
  its second.
- **D11 After a save the Details tab opens** (Lasso's redirect), with "Group saved." (U6's plain wording; Lasso's
  "save-success" toast).
- **D13 The Groups list loads in place**, as Lasso's AJAX list and Affibloom's Dashboard (13D, the user's direction):
  the page prints the first list; search and page links fetch the same partial through REST and keep the address in
  step (Back, reload and bookmarks work); without JavaScript the search is a form and the pages are links.
- **D12 The order is read against the members.** A group's products are its published members (the relationships);
  `_affibloom_order` only ranks them: IDs no longer members are skipped, members not in it come last. So a product
  deleted for good (17A, WordPress removes its relationships) or trashed (17B, its relationships stay) simply drops
  out of the list, and one brought back (17D) returns to its old place; the delete code needs no group hook.

## 5. Scope that needs a home

- **Group displays** (R24): the List, Grid and Gallery display types (F16's segment) rendering a group's products in
  its order. **Phase 3**, after the single box (Tasks 18-22) and Task 18S: a task "Group displays" is added there.
- **The editor's group picker and "create a group"** in the display modal → Phase 4 (F4's gates).
- **Lasso's groups and order** → Task 26's migration (`lasso-cat` → `affibloom_group`, `lasso_category_order` →
  `_affibloom_order`); the staging import (deviation 87) copies no groups (the site has one, empty).

## 6. Edge cases

1. A group named with HTML or quotes: names are `sanitize_text_field`, printed escaped.
2. Two editors: the order is saved whole; the last save wins (Lasso's rule).
3. A product in the trash or retired is not listed in a group and not counted (R2's published-only count).
4. A group deleted while a product page is open: saving that page drops the unknown ID silently.
5. The Group filter's group deleted while a saved view holds it: the view falls back to "All groups".
