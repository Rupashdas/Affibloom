<?php
/**
 * Creates custom fields through the plugin's field library in one WP-CLI call: `wp eval-file create-fields.php '<json>'`.
 *
 * The JSON is a list of { "name": string, "type": string } (FieldType values); prints the new fields' IDs as a JSON list,
 * in the same order. Used by the box's field tests (F5 AC18, Task 21B).
 *
 * @package Affibloom
 */

defined( 'WP_CLI' ) || exit;

use Affibloom\Domain\Fields\FieldType;
use Affibloom\Infrastructure\WordPress\OptionFieldRepository;

$affibloom_input      = json_decode( (string) ( $args[0] ?? '[]' ), true );
$affibloom_repository = new OptionFieldRepository();
$affibloom_library    = $affibloom_repository->load();
$affibloom_ids        = array();
foreach ( (array) $affibloom_input as $affibloom_row ) {
	list( $affibloom_library, $affibloom_field ) = $affibloom_library->create( (string) $affibloom_row['name'], FieldType::from( (string) $affibloom_row['type'] ), '' );
	$affibloom_ids[]                             = $affibloom_field->id;
}
$affibloom_repository->save( $affibloom_library );
echo wp_json_encode( $affibloom_ids );
