#!/usr/bin/env bash
# Runs WP-CLI against the Local (Local by Flywheel) copy of the site from any terminal,
# the way Local's "Open site shell" does: Local's PHP with the site's php.ini, Local's
# mysql client, and the WP-CLI that ships with Local. Override the paths with the
# variables below if Local is installed elsewhere or the versions change.
set -euo pipefail

LOCAL_DATA="${LOCAL_DATA:-$APPDATA/Local}"
LOCAL_SITE_ID="${LOCAL_SITE_ID:-XMaGBQZ-W}"
LOCAL_PHP_DIR="${LOCAL_PHP_DIR:-$LOCAL_DATA/lightning-services/php-8.2.29+0/bin/win64}"
LOCAL_MYSQL_DIR="${LOCAL_MYSQL_DIR:-$LOCAL_DATA/lightning-services/mysql-8.4.0+2/bin/win64/bin}"
LOCAL_WP_CLI="${LOCAL_WP_CLI:-/c/Program Files (x86)/Local/resources/extraResources/bin/wp-cli/wp-cli.phar}"
# The site's MySQL port, from Local's run/<site id>/conf/mysql/my.cnf.
LOCAL_MYSQL_PORT="${LOCAL_MYSQL_PORT:-10005}"

# The WordPress root is four levels up from this file: wp-content/plugins/affibloom/tools.
SITE_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../../.." && pwd)"

export PHPRC="$LOCAL_DATA/run/$LOCAL_SITE_ID/conf/php"
# PHP's folder and its ImageMagick folder hold the DLLs that php_imagick.dll needs. Without
# them PHP prints a startup warning on stdout, which breaks --format=json output.
export PATH="$LOCAL_PHP_DIR/ImageMagick:$LOCAL_PHP_DIR:$LOCAL_MYSQL_DIR:$PATH"
# PHP gets the port from php.ini, but `wp db …` runs the mysql client with --no-defaults
# and DB_HOST "localhost", so without this it tries port 3306.
export MYSQL_TCP_PORT="$LOCAL_MYSQL_PORT"

exec "$LOCAL_PHP_DIR/php.exe" "$LOCAL_WP_CLI" --path="$SITE_ROOT" "$@"
