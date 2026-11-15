<?php
/**
 * An element's attributes, each escaped: ` name="value"` in the given order. Addresses go through esc_url().
 *
 * @package Affibloom
 * @var array<string, string> $attributes Name => raw value.
 */

defined( 'ABSPATH' ) || exit;

foreach ( $attributes as $attribute_name => $attribute_value ) {
	$attribute_value = in_array( $attribute_name, array( 'href', 'src' ), true ) ? esc_url( $attribute_value ) : esc_attr( $attribute_value );
	echo ' ' . esc_attr( $attribute_name ) . '="' . $attribute_value . '"'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped on the line above.
}
