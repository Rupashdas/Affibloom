---
description: Continue the Affibloom plan with the next unticked task, following the plan's working method
---

Continue the Affibloom implementation plan.

1. Read `wp-content/plugins/affibloom/docs/progress.md`, `wp-content/plugins/affibloom/docs/plans/00-overview.md` and the current phase file in
   `wp-content/plugins/affibloom/docs/plans/`. Find the first task (or step) that is not ticked. $ARGUMENTS
2. Check its preconditions: the previous phase's exit check is met and signed off, gate 4 approval for its
   feature is recorded, and any open decision in `progress.md` that affects it is settled. If not, stop and
   ask the user.
3. Before writing code, tell the user in 2–3 sentences what the task builds, why it is needed now, and how
   it will be checked; show its request path. Wait for "go" if this is the first task of the session.
4. Work outside-in, step by step, exactly as the plan says: the outermost test first (run it, confirm it
   fails for the right reason), then entry point → controller → service/domain/repository → template or
   response. Run `composer lint`, `composer analyse` and the tests the task names that run on this machine.
   Fix causes, never suppress. If the plan's code must change, record the deviation in `progress.md`.
   Skip the plan's branch, pull request and CI steps (the user's simple workflow, see `CLAUDE.md`).
5. Run the task's entry point by hand (screen, route, command) and report what you saw.
6. Tick each finished step (`- [ ]` → `- [x]`) and the task in the phase file. Update `progress.md`
   (status, next up, deviations, session log).
7. Go through the checklist in `.github/pull_request_template.md` yourself and report it. Commit on
   `main` as the plan says: the user's identity, imperative message with a short why, **no
   `Co-Authored-By` trailer**. Then push to GitHub (backup).
8. Finish with: what changed, what the verification showed, and what is next. **Stop after this one task: never
   start the next task without the user's explicit approval** (one task per session, the user, 2026-11-14).
