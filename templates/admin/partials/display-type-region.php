<?php
/**
 * One display type's part of the Display rail: its theme row, its Layout & spacing panel or its colours and labels.
 * All three types are on the page, as in Lasso (display.php, settings.js 2073-2087; deviation 144): the type shown is
 * live in the form, the other two wait in a <template>, and display-settings.js swaps them when Single, Grid or List is
 * chosen, so the page does not reload and one Save Changes stores every type that was edited.
 *
 * @package Affibloom
 * @var array{region: string, type: string, live: bool, partial: string, data: array<string, mixed>} $view
 */

defined( 'ABSPATH' ) || exit;

$attributes = 'class="affibloom-type-region" data-type-region="' . esc_attr( $view['region'] ) . '" data-display-type="' . esc_attr( $view['type'] ) . '"';
// Markup from Affibloom's own templates, escaped where it is built.
$inner = $this->fetch( $view['partial'], $view['data'] );
?>
<?php if ( $view['live'] ) : ?>
	<div <?php echo $attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>><?php echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by its template. ?></div>
<?php else : ?>
	<template data-type-region="<?php echo esc_attr( $view['region'] ); ?>" data-display-type="<?php echo esc_attr( $view['type'] ); ?>"><div <?php echo $attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>><?php echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by its template. ?></div></template>
<?php endif; ?>
