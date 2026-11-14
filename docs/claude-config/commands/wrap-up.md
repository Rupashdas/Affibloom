---
description: End an Affibloom session — bring progress, plan ticks and the session log up to date
---

Wrap up this Affibloom session.

1. Check `git status` and `git log` in `wp-content/plugins/affibloom`: list anything uncommitted or unpushed
   and ask the user what to do with it.
2. Make sure every finished step and task is ticked in the current phase file in `wp-content/plugins/affibloom/docs/plans/`.
3. Update `wp-content/plugins/affibloom/docs/progress.md`: the status table, "Next up", new deviations, decisions taken (with the
   user's answer and date), and a new session log entry at the top of the log.
4. If a phase is complete, run its exit check from the phase file and report the result; the user signs it
   off before the next phase starts.
5. Remind the user which roadmap items to tick in the Claude Docs roadmap
   (https://claude.ai/artifact/VbZoaqD53pxA5LqFivfyh1) — the local `reference/roadmap.md` is a read-only copy.
6. Summarise in a few lines: done, verified, open, next.
