# Affibloom — task batches (one batch per session)

**Decided (the user, 2026-11-15):** a session runs one **batch**, a short run of small, related tasks, instead of one task.
This replaces "one task per session" (2026-11-14). Nothing else about how a task is done changes.

## Rules

Lean method of 2026-11-15 (the user, deviation 129): few records, few test runs, no database resets.

1. **The user assigns the batch** ("Batch 3.4", or a range such as "C1B-C1D"). Claude never starts a batch, or the next
   one, without the user's word. A task not listed below is a batch of its own.
2. **Inside a batch, tasks run one at a time, in the phase file's run order:** the outermost test is written first (no
   separate red run, deviation 130), then code inward; the checks of rule 5; the step and task ticks in the phase file (plus a 1–3 line
   *Note* under the task when something differed from the plan); **its own commit** (the plan's message) and push. A
   task is finished before the next one starts; no work for two tasks shares a commit.
3. **Explain in English, briefly, per task:** before it, what it builds and how it is checked; after it, what changed
   and what you saw. The user's "go" is asked once, at the start of the batch.
4. **Stop the batch early**, after committing whatever task is complete, when:
   - a check fails and its cause cannot be fixed inside the task;
   - a task needs a change that alters a later task in the batch, or needs the user's answer or by-hand check;
   - the session's context is getting long (finish the current task first).
   Say which tasks are left; they become the next batch.
5. **Checks: as few as needed (deviations 130, 131).** No separate red run: a test is written first and run after the code.
   - **Per task (~20 s of commands):** the unit tests (`composer test:unit`, or `vendor/bin/phpunit --filter <Test>`),
     `vendor/bin/phpcs <files>` on the changed files, and one look in the browser at 1280 px at the screen the task
     changed (`tools/shot.cjs` or the browser pane). The task's Playwright AC is written, not run.
   - **At a batch's end (~2–3 minutes):** `composer lint && composer analyse && composer test:unit` in one command
     (~10 s with warm caches). No Playwright and no 375/1280 px screenshots: the user tests by hand.
   - **The manual test guide**, in chat after the batch's summary: numbered steps, each with the screen (menu path or
     URL), what to do and what to expect; the last step a 375 px check (F12 → device toolbar) of the changed screens;
     about 5 minutes in all. A problem the user reports is fixed and committed as "Fix <what> (<task ID>)".
   - **Playwright:** the phase's ACs all together at its exit check, or any time the user asks. A test is run twice only
     to chase a flaky one.
   - **The Local database is dummy data:** runs change it freely; no backup, save/restore or reset around them.
6. **Records.** Per task only the phase file (ticks and the optional *Note*). A numbered deviation in `deviations.md`
   (at most ~10 lines, plus its index line) only when the plan's design or a later task changes. `progress.md`: one
   "Next up" update at the batch's end. `progress-log.md` is closed. Review against
   `.github/pull_request_template.md` once, at the batch's end.
7. **Phases still run in order** (0 → 8, then M, the migrator plugin, then 9; deviation 146). A phase's exit check and the user's sign-off come after its last batch.

## How the batches were cut

252 open tasks (2026-11-15) in 139 batches; every open task is in exactly one. Re-cut 2026-11-17 (deviation 146):
Phase 5 has 31 core tasks in 17 batches; Phase M has 34 tasks in 20 batches; 26K, 26I, 26N and 29C moved there from
Phases 6 and 7, and 27E, IM11, IM18 and IM20 were dropped.

Consecutive tasks in the phase's run order (never reordered), usually from one feature, 2–3 tasks per batch, at most 4.
These run **alone**: a feature's first task that lays the foundation, baseline and parity tasks, a task of 40 or more
plan lines, reviews, and staging or production work.

## Phase 2 (rest of C1)

| Batch | Tasks |
|---|---|
| 2.1 | C1B, C1C, C1D |
| 2.1Q0 | C1Q0 (full Amazon data, deviation 117: the user approves each Q task; before C1K) |
| 2.1Q1 | C1Q1 |
| 2.1Q2 | C1Q2 |
| 2.1Q3 | C1Q3 |
| 2.1Q4 | C1Q4a, C1Q4b (done; deviations 124, 125) |
| 2.1Q5 | C1Q5 |
| 2.1Q6 | C1Q6a, C1Q6b (done; deviations 127, 128) |
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

The Meadow restyle runs first (the user, 2026-11-16, deviation 142): five short batches, then F4's.

| Batch | Tasks |
|---|---|
| 4.M1 | MD1 (fonts and colours; the font download needs the user's OK) |
| 4.M2 | MD2, MD3 |
| 4.M3 | MD4 |
| 4.M4 | MD5 |
| 4.M5 | MD6, MD7 |
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

Rewritten 2026-11-17 (deviation 146): core tasks only; the Lasso tasks are in Phase M, the other plugins' are dropped.

| Batch | Tasks |
|---|---|
| 5.1 | LC1, LC2 |
| 5.2 | 35A |
| 5.3 | 35B, 35C |
| 5.4 | 35D, 35E |
| 5.5 | 35F, 35G |
| 5.6 | 9J, ON3, ON4 |
| 5.7 | 35I |
| 5.8 | CL1, CL2 |
| 5.9 | CL3 |
| 5.10 | CL4, CL5 |
| 5.11 | CL6, CL7 |
| 5.12 | CL8, CL9 |
| 5.13 | CL10, CL11 |
| 5.14 | V1, V2 |
| 5.15 | V3, V4 |
| 5.16 | V5 |
| 5.17 | 35H, 35J |

## Phase 6

| Batch | Tasks |
|---|---|
| 6.1 | 30A |
| 6.2 | 30B, 30C |
| 6.3 | 31A, 31B |
| 6.4 | 31C, 31D |
| 6.5 | 32A, 32B, 32C |
| 6.6 | 32D, 32E |

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
| 7.8 | C1E (the Locations arm only; deviation 117), C1H |
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
| 7.21 | AM6 |
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
| 8.G1 | GF1, GF2 (gap fixes, deviation 156: link rules) |
| 8.G2 | GF3, GF4, GF5 (WordPress side effects and hooks) |
| 8.G3 | GF6 (alone: E17, many files) |
| 8.G4 | GF7 (records and leftovers) |

## Phase M (the Affibloom Lasso Migrator plugin; after Phase 8, before Phase 9; deviation 146)

Its tasks run in the migrator's repository (commits there, and in Affibloom's for each extension-API method). The
same rules apply; checks run in the migrator's folder.

| Batch | Tasks |
|---|---|
| M.1 | LM1 |
| M.2 | 25A |
| M.3 | 25B, 25C, 25D |
| M.4 | 26A, 26B |
| M.5 | 26C, 26D, 26E |
| M.6 | 26F, 26L, 26M |
| M.7 | 26G, 26H |
| M.8 | 26K, 26I, 26N |
| M.9 | 26O |
| M.10 | 26P |
| M.10a | 26Q (after 8.G3; deviation 156) |
| M.11 | LM2 |
| M.12 | LM3 |
| M.13 | LM4, LM5 |
| M.14 | LM6 |
| M.15 | LM7, LM8 |
| M.16 | LM9 |
| M.17 | 29A |
| M.18 | 29B, 29C |
| M.19 | 29D, 29E |
| M.20 | LM10 |

## Phase 9

| Batch | Tasks |
|---|---|
| 9.1 | 40 |
| 9.2 | 41 |
| 9.3 | 42 |
