<?php
/**
 * Merges values over the stored Affibloom settings in one WP-CLI call (deviation 111):
 * `wp eval-file set-settings.php '<json object>'`.
 *
 * Same result as the `wp option get` + `wp option update` pair utils.ts setSettings() made before: a shallow merge
 * (later keys win), stored through update_option() so the option's hooks run as they did for `wp option update`.
 *
 * @package Affibloom
 */

defined( 'WP_CLI' ) || exit;

$affibloom_values = json_decode( (string) ( $args[0] ?? '' ), true );
if ( ! is_array( $affibloom_values ) ) {
	WP_CLI::error( 'set-settings.php: the argument is not a JSON object.' );
}

$affibloom_current = get_option( 'affibloom_settings', array() );
update_option( 'affibloom_settings', array_merge( is_array( $affibloom_current ) ? $affibloom_current : array(), $affibloom_values ) );
