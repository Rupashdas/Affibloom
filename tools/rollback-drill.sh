#!/usr/bin/env bash
# Task 29E: the rollback drill on Local (docs/cutover/rollback.md). Takes Lasso's fingerprint and the content's, migrates
# and converts as the cutover does (Lasso off), goes back (every place reverted, Affibloom off, Lasso on), and takes both
# fingerprints again: the script fails when either differs. Then run the guides against Lasso's pages:
#   PARITY_MODE=lasso npx playwright test tests/Visual/parity.spec.ts --config=.local/no-login.config.ts
# and switch Affibloom (and the migrator) on again. Local only: it writes the site.
set -euo pipefail
cd "$(dirname "$0")/.."

wp() { bash tools/local-wp.sh "$@"; }
win() { if command -v cygpath > /dev/null; then cygpath -w "$1"; else echo "$1"; fi; }
OUT=.local/rollback
mkdir -p "$OUT"
LASSO_FP="$(win "$PWD/../affibloom-lasso-migrator/tools/lasso-fingerprint.php")"
CONTENT_FP="$(win "$PWD/tools/content-fingerprint.php")"

echo "1. Fingerprints before"
wp eval-file "$LASSO_FP" > "$OUT/lasso-before.json"
wp eval-file "$CONTENT_FP" > "$OUT/content-before.json"

echo "2. The cutover: migrate, Lasso off, convert"
wp plugin activate affibloom affibloom-lasso-migrator
wp affibloom-lasso migrate
wp plugin deactivate lasso
for step in convert stamps blocks css; do wp affibloom-lasso "$step"; done

echo "3. Going back: revert every place, Affibloom off, Lasso on"
for step in blocks stamps convert css; do wp affibloom-lasso "$step" --revert; done
wp plugin deactivate affibloom-lasso-migrator affibloom
wp plugin activate lasso

echo "4. Fingerprints after"
wp eval-file "$LASSO_FP" > "$OUT/lasso-after.json"
wp eval-file "$CONTENT_FP" > "$OUT/content-after.json"

failed=0
for kind in lasso content; do
	if cmp -s "$OUT/$kind-before.json" "$OUT/$kind-after.json"; then
		echo "   $kind: equal"
	else
		echo "   $kind: DIFFERENT"
		diff "$OUT/$kind-before.json" "$OUT/$kind-after.json" | head -20 || true
		failed=1
	fi
done
exit "$failed"
