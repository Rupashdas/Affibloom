<?php
/**
 * Plugin Name: Affibloom E2E classic filter
 * Description: Test fixture (F16 B13): switches Affibloom's advanced box rendering off through its filter. Copied into
 * mu-plugins by the test and removed after it.
 *
 * @package Affibloom
 */

add_filter( 'affibloom_display_advanced_enabled', '__return_false' );
