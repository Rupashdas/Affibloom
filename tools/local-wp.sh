#!/usr/bin/env bash
# Runs WP-CLI against the Local (Local by Flywheel) copy of the site from any terminal,
# the way Local's "Open site shell" does: Local's PHP with the site's php.ini, Local's
# mysql client, and the WP-CLI that ships with Local. Override the paths with the
# variables below if Local is installed elsewhere or the versions change.
set -euo pipefail

LOCAL_DATA="${LOCAL_DATA:-$APPDATA/Local}"
LOCAL_SITE_ID="${LOCAL_SITE_ID:-XMaGBQZ-W}"
# The newest PHP 8.3 that Local has installed: Local replaces the folder when it updates PHP (8.3.36+0 became
# 8.3.37+4 on 2026-11-22), so the folder is looked up, not fixed.
if [ -z "${LOCAL_PHP_DIR:-}" ]; then
	LOCAL_PHP_DIR="$(ls -d "$LOCAL_DATA"/lightning-services/php-8.3.* 2>/dev/null | sort -V | tail -n 1)/bin/win64"
fi
LOCAL_MYSQL_DIR="${LOCAL_MYSQL_DIR:-$LOCAL_DATA/lightning-services/mysql-8.4.0+2/bin/win64/bin}"
LOCAL_WP_CLI="${LOCAL_WP_CLI:-/c/Program Files (x86)/Local/resources/extraResources/bin/wp-cli/wp-cli.phar}"
# The site's MySQL port, read from Local's run/<site id>/conf/mysql/my.cnf on every run:
# Local can give the site a new port when it restarts (10005 became 10006 on 2026-11-12).
if [ -z "${LOCAL_MYSQL_PORT:-}" ]; then
	LOCAL_MYSQL_CNF="$LOCAL_DATA/run/$LOCAL_SITE_ID/conf/mysql/my.cnf"
	LOCAL_MYSQL_PORT="$(sed -nE 's/^[[:space:]]*port[[:space:]]*=[[:space:]]*([0-9]+).*/\1/p' "$LOCAL_MYSQL_CNF" 2>/dev/null | head -n 1 || true)"
	if [ -z "$LOCAL_MYSQL_PORT" ]; then
		echo "local-wp.sh: no MySQL port in $LOCAL_MYSQL_CNF. Is the site set up in Local?" >&2
		exit 1
	fi
fi

# The WordPress root is four levels up from this file: wp-content/plugins/affibloom/tools.
# Another Local site (the cutover rehearsal on volleyballvault-testing): set LOCAL_SITE_ID and LOCAL_SITE_ROOT, its
# public folder, together.
SITE_ROOT="${LOCAL_SITE_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../../../.." && pwd)}"

export PHPRC="$LOCAL_DATA/run/$LOCAL_SITE_ID/conf/php"
# PHP's folder and its ImageMagick folder hold the DLLs that php_imagick.dll needs. Without
# them PHP prints a startup warning on stdout, which breaks --format=json output.
export PATH="$LOCAL_PHP_DIR/ImageMagick:$LOCAL_PHP_DIR:$LOCAL_MYSQL_DIR:$PATH"
# PHP gets the port from php.ini, but `wp db …` runs the mysql client with --no-defaults
# and DB_HOST "localhost", so without this it tries port 3306.
export MYSQL_TCP_PORT="$LOCAL_MYSQL_PORT"

# OPcache keeps compiled PHP only for one process on the command line, so every call compiled WP-CLI, WordPress and
# all plugins again (about 1.8 s a call). A file cache keeps them between calls (about 0.95 s; deviation 111).
# Timestamps are still checked (opcache.validate_timestamps is on), so an edited file is compiled again.
# The cache lives in the plugin's gitignored .local/; OPCACHE_FILE_CACHE= (empty) turns it off.
PLUGIN_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OPCACHE_FILE_CACHE="${OPCACHE_FILE_CACHE-$PLUGIN_ROOT/.local/opcache}"
PHP_ARGS=()
if [ -n "$OPCACHE_FILE_CACHE" ]; then
	mkdir -p "$OPCACHE_FILE_CACHE"
	# php.exe needs a Windows path; cygpath is in Git Bash.
	PHP_ARGS=( -d "opcache.file_cache=$(cygpath -w "$OPCACHE_FILE_CACHE" 2>/dev/null || echo "$OPCACHE_FILE_CACHE")" )
fi

exec "$LOCAL_PHP_DIR/php.exe" "${PHP_ARGS[@]}" "$LOCAL_WP_CLI" --path="$SITE_ROOT" "$@"
