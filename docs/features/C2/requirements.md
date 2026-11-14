# C2 Automatic affiliate link detection: requirements

Status: In scope (the user's decision, 2026-11-12: Lasso supports it, so it is included). Decided 2026-11-12:
automatic creation, as in Lasso (U8 rejected); the networks the site uses, Impact and Post Affiliate Pro (decision 8).
**Rewritten 2026-11-14 from a sweep of Lasso 369** (F35): C2 is the first slice of **F35 Auto-monetization**
(`docs/features/F35/requirements.md`, whose rules R1-R18 and decisions D1-D9 this slice builds; the Amazon half is
F35's Tasks AM1-AM6). **Decision 8 is superseded:** it was written before the full-scope rule of 2026-11-13, which
puts every network Lasso knows in scope, and the later rule governs (F35 D1): all 32 auto-detected networks and the
37-entry registry are built. Tasks C2A-C2C; gate 4 for them: waiting.

## User stories

- As an editor, when I publish a post containing an affiliate link from a known network, the link appears as a
  product in the library, so its clicks, health and locations are tracked like any other.
- As the site owner, I can switch the detection off in Settings → General.

## Acceptance criteria

- **AC1** With `Auto-Detect Affiliate Links` on, saving a published post that contains an Impact link with no
  product creates one product for it, with the site's link defaults. (corrected 2026-11-14: Lasso names it through its
  add path, not from the link text, keeps the link as written as its Primary URL, creates it with link cloaking off,
  and does it in a background run, not in the save — F35 R13-R14, D5, D7; the product is a "Basic Link" (`other`);
  the name follows F18 D3 unless the user decides otherwise, F35 N1; the E2E test runs the due cron event after the
  save.) (C2C)
- **AC2** Saving the same post again creates nothing new; a link that already has a product is never duplicated (it is
  recorded once and tied to that product). (C2B, C2C)
- **AC3** The post's content is byte-for-byte unchanged after detection. (Lasso's later scan does rewrite the anchor,
  F35 R15; Affibloom prints the same at render time, F35 D6.) (C2B)
- **AC4** Links that match no known network (internal links, plain shop links, Amazon links) create nothing. (clarified
  2026-11-14: Amazon links are never *detected*; with Amazon Auto-Monetization on, F35's Amazon import makes Amazon
  products on its own 5-minute schedule, F35 AC1.) (C2B)
- **AC5** With the setting off, saving a post creates nothing. (and records nothing, F35 D23) (C2B)
- **AC6** Each of the 32 auto-detected networks recognises Lasso's example URL and records its stored name, affiliate
  ID, advertiser ID, deep link and domain exactly as Lasso's class does (F35 Appendix B); each of the 37 registry
  entries matches its examples and not the site's other links; the four registry-only networks (Everflow, Aragon,
  Commission Factory, FirstPromoter) are affiliate links but are not detected. `wp affibloom links detect <url>` prints
  the match. (C2A, F35 R2-R9)
- **AC7** A detected link is stored once per URL in `affibloom_detected_links`, and the product made for it is
  recorded on the row; a link already a product is tied to it without a new product. (C2B, C2C, F35 R10-R11, D4)
- **AC8** `wp affibloom links rebuild` detects network links in every indexed post, as Lasso's scan does; a daily run
  makes products for rows still without one (at most 100 a run), including rows whose product was deleted for good.
  (C2B, C2C, F35 R12-R13, D3, D5)
- **AC9** On the front end a detected link is tied to its product and printed with the product's link, `rel` and
  `target` by F21's render filter (35H), while `post_content` is unchanged. (C2C with F21 35H, F35 D6)

## Decisions kept

- U8 ("ask first") rejected 2026-11-12; products are made without asking (Lasso's behaviour, F35 R13).
- Decision 8 (two networks) superseded 2026-11-13 by the full-scope rule (above).
- Never change the post content (the standing rule; F35 D6).

The rules, data, edge cases and the user's open choice on naming (F35 N1) are in `docs/features/F35/requirements.md`.
