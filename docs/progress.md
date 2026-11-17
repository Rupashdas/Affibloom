# Affibloom — progress

Short on purpose: read this at the start of every session. Update it at the end of every batch (deviation 129).

| Record | What it holds | When to read it |
|---|---|---|
| `progress.md` (this file) | Status by phase, next up | Every session |
| [`deviations.md`](deviations.md) | Every deviation from the plan text, numbered, with an index | `grep` it for the task ID and the features it touches; read a deviation in full when a note names it |
| [`progress-log.md`](progress-log.md) | Session log up to 2026-11-15, early history (closed, deviation 129) | Only for the history of an older task |
| [`open-decisions.md`](open-decisions.md) | Decisions for the user | When a task touches one |

"Deviation N" anywhere in the docs means item N in `deviations.md`.

## Status by phase (verified against the code, 2026-11-14)

The status below was checked against the code by the full planning pass (deviation 89; audits in the session log).
Every phase is planned in full; **gate 4 was given for the whole plan on 2026-11-14** with the answers in
[`open-decisions.md`](open-decisions.md). The task lists are in `plans/phase-<n>-*.md`; the index is in
`plans/00-overview.md`.

| Phase | Name | Done | Open | Status |
|---|---|---|---|---|
| 0 | Foundation and tooling | 1-3 | — | **Done**, signed off 2026-11-12 (CI never ran: deviation 10; the integration suite has never run) |
| 1 | Behaviour specs and baselines | 4-7 | — | **Done**, signed off 2026-11-12 |
| 2 | Settings and product library | 2X0-2X6, 8, 9, 9A, 9B-9I, 9K, 9L, 10A-10C, 10E, 10F, 11A-11E, 12A-12E, 13A-13J, 14A-14D, 14F-14H, 15A-15D, 16A-16C, 17A-17D, C1A-C1D, C1I, C1K-C1P, C1Q0-C1Q6b, G1-G5, FL1-FL5, TL1, TL2, ON1, ON2 (+ the one-time Lasso import button, deviation 87) | — (C1J is no code) | **Done**, signed off 2026-11-15. Audited 2026-11-15 (deviation 107): everything but C1 is built, recorded and passes its acceptance tests; C1A done 2026-11-15 (deviation 110); C1B done (deviation 112); C1C done (deviation 113); C1D done (deviation 114); C1I done (deviation 116); C1Q0-C1Q6b done (deviations 117-128; rechecked 2026-11-15: unit, lint, analyse and the 18 C1 ACs pass); Batch 2.2 done 2026-11-15 (C1K, C1L; lint, analyse, AC17 and AC18 pass); Batch 2.3 done 2026-11-15 (C1M, C1N; AC15, AC19 pass); Batch 2.4 done 2026-11-15 (C1O, C1P); **exit check passed 2026-11-15** |
| 3 | Product displays | 18A-18K, 19A, 20A-20D, 21A-21C, 22A-22C, 22E, 10D, 14E, C1F, C1G, 18S1-18S12, 18S14, DT1-DT9 | — (18S13 is in Phase 7) | **Done**, signed off 2026-11-16 (Batches 3.1-3.26; 3.5-3.26 in one session at the user's word); exit check passed 2026-11-16 |
| 4 | Editors | MD1-MD7, 23A-23E, 23G, 24A-24E, ED1-ED5 | — (MCP1 dropped, 23F in Phase 7, ED6 in Phase 5) | All batches done 2026-11-17 (4.M1-4.M5, 4.1-4.9). **Done**, signed off 2026-11-17 (Batches 4.M1-4.M5, 4.1-4.9); exit check passed 2026-11-17 (F4 22/22, admin-design 6/6, F5 AC1 7/7) |
| 5 | Lasso compatibility and migration | — | 92: 25A-26P (core), 35A-35J and 9J (link index), ON3-ON4, 27A-27X8, ED6, CL1-CL11, V1-V5, 28A-28E, H1-H4, IM1-IM19 (not IM11, IM18), 29A-29E (not 29C) | Planned |
| 6 | Link health | — | 13: 30A-30C, 31A-31D, 32A-32E, 26K | Planned |
| 7 | Locations, clicks, opportunities, auto-detect, tables | — | 63: 36A-36E, 33A-34C, C3A-C3C, 13K, 26J, C1E, C1H, O1-O10, C2A-C2C, AM1-AM6, 26N, T1-T17, 18S13, 26I, 27E, IM11, IM20, IM18, 29C | Planned |
| 8 | Hardening and documentation | — | 16: 37A-37D, 38, TL3-TL7 (not TL5, in 38), ON5-ON9, 39, 39A | Planned |
| 9 | Staging rollout and cutover | — | 3: 40-42 | Planned (rewritten 2026-11-14) |

## Next up

1. **Phase 3 signed off 2026-11-16** (exit check passed: F5 90/90, F6, F7, F8, F16, F29, C1, F30, F31; F9 too).
   React admin dropped (2026-11-16): the admin stays PHP. **One save behaviour done 2026-11-17** (deviation 143): every
   settings screen saves over REST and stays on the page. Display switches Single/Grid/List in place (deviation 144).
   **Next: the Meadow restyle** (the user, 2026-11-16, deviation 142): Meadow replaces Aurora (`ui/design-system.md`
   section 0b); Tasks MD1-MD7 in `plans/phase-4-editor-block.md`, Batches 4.M1-4.M5, now, before 23A. **Batch 4.M1
   done 2026-11-17** (MD1: bundled fonts and Meadow's colours); **Batch 4.M2 done 2026-11-17** (MD2 white header and
   notice cards, MD3 page head instead of the band); **Batches 4.M3-4.M5 done 2026-11-17** (MD4 Dashboard, MD5
   product page, MD6 Settings/dialogs/toast, MD7 design system: the restyle is complete). **F4 2026-11-17** (the user asked for every Phase 4 batch at
   once): `@wordpress/scripts` installed at the user's word; 23A-23E, 23G and 24A done (block, chooser, Choose a Link /
   a Group, Create Group and Add to Group, Create a Link with the quick detail, Customize Display); then 24B-24E
   (schema panel, shortcode box, Edit Display, preview queue); **Batches 4.8-4.9 done 2026-11-17** (ED1 Classic button,
   ED2 Elementor widget, ED3 Rank Math, ED4 AMP CSS, ED5 Lasso link attributes in core blocks; Classic Editor, Elementor
   and AMP installed on Local at the user's word, left inactive). **Phase 4 exit check passed 2026-11-17:** F4 22/22, admin-design 6/6, F5 AC1 7/7 (the box against Lasso's
   baselines). It found one product bug (empty `"overrides":{}` stored in blocks, fixed 52eafdc) and test faults (fixed).
   **Phase 4 signed off by the user 2026-11-17.** **Next: Phase 5** (Lasso compatibility and migration), at the
   user's word, one batch per session (`plans/batches.md`). Open for the user: ED5 left out Lasso's Stackable
   support (no Stackable on the site). The block and the Elementor script build into `build/` (`npm run build`,
   webpack.config.js; a release zip needs a fresh build, Task 39).
2. **Lean method from 2026-11-15 (deviations 129-131):** few records (phase-file ticks and *Notes*); per task only
   unit tests, `phpcs` and a look (~20 s); at a batch's end lint, analyse and unit tests, then a manual test guide for
   the user; Playwright only at the phase exit; no red run; no save/restore, backup or reset on Local (dummy data).
   Rules: `CLAUDE.md`, `plans/batches.md`.
3. Open, environment: C1 AC2 times out on Local (also on C1Q6a); Local's PHP workers raised from 2 to 4 (its `PhpService.js` patched 2026-11-15, backup `.bak`; an update of Local undoes it; not yet seen running).
4. Open, the user's call: the optional GA4 check from the client. Local holds the site's real, validated Creators API
   credentials (seen 2026-11-15; deviation 112's "empty" is past): tests that need none remove and restore them.
5. Standing rules: Affibloom works with no Lasso dependency and no Lasso branding (2026-11-14); one batch per session
   (2026-11-15). Lasso 370 is active on Local since 2026-11-16 (copied from staging by the user for 18D).
6. Pending, no action unless the user brings it up: CI has never run (deviation 10); the integration suite has never
   run (no wp-env).
