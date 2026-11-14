# Affibloom — progress

Short on purpose: read this at the start of every session. Update it at the end of every task.

| Record | What it holds | When to read it |
|---|---|---|
| `progress.md` (this file) | Status by phase, next up | Every session |
| [`deviations.md`](deviations.md) | Every deviation from the plan text, numbered, with an index | `grep` it for the task ID and the features it touches; read a deviation in full when a note names it |
| [`progress-log.md`](progress-log.md) | Session log (newest first), early history, Phase 0 decisions | When you need the history of a task; add the session's entry at the top of its Session log |
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
| 2 | Settings and product library | 2X0-2X6, 8, 9, 9A, 9B-9I, 9K, 9L, 10A-10C, 10E, 10F, 11A-11E, 12A-12E, 13A-13J, 14A-14D, 14F-14H, 15A-15D, 16A-16C, 17A-17D, G1-G5, FL1-FL5, TL1, TL2, ON1, ON2 (+ the one-time Lasso import button, deviation 87) | 10: C1B-C1D, C1I, C1K-C1P (C1J is no code) | **In progress.** Audited 2026-11-15 (deviation 107): everything but C1 is built, recorded and passes its acceptance tests; C1A done 2026-11-15 (deviation 110); C1B is next |
| 3 | Product displays | — | 50: 18A-18K, 19A, 20A-20D, 21A-21C, 22A-22E, 10D, 14E, C1F-C1G, 18S1-18S12, 18S14, DT1-DT10 | Planned |
| 4 | Editors | — | 18: 23A-23G, 24A-24E, ED1-ED5, MCP1 | Planned |
| 5 | Lasso compatibility and migration | — | 92: 25A-26P (core), 35A-35J and 9J (link index), ON3-ON4, 27A-27X8, ED6, CL1-CL11, V1-V5, 28A-28E, H1-H4, IM1-IM19 (not IM11, IM18), 29A-29E (not 29C) | Planned |
| 6 | Link health | — | 13: 30A-30C, 31A-31D, 32A-32E, 26K | Planned |
| 7 | Locations, clicks, opportunities, auto-detect, tables | — | 63: 36A-36E, 33A-34C, C3A-C3C, 13K, 26J, C1E, C1H, O1-O10, C2A-C2C, AM1-AM6, 26N, T1-T17, 18S13, 26I, 27E, IM11, IM20, IM18, 29C | Planned |
| 8 | Hardening and documentation | — | 16: 37A-37D, 38, TL3-TL7 (not TL5, in 38), ON5-ON9, 39, 39A | Planned |
| 9 | Staging rollout and cutover | — | 3: 40-42 | Planned (rewritten 2026-11-14) |

## Next up

1. **Phase 2 audit done (2026-11-15, deviation 107).** The records were reconciled and the open checks run:
   the F9 test faults fixed (no code fault), F1 twice in a row, F9, F16, F19 and F23 passed, the owed by-hand checks
   and 375 px looks made, Display's clipped Button alignment fixed, and the product page's squeezed Button Text
   (deviation 108), and the doubled card gaps on Settings → General and Amazon (deviation 109).
2. **C1A done (2026-11-15, deviation 110). Next task: C1B** (the catalogue client, `wp affibloom amazon get`), paused
   at the user's word until another session's work is applied; then C1C, C1D, C1I, C1K-C1P, then Phase 2's exit check
   and the user's sign-off.
3. Standing rule (the user, 2026-11-14): **Affibloom works with no Lasso dependency and no Lasso branding.**
   Standing rule (the user, 2026-11-15): **one batch per session**, as listed with its rules in
   [`plans/batches.md`](plans/batches.md); it replaces "one task per session". Each task still has its own commit.
4. Lasso is inactive on the Local copy since 2026-11-14 (backup `app/affibloom-backup-2026-11-14-before-lasso-deactivate.sql`).
   **`tools/reset-local.sh` is not safe to run as it stands:** `.local/snapshot.sql` dates from 2026-11-12, before the
   library was seeded from Lasso (deviation 51) and before the saved sign-in Claude's runs use, so a reset would lose
   both. The test runs of 2026-11-15 ran on the copy as it was (backup `.local/backup-2026-11-15-before-phase2-audit-runs.sql`).
   A new snapshot is the user's call.
5. Open, from the client: C1 Creators API credentials; the optional GA4 check.
6. Pending, no action unless the user brings it up: CI has never run (deviation 10); the integration suite has never
   run (no wp-env).
