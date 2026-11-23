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
| Title: Lasso "Asics Sky Elite FF Men s Volleyball Shoes", ours "Men’s"; "Tachikara TB-18 The Setter", ours "“The Setter”" | 5 | The baseline capture dropped curly quotes and apostrophes; the stored names have them, and so does Lasso's box on the live page |
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
| After the fix below and the restore (deviation 161) | 7, all the titles above whose quotes the baseline dropped (listed in the spec's `EXPLAINED`) |

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
| Titles: "Men’s", "“The Setter”" (5 boxes, title and link text) | The baseline's capture dropped curly quotes (above). In the spec's `EXPLAINED` |
