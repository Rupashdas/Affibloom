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
