# Affibloom — task batches (one batch per session)

**Decided (the user, 2026-11-15):** a session runs one **batch**, a short run of small, related tasks, instead of one task.
This replaces "one task per session" (2026-11-14). Nothing else about how a task is done changes.

## Rules

1. **The user assigns the batch** ("Batch 3.4", or a range such as "C1B-C1D"). Claude never starts a batch, or the next
   one, without the user's word. A task not listed below is a batch of its own.
2. **Inside a batch, tasks run one at a time, in the phase file's run order, each in full:** the outermost test first
   and red for the right reason, then code inward; `composer lint`, `composer analyse`, the task's tests and the check by
   hand; the self-review against `.github/pull_request_template.md`; the step and task ticks; the records
   (`progress.md`, `deviations.md`, `progress-log.md`); **its own commit** (the plan's message) and push. A task is
   finished before the next one starts; no work for two tasks shares a commit.
3. **Explain as you go, per task:** before each task, 2–3 sentences on what it builds and how it is checked; after it,
   what changed and what the checks showed. The user's "go" is asked once, at the start of the batch.
4. **Stop the batch early**, after committing whatever task is complete, when:
   - a check fails and its cause cannot be fixed inside the task;
   - a task needs a deviation that changes a later task in the batch, or needs the user's answer or by-hand check;
   - the session's context is getting long (finish the current task first).
   Say which tasks are left; they become the next batch.
5. **Test tiers: each check as often as it is needed, no more (the user, 2026-11-15, deviation 111):**
   - **While coding:** `composer test:unit`, `composer analyse`, `composer lint` (seconds each).
   - **At a task's end:** those three in full, plus **only that task's acceptance tests**, run with
     `-g "<AC or task ID>"` (and `-x` while fixing), not the whole spec file; the by-hand look when the task changed
     what a user sees (`tools/shot.cjs`). A task whose acceptance criteria name a by-hand look still gets it.
   - **At a batch's end:** only the tests that cover what the batch changed, picked with `-g`: the batch's own
     acceptance tests together, plus the other features' tests that drive a changed entry point (e.g. a batch that
     changes adding a product runs F1's and F18's add tests, not all of F1 and F18). Not whole spec files. Plus the
     looks at 375 px and 1280 px on the screens the batch changed. (Narrowed by the user, 2026-11-15, deviation 115.)
   - **At a phase's exit check:** the full suite, once. Whole feature specs run only here.
   - A test is run "twice in a row" only to chase a flaky one. Runs restore the site themselves (`run-state.php`), so
     no backup, reset or clean-up by hand around a run. Short runs go in the foreground, not the background.
6. **Phases still run in order** (0 → 9). A phase's exit check and the user's sign-off come after its last batch.

## How the batches were cut

252 open tasks (2026-11-15) in 139 batches; every open task is in exactly one.

Consecutive tasks in the phase's run order (never reordered), usually from one feature, 2–3 tasks per batch, at most 4.
These run **alone**: a feature's first task that lays the foundation, baseline and parity tasks, a task of 40 or more
plan lines, reviews, and staging or production work.

## Phase 2 (rest of C1)

| Batch | Tasks |
|---|---|
| 2.1 | C1B, C1C, C1D |
| 2.2 | C1I, C1K, C1L |
| 2.3 | C1M, C1N |
| 2.4 | C1O, C1P |

## Phase 3

| Batch | Tasks |
|---|---|
| 3.1 | 18A |
| 3.2 | 18B, 18C |
| 3.3 | 18D |
| 3.4 | 18E, 18F, 18G |
| 3.5 | 18H, 18I |
| 3.6 | 19A, 20A |
| 3.7 | 21A, 18J |
| 3.8 | 22A, 22B, 22C |
| 3.9 | 18K |
| 3.10 | 20B, 20C |
| 3.11 | 21B, 21C |
| 3.12 | 20D, 22E |
| 3.13 | 10D, 14E |
| 3.14 | C1F, C1G |
| 3.15 | 18S1 |
| 3.16 | 18S2, 18S3 |
| 3.17 | 18S4, 18S5 |
| 3.18 | 18S6, 18S9 |
| 3.19 | 18S10, 18S11 |
| 3.20 | 18S14 |
| 3.21 | DT1 |
| 3.22 | DT2 |
| 3.23 | DT3, DT4 |
| 3.24 | DT5, DT6, DT7 |
| 3.25 | DT8, DT9 |
| 3.26 | 18S7, 18S12 |

## Phase 4

| Batch | Tasks |
|---|---|
| 4.1 | 23A |
| 4.2 | 23B |
| 4.3 | 23C |
| 4.4 | 23D, 23E |
| 4.5 | 23G |
| 4.6 | 24A, 24B |
| 4.7 | 24C, 24D, 24E |
| 4.8 | ED1, ED2 |
| 4.9 | ED3, ED4, ED5 |

MCP1 is dropped (decision A10).

## Phase 5

| Batch | Tasks |
|---|---|
| 5.1 | 25A |
| 5.2 | 25B, 25C, 25D |
| 5.3 | 26A, 26B |
| 5.4 | 26C, 26D, 26E |
| 5.5 | 26F, 26L, 26M |
| 5.6 | 26O |
| 5.7 | 26P |
| 5.8 | 35A |
| 5.9 | 35B, 35C |
| 5.10 | 35D, 35E |
| 5.11 | 35F, 35G |
| 5.12 | 9J, ON3, ON4 |
| 5.13 | 35I |
| 5.14 | 27A, ED6 |
| 5.15 | 27B, 27C, 27D |
| 5.16 | 27F, 27G |
| 5.17 | 27H, 27I |
| 5.18 | 27X1, 27X2 |
| 5.19 | 27X3, 27X4 |
| 5.20 | 27X5, 27X6 |
| 5.21 | 27X7, 27X8 |
| 5.22 | CL1, CL2 |
| 5.23 | CL3 |
| 5.24 | CL4, CL5 |
| 5.25 | CL6, CL7 |
| 5.26 | CL8, CL9 |
| 5.27 | CL10, CL11 |
| 5.28 | 26G |
| 5.29 | V1, V2 |
| 5.30 | V3, V4 |
| 5.31 | V5, 26H |
| 5.32 | 35H, 35J |
| 5.33 | IM1 |
| 5.34 | IM2 |
| 5.35 | IM3 |
| 5.36 | IM4, IM5, IM6 |
| 5.37 | IM7, IM8 |
| 5.38 | IM9, IM10 |
| 5.39 | IM12, IM13, IM14 |
| 5.40 | IM15, IM16, IM17 |
| 5.41 | IM19 |
| 5.42 | 29A |
| 5.43 | 29B |
| 5.44 | 29E |

## Phase 6

| Batch | Tasks |
|---|---|
| 6.1 | 30A |
| 6.2 | 30B, 30C |
| 6.3 | 31A, 31B |
| 6.4 | 31C, 31D |
| 6.5 | 32A, 32B, 32C |
| 6.6 | 32D, 32E |
| 6.7 | 26K |

## Phase 7

| Batch | Tasks |
|---|---|
| 7.1 | 36A, 36B |
| 7.2 | 36C, 36D, 36E |
| 7.3 | 33A |
| 7.4 | 33B, 33C, 33D |
| 7.5 | 34A, 34B, 34C |
| 7.6 | C3A, C3B, C3C |
| 7.7 | 13K |
| 7.8 | C1E, C1H |
| 7.9 | O1 |
| 7.10 | O2, O3 |
| 7.11 | O4, O5 |
| 7.12 | O6, O7 |
| 7.13 | O8, O9 |
| 7.14 | O10 |
| 7.15 | C2A |
| 7.16 | C2B |
| 7.17 | C2C |
| 7.18 | AM1 |
| 7.19 | AM2, AM3 |
| 7.20 | AM4, AM5 |
| 7.21 | AM6, 26N |
| 7.22 | T1 |
| 7.23 | T2, T3 |
| 7.24 | T4 |
| 7.25 | T5, T6 |
| 7.26 | T7, T8 |
| 7.27 | T9, T10, T11 |
| 7.28 | T12, 18S13 |
| 7.29 | T13, T14 |
| 7.30 | T15, T16, T17 |
| 7.31 | 23F |
| 7.32 | 26I, 27E |
| 7.33 | IM11, IM20, IM18 |
| 7.34 | 29C |

## Phase 8

| Batch | Tasks |
|---|---|
| 8.1 | 37A |
| 8.2 | 37B |
| 8.3 | 37C |
| 8.4 | 37D |
| 8.5 | 38 |
| 8.6 | TL3, TL4 |
| 8.7 | TL6, TL7 |
| 8.8 | ON5 |
| 8.9 | ON6, ON7 |
| 8.10 | ON8, ON9 |
| 8.11 | 39 |
| 8.12 | 39A |

## Phase 9

| Batch | Tasks |
|---|---|
| 9.1 | 40 |
| 9.2 | 41 |
| 9.3 | 42 |
