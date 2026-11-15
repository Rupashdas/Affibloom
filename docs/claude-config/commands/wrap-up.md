---
description: End an Affibloom session — bring progress and plan ticks up to date
---

Wrap up this Affibloom session (lean method, deviation 129). Talk to the user in Bangla, briefly.

1. `git status` and `git log` in `wp-content/plugins/affibloom`: list anything uncommitted or unpushed and ask the
   user what to do with it.
2. Every finished step and task is ticked in the current phase file.
3. `progress.md`: the status table row and the one-line "Next up". Decisions the user took go into
   `open-decisions.md` (answer and date). No session-log entry: `progress-log.md` is closed.
4. If a phase is complete, run its exit check (the full suite, once) and report it; the user signs it off.
5. Remind the user which roadmap items to tick in the Claude Docs roadmap
   (https://claude.ai/artifact/VbZoaqD53pxA5LqFivfyh1).
6. Summarise in a few lines: done, checked, open, next.
