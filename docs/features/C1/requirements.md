# C1 Amazon product data on adding: requirements

Status: In scope (the user's decision, 2026-11-12: Lasso supports it, so it is included). Gate 4 approval of its
task is still needed before work starts.

## User stories

- As an editor, when I add an Amazon link, the product's name and image fill in from Amazon, so I only adjust
  them.
- As an editor, I can refresh a product's Amazon image and Prime status from its product page.
- As the site owner, I enter the Creators API credentials once in Settings → Amazon and can check them.

## Acceptance criteria

- **AC1** With valid credentials, adding an Amazon link or ASIN creates a product whose name and image come from
  Amazon, and whose Prime flag matches Amazon's.
- **AC2** Without credentials, or when Amazon does not answer, adding still works: the product is created with
  the fallback name and no image, and no error stops the editor.
- **AC3** `Validate` reports whether the credentials work, without saving anything else.
- **AC4** `Refresh` on the product page updates the image and Prime flag from Amazon and keeps the editor's name.
- **AC5** The secret never appears in a REST response, the CSV export or the page source.
