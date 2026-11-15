---
description: Continue the Affibloom plan with the next unticked task, following the plan's working method
---

Continue the Affibloom implementation plan. Assigned: $ARGUMENTS

A session runs one **batch** (the user, 2026-11-15): the batch or task range the user named, as listed in
`wp-content/plugins/affibloom/docs/plans/batches.md`; if none was named, the batch that holds the first unticked task.
Read the rules at the top of `batches.md`, then do steps 1-7 for each task of the batch in turn (one task at a time,
each with its own commit), and step 8 once at the end.

1. Read only what the task needs (the records are large; do not read whole files you do not need):
   - `wp-content/plugins/affibloom/docs/progress.md` (short: status and next up) and
     `wp-content/plugins/affibloom/docs/plans/00-overview.md`.
   - The current phase file in `wp-content/plugins/affibloom/docs/plans/`: its head, up to the first
     `### Task` heading (decisions and the task list), then find the batch's next task (or step) that is
     not ticked and read **only that task's section**: from its `### Task` / `#### Task` heading to
     the next heading of the same or a higher level (`grep -n` the ID for the line numbers, then read that
     range).
   - `grep -n` the task's ID and its feature IDs (e.g. `C1A`, `F5`) in `docs/deviations.md`,
     `docs/progress-log.md` and `docs/open-decisions.md`, and read the entries that come up, plus any
     deviation the task's text or `progress.md` names. Use the index at the top of `deviations.md` to
     find a deviation by subject.
2. Check its preconditions: the previous phase's exit check is met and signed off, gate 4 approval for its
   feature is recorded, and any open decision that affects it is settled. If not, stop and ask the user.
3. Before writing code, tell the user in 2–3 sentences what the task builds, why it is needed now, and how
   it will be checked; show its request path. For the batch's first task, also list the batch's
   tasks and wait for "go"; later tasks in the batch go on without a new "go".
4. Work outside-in, step by step, exactly as the plan says: the outermost test first (run it, confirm it
   fails for the right reason), then entry point → controller → service/domain/repository → template or
   response. Run `composer lint`, `composer analyse` and `composer test:unit` as you go, and **only this task's**
   acceptance tests (`-g "<AC or task ID>"`, `-x` while fixing; `batches.md` rule 5 has the tiers). Run them in the
   foreground; a run restores the site itself, so no backup or reset around it.
   Fix causes, never suppress. If the plan's code must change, add the deviation (at most ~10 lines: what changed, why, the checks) at the end of `deviations.md`
   (next number) and its line to the index at the top.
   Skip the plan's branch, pull request and CI steps (the user's simple workflow, see `CLAUDE.md`).
5. Run the task's entry point by hand (screen, route, command) and report what you saw (`tools/shot.cjs` for a
   screen). After the batch's last task: only the tests covering what the batch changed (`-g`, not whole specs) and
   the looks at 375 px and 1280 px; whole specs and the full suite only at the phase's exit check (`batches.md`, rule 5).
6. Tick each finished step (`- [ ]` → `- [x]`) and the task in the phase file. Update the records:
   `progress.md` (status, next up; keep it short), `deviations.md` (new deviations) and the session log at
   the top of `progress-log.md`'s Session log (at most ~4 lines a task).
7. Go through the checklist in `.github/pull_request_template.md` yourself and report it. Commit on
   `main` as the plan says: the user's identity, imperative message with a short why, **no
   `Co-Authored-By` trailer**. Then push to GitHub (backup).
   Then start the batch's next task at step 1, unless `batches.md` rule 4 says to stop early.
8. After the batch's last task (or an early stop): the batch's heavy checks (rule 5), then what changed per
   task, what the checks showed, what is left of the batch if it stopped early, and the next batch. **Stop: never
   start another batch without the user's explicit approval** (one batch per session, the user, 2026-11-15).
