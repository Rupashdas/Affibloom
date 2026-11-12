# F2 Amazon links: requirements

Status: Approved with Task 6 (gate 1), 2026-11-12. Decisions: `docs/decisions/task-6-decisions.md`.

## User stories

- As an editor, I paste any Amazon link or a bare ASIN and get a clean link with the site's tag.

## Acceptance criteria

- **AC1** An Amazon link in any format becomes `https://<amazon host>/dp/<ASIN>?tag=vbvault-20`.
- **AC2** A bare ASIN works.
- **AC3** Another tag is replaced.
- **AC4** The same ASIN twice shows "This Product Already Exists".
- **AC5** A link with a product slug gives the product a starting name from it (`/Mizuno-Wave-Momentum-3/dp/…` → "Mizuno Wave Momentum 3"); a link without one is named "Amazon" (decision 11).

Task 7 turns these into `tests/E2E/acceptance/F2.spec.ts`.
