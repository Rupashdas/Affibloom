# F2 Amazon links: requirements

Status: **gate 1 re-run under the full-Lasso scope rule** (`progress.md` deviation 24), 2026-11-13, before Task 12
is built. The first version (approved with Task 6, 2026-11-12) is replaced: a sweep of Lasso's Amazon code and of
the 233 live Amazon records contradicts it in five places (section 9).

Evidence, all read in this session from the read-only reference copy `D:\lasso\lasso` (Lasso **369**):

| What | Where |
|---|---|
| The add, its Amazon branches | `classes/class-lasso-affiliate-link.php::lasso_add_a_new_link` 815-1071 |
| Duplicate detection | `::is_lasso_url_exist` 763-807, `::get_lasso_post_id_by_url` 2537-2690, `::get_lasso_id_by_search_text` 2699 |
| The ASIN lookup | `classes/class-lasso-db.php::get_lasso_id_by_product_id_and_type` 5215-5255 |
| Storage of the Amazon fields | `::save_lasso_url` 1085+ (lines 1222-1250, 1572-1580, 1660-1720 of the method's file) |
| Amazon hosts, short links, countries | `classes/class-lasso-amazon-api.php::get_domains` 177, `::is_amazon_url` 556, `::is_amazon_shortened_url` 656, `::get_aff_link_and_flag` 432, `::get_country_by_url` 1194 |
| The ASIN | `::get_product_id_by_url` 1292, `::get_amazon_product_key_by_url` 1261 |
| The tag and the stored form | `::get_amazon_product_url` 1468-1585, `::keep_args` 1431, `::format_amazon_url` 2549 |
| Search pages | `::is_amazon_search_page` 2394, `::get_search_page_title` 2472, `::get_amazon_search_match_key` 2443 |
| Protected attribution links | `classes/class-helper.php::is_amazon_sponsored_url` 6956 |
| Where an Amazon link is requested | `classes/class-helper.php::get_redirect_final_target` 368-575, `::persist_amazon_shortlink_final` 577 |
| Query building | `classes/class-helper.php::add_params_to_url` 883, `::get_query_from_array` 830, `::build_url_parameter_string` |
| The live data | `tests/fixtures/inventory/products.json` and `settings.json` (Task 4) |

**What this feature is:** what "Add A New Link" (F18) does when the link is Amazon's: which links count as Amazon,
what is stored as the product's link (the tag, the query), which fields mark it as an Amazon product, how a second
add of the same product is found, what a short link becomes, and the name a new Amazon product starts with. It does
not cover Amazon product data (title, image, price, Prime: client point C1), the product page's Amazon fields
(Task 14), changing the link on an existing product (Task 15), or rewriting Amazon links in post content
(auto-monetization, link rewriting).

## 1. What the live site holds

- **233 Amazon records** (`url_details.product_type = amazon`), all country `us`: 228 on `www.amazon.com`, 1 on
  `amazon.com`, 4 `amzn.to` short links. 226 are "Amazon Product" (`affiliate_link_type`), 7 "Basic Link".
- **225 product links keep their path as pasted**, slug included, with `tag` first and Lasso's kept parameters after
  it: `https://www.amazon.com/Mikasa-MGV500-Weight-Volleyball-Official/dp/B00091PQ72?tag=vbvault-20&ref_=as_li_ss_tl`.
  None is reduced to `/dp/<ASIN>`. No path holds `/ref=`.
- **4 search pages** (`/s?k=…`) keep their whole query (`k`, `crid`, `sprefix`, `linkCode`, `linkId`, `language`,
  `ref_`) with the site's tag.
- **The 4 short links** were kept by product-page edits (Lasso keeps a short link only when an edit leaves the ASIN
  the same, `save_lasso_url`); a new add stores the short link's destination (section 5). Their `lasso_final_url`
  holds the destination.
- Settings: `amazon_tracking_id` `vbvault-20`, multiple tracking IDs on, whitelist `[vbvault-20]`, shorten Amazon
  links on, `check_duplicate_link` on, default tracking country `us`.

## 2. Which links are Amazon's

1. **An Amazon link** is one whose host, without `www.`, is in `get_domains()`: the 18 stores (`amazon.com`, `.ca`,
   `.co.uk`, `.com.au`, `.com.br`, `.com.mx`, `.fr`, `.de`, `.it`, `.in`, `.es`, `.cn`, `.co.jp`, `.nl`, `.se`,
   `.sg`, `.com.tr`, `.ae`), the short links `amzn.com`, `amzn.to`, `amzlink.to`, the embed host
   `amazon-adsystem.com` and `smile.amazon.com`. Built in 11D as `AffiliateUrl::is_amazon()`.
   Lasso strips "www." with `ltrim( $host, 'www.' )`, which removes any leading `w` and `.` characters; Affibloom
   removes the prefix only (no host in the list starts with `w`, so nothing changes).
2. **A short link** is an Amazon link whose text contains `amzn.to`, `amzn.com` or `amzlink.to`
   (`is_amazon_shortened_url`; Lasso Hub's `lasso.to` links are Hub's and have no Affibloom equivalent).
3. **Its ASIN** comes from Lasso's pattern on the store's own host:
   `https?://(www\.)?<host>(/.*)?(/gp/aw/d/|/d/<x>/<y>/|/dp/|/clp/|/gp/product/|/ASIN/|/gp/video/detail/)` followed by
   letters and digits. The last such marker in the path counts. The ASIN is kept as written (Lasso does not change
   its case or check its length). No marker, or nothing after it, means no ASIN.
4. **Its country** is the store's code from `get_aff_link_and_flag()` (`www.amazon.com` → `us`, `.co.uk` → `gb`,
   `.de` → `de`, …); anything else is `us`.
5. **An Amazon product link** has an ASIN. **A search page** has no ASIN, a path ending in `/s` or containing `/s/`,
   and a `k`, `field-keywords` or `keywords` parameter (in that order of preference): its keywords. Every other
   Amazon link is **an Amazon page that is not one product** (a store, a list, a category).

## 3. What is stored as the link (`get_amazon_product_url`, monetize on)

In this order:

1. `smile.amazon.` becomes `amazon.` in a product link (`format_amazon_url`): `https://smile.amazon.com/dp/X` →
   `https://amazon.com/dp/X`.
2. **Protected attribution links are left exactly as pasted** (`is_amazon_sponsored_url`): a `maas` parameter with
   `tag=maas`; a `campaignId` starting `amzn1.campaign.` (Creator Connections); `linkCode=tr1` with a `linkId`.
3. **The tag.** The pasted `tag` stays if it is on the whitelist (the whitelist counts only while "Allow Multiple
   Tracking IDs" is on; `maas` is always allowed). Otherwise the site's Tracking ID replaces it. With no Tracking ID
   set, the pasted tag (or none) stays. Lasso's other exception, a tag saved as a URL variant, belongs to the URL
   variants subsystem (none exist on the site).
4. **A product link**, with "Shorten Amazon affiliate links" on (default and live): the path as pasted, then `?`,
   then `tag=<tag>` (left out when there is no tag), then each of Lasso's kept parameters that is present, in this
   order: `maas`, `ref_`, `s`, `aa_campaignid`, `aa_creativeid`, `aa_adgroupid`, `campaignId`, `linkCode`,
   `linkId`, written `name=value` as parsed. Everything else in the query goes. With `maas` present the tag is left
   out. With the setting off: the whole query is kept and `tag` is set (added at the end, or replaced in place),
   values re-encoded with `rawurlencode`.
5. **A search page or another Amazon page**: the whole query is kept and `tag` is set, as with shorten off. A page
   with no query and no tag to set stays as it is.
6. Short links are not changed here (section 5 replaces them first).

## 4. What marks a product as Amazon's

| Lasso | When | Affibloom |
|---|---|---|
| `url_details.product_type = amazon` | the final link is any Amazon link | `ProductType::Amazon` (`_affibloom_type = amazon`) |
| `amazon_product_id` meta, `url_details.product_id` | the final link has an ASIN | `_affibloom_asin` |
| `lasso_amazon_product_country` meta, `url_details.country` | with the ASIN | `_affibloom_amazon_country` |
| `affiliate_link_type = Amazon Product` / `Basic Link` | ASIN or not | derived: Amazon with an ASIN |
| `url_details.search_text` (+ `base_domain`) | a search page | `_affibloom_search_key` = `<host without www.>|<keywords>` (section 7) |

## 5. Short links (`lasso_add_a_new_link` 858, `get_redirect_final_target`)

- Lasso follows a short link (one `wp_remote_get`, redirects followed) and **stores where it ends**, then treats
  that link as if it had been pasted (sections 3, 6, 7). The short link itself is not kept on a new product.
- **When it cannot be followed** (no answer), Lasso's cloud is asked; without an answer the short link itself is
  stored unchanged, typed Amazon, named "Amazon". Affibloom has no cloud, so no answer means that last case.
- **Affibloom never requests an Amazon store page** (the Associates licence; decision 11, overview C1). It requests
  the short link's own host and stops at the first redirect into an Amazon store, as 11D's fetcher already stops
  before Amazon: the redirect's `Location` is the destination, and the product page itself is never fetched.
- Lasso keeps a map of short link → destination (`lasso_amazon_shortened_url` table and an option per short link)
  so that a short link is followed once. Its readers that matter are the content scanner and link rewriter; for an
  add, pasting the same short link again follows it again and finds the product by its ASIN (section 7), the same
  result. The map is built with link rewriting; migration copies Lasso's table (Task 26).

## 6. The name of a new Amazon product

- **Lasso:** "Amazon" (`DEFAULT_AMAZON_NAME`) until its cloud returns the product's title; a search page whose cloud
  title is empty gets `get_search_page_title()`, "Amazon: <keywords>".
- **Affibloom** (no cloud, no request to Amazon; **the user's decision 11**, 2026-11-12, Task 6):
  - a product link with a slug before `/dp/` starts with that slug as words: `/Mizuno-Wave-Momentum-3/dp/…` →
    "Mizuno Wave Momentum 3" (hyphens and `+` as spaces, percent-decoding, spaces collapsed, case as written);
  - a search page starts as Lasso's own "Amazon: <keywords>" (keywords decoded, spaces collapsed; Claude's call,
    as F18 D3: Lasso's wording for exactly this page, without its cloud);
  - everything else is "Amazon".
  C1 replaces the starting name with the catalogue's title once the client's credentials exist.

## 7. The same Amazon product twice (`is_lasso_url_exist`, `get_lasso_post_id_by_url`)

With `check_duplicate_link` on (11B; the off mode is built with the General settings pass), in Lasso's order, each
step only when the one before found nothing, published products only:

1. The pasted link (after the repair, the short link's destination and `smile.`) against stored links, as 11B.
2. **An Amazon link with an ASIN:** a published Amazon product with the same ASIN **and the same country**
   (`get_lasso_id_by_product_id_and_type`). `amazon.com` and `amazon.co.uk` are different products, as in Lasso.
3. **A search page:** a published product whose link is a search page with the same keywords (decoded, trimmed,
   spaces collapsed) on the same store host (`get_amazon_search_match_key`). Lasso compares its stored `search_text`
   column; Affibloom stores the same key with the product (`_affibloom_search_key`).
4. **An Amazon page that is not one product** (search pages included): the link with Lasso's tag rule applied
   (section 3), against stored links (`is_lasso_url_exist` 784-787, after steps 1-3 found nothing).

Steps 2-3 run inside Lasso's `get_lasso_post_id_by_url` after its URL lookups, step 4 in `is_lasso_url_exist`
afterwards; steps 2 and 4 never apply to the same link.
5. A match opens it as F18 says (11B: today the Dashboard with the info toast; Task 14: the product page).
6. **Lasso's re-title:** when the match is an Amazon product whose name contains "Sorry!" or " wrong!" (Lasso's
   cloud names an Amazon error page so), it is renamed `get_search_page_title()` of the new link and stays
   published. Lasso's operator precedence also renames non-Amazon products whose name contains " wrong!" to
   "Amazon"; Affibloom applies the rule to Amazon products only (an improvement: Lasso's own comment says Amazon).
   No live product has such a name; migrated ones could (Task 26).

## 8. Additions beyond Lasso, from the approved records

- **A bare ASIN** (F2 AC2, Task 6): `B0` and 8 letters or digits, or an ISBN-10 (9 digits and a digit or `X`), on
  its own, becomes `https://www.amazon.com/dp/<ASIN>` and is then handled as a pasted product link. Lasso refuses it
  (`add_https` adds no scheme to a value with no dot, so it is "This is not a valid link."). The store is Lasso's
  default tracking country, `us` on the site (`get_default_product_domain`); its control is in C1's Product Data
  API card, so until C1 it is `www.amazon.com`. An ordinary ten-letter word is not taken for an ASIN.
- **The starting name from the slug** (section 6, decision 11).

## 9. Where the first version and the plan's Task 12 were wrong

| First version / plan | Lasso and the live data |
|---|---|
| AC1: every Amazon link becomes `https://<host>/dp/<ASIN>?tag=vbvault-20` | the path is kept as pasted, with `tag` first and `ref_`, `linkCode`, `linkId`… kept (225 of 225 live product links) |
| "Exactly one tag, the site's, which replaces any other tag" (overview) | a whitelisted tag is kept while multiple tracking IDs are on (F17, 10F built the whitelist) |
| The duplicate key `amazon:<ASIN>` "ignores the marketplace on purpose" | Lasso matches ASIN **and** country |
| Without a Tracking ID the add is refused (`no_tracking_id`) | Lasso adds the link, untagged or with the pasted tag; nothing is refused |
| An unresolved short link is refused (`short_link_unresolved`) | Lasso stores the short link as pasted, named "Amazon" |
| AC4: the same ASIN twice shows "This Product Already Exists" in the dialog | the existing product is opened (F18 D2, 11B) |
| A short link is resolved with HEAD requests by a new `WpShortLinkResolver` | Lasso uses GET; Affibloom reuses 11D's `WpPageFetcher`, which already stops before Amazon |

Following Lasso in each removes nothing Lasso does, so these are settled from Lasso (the working rule recorded in
`progress.md`): the two refusals are dropped, the path is kept, the whitelist and the country count.

## 10. Owned elsewhere, so nothing is lost

| Behaviour | Where Lasso does it | Planned with |
|---|---|---|
| title, image, price, Prime, features of a new Amazon product | cloud / Creators API | C1 |
| the Marketplace tab's Amazon search | `url-add.php`, Creators API | C1 (F18 D5) |
| keeping a short link when an edit leaves the ASIN the same; `shares_same_amazon_asin` | `save_lasso_url` | Task 15 |
| the Amazon Image field | product page | Task 14 (decision 11) |
| the short link → destination map | `lasso_amazon_shortened_url` | link rewriting; Task 26 copies it |
| a tag saved as a URL variant counting as allowed | `is_tracking_id_from_saved_url_variant` | URL variants (audit) |
| adding a pasted foreign tag to the whitelist | `maybe_auto_whitelist_amazon_tracking_id_from_url` (content scan only) | auto-monetization |
| replacing blacklisted tags, `force_change_from_link_details` | link rewriter | link rewriting |
| SiteStripe and AAWP links | `get_site_stripe_url`, import | link rewriting, Import |
| `check_duplicate_link` off: the final link without its query | `is_lasso_url_exist` | General settings pass (F18) |
| Lasso Hub (`lasso.to`) short links | Hub | not rebuilt (Lasso cloud, F18 D5) |
| the default tracking country's control | Product Data API card | C1 |

## 11. Acceptance criteria

`tests/E2E/acceptance/F2.spec.ts`, each with a random ASIN so no earlier run's product is a duplicate. External
HTTP is blocked on this copy, so AC7 sees the unresolved short link; the followed one is checked by unit tests and
by hand (analysis, risks).

- **AC1** A product link keeps its path and gets the site's tag first:
  `https://www.amazon.com/Mizuno-Wave/dp/<A>/ref=sr_1_3?keywords=shoe&ref_=x` →
  `https://www.amazon.com/Mizuno-Wave/dp/<A>/ref=sr_1_3?tag=vbvault-20&ref_=x`; typed Amazon, ASIN and country
  stored.
- **AC2** A bare ASIN becomes `https://www.amazon.com/dp/<A>?tag=vbvault-20`.
- **AC3** Another tag is replaced (`?tag=someone-20&th=1` → `?tag=vbvault-20`); a whitelisted tag is kept.
- **AC4** The same ASIN on the same store, pasted in another form (`/gp/product/<A>`), opens the existing product
  with "This product already exists…", and no second product is made; the same ASIN on `amazon.co.uk` is a new one.
- **AC5** The starting name: "Mizuno Wave Momentum 3" from the slug; "Amazon" without one; "Amazon: nike kd14" for
  `https://www.amazon.com/s?k=nike+kd14`.
- **AC6** A search page keeps its whole query with the site's tag; the same search pasted again is the existing
  product.
- **AC7** A short link that cannot be followed (external HTTP blocked) is stored as pasted, typed Amazon, named
  "Amazon". (A followed one is stored as where it leads: unit tests and the check by hand.)
- **AC8** A protected attribution link (`?maas=…&tag=maas`) is stored exactly as pasted.
