# Lasso plugin analysis (source-code review, 8 Nov 2026)

> **Partly out of date (2026-11-14).** Written before the full-scope rule (2026-11-13). Where it conflicts with `docs/features/F10, F21-F27, F35/requirements.md` or the phase plans, those win. Known stale parts: the "Not used (14)" list (auto-monetize, cloaking in content, tables, price) is all in scope now; the 242 Amazon products count; "the settings option and 30 custom tables were not readable" (F10's inventory read 29 tables and every option); the "Needs verification (7)" items were verified elsewhere and never updated here.

> Local copy for Claude Code of the claude.ai project doc `claude/lasso-plugin-analysis.md`, 2026-11-12.
> Full report: https://claude.ai/artifact/25FT6Ba8jCNffBuEGMFoMV

Basis: Lasso v369 source (528 first-party files), the plugin's own job logs (5–8 Nov 2026), and a scan of all 148 published posts/pages on volleyballvault.com. Lasso settings option and its 30 custom DB tables were not readable.

## In use on VolleyballVault (18)
- Link library: 263 lasso-urls records (242 Amazon, 21 other); 195 referenced on live pages
- Amazon product data from Lasso's cloud scraper (no Amazon API keys set), fetched once at creation
- Custom fields/ratings (3 boxes show stars)
- [lasso] shortcode + Gutenberg block affiliate-plugin/lasso: 250 boxes on 34 posts
- Single product box only; Lab theme + Display v2 layout; per-box badge/description; second button on 78 boxes; in-box disclosure
- Link scanner/content rewriter (save + every 10 min): 3,530 stamped links on 147 URLs; link location index rebuilt nightly
- Link defaults (nofollow, new tab); link health checks nightly (~200 links)
- Lasso click/performance tracking (LSJS via /js/e -> codedrink.com); Lasso GA click events (ID 371244810 looks wrong)
- Boosting/"matching", click sub-IDs, hourly cloud->site link sync; site->cloud data sync
- Platform services (licence, updates, flags, Sentry); side effects (sitemap exclusion, missed-schedule republisher, Kadence FAQ fix)

## Not used (14)
Creators API, Amazon price refresh, Amazon auto-monetize, price/timestamp/pros-cons in boxes, grid/list/gallery, comparison tables (draft only), button/image/AI-text displays, product schema, brag link, Classic/Elementor/AMP integrations, other plugins' shortcodes, link cloaking in content, imports, smart CSS loading.

## Needs verification (7)
Affiliate network auto-detect (likely on), URL variants, groups, post content history, opportunities/keywords, Amazon deep linking/international clicks, cloud REST/MCP routes.

## Key dependency for a replacement
The 250 boxes are saved as [lasso id link_id ref badge description] shortcodes; a replacement must render them (or convert the blocks) from lasso-urls post meta + lasso_amazon_products + lasso_fields.

Fixes regardless: 404 link /recommends/adidas-dame-8-all-volleyball/ on best-volleyball-shoes-for-hitters; Lasso GA tracking ID.
