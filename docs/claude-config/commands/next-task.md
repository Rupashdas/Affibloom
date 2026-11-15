---
description: Continue the Affibloom plan with the next unticked task, following the plan's working method
---

Continue the Affibloom implementation plan. Assigned: $ARGUMENTS

A session runs one **batch**: the batch or task range the user named in
`wp-content/plugins/affibloom/docs/plans/batches.md`, or, if none was named, the batch that holds the first unticked
task. Follow the lean method (`CLAUDE.md`, `batches.md` rules; the user, 2026-11-15, deviation 129): few records, few
test runs, no database resets. Talk to the user in Bangla, briefly.

For each task of the batch, in turn:

1. **Read only what the task needs:** `docs/progress.md`; in the phase file only the task's section (`grep -n` the ID,
   read from its heading to the next one of the same level); `grep -n` the ID in `docs/deviations.md` and
   `docs/open-decisions.md`. Overview or `environment.md` only when the task needs them.
2. **Preconditions:** the previous phase is signed off and no open decision blocks the task; otherwise ask the user.
3. **Before coding**, 2–3 sentences: what it builds, why, how it is checked, and its request path. For the batch's first
   task, list the batch's tasks and wait for "go"; later tasks go on without a new "go".
4. **Outside-in:** the outermost test is written first (no separate red run); then entry point → controller →
   service/domain/repository → template or response. Fix causes, never suppress. Skip the plan's branch, pull request
   and CI steps.
5. **Checks per task (~20 s of commands, deviation 130):** the unit tests, `vendor/bin/phpcs` on the changed files, and
   one look at the changed screen. The Playwright AC is written but not run here. No backup, save/restore or reset:
   the Local database is dummy data.
6. **Records:** tick the steps and the task in the phase file; if something differed from the plan, a 1–3 line *Note*
   under the task. A numbered deviation only when the plan's design or a later task changes.
7. **Commit** on `main` with the plan's message (the user's identity, **no `Co-Authored-By` trailer**) and push. Then
   the next task, unless `batches.md` rule 4 says to stop.

After the batch's last task (or an early stop): `composer lint`, `composer analyse`, the batch's Playwright ACs together
(`-g`) and the 375/1280 px looks on the changed screens (a failure is fixed and committed as "Fix <what> (<task ID>)"); one pass through `.github/pull_request_template.md`; the one-line "Next up" in `progress.md` (committed with
the last task, or as "Update progress after Batch <n>"); then a short Bangla summary: done, checked, left, next batch.
**Stop: never start another batch without the user's word.**
