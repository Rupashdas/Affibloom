## What and why

<!-- One paragraph. Link the task number from the implementation plan. -->

## Checks

- [ ] Correctness: meets every acceptance criterion; edge cases from the analysis are handled and tested.
- [ ] Architecture: code sits in the right layer; dependencies point inward; no business logic in templates or hooks.
- [ ] Security: capability checks and nonces on writes; input sanitised, output escaped; queries prepared; REST routes have permission callbacks.
- [ ] Performance: no queries inside loops; results cached where reused; assets load only where needed; background work batched.
- [ ] Consistency: naming, prefixes and file layout match the standards; no duplicated logic.
- [ ] UI/UX: matches the approved mockup and Lasso's layout, labels and help text; built from the shared UI kit; any difference is an approved register item.
- [ ] Maintainability: small focused classes, typed signatures, docblocks on public methods; no dead code.
- [ ] Tests: new code covered at the right level; tests are readable and independent.
- [ ] Roadmap: checklist updated, decisions recorded, anything deferred noted with a reason.

## Verified by hand

<!-- What you ran and what you saw. -->
