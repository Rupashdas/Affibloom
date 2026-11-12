# Client points and UI/UX improvements

Date: 2026-11-12

## Client points (feature plan, "Points to confirm before development")

Decided by the user on 2026-11-12: Lasso supports all three, so all three are in scope. Each became a task (C1–C3)
with its own requirements and analysis in `docs/features/C<n>/`. Each task is written out in full and approved at
gate 4 before work starts.

| Point | Decision | Task | How the live site uses it (2026-11-12) |
|---|---|---|---|
| 1. Fetch the title and image of new Amazon products | Yes | C1, after Task 12 | On, through Lasso's cloud (`lasso.link`); Creators API credentials are empty. Affibloom uses Amazon's Creators API, so it needs the client's credentials. |
| 2. Detect affiliate links automatically | Yes, automatic as in Lasso (U8 rejected); the two networks the site uses (decision 8) | C2, after Task 35 | On; 16 links detected since 2024, all turned into products. |
| 3. Send clicks to Google Analytics | Yes, with Lasso's event names | C3, after Task 33 | On; Lasso's own ID `371244810` is not a GA4 ID, but its events most likely reach the site's GA4 tag `G-K7T3M99G7Q`. |

**For the client to do:**
- C1: apply for Amazon Creators API credentials for the `vbvault-20` account, and send them when granted.
- C3: in GA4 (Reports → Engagement → Events), check whether `Lasso Display Primary Click` appears, and say whether
  any report or conversion uses these event names.

## UI/UX improvements register

Each item is built exactly as Lasso has it unless approved. **Decided 2026-11-12**; reasons in `task-6-decisions.md`.
Findings from the UI audit (roadmap 1.7, `docs/ui/screen-inventory.md`) are in the table.

| ID | Change | Before (Lasso) | After (if approved) | Audit finding | Decision |
|---|---|---|---|---|---|
| U1 | Block opens straight to product search | "Choose a Display Type" with 8 types, then Single, then search (6 steps) | search first (5 steps) | Only Single is built | **Approved** |
| U2 | Text labels on Dashboard filters | red icon + count, tooltip "See broken URLs" | "Broken links (3)" | Tooltip is hover only | **Approved** |
| U3 | Help text by keyboard and screen reader | hover tooltips on labels | focusable help, short line under key fields | Confirmed: tooltips never take focus | **Approved** |
| U4 | "Re-check now" on a broken product | wait for the nightly check | one click | — | **Rejected**: not required; nightly check and `check-links --product` |
| U5 | 7/30/90-day period for Clicks | fixed 30 days | period selector | — | **Rejected**: not required; Lasso's 30 days kept |
| U6 | Plain success wording | "Yee haw! Your … saved." | "Product saved." | Wording confirmed in `save-success.php` | **Approved** |
| U7 | Warning on leaving with unsaved changes | — | — | **Lasso already warns** (product page and Settings) | Parity: built anyway, no decision needed |
| U8 | Detected links offered before they are added (C2) | added as products without asking | notice on the post's edit screen: "Create products for these links" | Lasso created 16 products this way | **Rejected**: Lasso adds them automatically and the site uses all 16 |
