#!/usr/bin/env bash
# Returns the Local copy to the cleaned snapshot in .local/snapshot.sql. Safe to run any time.
# The safety constants live in wp-config.php, outside the database, so a reset keeps them.
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."

WP="bash tools/local-wp.sh"

# The snapshot carries Lasso's production licence, so never load it into a site that
# could reach the internet or run Lasso's jobs.
for constant in WP_HTTP_BLOCK_EXTERNAL DISABLE_WP_CRON SENTRY_DSN LASSO_POSTHOG_API_KEY; do
	if ! $WP config has "$constant" --type=constant; then
		echo "wp-config.php is missing $constant (Task 2, Step 2). Nothing was changed." >&2
		exit 1
	fi
done

if [ ! -f .local/snapshot.sql ]; then
	echo "No .local/snapshot.sql yet (Task 2, Step 6). Nothing was changed." >&2
	exit 1
fi

$WP db reset --yes
$WP db import .local/snapshot.sql

while read -r plugin; do
	[ -n "$plugin" ] && $WP plugin deactivate "$plugin" --quiet || true
done < tools/local-deactivate.txt

$WP plugin activate lasso affibloom --quiet
$WP rewrite flush --quiet
$WP cache flush --quiet
echo "Local copy reset."
