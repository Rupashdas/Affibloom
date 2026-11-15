---
description: Read-only preparation of an Affibloom batch — read the docs and the real code, trace the flow, check conflicts, report readiness; no changes
---

Prepare this Affibloom batch for implementation, **read only**. Assigned: $ARGUMENTS

The argument names the batch (or task range) from `wp-content/plugins/affibloom/docs/plans/batches.md`, and may name
another batch still running in a parallel session (for example "batch 3.3, batch 3.2 still running"). If no batch is
named, take the batch that holds the first unticked task. Talk to the user in English, briefly.

**Strict rules for the whole session, until the user explicitly says to proceed:**

- Create, edit or delete no file (code, docs, ticks, memory, records). No `git add`/`commit`/`checkout`/`stash`/`pull`.
- Run nothing that changes the repository, the Local or staging database, WordPress options or posts, or staging
  settings: no `wp` write commands, no Playwright runs, no `composer install/update`, no plugin activation, no cron,
  no Lasso jobs, no mail. Read-only commands are fine (`git status`, `git log`, `git diff`, `grep`, `wp option get`,
  `wp post list`, `wp eval` that only reads).
- Do not start implementing, and do not write a plan file. Do not hand the reading to subagents: read it yourself.
- Assume nothing. When something is unclear, investigate it; if it still is, report it as open.

## 1. Documentation and records

Read, in this order, and note what each says that the batch depends on:

1. `docs/progress.md`; the batch's entry and rules in `docs/plans/batches.md`.
2. In the phase file, each task's **full** section (heading to the next heading of the same level): steps, files,
   code, acceptance criteria, commit message. Also the sections of earlier tasks it builds on or names.
3. `grep -n` each task ID (and the feature IDs, e.g. F5, U3) in `docs/deviations.md`, `docs/open-decisions.md` and the
   phase file's *Notes*; read every hit in full, plus any deviation those hits refer to.
4. The parts of `docs/plans/00-overview.md` (architecture, file structure, API contract, test rules) and
   `docs/environment.md` (tools, WP-CLI, Playwright, staging, known issues) that the tasks touch; the matching parts of
   `docs/reference/` (roadmap, feature plan, Lasso analysis) and `docs/ui/design-system.md` for UI tasks.
5. The memory files listed in `MEMORY.md` that bear on the batch (credentials, staging, browser, tests, parallel
   sessions).

## 2. The actual source code

From the docs, list every component the batch reads, calls, extends, replaces or tests. Then **open and read the
complete implementation** of each — whole files or whole classes and functions, not filenames, grep hits, handler
lists or summaries. Cover, as far as the batch touches them:

- Existing Affibloom code on the request path: the entry point (hook, route, shortcode, CLI command, admin page),
  controller, service, domain, repository, templates, assets; `src/Plugin.php` wiring.
- Existing tooling the batch extends: scripts under `bin/` or `tools/`, baseline and capture tools, fixtures.
- Tests: `phpunit.xml*`, test bootstrap, Brain Monkey helpers; `playwright.config.*`, `.local/*.config.ts`,
  fixtures, helpers, global setup/auth, and the existing E2E/AC specs of the same feature.
- Lasso (read only, in `wp-content/plugins/lasso/` and `D:\lasso\lasso`): the option keys, settings save/restore
  handlers, product create/edit/delete handlers, shortcode rendering, theme selection, and any other behaviour the
  batch must match or drive — follow each call to where the data is actually read and written.
- Shared utilities and dependencies those files rely on (follow the `use` and `require` lines, helper functions,
  hooks fired and filtered, AJAX actions, REST routes, DB tables and meta keys).

Keep two lists as you go: **read** (opened and read in full, with path) and **identified only** (seen in a search or
named in a doc, not read). Anything needed that stays in the second list is a gap.

## 3. Trace the flow

Write down the execution flow end to end: who starts it (user, CLI, browser, test), each hop through WordPress, Lasso,
Affibloom, the browser and staging, what data each hop reads and writes (options, posts, meta, tables, files), and what
must be true before and after (clean state, credentials, logins, restored settings). Check each claim against the
code you read, with `file:line`.

## 4. Conflicts with a parallel batch

If another batch is still running: read its tasks' *Files* lists in the plan, and look at `git status`, `git log`
and `git diff --stat` in `wp-content/plugins/affibloom` (read only) for its work in progress. List every file, option,
fixture, test config or DB state both batches touch, and what each overlap would need (order, merge, waiting).

## 5. Readiness

Ready only if every file the batch will create or change, and everything they call, has been read, the flow is traced,
and no open decision blocks it. Otherwise say exactly what is missing.

## Final report (then stop)

1. **Documentation and records reviewed** — each with what mattered in it.
2. **Source files actually read**, grouped by component, with path.
3. **Identified as relevant but not read** — and whether that matters.
4. **Implementation flow and dependencies** — the traced flow, per task, with `file:line`.
5. **Conflicts with the parallel batch** — files and state, and how to handle each.
6. **Still to review before implementation** — files or information not yet seen.
7. **Open questions and decisions needing the user's approval** — settle what Lasso or the records decide (memory:
   resolve decisions from records); ask only to drop, deviate or reverse.
8. **Readiness verdict** — ready / not ready, and why. Never claim readiness from the plan alone.

**Stop and wait for the user's instruction.** Do not begin implementation (not even the first test) until the user
explicitly says to proceed, and, if a parallel batch was named, until it has finished.
