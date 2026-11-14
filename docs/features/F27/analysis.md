# F27 URL variants: analysis

Status: gate 2 written 2026-11-14 with `requirements.md`. Gate 4 for Tasks V1-V5: waiting.

**Tasks:** V1 (the Variants tab: list, search, empty state, count), V2 (add, edit, remove, save, with Lasso's
refusals), V3 (a variant permalink answers), V4 (Amazon variant tags kept by the formatter), V5 (the Dashboard search
arm). After F24 CL1 (V3) and F2's formatter (V4); no credentials needed.

## Depends on

- F1 (product page tabs; Variants is "Coming soon", F1 D4); F24 CL1 (`CloakResolver`, the redirect) for V3, F24 CL3
  (`_affibloom_old_slugs`, slugs) for V2's uniqueness; F2 (`LinkNormaliser`'s tag rule) for V4; F19 (Dashboard search,
  D4's sequenced arm) for V5; F10 26H (migration).

## Data

| Where | What | From Lasso |
|---|---|---|
| table `{prefix}affibloom_url_variants` | `id`, `parent_id`, `name`, `variant_key`, `variant_kind` (`permalink`/`amazon_tag`), `created_at`, `updated_at`; UNIQUE (`parent_id`, `variant_key`); KEY (`variant_key`, `variant_kind`) | `lasso_url_variants` (`parent_lasso_id` mapped to the product ID) |

Created by the plugin's schema step (`dbDelta`, versioned option `affibloom_db_version`, as F21's table).

## Entry points and request paths

```
GET admin.php?page=affibloom-product&id=N&tab=variants                                    (V1)
  → ProductScreen (Capabilities::PRODUCTS) → VariantService::list( id ) → VariantRepository::for_parent()
  ← product.php: tabs with counts (VariantRepository::count_for_parent), partial `variants-tab.php`
    (toolbar Add Variant + Search, head, rows, empty state); `variants.js` draws, filters, edits
GET /affibloom/v1/products/{id}/variants → { data: { variants:[{id,name,key,url}], kind } }   404

PUT /affibloom/v1/products/{id}/variants { variants:[ { name, key } ] }                    (V2)
  → VariantsController → VariantService::replace( id, rows )
       VariantKind::for( product )                 Amazon → amazon_tag, else permalink (R2)
       VariantKey::normalise( kind )               sanitize_title / sanitize_text_field (R3)
       VariantRules::check( rows, product, SlugTaken port )    R15 in order; first refusal → field error
       VariantRepository::replace( parent, rows ) (transaction; R16)
  ← 200 { data: { variants } }   422 affibloom_variant_invalid data.fields{ "variants.<i>.key": message }   404

GET /<key>/                                                                               (V3)
  → F24 CloakedLinkRedirect → CloakResolver: slug → old slug/prefix → VariantRepository::parent_by_permalink_key(
    path, sanitize_title(path) ) (root only, D4) → the parent's response

LinkNormaliser (F2) Amazon tag rule                                                       (V4)
  → TagAllowed port: whitelist (F17) or VariantRepository::amazon_tag_exists( tag ) (request cache)

GET /affibloom/v1/dashboard/products?search=…                                             (V5)
  → PostTypeProductRepository::page(): EXISTS over affibloom_url_variants (name or key LIKE; digits = id)
```

## Layers

- **Domain** `Variant\Variant`, `Variant\VariantKind`, `Variant\VariantKey` (normalise), `Variant\VariantRules`
  (R15, pure; takes a `SlugTaken` port), `Variant\VariantUrl` (R9: root URL or the destination with `tag`),
  `Variant\VariantRepository` (interface).
- **Application** `Variants\VariantService` (list, replace, count), `Variants\VariantInvalid` (field errors, as
  `ValidationFailed`).
- **Infrastructure** `WordPress\TableVariantRepository` (`$wpdb`, transaction), `WordPress\WpSlugTaken` (posts/pages
  publish|draft, products' slugs and old slugs), schema step for the table.
- **Interface** `Rest\VariantsController`, the tab in `Admin\ProductScreen` and `templates/admin/product.php`, partial
  `templates/admin/partials/variants-tab.php`, `assets/admin/variants.js`, `Messages` (the R15 texts, N1).

## Risks

- **Whole-list replace** loses a concurrent editor's rows (Lasso's rule, edge 2); acceptable, stated in the tab's
  behaviour, not new.
- **V4 touches the Amazon formatter**, which every Amazon URL passes through: one indexed query per distinct tag per
  request, cached; with 0 rows it is one query per request at most.
- **Table creation** on an existing site needs the schema step to run on upgrade (version option).

## Security

- `Capabilities::PRODUCTS` on the tab and both routes; REST nonce; `name` through `wp_kses_post` (Lasso's) and printed
  escaped; keys normalised before any query; IDs `absint`; queries prepared.

## Tests

- **Unit:** `VariantRulesTest` (every R15 message and its order), `VariantKeyTest`, `VariantUrlTest`, the slug
  suggestion in `variants.js` covered by E2E.
- **Integration:** `TableVariantRepositoryTest` (replace in a transaction, unique key), `VariantsControllerTest`.
- **E2E acceptance `tests/E2E/acceptance/F27.spec.ts`:** AC1-AC5 on a product page (1280 and 375 px, keyboard);
  AC6 by HTTP request; AC7 saving an Amazon product with a variant's tag; AC8 on the Dashboard.
