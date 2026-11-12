# C2 Automatic affiliate link detection: requirements

Status: In scope (the user's decision, 2026-11-12: Lasso supports it, so it is included). Gate 4 approval of its
task is still needed, including the choice between Lasso's automatic creation and the "ask first" variant (U8).

## User stories

- As an editor, when I publish a post containing an affiliate link from a known network, the link appears as a
  product in the library, so its clicks, health and locations are tracked like any other.
- As the site owner, I can switch the detection off in Settings → General.

## Acceptance criteria

- **AC1** With `Auto-Detect Affiliate Links` on, saving a published post that contains an Impact link with no
  product creates one `other` product for it, named from the link text, with the site's link defaults.
- **AC2** Saving the same post again creates nothing new; a link that already has a product is never duplicated.
- **AC3** The post's content is byte-for-byte unchanged after detection.
- **AC4** Links that match no known network (internal links, plain shop links, Amazon links) create nothing.
- **AC5** With the setting off, saving a post creates nothing.
