<?php
/**
 * A product's custom field rows (F1, Task 14C), in display order: Primary Rating first. Printed by the product page
 * and drawn again by the field routes after an add or a remove.
 *
 * @package Affibloom
 * @var array{product: int, fields: \Affibloom\Domain\Fields\ProductFields, library: \Affibloom\Domain\Fields\FieldLibrary} $view
 */

defined( 'ABSPATH' ) || exit;

$rows  = $view['fields']->all();
$count = count( $rows );
foreach ( $rows as $position => $row ) {
	$field = $view['library']->find( $row->field_id );
	if ( null === $field ) {
		continue; // A field whose definition is gone (the Fields area can delete one) is not shown.
	}
	$this->render(
		'admin/partials/field-row',
		array(
			'field' => $field,
			'row'   => $row,
			// Primary Rating is fixed in the first place; the others move among themselves.
			'first' => 0 === $position || ( 1 === $position && 1 === $rows[0]->field_id ),
			'last'  => $count - 1 === $position,
		)
	);
}
