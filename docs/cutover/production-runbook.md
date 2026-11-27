# Production cutover runbook (Task 41)

For the person moving volleyballvault.com from Lasso to Affibloom by hand (the user, 2026-11-24; deviation 166). Every
step was run twice on staging ([`staging-log.md`](staging-log.md)) and once on a copy of the live site
([`rehearsal-log.md`](rehearsal-log.md)); the figures to expect are staging's. Everything happens in wp-admin; no SSH
or WP-CLI is needed. Write each step's time and result in the table at the end. Two people: one runs the steps, one
watches the site.

**Stop and go back** ([Rollback](#rollback)) when a check in steps 6-7 fails and cannot be fixed within 30 minutes.

## What you need

- **The releases:** Affibloom **1.0.3** (`wp-content/plugins/affibloom/.local/affibloom-1.0.3.zip`)
  and the migrator **1.0.0** (`.local/affibloom-lasso-migrator-1.0.0-add8193.zip`). The migrator and the boxes are
  2026-11-26's, rehearsed on a copy of the live site that day ([`rehearsal-log.md`](rehearsal-log.md), third run); 1.0.2
  adds the editor fixes of 2026-11-27 and 2026-11-28 (the block named Affibloom, the Quick Detail's layout, its live title and image,
  + Add where WordPress isolates the editor's page, deviation 169, and the read-only fields' look). They
  carry staging's two fixes (S1 the link index on this host, S2 the posts' "Updated on" dates) and the dead settings
  removed on 2026-11-25 (deviations 167, 168). Do not use an older zip: staging's rc.1 builds lack the last of these.
- **A low-traffic window** of about two hours (the analytics' quietest hours).
- **Nothing from staging:** staging's snippet "Staging only: no mail, no Lasso cloud" must not be copied to the live
  site, and "Discourage search engines" stays off there.

## The day before

1. **Freeze product edits in Lasso** and tell the editors: no product, group or table changes until step 8.
2. **Backup:** a full production backup from the cutover day, and a restore of it tested by the host. Note its name.
3. **Fresh baselines** (Claude, on your word; about 35 minutes, nothing on the live site): put that day's backup on
   the Local site `volleyballvault-testing` (All-in-One WP Migration → Import), start the site in Local, and tell
   Claude. Claude keeps the copy's safety lines in `wp-config.php`, switches Mediavine and LiteSpeed off there, and
   captures what Lasso shows on the copy:
   - the inventory (`tools/inventory.php`): records, boxes, tied links, `.lasso-*` CSS rules;
   - every box's values (`tools/baselines.config.ts` → `boxes.json`);
   - every article link and cloaked address's redirect (the parity suite, `PARITY_CAPTURE=links`).
   They stay in `.local/` (the committed fixtures are Local's) and are what step 6 compares with. Lasso refreshes its
   Amazon data daily, so they are taken as close to the cutover as possible.

## On the day

### 1. Check before you start

- Plugins: Lasso 370, LiteSpeed Cache and Mediavine active; no Affibloom.
- The backup of "The day before" is there.

### 2. Install beside Lasso

1. Plugins → Add Plugin → Upload Plugin: the Affibloom zip → Install Now → **Activate**. Then the migrator's zip →
   Install Now → **Activate**.
2. Opening Affibloom's own pages now leads to its setup wizard: **leave it**; the migration brings Lasso's settings.
   Go straight to **Affibloom → Migrate from Lasso** (`wp-admin/admin.php?page=affibloom-lasso-migrator`).
3. Activation starts the link index in the background at once (Settings → General → Link Index shows "N completed of
   ~200"). Let it run; it is rebuilt in step 4.
4. Open three guides signed out (a private window): `best-colorful-volleyball-shoes`,
   `best-volleyball-training-equipment`, `best-basketball-shoes-for-volleyball`. **Expect:** Lasso's boxes (17, 13, 11),
   nothing changed to the eye. (Invisible and expected: a body class `affibloom-v1.0.3`, a second Font Awesome
   line, `noreferrer` dropped from links opening in a new tab.)

### 3. Migrate

1. The migration page opens with a **dry run** (it can take a minute on this host). **Expect** about: 266 records,
   255 to create, 9 skipped (no link), 1 to retire, 1 to trash; settings 13 to import, display 11, fields 1, groups 2,
   tables 3, Amazon 226. The Content card: about 253 boxes in about 174 places, 0 blocked. Compare the records with the
   inventory of "The day before" step 3: a product added since shows as one more.
2. Tick **I have a backup of this site's database** → **Run migration**. It runs family by family ("Display done: 2 of
   13" …) and reloads at the end (about a minute).
3. **Expect after the reload:** Products "Already migrated" 257, "To create" 0, Skipped 9; every family "To import" 0;
   no error line in any card. A notice "N product links look broken" may appear: the first link checks ran.
4. **Not today:** the `/recommends/adidas-dame-8-all-volleyball/` redirect (E13) waits for the client's approval.

### 4. Link index

1. Settings → General → **Link Index**. If it still says "We're indexing your links in the background", wait until it
   shows the finished figures (a rebuild started now answers "A rebuild is already running.").
2. Then **Rebuild** → Confirm. The run that started at activation found no products yet; this one ties the links to
   them. **Expect** after a few minutes: about 200 completed of 200; on the Dashboard the Locations column filled
   (staging: 693 tied links in 49 posts, to 176 products).
3. There is no "check links now" button: the link check ran on each product the migration saved, and runs every day at
   03:00. Broken links (staging: 3) are content work for later, not a stop.

### 5. Convert, then switch

1. Plugins → **deactivate Lasso** first (Lasso active would write its stamps back while the content changes).
2. Affibloom → Migrate from Lasso → **Content** card. Tick **I have a database backup from today** → **Convert**.
   It goes one place per request: "Converted N of 181 places" (staging: 4 minutes the first time, 12 the second with
   the server busy). If it stops with an error, press Convert again: it continues where it stopped.
3. **Expect:** every figure on the card 0, "181 converted places" with a Revert button each, and the **Check** card
   "Nothing left: Nothing of Lasso is left in your content."
4. Plugins → **deactivate Affibloom Lasso Migrator** (keep it installed: it is the way back).
5. LiteSpeed Cache → Toolbox → **Purge All**, and the CDN (QUIC.cloud) if it is used.
6. Not needed: a LiteSpeed JS-delay exclusion (Affibloom's click script bypasses it itself, Task 37D). The two broken
   button icons in Additional CSS (Full review, YouTube; deviation 71a) are broken on the live site today too and no box
   uses them: fix them later if wanted.

### 6. Check

1. **Claude, on your word** (read only: no click is counted, GA and Mediavine are blocked): the parity suite against
   the live site, `PARITY_MODE=finished`, boxes and links, with "The day before" baselines. **Expect:** 250 boxes and
   every link as Lasso showed them, and no request to Lasso's servers; a retired product's address
   (`/mizuno-neo-jump-unisex-volleyball-shoe-for-sale/`) now goes to its Amazon product (deviation 165).
2. Open two converted guides in the editor (Posts → Edit): no "This block contains unexpected or invalid content".
3. Five high-traffic guides on a phone, signed out: boxes, buttons and images as before; "Updated on" dates unchanged.
4. A cloaked address answers: e.g. open `https://volleyballvault.com/mizuno-neo-jump-unisex-volleyball-shoe-for-sale/`
   → it lands on Amazon with `tag=vbvault-20`.

### 7. Clicks and events

1. Click a box's button on a guide (it opens Amazon).
2. Affibloom → Dashboard: **Clicks (30 days)** goes up by one (counting starts at zero today, decision E1).
3. GA: the event is `affibloom_primary_click` (decision C6); if the client has GA4 DebugView, it shows there. No event
   named "Lasso …" is sent. The site's tags stay as they were (its own `G-K7T3M99G7Q`, and Lasso's GA setting
   `371244810`, now Affibloom's).

### 8. Tell the editors

They can edit again, now in Affibloom; send them the admin guide (`docs/admin-guide.md`). Amazon products are not
checked or refreshed until the client enters Creators API credentials (Settings → Amazon; the live Lasso has none).

## Rollback

In this order (staging, 2026-11-24: the 181 places went back in about 5 minutes; [`rollback.md`](rollback.md)):

1. Plugins → **activate Affibloom Lasso Migrator**.
2. Affibloom → Migrate from Lasso → Content → **Revert All** → OK. Places go back one per request; if it stops, press
   it again. A place someone edited after the conversion stays and is named: put it back by hand.
3. Plugins → deactivate the migrator, then Affibloom; **activate Lasso**.
4. LiteSpeed → Purge All (and the CDN).
5. Open the three guides of step 2: Lasso's boxes and links are back.

Lasso's own data was never changed, so Lasso works as before. Affibloom's products stay in the database for the next
attempt. Only if the content itself is wrong: restore the backup of "The day before".

## Log

| Step | Time | Result |
|---|---|---|
| The day before: freeze, backup, baselines | | |
| 1. Check | | |
| 2. Install | | |
| 3. Migrate | | |
| 4. Link index | | |
| 5. Convert, switch | | |
| 6. Check | | |
| 7. Clicks and events | | |
| 8. Editors told | | |

After the cutover: start [`monitoring.md`](monitoring.md) (Task 42).
