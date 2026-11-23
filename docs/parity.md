# Migration parity: Affibloom's box against Lasso's baselines

Task 26P (F10 AC24). `bash tools/local-wp.sh eval-file tools/compare-migration.php [fresh]` draws Affibloom's box for
each of the 250 boxes in `tests/fixtures/baselines/boxes.json` (what Lasso's box showed on the live site) and compares
title, badge, description, image, rating, Prime, disclosure and each link's href, text, `rel` and `target`. Every
difference is fixed at its cause or written here.

## Run of 2026-11-23 on Local

| Mode | Result |
|---|---|
| `fresh`: each product made now from its Lasso record by the products family's mapper, through Affibloom's API, as a migration on a site without Affibloom makes it (production) | **250 boxes: 238 equal, 12 different**, all explained below |
| default: the product the migration linked on Local | 250 different: Local's products were adopted from the one-time import with their own values (see "Local only") |

## Fixed at the cause

| Difference | Cause | Fix |
|---|---|---|
| Prime missing on 242 boxes (`fresh`) | The mapper took Prime from the cached Amazon row's `is_prime`; Lasso's box shows the logo for every Amazon product while its setting is on (`is_prime => enable_amazon_prime && amazon_id`) | `LassoRecordMapper`: Prime for every Amazon record with an ASIN; the setting moves with the settings family. Test: `LassoRecordMapperTest::test_every_amazon_record_shows_prime_as_lasso_s_box_does_whatever_its_cached_row_says` |
| `lasso-br-code` printed in 13 descriptions | Lasso's line-break code in a shortcode's description | Content conversion rewrites it to Affibloom's `-br-` (LM2, as planned); the tool applies the same rewrite |

## Explained, not differences of the migration

| Difference (`fresh`) | Boxes | Why |
|---|---|---|
| Title: Lasso "Asics Sky Elite FF Men s Volleyball Shoes", ours "Men’s"; "Tachikara TB-18 The Setter", ours "“The Setter”" | 5 | Lasso's box replaces every character outside plain ASCII punctuation with a space (`remove_unexpected_characters_from_post_title()`), so readers see "Men s"; Affibloom prints the stored title. Kept on purpose: the user, 2026-11-23, deviation 162 |
| Link href: `/aircat/` against `/aircat-volleyball-serving-machine/` and three more | 4 | A product made fresh gets its name's slug; the slugs family (26G), which runs after products, gives it Lasso's address. In the default mode, after 26G, these links match |
| Rating: Lasso 4.5 or 3.5, ours none | 3 | The Primary Rating is a custom field value, moved by the fields family (26E) after products; `fresh` does not run it |

## Local only: products adopted from the one-time import

On Local, 252 products came from Affibloom's earlier one-time import and were adopted by the products family (26A) with
their own values. 247 of them have New Window / Tab off where Lasso has it on (`open_new_tab = 1` on all 256 records),
so Button 1, the title and the image open in the same tab with `rel="nofollow"` instead of a new tab with
`nofollow noopener`; a few have another rating, link or Prime. Lasso has not changed since the adoption, so
`--update --force` changes nothing (26B: `--force` takes Lasso's value only over an Affibloom edit made after a Lasso
change). Production has no one-time import: there the products family creates every product from Lasso's values, as
the `fresh` mode shows. Whether `--force` should also put Lasso's values back on adopted products (Local, and staging if
the one-time import ran there) is open: a change to 26B's rule, for the user to decide.

**Settled 2026-11-23 (the user, deviation 161):** `--update --force` gives an adopted product Lasso's values, its Amazon
price kept. Local ran it: 254 products restored (New Window 252, Prime 30, URL 13, a few names, images, one
description, disclosure, theme); a second run changes nothing. Staging needs the same run (its one-time import, deviation
87).

## Task 29A: every box on every guide, Lasso off (2026-11-23)

`npx playwright test tests/Visual/parity.spec.ts` migrates, converts the content (LM3-LM7), switches Lasso and the
migrator off, and reads every box of the 34 guides in the browser against `tests/fixtures/baselines/boxes.json` (title,
badge, description, image, rating, Prime, disclosure, each link's href, text, rel and target); afterwards it puts the
site back (every conversion reverted, Lasso on).

| Run | Differences |
|---|---|
| First, Local's products as adopted | 973: 728 New Window (`target`), 182 quotes and dashes, 27 `rel`, 21 links, 6 Prime and rating, 9 titles |
| After the fix below and the restore (deviation 161) | 7, the titles above, then the ratings below (listed in the spec's `EXPLAINED`) |

### Fixed at the cause

| Difference | Cause | Fix |
|---|---|---|
| "Women’s", "adidas’" and other curly quotes and dashes in 182 descriptions, button texts and a badge | A block draws at `do_blocks` (the_content 9), before WordPress's `wptexturize` (10); Lasso's block drew its box through its shortcode, after it (11), so its text stayed as typed | `ProductBoxBlock::past_texturize()`: inside the_content the drawn box goes through the `[affibloom_box_html]` shortcode, which `wptexturize` skips (`no_texturize_shortcodes`) and `do_shortcode` unwraps; elsewhere it is returned as drawn. Test: `ProductBoxBlockTextureTest` |

### Explained, not differences of Affibloom

| Difference | Why |
|---|---|
| `rel="nofollow sponsored noopener"` on two boxes | Rank Math's link attributes (the_content 11, on `wp`) add `noopener` to an external link without it: with the adopted New Window off the box printed `nofollow sponsored`; with Lasso's New Window on it prints `nofollow noopener sponsored` and Rank Math leaves it |
| Rating 4.5 missing on ASICS Sky Elite FF 3 (two guides) | Local only: the product had no field values while Lasso's record has a Primary Rating of 4.5; the fields family keeps Affibloom's differing values without `--update` (26E). `migrate --only=fields --update` gave it and two other products theirs |
| Rating 3.5 on adidas Court Stabil (two guides), Lasso none | Local only: the product has a rating Lasso's record does not have, set on this copy; a migration gives a product only Lasso's field values. In the spec's `EXPLAINED` |
| Titles: "Men’s", "“The Setter”" (5 boxes, title and link text) | Lasso's box turns curly quotes and apostrophes into spaces (above); Affibloom prints the title as stored (deviation 162). In the spec's `EXPLAINED` |

Production's Lasso boxes stack the image above the title with the badge at top left; Local's Lasso and Affibloom
both draw them side by side (Local's Lasso display settings differ from production's). A migration from production
carries production's settings, so the suite compares Local with Local.

## Task 29B: every guide, whole, at 1280 and 390 px (2026-11-23)

The suite captures Lasso's pages from this site with Lasso on, before converting (`PARITY_CAPTURE=pages`; the
2026-11-12 capture was older than the site's content since), and compares each converted page signed out, lazy
images loaded, at `maxDiffPixelRatio` 0.01 (roadmap, 2026-11-12). **65 of 68 pages match.** The other 3 are not
compared (a page of another height fails whatever the tolerance) and are noted on the test, each box measured:

| Page | Box | Why |
|---|---|---|
| best-weighted-volleyballs, 1280 | Tachikara TB-18 “The Setter” 431 → 469 px | The title keeps its quotes and wraps to two lines (deviation 162) |
| best-weighted-volleyballs, 390 | the same box | The same title (deviation 162) |
| best-volleyball-shoes-for-setters, 390 | ASICS Gel-Rocket 12 771 → 755 px | Its shortcode has `title_type="Best Lightweight"`; Lasso prints it as a `<best lightweight>` tag around the title, whose margin adds 16 px. Affibloom takes only heading tags (R79) |

## Task 29C: every link, the cloaked addresses, and no call to Lasso (2026-11-23)

The suite captures, with Lasso on (`PARITY_CAPTURE=links`), every `<a href>` of each guide's article and each
own-site address's redirect (`tests/fixtures/baselines/links-all.json`: 2,302 links in 34 guides; 93 own-site
addresses: 87 pages, 4 cloaked redirects, 2 already 404), then compares with Lasso off. **Every link goes where it went,
the 4 cloaked addresses redirect as before, and no page requests anything from `codedrink.com`, `lasso.link` or
`getlasso.co`** (requests are logged even where the tracker block aborts them).

The suite also compares destinations (an Amazon link by its store, ASIN and tracking ID; a cloaked address by its
redirect; any other without a trailing slash) and notes on the test any link that reaches the same place spelled
another way. **None does since Task 29D.** The 72 first noted (36 pairs: `/dp/ASIN?tag=` against `/dp/ASIN/?tag=`, long
against short Amazon URLs, a cloaked address against its target) were not Lasso's: the capture ran with Affibloom on
beside Lasso, and Affibloom's render-time link rules rewrote the links Lasso's boxes draw. 29D fixed that and the
baseline was captured again.

## Task 29D: parity survives conversion and revert (2026-11-23)

`PARITY_MODE` switches the suite: `converted` (migrate, convert, Lasso and the migrator off) and `reverted` (the same
conversion, then every place reverted in reverse order and Lasso on again, Affibloom still on). **Both pass on Local**:
the boxes, the 68 pages and the 2,302 links, with only the differences above (`converted`) and none at all (`reverted`).

- **Fixed at the cause:** with both plugins on, Affibloom rewrote the links of Lasso's own boxes (143 differences in
  `reverted`). Affibloom now asks the filter `affibloom_leave_link` about each link (it names no plugin); the migrator
  answers yes for a link carrying `data-lasso-box-trackable`, so Lasso's boxes are left as Lasso draws them and are not
  counted against the text's links.

## Task 29E: rollback drill (2026-11-23)

`tools/rollback-drill.sh` takes Lasso's fingerprint and the content's, migrates and converts, goes back as
`docs/cutover/rollback.md` says (revert everything, Affibloom and the migrator off, Lasso on) and takes both again.
**Both are equal** (2 min 54 s). Then `PARITY_MODE=lasso` checks the guides with Lasso alone: the boxes and the 68 pages
pass; the links pass with two answers that are Lasso's own, where the links baseline (taken with Affibloom on) holds
Affibloom's:

| Where | Lasso alone | Affibloom |
|---|---|---|
| best-shoes-for-grass-volleyball, link 67 | prints the text link's href as written, a space before `?tag=` (`%20`) | the tied product's link, no space |
| `/mizuno-neo-jump-unisex-volleyball-shoe-for-sale/` | a product Lasso deleted and repointed: 302 to the home page | 302 to the product it was repointed to |

Both are Lasso's data as it was (the fingerprint is unchanged); the spec names them (`LASSO_ALONE`) for `lasso` mode.

## Task LM10: Affibloom alone, after Finish (2026-11-24)

On Local the migration was finished from the page and the migrator uninstalled; `PARITY_MODE=finished` checks the site as
it is, without setting anything up or putting anything back. **The boxes, the 68 pages and the 2,302 links pass**, with
the converted site's differences above.
