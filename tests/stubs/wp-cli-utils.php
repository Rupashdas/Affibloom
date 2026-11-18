<?php
/**
 * The WP-CLI utility the plugin calls, for PHPStan only (see wp-cli.php beside it). Never loaded at run time.
 *
 * @package Affibloom
 */

// phpcs:ignoreFile -- a signature stub, not plugin code.

namespace WP_CLI\Utils;

/**
 * Prints items as a table, JSON, CSV, YAML, IDs or a count (`wp affibloom check-links`, Task 32A).
 *
 * @param string                           $format Format to use: table, json, csv, yaml, ids, count.
 * @param array<int, array<string, mixed>> $items  The items.
 * @param array<int, string>|string        $fields The fields to show.
 */
function format_items( $format, $items, $fields ): void {}
