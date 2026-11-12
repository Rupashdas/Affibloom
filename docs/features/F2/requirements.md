# F2 Amazon links: requirements

Status: Proposed (gate 1; approved with Task 6). Date: 2026-11-12.

## User stories

- As an editor, I paste any Amazon link or a bare ASIN and get a clean link with the site's tag.

## Acceptance criteria

- **AC1** An Amazon link in any format becomes `https://<amazon host>/dp/<ASIN>?tag=vbvault-20`.
- **AC2** A bare ASIN works.
- **AC3** Another tag is replaced.
- **AC4** The same ASIN twice shows "This Product Already Exists".

Task 7 turns these into `tests/E2E/acceptance/F2.spec.ts`.
