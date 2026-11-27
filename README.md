# Affibloom

An affiliate product library for WordPress: one place for your products, drawn in posts as product boxes, buttons,
grids, lists and comparison tables, with link checking, click counts, a link index and monetization opportunities.
It works on its own, with no outside service apart from the optional Amazon Creators API and Google Analytics.

- **Owners and editors:** [`docs/admin-guide.md`](docs/admin-guide.md): every screen, what needs Amazon credentials,
  and where each feature of the plugin it replaces went.
- **Developers:** [`docs/developer.md`](docs/developer.md): architecture, every entry point and stored name, tests and
  releases.

## Requirements

WordPress 7.1 or later, PHP 8.3 or later.

## Install

From a release: Plugins → Add New → Upload Plugin, choose `affibloom.zip`, activate. On a new site the setup wizard
opens on the first Affibloom page.

From this repository (development):

```bash
composer install
npm ci && npm run build      # the block, compiled into build/
```

then activate the plugin folder in a local WordPress. `docs/environment.md` describes the development machine
(Local, WP-CLI through `tools/local-wp.sh`, Playwright).

## Checks

```bash
composer lint          # PHPCS, WordPress Coding Standards
composer analyse       # PHPStan level 6
composer test:unit     # PHPUnit + Brain Monkey
npx playwright test    # end to end, against a running site
```

## Moving a site from Lasso

Not part of this plugin. A separate helper plugin, the Affibloom Lasso Migrator, copies the data and converts the
content once, then is deleted (`docs/plans/phase-m-lasso-migrator.md`).

## Licence

GPL-2.0-or-later. The bundled fonts (Inter, Plus Jakarta Sans) are under the SIL Open Font License 1.1
(`assets/admin/fonts/`).
