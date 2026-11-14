# F23 Groups: analysis

Status: gate 2 written 2026-11-14 with `requirements.md`. Gate 4 for Tasks G1-G5: waiting.

**Tasks:** G1 (the Groups list and a new group), G2 (a group's Details: edit and delete), G3 (Groups on the product
page and the Dashboard column), G4 (a group's Links tab: order, remove, add), G5 (the Dashboard: quick edit, filter,
bulk Add to Group). They follow Task 17 in Phase 2 (dashboard-completion-plan area 0).

## Depends on

- ADR-001 (products are posts; groups are their taxonomy), ADR-007 (PHP-rendered screens, plain scripts), ADR-008
  (Aurora's look, Lasso's arrangement).
- F1: the product page's Groups placeholder (F1 D4) and the quick edit's (D24); `PATCH /products/{id}` (15A) takes
  `groups`. F19: the Dashboard's Groups column placeholder (13C), the filters panel and saved views (13E), the selection
  bar (13C, 17B).
- `Capabilities::PRODUCTS` (decision 1).

## Data

| Where | What |
|---|---|
| Taxonomy `affibloom_group` on `affibloom_product` | a group = a term: name, slug (made by WordPress from the name, never changed after), description. Not public, not hierarchical, `show_ui` false, no REST, no rewrite |
| Term meta `_affibloom_order` | the group's product IDs in display order (D1, D12) |
| User meta of saved views (13E) | a view gains `group` (0 all, -1 not in groups, else a term ID) |

Lasso's keys map one to one (Task 26): `lasso-cat` → `affibloom_group`, `lasso_category_order` rows → `_affibloom_order`.

## Entry points and request paths

```
GET admin.php?page=affibloom-groups[&s=…&paged=N]                                                     (G1)
  → AdminMenu (Capabilities::PRODUCTS) → GroupsScreen::render()
  → GroupService::list( SearchTerm, page, 10 ) → GroupRepository::page()   name LIKE, spaces as wildcards (R2),
                                                term_id DESC, counts of published members
  ← templates/admin/groups.php (band, search, table or empty state, pager)
GET /affibloom/v1/groups?search=&page=   → { html, url }: the same list partial, drawn in place by groups.js (D13)

GET admin.php?page=affibloom-group                     "Add a New Group" (G1)
GET admin.php?page=affibloom-group&id=N&tab=details    a group's Details (G2); no such group → the Groups list
GET admin.php?page=affibloom-group&id=N                a group's Links tab (G4)
  → GroupScreen::render() → GroupService::get( id ), members( id )
  ← templates/admin/group.php (heading, tabs Links <n> / Details, Copy Shortcode "Coming soon", the tab's card, footer)

POST   /affibloom/v1/groups { name, description }        → 201 { id, redirect_to: Details }   422 name: required|taken
PATCH  /affibloom/v1/groups/{id} { name, description }   → 200                                422, 404
DELETE /affibloom/v1/groups/{id}                          → 200 { redirect_to: list }          409 affibloom_group_in_use, 404
  → GroupsController → GroupService::create() / update() / delete()
       GroupName rules (required, sanitize_text_field, unique by term_exists) → GroupRepository (wp_insert_term,
       wp_update_term name + description only, wp_delete_term)

PATCH /affibloom/v1/products/{id} { …, groups: [ id | "new name", … ] }                              (G3, G5)
  → ProductInput: `groups` kept as given → ProductService::update()
       GroupService::resolve( values ) → term IDs (an ID that exists, a name found, else created; R14)
       ProductRepository::save() … then GroupService::set_for_product( id, term IDs )   replace (R14), order: appended
                                                                                       in groups joined, dropped in left
  ← ProductResource::full() gains `groups` [ { id, name } ]

GET    /affibloom/v1/products?search=&page=&group=N   → { items: [ { id, name, url, image_url, in_group } ], pages } (G4)
POST   /affibloom/v1/groups/{id}/products { product }  → 201 { html: row }   409 "Link already exists."          (G4)
DELETE /affibloom/v1/groups/{id}/products?ids[]=…      → 200 { removed, count }                               (G4)
PUT    /affibloom/v1/groups/{id}/order { ids: [] }     → 200                                                  (G4)

POST /affibloom/v1/products/groups { ids: [ 1-50 ], groups: [ id | "new name" ] }  → 200 { updated }          (G5)
  → GroupService::append( product IDs, term IDs ) (R23: existing memberships kept)
GET  /affibloom/v1/dashboard/products?…&group=0|-1|N   the list route (13D) gains the Group filter              (G5)
```

## Layers

- **Domain** `Group\Group` (id, name, slug, description, count), `Group\GroupPage`, `Group\GroupOrder` (rank members by
  the stored list, D12; pure PHP, unit-tested), `Group\GroupRepository` (interface).
- **Application** `Groups\GroupService` (list, get, create, update, delete with the in-use refusal, members, resolve
  names, set for a product, append, add one, remove, reorder), `Groups\GroupNotFound`, `Groups\GroupInUse`,
  `Groups\GroupInvalid` (field errors, as `ValidationFailed`); `ProductService::update()` calls `set_for_product()`.
- **Infrastructure** `WordPress\GroupTaxonomy` (Module: registers the taxonomy on `init`),
  `WordPress\TermGroupRepository` (terms, relationships, `_affibloom_order`; the list's count joins published products
  as Lasso's `get_groups_query`); `PostTypeProductRepository::page()` gains the group condition (`tax_query`: IN, or NOT
  EXISTS for "Not in Groups").
- **Interface** `Admin\GroupsScreen`, `Admin\GroupScreen` (hidden submenu, as the product page), the header's Groups
  tab; `Rest\GroupsController`; `ProductsController` (search route, bulk groups route, `groups` field);
  `DashboardController` (`group`); templates `groups.php`, `group.php`, partials `group-row.php`, `group-member-row.php`,
  `group-picker.php`, `add-to-group-modal.php`; scripts `groups.js` (list and Details), `group-members.js` (Links tab),
  the picker in `admin.js` (three users: product page, quick edit, bulk).

## Risks

- **The picker (D8) is new UI** with keyboard and screen-reader needs (combobox and listbox roles, chips with remove
  buttons); built once in G3, reused in G5. Checked by hand at 1280 and 375 px and with the keyboard only.
- **Saving groups on the product page** runs after the product save; a refused product save (422, 409) stores no
  groups, and a group name that WordPress refuses is reported as the field's error.
- **The list query's group filter** joins term relationships; with 263 products and a handful of groups the cost does
  not matter.
- **Group displays** (Phase 3) will read `GroupService::members()`: its order rule (D12) is the one they show.

## Security

- `Capabilities::PRODUCTS` on both screens and every route; REST nonce through `wp.apiFetch`.
- Names and descriptions: `sanitize_text_field` / `sanitize_textarea_field`, printed escaped; IDs `absint`; a term ID
  from the client is used only after it is found in `affibloom_group`.
- No post content is written (D6).
