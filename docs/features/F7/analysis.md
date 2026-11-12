# F7 Per-box text: analysis

Status: Approved with Task 6 (gate 2), 2026-11-12. Decisions: `docs/decisions/task-6-decisions.md`.

**Tasks:** 20, 24.

## Depends on

- F4, F5.

## Data touched

- Block attributes `badge`, `hideBadge`, `description`, `hideDescription`.
- `[lasso badge="…" description="…"]` (Task 27).

## Entry points and hooks

- The box render; the block sidebar.

## Facts from Phase 1

- `badge` is set on 113 boxes and `description` on 119 (Task 4); Lasso writes line breaks as `lasso-br-code`.
- Some badges start with spaces ("  Best Overall").

## Risks

- Badges carry `<span class="medal-icon">`: kept through the `span[class]` allowlist (decision 5).

## Security

- The description through `wp_kses_post`; the badge allows only `<span class>`.
