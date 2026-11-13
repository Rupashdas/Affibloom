#!/usr/bin/env bash
# Returns the Local copy to the cleaned snapshot in .local/snapshot.sql. Safe to run any time.
# The safety constants live in wp-config.php, outside the database, so a reset keeps them.
#
# Why it loads the snapshot sideways instead of straight in:
# the snapshot is ~150 MB and takes about a minute to load. The first version of this script
# emptied the site's database (`wp db reset`) and then spent that minute refilling it, so for a
# whole minute the site really had no users and most of its tables missing - and a run that was
# interrupted (a time-out, Ctrl+C, a closed terminal) left it that way. Instead the snapshot is
# loaded into a staging database beside the site's own, checked for completeness, and only then
# swapped in by a single RENAME TABLE statement. MySQL renames the pairs left to right and
# reverses the whole statement if any one pair fails, so the site holds either its old tables or
# the snapshot's, never half of either. The tables it replaces are moved aside (also by rename,
# so nothing is copied) and are dropped only once the new ones are verified in place.
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."

WP="bash tools/local-wp.sh"
SNAPSHOT=".local/snapshot.sql"
LOCK=".local/reset.lock"

fail() {
	echo "reset-local.sh: $1" >&2
	exit 1
}

# Reads one value out of the database. The SQL goes in on stdin, never as an argument: WP-CLI
# runs the mysql client through cmd.exe, which would eat anything between two % signs.
db_value() {
	printf '%s\n' "$1" | $WP db query --skip-column-names | tr -d '\r' | sed '/^$/d'
}

table_count() {
	db_value "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$1' AND table_type='BASE TABLE';"
}

# One "`from`.`table` TO `to`.`table`" line per table in <from>, for RENAME TABLE.
rename_pairs() {
	db_value "SELECT CONCAT('\`$1\`.\`', table_name, '\` TO \`$2\`.\`', table_name, '\`') FROM information_schema.tables WHERE table_schema='$1' AND table_type='BASE TABLE' ORDER BY table_name;"
}

# ---------------------------------------------------------------- preflight

# The snapshot carries Lasso's production licence, so never load it into a site that
# could reach the internet or run Lasso's jobs.
for constant in WP_HTTP_BLOCK_EXTERNAL DISABLE_WP_CRON SENTRY_DSN LASSO_POSTHOG_API_KEY; do
	if ! $WP config has "$constant" --type=constant; then
		fail "wp-config.php is missing $constant (Task 2, Step 2). Nothing was changed."
	fi
done

if [ ! -f "$SNAPSHOT" ]; then
	fail "No $SNAPSHOT yet (Task 2, Step 6). Nothing was changed."
fi

# mysqldump writes this footer last, so a snapshot that has it was written to the end.
if ! tail -c 400 "$SNAPSHOT" | grep -q '^-- Dump completed'; then
	fail "$SNAPSHOT has no \"Dump completed\" footer, so it is truncated. Nothing was changed."
fi

expected_tables="$(grep -c '^CREATE TABLE ' "$SNAPSHOT" || true)"
if [ "$expected_tables" -lt 1 ]; then
	fail "$SNAPSHOT holds no CREATE TABLE statements. Nothing was changed."
fi

# Two runs on one database wreck each other. mkdir is atomic, so it is the lock. A run that is
# killed outright (a time-out ends the whole process tree) never reaches its exit handler, so
# the lock holds the run's process id and a later run clears it once that process is gone.
if ! mkdir "$LOCK" 2>/dev/null; then
	holder="$(cat "$LOCK/pid" 2>/dev/null || true)"
	if [ -n "$holder" ] && kill -0 "$holder" 2>/dev/null; then
		fail "a reset is already running (process $holder). Wait for it to finish. Nothing was changed."
	fi
	echo "Clearing the lock of a reset that is no longer running (process ${holder:-unknown})."
	rm -rf "$LOCK"
	mkdir "$LOCK" || fail "another reset took the lock just now. Nothing was changed."
fi
echo "$$" > "$LOCK/pid"

phase="staging"
db_name=""
staging=""
previous=""
on_exit() {
	status=$?
	rm -rf "$LOCK"
	# An if, not `[ ] && return`: under set -e that list would end the handler before the message.
	if [ "$status" -eq 0 ]; then
		return
	fi
	case "$phase" in
		staging)
			echo "reset-local.sh stopped before the swap, so the site's own database was not touched." >&2
			;;
		swap)
			echo "reset-local.sh stopped during the swap. Nothing was dropped; see where the tables are with:" >&2
			echo "  printf \"SELECT table_schema, COUNT(*) FROM information_schema.tables WHERE table_schema IN ('$db_name','$previous','$staging') GROUP BY table_schema;\n\" | bash tools/local-wp.sh db query" >&2
			;;
		wordpress)
			echo "reset-local.sh stopped after the data was in place, while putting the plugins back. The data is complete; run the script again." >&2
			;;
	esac
}
trap on_exit EXIT

db_name="$($WP config get DB_NAME --type=constant | tr -d '\r\n')"
staging="${db_name}_affibloom_staging"
previous="${db_name}_affibloom_previous"

# ---------------------------------------------------------------- load the staging database

echo "Loading $SNAPSHOT into \`$staging\` (about a minute; the site keeps running on \`$db_name\`)…"
{
	printf 'DROP DATABASE IF EXISTS `%s`;\n' "$staging"
	printf 'CREATE DATABASE `%s`;\n' "$staging"
	printf 'USE `%s`;\n' "$staging"
	cat "$SNAPSHOT"
} | $WP db query

staged_tables="$(table_count "$staging")"
if [ "$staged_tables" -ne "$expected_tables" ]; then
	fail "\`$staging\` has $staged_tables of the snapshot's $expected_tables tables, so the load did not finish. The site's own database was not touched."
fi

# The point of the snapshot is a site you can sign in to, so check that before swapping.
staged_admins="$(db_value "SELECT COUNT(*) FROM \`$staging\`.wp_usermeta WHERE meta_key='wp_capabilities' AND INSTR(meta_value,'administrator')>0;")"
if [ "$staged_admins" -lt 1 ]; then
	fail "\`$staging\` holds no administrator, so the snapshot is not usable. The site's own database was not touched."
fi

# ---------------------------------------------------------------- swap it in

phase="swap"
echo "Swapping $staged_tables tables into \`$db_name\` ($staged_admins administrators)…"

printf 'DROP DATABASE IF EXISTS `%s`;\nCREATE DATABASE `%s`;\n' "$previous" "$previous" | $WP db query

# Whatever the site has now (a full copy, or a half one left by an older run) moves to
# `previous`, and the staged tables take its place, in one statement.
pairs="$( { rename_pairs "$db_name" "$previous"; rename_pairs "$staging" "$db_name"; } | paste -sd ',' - )"
if [ -z "$pairs" ]; then
	fail "no tables to rename, which should not happen after the checks above."
fi
printf 'RENAME TABLE %s;\n' "$pairs" | $WP db query

live_tables="$(table_count "$db_name")"
if [ "$live_tables" -ne "$expected_tables" ]; then
	fail "the swap left $live_tables of $expected_tables tables in \`$db_name\`. The tables it replaced are still in \`$previous\`; nothing was dropped."
fi

printf 'DROP DATABASE IF EXISTS `%s`;\nDROP DATABASE IF EXISTS `%s`;\n' "$previous" "$staging" | $WP db query

# ---------------------------------------------------------------- put WordPress back

phase="wordpress"
while read -r plugin; do
	# </dev/null so WP-CLI cannot read the rest of the plugin list off this loop's stdin.
	[ -n "$plugin" ] && $WP plugin deactivate "$plugin" --quiet </dev/null || true
done < tools/local-deactivate.txt

$WP plugin activate lasso affibloom --quiet
# The snapshot's CSS links two button icons to files that never existed (F28 D4); put them right after every reset.
$WP eval-file tools/local-icon-links.php
$WP rewrite flush --quiet
$WP cache flush --quiet

administrators="$($WP user list --role=administrator --format=count | tr -d '\r\n')"
echo "Local copy reset: $live_tables tables, $administrators administrators."
